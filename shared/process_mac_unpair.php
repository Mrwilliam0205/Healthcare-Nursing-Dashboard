<?php
    $mac = isset($_GET['mac']) ?$_GET['mac'] : '';
    
    // 因為這支檔案已經被搬進 Nurse Station 裡面了，
    // 所以你需要確認一下 login_database 資料夾是在哪裡。
    // 如果它原本在 C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/ 
    // 且這是你的絕對路徑，那這行不用動。
    require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
    
    $link = create_connection();

    // 更新 `tag` 把配對的IDP及使用中都改成NULL(字串)
    $sql_update_correspond = "UPDATE `tag` SET `owner`='' WHERE `tagmac`= '$mac'";
    $result_update_correspond = execute_sql($link,"wetness_database",$sql_update_correspond);
    $error_correspond = mysqli_error($link);

    // 取得當前時間
    $time = date("Y-m-d H:i:s");

    // 更新 `correspondhistory` 加上 $end 時間
    $sql_update_history = "UPDATE `correspondhistory` SET `end`='$time' WHERE `mac_address`='$mac' AND `end` = '1970-01-01 00:00:00'";
    $result_update_history = execute_sql($link,"wetness_database",$sql_update_history);
    $error_history = mysqli_error($link);

    // 為了配合你新版系統的美感，我們把成功或失敗的訊息稍微美化
    echo "<div style='display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; background-color: #f8fafc;'>";
    
    if($result_update_correspond &&$result_update_history){
        echo "<h2 style='color: #10b981; font-weight: bold;'>✔ 解除配對成功</h2>";
        echo "<p style='color: #64748b; font-size: 1.1rem;'>裝置 MAC: {$mac} 已安全解除</p>";
    }else{
        echo "<h2 style='color: #ef4444; font-weight: bold;'>✘ 解除配對失敗</h2>";
        echo "<p style='color: #64748b;'>裝置 MAC: {$mac}</p>";
        echo "<p style='color: #64748b; font-size: 0.9rem;'>錯誤訊息: {$error_correspond} <br> {$error_history}</p>";
    }
    
    echo "</div>";

    // 取得前一頁的網址 (HTTP_REFERER)，如果沒有來源，預設回到全病床頁面
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ?$_SERVER['HTTP_REFERER'] : 'show.php';
?>

<!-- 1.5秒後自動跳轉回上一頁 -->
<meta http-equiv="refresh" content="1.5;url=<?= $redirect_url ?>">