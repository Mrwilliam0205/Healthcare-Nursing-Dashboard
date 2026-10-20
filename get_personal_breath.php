<?php
    // 確保 <?php 前面沒有任何多餘的空白或 HTML 換行
    require_once("time.php");
    require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");

    $link = create_connection();
    
    // 接收前端傳遞過來的變數
    $IDP = $_GET["IDP"];
    
    // 設定時間界線 (7天前)
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

    // =========================================================================
    // 【第一關】：找總表 (7天內熱資料)
    // =========================================================================
    if ($end_date_only >= $seven_days_ago) {
        $tables_to_query[] = "SELECT time, breath, mac_address FROM `$table_general` WHERE time BETWEEN '$startdate' AND '$enddate'";
        $debug_tables[] = "總表 (valuehistory)";
    }

    // =========================================================================
    // 【第二關】：找個人表 (溫資料)
    // =========================================================================
    $oldest_in_db = null;
    $check_ind_sql = "SHOW TABLES LIKE '$table_individual'";
    $check_ind_res = execute_sql($link, "wetness_database2", $check_ind_sql);

    if (mysqli_num_rows($check_ind_res) > 0) {
        if ($start_date_only < $seven_days_ago) {
            $tables_to_query[] = "SELECT time, breath, mac_address FROM `$table_individual` WHERE time BETWEEN '$startdate' AND '$enddate'";
            $debug_tables[] = "個人表 ($table_individual)";
        }
        
        // 抓出個人表中最舊的時間，準備給第三關做判斷
        $min_time_sql = "SELECT MIN(time) as oldest_time FROM `$table_individual`";
        $min_res = execute_sql($link, "wetness_database2", $min_time_sql);
        if ($min_row = mysqli_fetch_assoc($min_res)) {
            $oldest_in_db = $min_row['oldest_time'];
        }
    }

    // =========================================================================
    // 【第三關】：判斷與精準匯入打包檔 (冷資料)
    // =========================================================================
    if ($startdate < $oldest_in_db || empty($oldest_in_db)) {
        
        $history_sql = "SELECT Admission_date, discharge_date FROM discharged_patient WHERE IDP='$IDP'";
        $history_res = execute_sql($link, "wetness_database2", $history_sql);
        
        while ($row = mysqli_fetch_assoc($history_res)) {
            $adm_date = date('Y-m-d', strtotime($row['Admission_date']));
            $dis_date = $row['discharge_date'];
            
            // 【精準打擊】：判斷區間是否有重疊
            if ($start_date_only <= $dis_date && $end_date_only >= $adm_date) {
                
                $folder_path = "C:/xampp/htdocs/test/newwww/ch/test/discharged_patient_data/".$IDP . "_" . $adm_date . "_" . $dis_date."/";
                $file_name = $IDP . "_" . $adm_date . "_" . $dis_date . "_value_history_data.sql";
                $file_path = $folder_path . $file_name;
                
                if (file_exists($file_path)) {
                    $current_date = date('Ymd');
                    $archive_table = "valuehistory_import_" . $current_date . "_" . md5($file_name); 
                    
                    $check_arc_sql = "SHOW TABLES LIKE '$archive_table'";
                    $check_arc_res = execute_sql($link, "wetness_database2", $check_arc_sql);
                    
                    if (mysqli_num_rows($check_arc_res) == 0) {
                        $create_table_sql = "CREATE TABLE `$archive_table` LIKE valuehistory_import";
                        if (execute_sql($link, "wetness_database2", $create_table_sql)) {
                            $sql_content = file_get_contents($file_path);
                            if ($sql_content !== false && !empty($sql_content)) {
                                $queries = explode(";", $sql_content);
                                $batch_size = 1000;
                                $count = count($queries);
                                $i = 0;
                                while ($i < $count) {
                                    $batch_queries = array_slice($queries, $i, $batch_size);
                                    $batch_sql = implode(";", $batch_queries);
                                    $batch_sql = str_replace('INSERT INTO valuehistory_import', "INSERT INTO `$archive_table`", $batch_sql);
                                    if (mysqli_multi_query($link, $batch_sql)) {
                                        do {
                                            if ($res_store = mysqli_store_result($link)) {
                                                mysqli_free_result($res_store);
                                            }
                                        } while (mysqli_next_result($link));
                                    }
                                    $i += $batch_size;
                                }
                            }
                        }
                    }
                    // 將這個有命中且存在的匯入表，加入查詢清單 (注意 SELECT 欄位為 breath)
                    $tables_to_query[] = "SELECT time, breath, mac_address FROM `$archive_table` WHERE time BETWEEN '$startdate' AND '$enddate'";
                    $debug_tables[] = "匯出備份檔 ($adm_date ~ $dis_date)";
                }
            }
        }
    }

    // =========================================================================
    // 【第四關】：組合查詢與輸出結果
    // =========================================================================
    if (empty($tables_to_query)) {
        $from_clause = "`$table_individual` vh";
        $debug_target_table = "預設個人表 (無符合區間)";
    } else {
        $union_sql = implode(" UNION ALL ", $tables_to_query);
        $from_clause = "(" . $union_sql . ") vh";
        $debug_target_table = implode(" + ", $debug_tables);
    }

    $select_query = "SELECT DISTINCT ch.mac_address, 
                        CASE 
                            WHEN ch.end = '1970-01-01 00:00:00' THEN NOW() 
                            ELSE ch.end 
                        END AS end_time 
                        FROM correspondhistory ch 
                        WHERE ch.IDP = '$IDP' 
                        AND ch.begin <= '$enddate' 
                        AND (ch.end >= '$startdate' OR ch.end = '1970-01-01 00:00:00')";
    $result = execute_sql($link, "wetness_database2", $select_query);

    // 針對 breath 進行 AVG, MAX, MIN 計算
    $sql = "SELECT DATE_FORMAT(vh.time, '%Y-%m-%d %H:00:00') AS hour_time,
            AVG(vh.breath) AS avg_breath,
            MAX(vh.breath) AS max_breath,
            MIN(vh.breath) AS min_breath
            FROM $from_clause
            INNER JOIN correspondhistory ch ON vh.mac_address = ch.mac_address
            WHERE vh.time BETWEEN '$startdate' AND '$enddate'
            AND vh.breath > 0 
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

    // 判斷是否有資料斷層，並產生回饋提示訊息
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
        $warning_msg = "⚠️ 提示：系統中最早的紀錄始於 {$oldest_date_format}，在此之前查無更早的歷史打包檔。";
    } elseif (empty($oldest_in_db) && !$has_archive_loaded && $start_date_only < $seven_days_ago) {
        $warning_msg = "⚠️ 提示：查無個人歷史資料與相關打包檔。";
    }

    $arr[] = array(
        "debug_target_table" => $debug_target_table,
        "warning_msg" => $warning_msg
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
?>