<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>智慧護理系統</title>
    <!-- 退回上一層找 css 資料夾裡的 style.css -->
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body>
    
    <!-- 退回上一層找共用的導覽列 -->
    <?php include ('../menubar.php'); ?>

    <div class="app-container">
        
        <aside class="sidebar">
            <!-- 因為側邊欄檔案在同一個資料夾，所以直接 include -->
            <?php include ('nursingStation_type.php'); ?>
        </aside>

        <main class="main-content">
            <?php
                $ward = explode("=",$_SERVER['QUERY_STRING'])[1];
                if($ward !="warning_ward") include ('show.php');
                else include ('show_warningt.php');
            ?>
        </main>
        
    </div>
</body>
</html>