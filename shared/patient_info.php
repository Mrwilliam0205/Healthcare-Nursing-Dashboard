<?php
    /*
    必須開啟兩個資料庫wetness_database和nfc_hub
    */
    // 檢查若 $link 尚未建立則建立連線（避免重複呼叫衝突）
    if (!isset($link)) {
        require_once("../login_database/dbtools.inc.php");   
        $link = create_connection();
    }

    if (!function_exists('mysqli_result')) {
        function mysqli_result($res,$row=0,$col=0){
            $numrows = mysqli_num_rows($res);
            if ($numrows &&$row <= ($numrows-1) &&$row >=0){
                mysqli_data_seek($res,$row);
                $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
                if (isset($resrow[$col])){
                    return $resrow[$col];
                }
            }
            return false;
        }
    }

    $sql_warning="SELECT * FROM warning_standard";
    $result_warning = execute_sql($link,"wetness_database2",$sql_warning);
    
    if (!$result_warning) {
        die("<div style='color:red; font-size:20px; padding:20px;'><b>SQL_WARNING 查詢失敗！</b><br>錯誤原因: " . mysqli_error($link) . "</div>");
    }

    $warning = mysqli_fetch_assoc($result_warning);
    $wet_top =$warning['wet_top'];
    $temp_top =$warning['temp_top'];
    $temp_bot =$warning['temp_bot'];
    $breath_top =$warning['breath_top'];
    $breath_bot =$warning['breath_bot'];

    $IDP = isset($_REQUEST['IDP']) ? $_REQUEST['IDP'] : '';$array_tag = ''; 

    //查詢tag資訊
    $sql_tag = "SELECT DISTINCT tagmac,lastPID FROM tag WHERE `owner` = '$IDP' ORDER BY `lastPID` ASC";
    $result_tag = execute_sql($link,"wetness_database2",$sql_tag);
    
    if (!$result_tag) {
        die("<div style='color:red; font-size:20px; padding:20px;'><b>SQL_TAG 標籤查詢失敗！</b></div>");
    }

    if (mysqli_num_rows($result_tag) == 0){
        $array_tag = 0; } else {$array_tag = 1;
    }

    //查詢生理資訊預設值
    $temp = 0; $wet = 0; $breath = 0;
    
    // 溫度
    $sql_temp = "SELECT DISTINCT tag.tagmac, tag.lastPID, valueimmediate.bodytemp, valueimmediate.breath, valueimmediate.time
                 FROM tag , valueimmediate
                 WHERE tag.owner = '$IDP' AND tag.tagmac = valueimmediate.mac_address AND tag.lastPID = '03' ORDER BY valueimmediate.time DESC";
    $result_temp = execute_sql($link,"wetness_database2",$sql_temp);
    if (mysqli_num_rows($result_temp) != 0){$temp_mac = mysqli_result($result_temp,0,0);$temp = mysqli_result($result_temp,0,2);$temp_time = mysqli_result($result_temp,0,4); } else {$temp = "無配對"; $temp_time = ""; $temp_mac = "";
    }

    // 濕度
    $sql_wet = "SELECT DISTINCT tag.tagmac, tag.lastPID, valueimmediate.wet, valueimmediate.breath, valueimmediate.time
                FROM tag , valueimmediate
                WHERE tag.owner = '$IDP' AND tag.tagmac = valueimmediate.mac_address AND (tag.lastPID = '02' OR tag.lastPID = '06') ORDER BY valueimmediate.time DESC";
    $result_wet = execute_sql($link,"wetness_database2",$sql_wet);
    if (mysqli_num_rows($result_wet) != 0){$wet_mac = mysqli_result($result_wet,0,0);$wet = mysqli_result($result_wet,0,2 );$wet_time = mysqli_result($result_wet,0,4);$breath = mysqli_result($result_wet,0,3); } else {$wet = "無配對"; $wet_time = ""; $wet_mac = "";
    }

    // 呼吸
    $sql_breath = "SELECT DISTINCT tag.tagmac, tag.lastPID, valueimmediate.breath, valueimmediate.time
                   FROM tag , valueimmediate
                   WHERE tag.owner = '$IDP' AND tag.tagmac = valueimmediate.mac_address AND tag.lastPID = '05' ORDER BY valueimmediate.time DESC";
    $result_breath = execute_sql($link,"wetness_database2",$sql_breath);
    if (mysqli_num_rows($result_breath) != 0){
        $breath_time = mysqli_result($result_breath,0,3);
        if ((strtotime($breath_time) > strtotime($wet_time))){
            $breath_mac = mysqli_result($result_breath,0,0);
            $breath = mysqli_result($result_breath,0,2);
        } else {
            $breath_mac =$wet_mac;
            $breath_time =$wet_time;
        }
    } else {
        if ($wet != "無配對"){
            $breath_mac =$wet_mac;
            $breath_time =$wet_time;
        } else {
            $breath = "無配對"; $breath_time = ""; $breath_mac = "";
        }
    }
?>

<!-- ========================================== -->
<!-- 保留 JavaScript Ajax 自動更新邏輯 -->
<!-- ========================================== -->
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const pageIDP = urlParams.get('IDP');

    if (pageIDP) {
        function updateNumericData(IDP) {
            var xhr = new XMLHttpRequest();
            var url = "ajax/get_patient_data.php?IDP=" + IDP; 
            xhr.open("GET", url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    
                    document.getElementById("temp").innerText = (data.temp !== "無配對") ? data.temp + " °C" : "無配對";
                    document.getElementById("temp_time").innerText = data.temp_time;
                    document.getElementById("temp_mac").innerText = data.temp_mac;
                    
                    document.getElementById("wet").innerText = (data.wet !== "無配對") ? data.wet + " %" : "無配對";
                    document.getElementById("wet_time").innerText = data.wet_time;
                    document.getElementById("wet_mac").innerText = data.wet_mac;
                    
                    document.getElementById("breath").innerText = (data.breath !== "無配對") ? data.breath + " 次/分" : "無配對";
                    document.getElementById("breath_mac").innerText = data.breath_mac;
                    document.getElementById("breath_time").innerText = data.breath_time;
                }
            };
            xhr.send();
        }

        updateNumericData(pageIDP);
        setInterval(function() {
            updateNumericData(pageIDP);
        }, 2000); 
    }
</script>

<style>
    .dashboard-wrapper { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 10px; }
    
    /* 頂部標題與按鈕區塊 */
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .header-title { font-size: 1.25rem; font-weight: 700; color: #1e3a8a; display: flex; align-items: center; gap: 8px; }
    .btn-history { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background-color: #fff; border: 1px solid #10b981; color: #10b981; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
    .btn-history:hover { background-color: #10b981; color: #fff; }

    /* Part 1: 即時生理數據與監測 (三個卡片並排) */
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .metric-card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
    
    .metric-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .metric-title { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: #334155; }
    .metric-value { font-size: 1.5rem; font-weight: 800; }
    
    .text-temp { color: #ef4444; }
    .text-wet { color: #0ea5e9; }
    .text-breath { color: #10b981; }

    .metric-meta { font-size: 0.8rem; color: #64748b; display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; }
    .meta-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #f1f5f9; padding-bottom: 4px; }
    .mac-pill { background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-family: monospace; letter-spacing: 0.5px; }

    /* 圖表放置區 (iframe 容器) */
    .chart-box { width: 100%; height: 160px; background: #fafafa; border-radius: 8px; overflow: hidden; position: relative; }
    .chart-box iframe { width: 100%; height: 100%; border: none; }

    /* Part 2 & Part 3: 獨立區塊樣式 */
    .section-card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; margin-bottom: 25px; }
    .section-header { font-size: 1.1rem; font-weight: 700; color: #1e3a8a; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    
    /* 表格樣式 */
    .custom-table { width: 100%; border-collapse: collapse; text-align: left; }
    .custom-table th { padding: 12px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 0.95rem; }
    .custom-table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.95rem; }
    .custom-table tr:last-child td { border-bottom: none; }
    
    /* 操作按鈕 */
    .btn-danger-outline { padding: 6px 12px; border: 1px solid #ef4444; color: #ef4444; background: #fff; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: all 0.2s; }
    .btn-danger-outline:hover { background: #ef4444; color: #fff; }
</style>

<div class="dashboard-wrapper">
    
    <!-- Header -->
    <div class="header-bar">
        <div class="header-title">
            <i class="fa-solid fa-heart-pulse"></i> 即時生理數據與監測
        </div>
        <a href="../frame_personal_history.php?IDP=<?php echo $IDP;?>" class="btn-history">
            <i class="fa-solid fa-clock-rotate-left"></i> 歷史紀錄查詢
        </a>
    </div>

    <!-- Part 1: 即時生理數據與監測 (三個卡片並排，內含數據與折線圖 iframe) -->
    <div class="metrics-grid">
        
        <!-- 體溫卡片 -->
        <div class="metric-card">
            <div class="metric-top">
                <div class="metric-title"><i class="fa-solid fa-temperature-half text-temp"></i> 體溫監測</div>
                <?php $tempDisplay = is_numeric($temp) ? $temp . " °C" : $temp; ?>
                <div class="metric-value text-temp" id="temp"><?php echo $tempDisplay; ?></div>
            </div>
            <div class="metric-meta">
                <div class="meta-row"><span>MAC :</span> <span class="mac-pill" id="temp_mac"><?php echo $temp_mac; ?></span></div>
                <div class="meta-row"><span>更新時間 :</span> <span><i class="fa-regular fa-clock"></i> <span id="temp_time"><?php echo $temp_time; ?></span></span></div>
            </div>
            <div class="chart-box">
                <iframe src='index_temp.php?IDP=<?php echo $IDP; ?>'></iframe>
            </div>
        </div>

        <!-- 濕度卡片 -->
        <div class="metric-card">
            <div class="metric-top">
                <div class="metric-title"><i class="fa-solid fa-droplet text-wet"></i> 尿褲濕度</div>
                <?php $wetDisplay = is_numeric($wet) ? $wet . " \%" : $wet; ?>
                <div class="metric-value text-wet" id="wet"><?php echo $wetDisplay; ?></div>
            </div>
            <div class="metric-meta">
                <div class="meta-row"><span>MAC :</span> <span class="mac-pill" id="wet_mac"><?php echo $wet_mac; ?></span></div>
                <div class="meta-row"><span>更新時間 :</span> <span><i class="fa-regular fa-clock"></i> <span id="wet_time"><?php echo $wet_time; ?></span></span></div>
            </div>
            <div class="chart-box">
                <iframe src='index_wet.php?IDP=<?php echo $IDP; ?>'></iframe>
            </div>
        </div>

        <!-- 呼吸卡片 -->
        <div class="metric-card">
            <div class="metric-top">
                <div class="metric-title"><i class="fa-solid fa-lungs text-breath"></i> 呼吸率</div>
                <?php $breathDisplay = is_numeric($breath) ? $breath . " 次/分" : $breath; ?>
                <div class="metric-value text-breath" id="breath"><?php echo $breathDisplay; ?></div>
            </div>
            <div class="metric-meta">
                <div class="meta-row"><span>MAC :</span> <span class="mac-pill" id="breath_mac"><?php echo $breath_mac; ?></span></div>
                <div class="meta-row"><span>更新時間 :</span> <span><i class="fa-regular fa-clock"></i> <span id="breath_time"><?php echo $breath_time; ?></span></span></div>
            </div>
            <div class="chart-box">
                <iframe src='index_breath.php?IDP=<?php echo $IDP; ?>'></iframe>
            </div>
        </div>

    </div>

    <!-- Part 2: 事件紀錄清單 -->
    <div class="section-card">
        <div class="section-header">
            <i class="fa-solid fa-list-ul text-wet"></i> 事件紀錄清單
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 25%;">發生時間</th>
                    <th>發生事件內容</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>2026-07-28 16:30:12</td>
                    <td><i class="fa-solid fa-droplet text-wet"></i> 完成尿布更換 (濕度回落至 12%)</td>
                </tr>
                <tr>
                    <td>2026-07-28 14:15:00</td>
                    <td><i class="fa-solid fa-triangle-exclamation text-temp"></i> 發燒警示觸發 (體溫達到 37.8 °C)</td>
                </tr>
                <tr>
                    <td>2026-07-28 12:00:45</td>
                    <td><i class="fa-solid fa-arrows-rotate" style="color: #64748b;"></i> 協助病患定時翻身與體位調整</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Part 3: 配對裝置列表 -->
    <div class="section-card">
        <div class="section-header">
            <i class="fa-solid fa-link text-temp"></i> 配對裝置列表
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 40%;">裝置類型</th>
                    <th style="width: 40%;">MAC 位址</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($array_tag == '0'){
                    echo "<tr><td colspan='3' style='text-align: center; color: #94a3b8;'>目前無配對任何裝置</td></tr>";
                } else {
                    for($j=0; $j<mysqli_num_rows($result_tag);$j++){
                        echo "<tr>";
                        
                        $deviceType = mysqli_result($result_tag,$j,1);
                        echo "<td><b>";
                        if ($deviceType == "03") echo "Nursemaid - 感溫衣";
                        else if ($deviceType == "05") echo "Nursemaid - 腰帶";
                        else if ($deviceType == "06") echo "Nursemaid - 智慧尿褲";
                        else if ($deviceType == "02") echo "電子尿布";
                        else echo "未知裝置";
                        echo "</b></td>";
                        
                        $mac = mysqli_result($result_tag,$j,0);
                        echo "<td><span class='mac-pill'>" . $mac . "</span></td>";
                        
                        echo "<td><a href='process_mac_unpair.php?mac=" . $mac . "' class='btn-danger-outline'><i class='fa-solid fa-link-slash'></i> 解除配對</a></td>";
                        
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</div>