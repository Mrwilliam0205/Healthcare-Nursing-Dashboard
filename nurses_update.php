<?php
session_start();
header("Content-Type: text/plain; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['emp_id'])) {
        echo "未授權的操作";
        exit;
    }

	require_once($_SERVER['DOCUMENT_ROOT'] . "/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	
    $link = create_connection();

    $old_emp_id = trim($_POST['old_emp_id']); 
    $emp_id     = trim($_POST['emp_id']);
    $name       = trim($_POST['name']);
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']);
    $role       = trim($_POST['role']); 

    $is_superadmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin');
    $is_admin = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'superadmin']));
    $is_self  = ($_SESSION['emp_id'] === $old_emp_id);

    $check_target = execute_sql($link, "wetness_database2", "SELECT `role` FROM `nurses_account` WHERE `emp_id` = '$old_emp_id'");
    $target_data = mysqli_fetch_assoc($check_target);
    $target_role = $target_data ? $target_data['role'] : 'user';

    if ($target_role === 'superadmin') {
        $emp_id = $old_emp_id; 
        $role = 'superadmin';       
    }

    if (!$is_superadmin && $role === 'superadmin') {
        $role = 'admin';
    }

    if ($is_admin && !$is_superadmin && $target_role === 'admin' && !$is_self) {
        echo "您只能修改一般護理人員或自己的資料";
        mysqli_close($link);
        exit;
    }

    if (!$is_admin && !$is_self) {
        echo "權限不足，無法修改他人資料";
        mysqli_close($link);
        exit;
    }

    if (empty($name) || empty($username)) {
        echo "姓名與帳號不可為空";
        mysqli_close($link);
        exit;
    }

    if ($is_admin) {
        if (empty($emp_id)) {
            echo "編號不可為空";
            mysqli_close($link);
            exit;
        }

        $check_sql = "SELECT * FROM `nurses_account` WHERE (`username` = '$username' OR `emp_id` = '$emp_id') AND `emp_id` != '$old_emp_id'";
        $check_result = execute_sql($link, "wetness_database2", $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            echo "新的帳號或編號已與其他人重複";
            mysqli_close($link);
            exit;
        }

        if (!empty($password)) {
            $sql = "UPDATE `nurses_account` SET `emp_id`='$emp_id', `name`='$name', `username`='$username', `password`='$password', `role`='$role' WHERE `emp_id`='$old_emp_id'";
        } else {
            $sql = "UPDATE `nurses_account` SET `emp_id`='$emp_id', `name`='$name', `username`='$username', `role`='$role' WHERE `emp_id`='$old_emp_id'";
        }
    } else {
        // 一般人員修改自己的帳號、姓名、密碼 (忽略前端傳入的 emp_id 與 role，確保安全性)
        $check_sql = "SELECT * FROM `nurses_account` WHERE `username` = '$username' AND `emp_id` != '$old_emp_id'";
        $check_result = execute_sql($link, "wetness_database2", $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            echo "新的帳號已與其他人重複";
            mysqli_close($link);
            exit;
        }

        if (!empty($password)) {
            $sql = "UPDATE `nurses_account` SET `name`='$name', `username`='$username', `password`='$password' WHERE `emp_id`='$old_emp_id'";
        } else {
            $sql = "UPDATE `nurses_account` SET `name`='$name', `username`='$username' WHERE `emp_id`='$old_emp_id'";
        }
    }
    
    if (execute_sql($link, "wetness_database2", $sql)) {
        if ($old_emp_id === $_SESSION['emp_id']) {
            $_SESSION['emp_id'] = ($is_admin) ? $emp_id : $old_emp_id;
            if ($is_admin || $is_superadmin) {
                $_SESSION['role'] = $role;
            }
        }
        echo "success";
    } else {
        echo "資料庫更新失敗";
    }
    
    mysqli_close($link);
}
?>