<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

    <style>
        /* 整頁的排版 */
        .searcharea {
            margin: 10px auto;
            text-align: center;
        }
        
        /* 查詢的大標題 */
        .searcharea p {
            font-size: 24px;
            font-weight: bold;
        }
        
        /* 日期的字 */
        .searcharea input[type='date'] {
            background: none;
            border: none;
            border-bottom: solid 2px #474544;
            color: #474544;
            font-size: 16px;
            font-weight: 400;
            letter-spacing: 1px;
            margin: 0em 0 5px 0;
            padding: 0 0 8px 0;
            text-transform: uppercase;
            text-align: center;
            width: 25%;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        
        .searcharea input[type='date']:focus {
            outline: none;
        }
        
        /* 選擇日期的按鈕 */
        .searcharea input[type='submit'] {
            background: none;
            border: solid 2px #474544;
            border-radius: 5px;
            color: #474544;
            cursor: pointer;
            display: inline;
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 18px;
            font-weight: bold;
            outline: none;
            padding: 10px 15px;
            margin: 0 20px;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .searcharea input[type='submit']:hover {
            background: #474544;
            color: #F2F3EB;
        }

        /* 圖表的框 */
        .showarea {
            border: 2px solid #000000;
            border-radius: 10px;
            height: 250px;
            width: 90%;
            margin: 10px auto;
        }
        
        /* 圖表的標題 */
        .showarea span {
            display: block;
            font-size: 18px;
            font-weight: bolder;
            text-decoration: underline;
            text-align: center;
            padding: 5px 0 0;
        }
        
        .showarea object {
            width: 100%;
            padding: 0;
            margin: 0;
            text-align: center;
            border: none;
            height: 100%;
        }

        .chart-button {
            background: none;
            border: solid 2px #474544;
            border-radius: 5px;
            color: #474544;
            cursor: pointer;
            display: inline-block;
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 18px;
            font-weight: bold;
            outline: none;
            padding: 10px 15px;
            margin: 0 20px;
            text-transform: uppercase;
            transition: all 0.3s;
        }

        .chart-button:hover {
            background: #474544;
            color: #F2F3EB;
        }

        /* 選中的就變顏色 */
        .chart-button.selected {
            background: #474544;
            color: #F2F3EB;
        }

        /* 沒被選中 */
        .chart-button.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>

    <?php
        $today = date("Y-m-d");
        if (!isset($_GET['startdate'])) {
            $startdate = $today;
            $enddate = $today;
        } else {
            if (strtotime($_GET['startdate']) <= strtotime($_GET['enddate'])) {
                $startdate = $_GET['startdate'];
                $enddate = $_GET['enddate'];
            } else {
                $startdate = $today;
                $enddate = $today;
                echo "<script>alert('時間區間錯誤');</script>";
            }       
        }
    ?>
</head>
<body>
    <div class="searcharea">
        <p>個人圖表紀錄(今天以前)</p>

        <h3 style="margin-top: -10px; margin-bottom: 20px; color: #474544;">
            目前病床使用者 ID：<?php echo isset($IDP) ? htmlspecialchars($IDP) : (isset($_GET['IDP']) ? htmlspecialchars($_GET['IDP']) : '未知'); ?>
        </h3>

        <form method="get" action="frame_patient_history.php">
            <input type="date" name="startdate" id="startdate" max="<?php echo $today; ?>" value="<?php echo $startdate; ?>" required> ~  
            <input type="date" name="enddate" id="enddate" max="<?php echo $today; ?>" value="<?php echo $enddate; ?>" required>
            <input type="hidden" name="IDP" value="<?php echo $IDP; ?>"> 
            <input type="submit" value="選擇日期"> 
            
            <script>
                var startDateInput = document.getElementById('startdate');
                var endDateInput = document.getElementById('enddate');
                
                // 設置結束日期的最大值為開始日期+7天
                startDateInput.addEventListener('input', function () {
                    var startDate = new Date(startDateInput.value);
                    var endDateMax = new Date(startDate);
                    endDateMax.setDate(startDate.getDate() + 7);
                    endDateInput.max = endDateMax.toISOString().split('T')[0];

                    // 檢查結束日期是否在合理範圍內
                    var currentEndDate = new Date(endDateInput.value);
                    if (currentEndDate < startDate) {
                        endDateInput.value = startDateInput.value;
                    }
                });
                
                // 設置開始日期的最小值為結束日期-7天
                endDateInput.addEventListener('input', function () {
                    var endDate = new Date(endDateInput.value);
                    var startDateMin = new Date(endDate);
                    startDateMin.setDate(endDate.getDate() - 7);
                    startDateInput.min = startDateMin.toISOString().split('T')[0];
                });
            </script>
        </form>

        <button type="button" class="chart-button" style="font-weight: bold;" id="temperatureButton" onclick="showChart('temperatureArea')" data-chart="temperatureArea">溫度</button>
        <button type="button" class="chart-button" style="font-weight: bold;" id="humidityButton" onclick="showChart('humidityArea')" data-chart="humidityArea">濕度</button>
        <button type="button" class="chart-button" style="font-weight: bold;" id="breathButton" onclick="showChart('breathArea')" data-chart="breathArea">呼吸</button>
        <!-- <button type="button" class="chart-button" style="font-weight: bold;" id="test1Button" onclick="showChart('test1Area')" data-chart="test1Area">測試一</button>
        <button type="button" class="chart-button" style="font-weight: bold;" id="test2Button" onclick="showChart('test2Area')" data-chart="test2Area">測試二</button>
        <button type="button" class="chart-button" style="font-weight: bold;" id="test3Button" onclick="showChart('test3Area')" data-chart="test3Area">測試三</button> -->
    </div>

    <div class="showarea" id="temperatureArea" style="display: none; height: 450px; width: 100%;">
        <iframe src="chart_personal_temperature.php?startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&IDP=<?php echo $IDP;?>" width="100%" height="100%" style="border: none;"></iframe>
    </div>

    <div class="showarea" id="humidityArea" style="display: none; height: 450px; width: 100%;">
        <iframe src="chart_personal_wet.php?startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&IDP=<?php echo $IDP;?>" width="100%" height="100%" style="border: none;"></iframe>
    </div>

    <div class="showarea" id="breathArea" style="display: none; height: 450px; width: 100%;">
        <iframe src="chart_personal_breath.php?startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&IDP=<?php echo $IDP;?>" width="100%" height="100%" style="border: none;"></iframe>
    </div>

    <!-- <div class="showarea" id="test1Area" style="display: none;">
        <span>測試一</span>
        <object data="flot/test1.php?startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&IDP=<?php echo $IDP;?>" width="100%" height="100%"></object>
    </div>

    <div class="showarea" id="test2Area" style="display: none;">
        <span>測試二</span>
        <object data="flot/test2.php?startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&IDP=<?php echo $IDP;?>" width="100%" height="100%"></object>
    </div>

    <div class="showarea" id="test3Area" style="display: none;">
        <span>測試三</span>
        <object data="flot/test3.php?startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&IDP=<?php echo $IDP;?>" width="100%" height="100%"></object>
    </div> -->

    <script>
        var isFirstSyncDone = false; // 全域變數：紀錄是否已經完成過首次同步

        // 提供給 iframe 呼叫的廣播函數
        function syncDates(sDate, eDate, sourceWindow) {
            if (isFirstSyncDone) return; 
            isFirstSyncDone = true; 

            var iframes = document.querySelectorAll('.showarea iframe');
            iframes.forEach(function(iframe) {
                if (iframe.contentWindow !== sourceWindow) {
                    if (typeof iframe.contentWindow.applySyncDates === 'function') {
                        iframe.contentWindow.applySyncDates(sDate, eDate);
                    }
                }
            });
        }

        var maxCharts = 3; // 最多可顯示的圖表
        var selectedCharts = 0; // 目前已選的

        function showChart(chartId) {
            var chartArea = document.getElementById(chartId);

            if (chartArea.style.display === 'block') {
                chartArea.style.display = 'none';
                selectedCharts--;
            } else {
                if (selectedCharts < maxCharts) {
                    chartArea.style.display = 'block';
                    var chartWidth = chartArea.clientWidth;
                    var chartMedia = chartArea.querySelector('iframe') || chartArea.querySelector('object');
                    
                    if (chartMedia) {
                        chartMedia.width = chartWidth;
                        
                        if (chartMedia.tagName.toLowerCase() === 'iframe' && chartMedia.contentWindow && typeof chartMedia.contentWindow.GetData === 'function') {
                            chartMedia.contentWindow.GetData();
                        }
                    }
                    selectedCharts++;
                } else {
                    alert("最多只可顯示三格圖表！");
                    return;
                }
            }
            updateButtonStatus();
        }

        function updateButtonStatus() {
            var buttons = document.querySelectorAll('.chart-button');
            buttons.forEach(function(button) {
                var chartId = button.getAttribute('data-chart');
                var chartArea = document.getElementById(chartId);
                if (chartArea.style.display === 'block') {
                    button.classList.add('selected');
                    button.classList.remove('disabled');
                } else {
                    button.classList.remove('selected');
                    if (selectedCharts >= maxCharts) {
                        button.classList.add('disabled');
                    } else {
                        button.classList.remove('disabled');
                    }
                }
            });
        }
    </script>
</body>
</html>