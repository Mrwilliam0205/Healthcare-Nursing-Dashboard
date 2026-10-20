<?php
set_time_limit(0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);

//載入資料庫連線設定
require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");

$link = create_connection();
$database = 'wetness_database2';

//找一個等待執行的任務改為執行中
$find_task_sql = "SELECT * FROM export_tasks WHERE status = 'pending' ORDER BY task_id ASC LIMIT 1";
$task_res = execute_sql($link, $database, $find_task_sql);

if (!$task_res || mysqli_num_rows($task_res) == 0) {
    exit();
}

$task = mysqli_fetch_assoc($task_res);
$task_id = $task['task_id'];
$safe_idp = mysqli_real_escape_string($link, $task['patient_id']);

execute_sql($link, $database, "UPDATE export_tasks SET status = 'processing' WHERE task_id = $task_id");

$target_table = "`{$safe_idp}_valuehistory`";
$three_months_ago = date('Y-m-d H:i:s', strtotime('-1 days'));

//檢查資料表是否存在
$check_table = execute_sql($link, $database, "SHOW TABLES LIKE '{$safe_idp}_valuehistory'");
if (mysqli_num_rows($check_table) == 0) {
    execute_sql($link, $database, "UPDATE export_tasks SET status = 'failed', error_message = '資料表不存在' WHERE task_id = $task_id");
    exit();
}

//建立實體資料夾路徑
$folder_name = __DIR__ . "/discharged_patient_data/" . $safe_idp . "/";
if (!file_exists($folder_name)) {
    mkdir($folder_name, 0777, true);
}

//命名檔案
$range_sql = "SELECT MIN(time) as min_time, MAX(time) as max_time FROM {$target_table} WHERE time <= '$three_months_ago'";
$range_result = execute_sql($link, $database, $range_sql);
$date_range_str = "valuehistory";
if ($range_result && mysqli_num_rows($range_result) > 0) {
    $range_row = mysqli_fetch_assoc($range_result);
    if (!empty($range_row['min_time']) && !empty($range_row['max_time'])) {
        $start_date = date('Ymd', strtotime($range_row['min_time']));
        $end_date = date('Ymd', strtotime($range_row['max_time']));
        $date_range_str = $start_date . "_" . $end_date;
    }
}

$file_name = $safe_idp . "_" . $date_range_str . ".sql";
$full_file_path = $folder_name . $file_name;
//存進資料庫的相對路徑
$db_file_path = "discharged_patient_data/" . $safe_idp . "/" . $file_name;

//計算總資料筆數
$count_sql = "SELECT COUNT(id) as total FROM {$target_table} WHERE time <= '$three_months_ago'";
$count_res = execute_sql($link, $database, $count_sql);
$total_rows = 0;
if ($count_row = mysqli_fetch_assoc($count_res)) {
    $total_rows = $count_row['total'];
}

if ($total_rows == 0) {
    //沒資料也算完成
    execute_sql($link, $database, "UPDATE export_tasks SET status = 'completed', progress = 100 WHERE task_id = $task_id");
    exit();
}

$file_handle = fopen($full_file_path, 'w');
if (!$file_handle) {
    execute_sql($link, $database, "UPDATE export_tasks SET status = 'failed', error_message = '無法建立實體檔案' WHERE task_id = $task_id");
    exit();
}

$link_update = create_connection();
$export_sql = "SELECT * FROM {$target_table} WHERE time <= '$three_months_ago' ORDER BY time ASC";
$export_result = mysqli_query($link, $export_sql, MYSQLI_USE_RESULT);

$processed_count = 0;
$batch_count = 0;
$values_array = [];

$insert_prefix = "INSERT IGNORE INTO {$target_table} (id, mac_address, data, wet, bodytemp, roomtemp, breath, SpitMilk, LeaveBed, GetUp, time, hubnum, power, rssi, hubtime) VALUES \n";

// 溫度計時器初始化
$last_temp_time = null;
$last_bodytemp = '0';
$last_roomtemp = '0';

if ($export_result) {
    while ($row = mysqli_fetch_assoc($export_result)) {
        
        $current_time_stamp = strtotime($row['time']);
        $current_bodytemp = $row['bodytemp'];
        $current_roomtemp = $row['roomtemp'];

        // 5 分鐘過濾邏輯
        if ($last_temp_time === null || ($current_time_stamp - $last_temp_time) >= 300) {
            $last_temp_time = $current_time_stamp;
            $last_bodytemp = $current_bodytemp;
            $last_roomtemp = $current_roomtemp;
            $export_bodytemp = $current_bodytemp;
            $export_roomtemp = $current_roomtemp;
        } else {
            $export_bodytemp = $last_bodytemp;
            $export_roomtemp = $last_roomtemp;
        }

        $values_array[] = "('".$row['id']."', '".$row['mac_address']."', '".$row['data']."', '".$row['wet']."', '".$export_bodytemp."', '".$export_roomtemp."', '".$row['breath']."', '".$row['SpitMilk']."', '".$row['LeaveBed']."', '".$row['GetUp']."', '".$row['time']."', '".$row['hubnum']."', '".$row['power']."', '".$row['rssi']."', '".$row['hubtime']."')";
        
        $processed_count++;
        $batch_count++;
        if ($batch_count >= 2000) {
            $sql_chunk_content = $insert_prefix . implode(",\n", $values_array) . ";\n";
            fwrite($file_handle, $sql_chunk_content);

            $values_array = [];
            $batch_count = 0;

            $current_progress = floor(($processed_count / $total_rows) * 100);
            static $last_progress = 0;
            if ($current_progress > $last_progress) {
                execute_sql($link_update, $database, "UPDATE export_tasks SET progress = $current_progress WHERE task_id = $task_id");
                $last_progress = $current_progress;
            }
        }
    }

    // 迴圈結束後，把陣列裡剩下的零頭資料寫進去
    if (!empty($values_array)) {
        $sql_chunk_content = $insert_prefix . implode(",\n", $values_array) . ";\n";
        fwrite($file_handle, $sql_chunk_content);
    }
}

fclose($file_handle);
mysqli_close($link_update);

$raw_table_name = str_replace('`', '', $target_table);
$table_new = "`{$raw_table_name}_new`";
$table_old = "`{$raw_table_name}_old`";
execute_sql($link, $database, "CREATE TABLE IF NOT EXISTS {$table_new} LIKE {$target_table}");

$insert_keep_sql = "INSERT INTO {$table_new} SELECT * FROM {$target_table} WHERE time > '$three_months_ago'";
$insert_result = execute_sql($link, $database, $insert_keep_sql);

if ($insert_result !== false) {
    $swap_sql = "RENAME TABLE {$target_table} TO {$table_old}, {$table_new} TO {$target_table}";
    execute_sql($link, $database, $swap_sql);
    execute_sql($link, $database, "DROP TABLE {$table_old}");

} else {
    execute_sql($link, $database, "UPDATE export_tasks SET status = 'failed', error_message = '影子表資料搬移失敗，請檢查硬碟空間' WHERE task_id = $task_id");
    execute_sql($link, $database, "DROP TABLE IF EXISTS {$table_new}");
    exit();
}
execute_sql($link, $database, "UPDATE export_tasks SET status = 'completed', progress = 100, file_path = '$db_file_path' WHERE task_id = $task_id");

exit();
?>