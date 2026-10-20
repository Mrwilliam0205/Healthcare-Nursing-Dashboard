<script>
    function checkrange(top, bot, number){
        if(bot<=number && number<=top) return true;
        else return false;
    }
    
    function checkwarning(id, item, value, time, patientNum, NumOfJData){
        var wet_top = <?php echo $warning['wet_top'];?>;
        var temp_top = <?php echo $warning['temp_top'];?>;
        var temp_bot = <?php echo $warning['temp_bot'];?>;
        var breath_top = <?php echo $warning['breath_top'];?>;
        var breath_bot = <?php echo $warning['breath_bot'];?>;
        var count = 0;

        for (var i = 0; i < item.length; i++) {
            var currentItem = item[i];
            var currentId = id[i];

            // 🔥 修正：只判斷顏色，不再於 JS 中加上 '%' 或 '度' 等單位，因為新版 HTML 已經有獨立的單位標籤了
            if (currentItem.includes("wet")) {
                if (time[currentItem + "_time"] < 45) {
                    if (!checkrange(wet_top, 0, value[currentItem])) {
                        $(currentId).attr("style","color:#ef4444; font-weight:bold;"); // 紅色
                        count++;
                    } else {
                        $(currentId).attr("style","color:#10b981; font-weight:bold;"); // 綠色
                    }
                } else {
                    $(currentId).attr("style","color:#334155"); // 預設深灰
                }
            }

            if(currentItem.includes("temp")){
                if(time[currentItem + "_time"] < 45){
                    if(!checkrange(temp_top, temp_bot, value[currentItem])) {
                        $(currentId).attr("style","color:#ef4444; font-weight:bold;");
                        count++;
                    } else {
                        $(currentId).attr("style","color:#10b981; font-weight:bold;");
                    }
                }else{
                    $(currentId).attr("style","color:#334155");
                }
            }

            if(currentItem.includes("breath")){
                if(time[currentItem + "_time"] < 45){
                    if(!checkrange(breath_top, breath_bot,value[currentItem])) {
                        $(currentId).attr("style","color:#ef4444; font-weight:bold;");
                        count++;
                    } else {
                        $(currentId).attr("style","color:#10b981; font-weight:bold;");
                    }
                }else{
                    $(currentId).attr("style","color:#334155");
                }
            }
            
            // ... (為求簡潔，此處省略你原本 spitmilk, leavebed 等其他判斷，實務上請保留)
        }
        
        // (此處保留你原本判斷 count > 0 來做卡片排序移位的邏輯，完全不動它)
    }

    function reloadData(IDP, patientNum, NumOfJData){
        var xhr = new XMLHttpRequest();
        xhr.open('GET', './ajax/nursing_data.php?IDP=' + IDP + '&patientNum=' + patientNum, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status == 200) {
                var arr = JSON.parse(xhr.responseText);
                
                // 更新 HTML 數值 (只填數字)
                document.querySelector('.wet' + patientNum).innerHTML = (arr.wet === '濕度未配對') ? '--' : arr.wet;
                document.querySelector('.temp' + patientNum).innerHTML = (arr.temp === '溫度未配對') ? '--' : arr.temp;
                document.querySelector('.breath' + patientNum).innerHTML = (arr.breath === '呼吸未配對') ? '--' : arr.breath;

                var item = Object.keys(arr).slice(0, 10);
                var id = [];
                for (var i = 0; i < item.length; i++) {
                    id.push("."+item[i] + patientNum);
                }

                var time = {
                    wet_time: arr.wet_time,
                    temp_time: arr.temp_time,
                    breath_time: arr.breath_time
                    // ... 補齊其他 time
                };

                checkwarning(id, item, arr, time, patientNum, NumOfJData);
            }
        };
        xhr.send();
    }
    
    $(document).ready(function(){
        var NumOfJData = []; 
        
        // 🔥 修正：舊版是找 .table 的 title，新版改為直接計算卡片數量！
        var num = $(".bedside-card").length; 
        
        var innerArray = [];
        NumOfJData.push([]);
        
        for(var i=0; i<num; i++){
            var IDP = $(".patient"+i).attr('title');
            innerArray.push(i);
        }
        NumOfJData.push(innerArray); 

        setInterval(function(){
            for(var i=0; i<num; i++){
                var IDP = $(".patient"+i).attr('title');
                if(IDP) reloadData(IDP, i, NumOfJData);
            }
        }, 1000);
    });
</script>

<style>
    /* 網格排版：確保一排 4 張卡片 */
    .card-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(260px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    @media (max-width: 1400px) {
        .card-grid { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
    }
    /* 卡片主體與其餘 CSS 保留與先前相同 */
    .bedside-card { width: 100%; max-width: 310px; height: 175px; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); border: 1px solid rgba(226, 232, 240, 0.8); position: relative; display: flex; padding: 12px 10px; overflow: hidden; transition: all 0.25s ease; }
    .status-badge-container { position: absolute; top: 0; right: 0; background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); color: #ffffff; padding: 4px 14px 6px 14px; font-size: 10px; font-weight: 500; border-bottom-left-radius: 12px; }
    .left-panel { width: 38%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .avatar-container { width: 65px; height: 65px; position: relative; margin-bottom: 6px; display: flex; justify-content: center; align-items: center; }
    .biowave-ring { position: absolute; width: calc(100% + 6px); height: calc(100% + 6px); border-radius: 50%; border: 2px solid var(--primary-blue, #0284c7); }
    .avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2px solid #ffffff; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08); z-index: 2; }
    .patient-name { font-size: 15px; font-weight: 700; color: #334155; line-height: 1.1; }
    .patient-bed { font-size: 10px; font-weight: 700; color: #64748b; margin-top: 2px; }
    .right-panel { width: 62%; display: flex; flex-direction: column; justify-content: center; padding-left: 10px; border-left: 1px solid #f1f5f9; gap: 8px; }
    .vital-item { display: flex; align-items: center; }
    .vital-icon-box { width: 26px; height: 26px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 10px; margin-right: 8px; flex-shrink: 0; }
    .vital-icon-box.temp { background-color: #e0f2fe; color: #0284c7; }
    .vital-icon-box.resp { background-color: #f0fdf4; color: #16a34a; }
    .vital-icon-box.diaper { background-color: #fef3c7; color: #d97706; }
    .vital-text-content { display: flex; flex-direction: column; }
    .vital-name { font-size: 10px; color: #64748b; font-weight: 600; line-height: 1; margin-bottom: 2px; }
    .vital-numerical { font-family: 'Quicksand', sans-serif; font-size: 16px; font-weight: 800; color: #334155; display: flex; align-items: baseline; line-height: 1.2; }
    .vital-unit { font-size: 10px; font-weight: 500; color: #64748b; margin-left: 3px; }
</style>

<!-- ========================================== -->
<!-- PHP 資料庫撈取與卡片輸出區塊 -->
<!-- ========================================== -->
<div class="card-grid">
    <?php
        $ward = isset($_GET['ward']) ? $_GET['ward'] : 'all_ward';

        if($ward != "all_ward" && $ward != "warning_ward" ){
            $sql = "SELECT DISTINCT warddata.ward, warddata.bed, warddata.IDP, memberlogin.name, memberlogin.blood, memberlogin.birthday
                    FROM warddata, memberlogin
                    WHERE warddata.ward = '$ward' AND warddata.IDP = memberlogin.IDP AND warddata.IDP IS NOT NULL
                    ORDER BY warddata.ward ASC, warddata.bed ASC";
        }else{
            $sql = "SELECT DISTINCT warddata.ward, warddata.bed, warddata.IDP, memberlogin.name, memberlogin.blood, memberlogin.birthday
                    FROM warddata, memberlogin
                    WHERE warddata.IDP = memberlogin.IDP AND warddata.IDP IS NOT NULL
                    ORDER BY warddata.ward ASC, warddata.bed ASC";
        }

        $result = execute_sql($link,"wetness_database2",$sql);
        $patientNum = 0; 

        while($data = mysqli_fetch_assoc($result)){
            $IDP = $data['IDP'];
            $avatar_src = '男生大頭.png'; 
            $ring_color = 'var(--primary-blue, #0284c7)';

            // 🔥 恢復：你在舊版有的 SQL 查詢，這裡把它放回來，讓卡片一載入就有數值！
            $sql_wet = "SELECT DISTINCT tag.tagmac, tag.lastPID, valueimmediate.wet, valueimmediate.time FROM tag, valueimmediate WHERE tag.owner = '$IDP' AND tag.tagmac = valueimmediate.mac_address AND LEFT(valueimmediate.id, 2) = tag.lastPID AND tag.lastPID IN ('02', '06') ORDER BY valueimmediate.time DESC";
            $sql_temp = "SELECT DISTINCT tag.tagmac, tag.lastPID, valueimmediate.bodytemp, valueimmediate.time FROM tag, valueimmediate WHERE tag.owner = '$IDP' AND tag.tagmac = valueimmediate.mac_address AND LEFT(valueimmediate.id, 2) = tag.lastPID AND tag.lastPID = '03' ORDER BY valueimmediate.time DESC";
            $sql_breath = "SELECT DISTINCT tag.tagmac, tag.lastPID, valueimmediate.breath, valueimmediate.time FROM tag, valueimmediate WHERE tag.owner = '$IDP' AND tag.tagmac = valueimmediate.mac_address AND LEFT(valueimmediate.id, 2) = tag.lastPID AND tag.lastPID IN ('03', '05', '06') ORDER BY tag.lastPID DESC, valueimmediate.time DESC LIMIT 1";
            
            $result_wet = execute_sql($link,"wetness_database2",$sql_wet);
            $result_temp = execute_sql($link,"wetness_database2",$sql_temp);
            $result_breath = execute_sql($link,"wetness_database2",$sql_breath);

            // 處理初始值，如果沒資料就印 "--"
            $init_wet = (mysqli_num_rows($result_wet) < 1) ? '--' : mysqli_result($result_wet, 0, 2);
            $init_temp = (mysqli_num_rows($result_temp) < 1) ? '--' : mysqli_result($result_temp, 0, 2);
            $init_breath = (mysqli_num_rows($result_breath) < 1) ? '--' : mysqli_result($result_breath, 0, 2);
    ?>
            
            <!-- 單一病患卡片結構開始 -->
            <div class="bedside-card patient<?= $patientNum ?>" title="<?= $IDP ?>">
                <div class="status-badge-container">臥床</div>
                
                <div class="left-panel">
                    <a href="../shared/frame_patient.php?IDP=<?= $IDP ?>" style="text-decoration:none; display:flex; flex-direction:column; align-items:center;">
                        <div class="avatar-container">
                            <div class="biowave-ring" style="border-color: <?= $ring_color ?>;"></div>
                            <img class="avatar-img" src="<?= $avatar_src ?>" alt="大頭貼">
                        </div>
                        <div class="patient-info" style="text-align:center;">
                            <div class="patient-name"><?= $data['name'] ?></div>
                            <div class="patient-bed"><?= $data['ward'] ?>-<?= $data['bed'] ?></div>
                        </div>
                    </a>
                </div>

                <div class="right-panel">
                    <!-- 溫度 -->
                    <div class="vital-item">
                        <div class="vital-icon-box temp" id="iconTemp<?= $patientNum ?>"><i class="fa-solid fa-temperature-three-quarters"></i></div>
                        <div class="vital-text-content">
                            <span class="vital-name">溫度</span>
                            <div class="vital-numerical">
                                <span class="temp<?= $patientNum ?>"><?= $init_temp ?></span>
                                <span class="vital-unit">°C</span>
                            </div>
                        </div>
                    </div>

                    <!-- 呼吸 -->
                    <div class="vital-item">
                        <div class="vital-icon-box resp" id="iconResp<?= $patientNum ?>"><i class="fa-solid fa-lungs"></i></div>
                        <div class="vital-text-content">
                            <span class="vital-name">呼吸</span>
                            <div class="vital-numerical">
                                <span class="breath<?= $patientNum ?>"><?= $init_breath ?></span>
                                <span class="vital-unit">bpm</span>
                            </div>
                        </div>
                    </div>

                    <!-- 尿布濕度 -->
                    <div class="vital-item">
                        <div class="vital-icon-box diaper" id="iconDiaper<?= $patientNum ?>"><i class="fa-solid fa-droplet"></i></div>
                        <div class="vital-text-content">
                            <span class="vital-name">尿布濕度</span>
                            <div class="vital-numerical">
                                <span class="wet<?= $patientNum ?>"><?= $init_wet ?></span>
                                <span class="vital-unit">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 單一病患卡片結構結束 -->

    <?php
            $patientNum++;
        }
    ?>
</div>