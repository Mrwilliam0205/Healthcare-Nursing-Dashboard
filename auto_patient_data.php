<?php
ignore_user_abort(true);
set_time_limit(0);

require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
$link = create_connection();
$database = 'wetness_database2';

$overdue_list_file = "discharged_patient_data/overdue_patients.json";

// 確保資料夾存在
if (!file_exists("discharged_patient_data/")) {
    mkdir("discharged_patient_data/", 0777, true);
}

// ==========================================
// 區塊一：Task A (將總表資料分發到個人表單)
// ==========================================
$sql_get_ids = "SELECT DISTINCT IDP FROM correspondhistory WHERE IDP IS NOT NULL AND IDP != '' AND (end IS NULL OR end = '1970-01-01 00:00:00')";
$result_ids = execute_sql($link, $database, $sql_get_ids);

if ($result_ids) {
    while ($row = mysqli_fetch_assoc($result_ids)) {
        $safe_idp = mysqli_real_escape_string($link, trim($row['IDP']));
        $target_table = "`{$safe_idp}_valuehistory`";

        execute_sql($link, $database, "CREATE TABLE IF NOT EXISTS {$target_table} LIKE valuehistory");
        
        $move_criteria = "FROM valuehistory v
                          JOIN correspondhistory c ON v.mac_address = c.mac_address
                          WHERE c.IDP = '{$safe_idp}'
                          AND v.time >= c.begin
                          AND (c.end IS NULL OR c.end = '1970-01-01 00:00:00' OR v.time <= c.end)";

        execute_sql($link, $database, "INSERT IGNORE INTO {$target_table} SELECT v.* {$move_criteria}");
    }
    mysqli_free_result($result_ids);
}

//刪除 7 天前的舊資料
$delete_old_sql = "DELETE FROM valuehistory WHERE time < DATE_SUB(NOW(), INTERVAL 7 DAY)";
execute_sql($link, $database, $delete_old_sql);


// ==========================================
// 區塊二：Task B (檢查是否有超過 6 個月的個人資料)
// ==========================================
$overdue_patients = [];
$six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months')); 

$sql_get_all_ids = "SELECT DISTINCT IDP FROM correspondhistory WHERE IDP IS NOT NULL AND IDP != ''";
$result_all_ids = execute_sql($link, $database, $sql_get_all_ids);

if ($result_all_ids) {
    while ($row = mysqli_fetch_assoc($result_all_ids)) {
        $safe_idp = mysqli_real_escape_string($link, trim($row['IDP']));
        $target_table = "`{$safe_idp}_valuehistory`";

        $check_table = execute_sql($link, $database, "SHOW TABLES LIKE '{$safe_idp}_valuehistory'");
        if (mysqli_num_rows($check_table) > 0) {
            $check_old_data = execute_sql($link, $database, "SELECT 1 FROM {$target_table} WHERE time <= '$six_months_ago' LIMIT 1");
            if (mysqli_num_rows($check_old_data) > 0) {
                $check_task_sql = "SELECT task_id FROM export_tasks WHERE patient_id = '$safe_idp' AND status IN ('pending', 'processing', 'completed')";
                $task_res = execute_sql($link, $database, $check_task_sql);
                if (mysqli_num_rows($task_res) == 0) {
                    $overdue_patients[] = $safe_idp;
                }
            }
        }
    }
    mysqli_free_result($result_all_ids);
}

// 將結果寫入 JSON
file_put_contents($overdue_list_file, json_encode($overdue_patients));
mysqli_close($link);
?>