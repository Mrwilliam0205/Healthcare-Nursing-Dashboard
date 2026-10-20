<?php
session_start();
header("Content-Type: text/plain; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_superadmin = ($_SESSION['role'] === 'superadmin');
    $is_admin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');

    if (!$is_admin) {
        echo "權限不足，無法刪除人員";
        exit;
    }

    require_once($_SERVER['DOCUMENT_ROOT'] . "/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	
    $link = create_connection();

    $emp_id = trim($_POST['emp_id']);

    // 取得目標的真實權限級別
    $check_sql = "SELECT `role` FROM `nurses_account` WHERE `emp_id` = '$emp_id'";
    $result = execute_sql($link, "wetness_database2", $check_sql);
    if (mysqli_num_rows($result) == 0) {
        echo "找不到該人員";
        mysqli_close($link);
        exit;
    }
    $target = mysqli_fetch_assoc($result);

    // 防護 1: superadmin 絕對不可被刪除
    if ($target['role'] === 'superadmin') {
        echo "最高管理員帳號無法被刪除";
        mysqli_close($link);
        exit;
    }

    // 防護 2: 一般管理員不能刪除其他管理員，只能刪自己或 user
    if (!$is_superadmin && $target['role'] === 'admin' && $emp_id !== $_SESSION['emp_id']) {
        echo "您只能刪除一般護理人員或自己的帳號";
        mysqli_close($link);
        exit;
    }

    if (!empty($emp_id)) {
        $sql = "DELETE FROM `nurses_account` WHERE `emp_id` = '$emp_id'";
        if (execute_sql($link, "wetness_database2", $sql)) {
            echo "success";
        } else {
            echo "資料庫刪除失敗";
        }
    } else {
        echo "未提供人員編號";
    }
    
    mysqli_close($link);
}
?>