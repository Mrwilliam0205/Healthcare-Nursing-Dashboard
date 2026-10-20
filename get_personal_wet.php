<?php
    // 確保 <?php 前面沒有任何多餘的空白或 HTML 換行
    require_once("time.php");
    require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");

    $link = create_connection();
    
    // =========================================================================
    // 【新增】：自動清道夫 (Garbage Collection)
    // 每次執行時，自動掃描並刪除「非今天」的歷史匯入暫存表，防止資料庫爆掉
    // =========================================================================
    $today_prefix = "valuehistory_import_" . date('Ymd');
    $cleanup_sql = "SHOW TABLES LIKE 'valuehistory_import_%'";
    $cleanup_res = execute_sql($link, "wetness_database2", $cleanup_sql);
    
    if ($cleanup_res) {
        while ($tbl_row = mysqli_fetch_array($cleanup_res)) {
            $tbl_name = $tbl_row[0];
            // 如果表格名稱不包含「今天的日期」，代表是過期的暫存表，直接 DROP 掉
            if (strpos($tbl_name, $today_prefix) === false && $tbl_name !== 'valuehistory_import') {
                execute_sql($link, "wetness_database2", "DROP TABLE `$tbl_name`");
            }
        }
    }
    // =========================================================================

    $IDP = $_GET["IDP"];
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
    
    $table_individual = strtolower($IDP) . "_valuehistory";
    $table_general = "valuehistory";

    if (empty($_GET['startdate'])) {
        $startdate = '2023-01-01 00:00:00';
        $enddate = '2030-12-31 23:59:59';
        $start_date_only = '2023-01-01';
        $end_date_only = '2030-12-31';
    } else {
        $startdate = $_GET['startdate'] . ' 00:00:00';
        $enddate = $_GET['enddate'] . ' 23:59:59';
        $start_date_only = $_GET['startdate'];
        $end_date_only = $_GET['enddate'];
    }

    $tables_to_query = array();
    $debug_tables = array(); 
    $debug_file_msgs = array(); 

    // 【第一關】：找總表 (7天內熱資料)
    if ($end_date_only >= $seven_days_ago) {
        $tables_to_query[] = "SELECT time, wet, mac_address FROM `$table_general` WHERE time BETWEEN '$startdate' AND '$enddate'";
        $debug_tables[] = "總表 (valuehistory)";
    }

    // 【第二關】：找個人表 (溫資料)
    $oldest_in_db = null;
    $check_ind_sql = "SHOW TABLES LIKE '$table_individual'";
    $check_ind_res = execute_sql($link, "wetness_database2", $check_ind_sql);

    if (mysqli_num_rows($check_ind_res) > 0) {
        if ($start_date_only < $seven_days_ago) {
            $tables_to_query[] = "SELECT time, wet, mac_address FROM `$table_individual` WHERE time BETWEEN '$startdate' AND '$enddate'";
            $debug_tables[] = "個人表 ($table_individual)";
        }
        
        $min_time_sql = "SELECT MIN(time) as oldest_time FROM `$table_individual`";
        $min_res = execute_sql($link, "wetness_database2", $min_time_sql);
        if ($min_row = mysqli_fetch_assoc($min_res)) {
            $oldest_in_db = $min_row['oldest_time'];
        }
    }

    // 【第三關】：直接掃描實體資料夾匯入打包檔 (冷資料) - 不依賴出院表
    if ($startdate < $oldest_in_db || empty($oldest_in_db)) {
        
        // 您可以考慮將資料夾名稱 discharged_patient_data 改為更通用的名稱，例如 patient_archive_data
        $folder_path = "C:/xampp/htdocs/test/newwww/ch/test/discharged_patient_data/" . $IDP . "/";
        
        // 檢查該病患的專屬資料夾是否存在
        if (is_dir($folder_path)) {
            // 掃描該目錄下所有的 .sql 檔案
            $sql_files = glob($folder_path . "*.sql");
            
            if (empty($sql_files)) {
                $debug_file_msgs[] = "實體資料夾中無打包檔案";
            } else {
                foreach ($sql_files as $file_path) {
                    $file_name = basename($file_path);
                    
                    // 假設您的打包檔命名規則依然是：身分證_開始日期_結束日期.sql
                    // 例如：B148523000_20230101_20231231.sql
                    $file_name_without_ext = str_replace('.sql', '', $file_name);
                    $parts = explode('_', $file_name_without_ext);
                    
                    // 確保檔名格式正確且包含日期 (至少切出3個部分: IDP, Start, End)
                    if (count($parts) >= 3) {
                        $file_start_date = date('Y-m-d', strtotime($parts[1]));
                        $file_end_date = date('Y-m-d', strtotime($parts[2]));
                        
                        // 判斷：使用者的查詢區間，與檔案的資料區間是否有交集？
                        if ($start_date_only <= $file_end_date && $end_date_only >= $file_start_date) {
                            
                            $current_date = date('Ymd');
                            $archive_table = "valuehistory_import_" . $current_date . "_" . md5($file_name); 
                            
                            $check_arc_sql = "SHOW TABLES LIKE '$archive_table'";
                            $check_arc_res = execute_sql($link, "wetness_database2", $check_arc_sql);
                            
                            if (mysqli_num_rows($check_arc_res) == 0) {
                                $create_table_sql = "CREATE TABLE `$archive_table` LIKE valuehistory_import";
                                if (execute_sql($link, "wetness_database2", $create_table_sql)) {
									// 改用 fopen 以「唯讀串流」模式打開大檔案，極度節省記憶體
									$handle = fopen($file_path, "r");
									if ($handle) {
										$query_buffer = '';
										
										// 逐行讀取檔案，直到檔尾
										while (($line = fgets($handle)) !== false) {
											$trim_line = trim($line);
											
											// 略過 SQL 檔案中的註解行 (以 -- 或 /* 開頭) 與空白行
											if (empty($trim_line) || substr($trim_line, 0, 2) == '--' || substr($trim_line, 0, 2) == '/*') {
												continue;
											}
											
											$query_buffer .= $line;
											
											// 如果這行結尾是分號 (;)，代表這是一個完整的 SQL 語句結束了
											if (substr($trim_line, -1, 1) == ';') {
												
												// 執行精準替換：把原始表名換成我們剛建立的暫存表
												$clean_query = preg_replace('/INSERT( IGNORE)? INTO `?[a-zA-Z0-9_]+`?/i', "INSERT IGNORE INTO `$archive_table`", $query_buffer);
												
												// 執行這單筆 (一大段) 的 INSERT 語法
												mysqli_query($link, $clean_query);
												
												// 清空緩衝區，準備讀取下一個語句
												$query_buffer = '';
											}
										}
										fclose($handle);
									}
								}
                            }
                            $tables_to_query[] = "SELECT time, wet, mac_address FROM `$archive_table` WHERE time BETWEEN '$startdate' AND '$enddate'";
                            $debug_tables[] = "匯出備份檔 ({$parts[1]} ~ {$parts[2]})";
                        }
                    } else {
                        // 如果有檔名不符合規則的檔案，可以記錄下來以便除錯
                        $debug_file_msgs[] = "檔案命名格式無法解析: " . $file_name;
                    }
                }
            }
        } else {
            $debug_file_msgs[] = "查無此病患的打包資料夾";
        }
    }

    // 【第四關】：組合查詢與輸出結果
    if (empty($tables_to_query)) {
        $from_clause = "`$table_individual` vh";
        $debug_target_table = "預設個人表 (無符合區間)";
    } else {
        $union_sql = implode(" UNION ALL ", $tables_to_query);
        $from_clause = "(" . $union_sql . ") vh";
        $debug_target_table = implode(" + ", $debug_tables);
    }

    // 【第四關】：正式版 (嚴格核對機器碼與使用時間，並排除 0 的雜訊)
    $sql = "SELECT DATE_FORMAT(vh.time, '%Y-%m-%d %H:00:00') AS hour_time,
            AVG(vh.wet) AS avg_wet,
            MAX(vh.wet) AS max_wet,
            MIN(vh.wet) AS min_wet
            FROM $from_clause
            INNER JOIN correspondhistory ch ON vh.mac_address = ch.mac_address
            WHERE vh.time BETWEEN '$startdate' AND '$enddate'
            AND vh.wet > 0 
            AND ch.IDP = '$IDP' 
            AND vh.time >= ch.begin 
            AND (vh.time <= ch.end OR ch.end = '1970-01-01 00:00:00')
            GROUP BY hour_time 
            ORDER BY hour_time ASC";
            
    $result = execute_sql($link, "wetness_database2", $sql);

    $arr = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row; 
        }
    }

    // =========================================================================
    // 產生回饋提示訊息
    // =========================================================================
    $warning_msg = "";
    $has_archive_loaded = false;
    foreach ($debug_tables as $dt) {
        if (strpos($dt, '匯出備份檔') !== false) {
            $has_archive_loaded = true;
            break;
        }
    }

    if (!empty($oldest_in_db) && $startdate < $oldest_in_db && !$has_archive_loaded) {
        $oldest_date_format = date('Y/m/d', strtotime($oldest_in_db));
        $warning_msg = "⚠️ 提示：系統最早紀錄始於 {$oldest_date_format}，查無更早的歷史打包檔。";
    } elseif (empty($oldest_in_db) && !$has_archive_loaded && $start_date_only < $seven_days_ago) {
        $warning_msg = "⚠️ 提示：查無歷史資料。";
    }

    if (!$has_archive_loaded && !empty($debug_file_msgs)) {
        $warning_msg .= "<br><span style='color: #856404; font-size: 14px;'>[系統偵錯原因]： " . implode(" | ", array_unique($debug_file_msgs)) . "</span>";
    }

    $arr[] = array(
        "debug_target_table" => $debug_target_table,
        "warning_msg" => $warning_msg
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
?>