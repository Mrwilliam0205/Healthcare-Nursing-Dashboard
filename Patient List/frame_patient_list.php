<?php
    // 判斷目前的分類狀態 (預設為 Hospitalized)
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
    <title>照護系統 - 病患列表</title>
    
    <!-- 引入新版主架構 CSS (因為在根目錄，直接寫 css/style.css 即可) -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    
    <!-- 頂部導覽列 -->
    <?php include ('../menubar.php'); ?>

    <div class="app-container">
        
        <!-- 左側邊欄 -->
        <aside class="sidebar">
            <?php include ('item_patient_list.php'); ?>
        </aside>

        <!-- 主要內容區 -->
        <main class="main-content">
            <?php include ('data_patient_list.php'); ?>
        </main>
        
    </div>

</body>
</html>