<?php
    // 定義目前所在的功能頁面，讓導覽列知道現在在哪個標籤
    $item = "data_member";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>照護系統 - 病患資料管理</title>
    
    <!-- 退回上一層找 css 資料夾 -->
    <link rel="stylesheet" href="../css/style.css">
    
    <style>
        /* 針對沒有側邊欄的頁面，額外設定讓內容置中 */
        .main-content-full {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center; /* 水平置中 */
            padding: 50px 20px;
            background-color: var(--bg-light); /* 吃你的淺灰底色變數 */
            width: 100%;
        }
    </style>
</head>
<body>
    
    <!-- 頂部導覽列 -->
    <?php include ('../menubar.php'); ?>

    <div class="app-container">
        
        <!-- 已經徹底移除左側邊欄 (aside) -->

        <!-- 主要內容區 (改為滿版置中) -->
        <main class="main-content-full">
            <?php include ('data_patient.php'); ?>
        </main>
        
    </div>

</body>
</html>