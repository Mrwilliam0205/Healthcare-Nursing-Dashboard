<?php
    if(!isset($_GET['item'])){
        $item = "Hospitalized";
    } else {
        $item = $_GET['item'];
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>照護系統 - 出院病患歷史列表</title>
    
    <!-- 加上 ../ 退回上一層找 css 資料夾 -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    
    <!-- 加上 ../ 退回上一層找 menubar.php -->
    <?php include ('../menubar.php'); ?>

    <div class="app-container">
        
        <!-- 左側邊欄 (假設與本檔案放在同一個子資料夾，不需 ../) -->
        <aside class="sidebar">
            <?php include ('item_patient_list.php'); ?>
        </aside>

        <!-- 主要內容區 (假設與本檔案放在同一個子資料夾，不需 ../) -->
        <main class="main-content">
            <?php include ('data_discharged_member_list.php'); ?>
        </main>
        
    </div>

</body>
</html>