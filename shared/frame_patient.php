<?php
// 1. 先處理後端邏輯與資料庫連線 (絕對路徑不用動)
require_once("../login_database/dbtools.inc.php");	

if(!isset($_GET['item'])){
    $item="Wet";
}else{
    $item=$_GET['item'];
}

// 驗證是否有傳入病患 IDP，若無則攔截並退回登入頁
if(!isset($_GET['IDP'])){
    echo "<script>alert('警告：查無入住資料');location.href = '../login.php';</script>"; // login.php 在外面，加上 ../
    exit;
}else{
    $IDP=$_GET['IDP'];
}

if(!isset($_GET['date'])){
    $date=$time;
}else{
    $date=$_GET['date'];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>照護系統 - 病患資訊</title>
    <!-- 【關鍵修改】補上 ../ 退回上一層去找 css 資料夾 -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    
    <!-- 2. 頂部導覽列 (補上 ../ 退回上一層找) -->
    <?php include ('../menubar.php'); ?>

    <!-- 3. 下方彈性容器 (取代舊的 <div class="content">) -->
    <div class="app-container">
        
        <!-- 4. 左側邊欄 -->
        <aside class="sidebar">
            <?php include ('item_patient_info.php'); ?>
        </aside>

        <!-- 5. 主要內容區 -->
        <main class="main-content">
            <?php include ('patient_info.php'); ?>
        </main>
        
    </div>
</body>
</html>