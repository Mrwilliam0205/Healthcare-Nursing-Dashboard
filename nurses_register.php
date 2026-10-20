<?php
session_start();

// 修正：允許 'admin' 或 'superadmin' 進入
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "<script>alert('權限不足，只有管理員能註冊人員！'); location.href='staff_list.php';</script>";
    exit;
}

header("Content-Type:text/html; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once($_SERVER['DOCUMENT_ROOT'] . "/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	
    $link = create_connection();

    $emp_id   = trim($_POST['emp_id']);
    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($emp_id) || empty($name) || empty($username) || empty($password)) {
        echo "<script>alert('所有欄位皆不可為空！');</script>";
    } else {
        $check_sql = "SELECT * FROM `nurses_account` WHERE `username` = '$username' OR `emp_id` = '$emp_id'";
        $check_result = execute_sql($link, "wetness_database2", $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            echo "<script>alert('這個帳號或人員編號已經被註冊過了，請重新確認！');</script>";
        } else {
            // 新增時強制將 role 設為 'user' (一般人員權限)[cite: 14]
            $insert_sql = "INSERT INTO `nurses_account` (`emp_id`, `name`, `username`, `password`, `role`) VALUES ('$emp_id', '$name', '$username', '$password', 'user')";
            $insert_result = execute_sql($link, "wetness_database2", $insert_sql);

            if ($insert_result) {
                echo "<script>alert('🎉 註冊成功！已將「{$name}」加入人員列表。'); location.href='nurses_register.php';</script>";
            } else {
                echo "<script>alert('系統錯誤，註冊失敗！');</script>";
            }
        }
    }
    mysqli_close($link);
}
?>
<!-- 下方 HTML 結構維持原樣 -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>智慧護理系統 - 註冊新護理人員</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="components/NavbarComponent.js" defer></script>
</head>
<body>
  <app-navbar active="settings"></app-navbar>

  <?php include ('menubar.php'); ?>

  <div class="app-container">
    <aside class="sidebar">
      <div class="sidebar-section-title">護理人員管理</div>
      <ul class="sidebar-menu">
        <li>
          <a href="staff_list.php" class="sidebar-link">
            <span>📋 人員列表</span>
          </a>
        </li>
        <li>
          <a href="nurses_register.php" class="sidebar-link active">
            <span>➕ 註冊人員</span>
          </a>
        </li>
        <!-- 開放 IP 管理給所有人，移除原本可能包圍的 if 判斷 -->
        <li>
          <a href="ip_management.php" class="sidebar-link">
            <span>🌐 IP 管理</span>
          </a>
        </li>
      </ul>
    </aside>

    <main class="main-content">
      <section id="section-register" class="content-section" style="display: block;">
        <div class="page-header">
          <h1 class="page-title">註冊新護理人員</h1>
        </div>

        <div class="upload-card" style="max-width: 500px;">
          <form id="registerForm" method="POST" action="nurses_register.php">
            <div class="form-group" style="margin-bottom: 1rem;">
              <label class="form-label">編號</label>
              <input type="text" id="regId" name="emp_id" class="form-input" placeholder="請輸入人員編號 (如: N-202403)" required>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
              <label class="form-label">姓名</label>
              <input type="text" id="regName" name="name" class="form-input" placeholder="請輸入姓名" required>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
              <label class="form-label">帳號</label>
              <input type="text" id="regAcc" name="username" class="form-input" placeholder="請設定登入帳號" required>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
              <label class="form-label">密碼</label>
              <input type="password" id="regPwd" name="password" class="form-input" placeholder="請設定登入密碼" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
              確認註冊並儲存
            </button>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html>