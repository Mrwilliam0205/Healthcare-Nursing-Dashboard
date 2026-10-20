<?php
session_start();
header("Content-Type:text/html; charset=utf-8");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	
$link = create_connection();

$check_empty = execute_sql($link, "wetness_database2", "SELECT COUNT(*) as cnt FROM `nurses_account`");
$row = mysqli_fetch_assoc($check_empty);
$is_empty = ($row['cnt'] == 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo "<script>alert('帳號密碼不可為空！');</script>";
    } else {
        if ($is_empty) {
            $emp_id = 'admin-' . date("Ymd");
            $name = '護理人員管理員';
            
            // 初始化直接寫入 superadmin 權限
            $sql = "INSERT INTO `nurses_account` (`emp_id`, `name`, `username`, `password`, `role`) 
                    VALUES ('$emp_id', '$name', '$username', '$password', 'superadmin')";
            
            if (execute_sql($link, "wetness_database2", $sql)) {
                $_SESSION['emp_id'] = $emp_id;
                $_SESSION['role'] = 'superadmin';
                echo "<script>alert('🎉 系統初始化完成！已為您建立最高管理員帳號。'); location.href='staff_list.php';</script>";
            } else {
                echo "<script>alert('系統錯誤，管理員建立失敗！');</script>";
            }
        } else {
            $sql = "SELECT * FROM `nurses_account` WHERE `username` = '$username' AND `password` = '$password'";
            $result = execute_sql($link, "wetness_database2", $sql);
            
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                $_SESSION['emp_id'] = $user['emp_id'];
                $_SESSION['role'] = $user['role'];
                header("Location: staff_list.php");
                exit;
            } else {
                echo "<script>alert('❌ 帳號或密碼錯誤，請重新輸入！');</script>";
            }
        }
    }
}
mysqli_close($link);
?>
<!-- 下方 HTML 表單與您原先的一樣，不需變動 -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>智慧護理系統 - 登入</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f1f5f9;">
  <div class="upload-card" style="max-width: 400px; width: 100%; padding: 2rem;">
    <h2 style="text-align: center; margin-bottom: 1.5rem;">護理人員登入</h2>
    <form method="POST" action="nurses_login.php">
      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label">登入帳號</label>
        <input type="text" name="username" class="form-input" placeholder="請輸入帳號" required>
      </div>
      <div class="form-group" style="margin-bottom: 1.5rem;">
        <label class="form-label">登入密碼</label>
        <input type="password" name="password" class="form-input" placeholder="請輸入密碼" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">登入</button>
    </form>
  </div>
</body>
</html>