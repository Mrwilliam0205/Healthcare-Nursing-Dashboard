<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>病患呼吸圖表</title>
    <style>
        #tooltip { position: absolute; border: 1px solid #333; padding: 5px; background: #fff; display: none; z-index: 1000; }
        .search-container { text-align: center; margin-top: 30px; font-family: sans-serif; }
        .search-container input, .search-container button { padding: 5px; margin: 0 5px; font-size: 14px; }
        .search-container button { cursor: pointer; }
    </style>
</head>
<body>

    <div class="search-container">
        <label for="start_date">開始日期：</label>
        <input type="date" id="start_date">
        
        <label for="end_date">結束日期：</label>
        <input type="date" id="end_date">
        
        <button id="btn_search">確認查詢</button>

        <br><br>
        <span id="debug_msg" style="font-weight: bold; color: #cc0000; font-size: 16px; background-color: #fff3cd; padding: 5px 10px; border-radius: 5px; display: inline-block; min-height: 20px;"></span>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.time.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.symbol.min.js"></script>

    <div id="flot-placeholder1" style="width:800px; height:300px; margin:20px auto;"></div>

    <script>
        var options = {
            series: {
                lines: { show: true },
                points: { radius: 3, fill: true, show: true }
            },
            xaxis: {
                mode: "time",
                minTickSize: [1, "hour"], 
                timezone: "browser",
                axisLabel: "Date and Time",
                tickFormatter: function (v, axis) {
                    var d = new Date(v);
                    var month = ("0" + (d.getMonth() + 1)).slice(-2);
                    var day = ("0" + d.getDate()).slice(-2);
                    var hour = ("0" + d.getHours()).slice(-2);
                    return month + "-" + day + " " + hour + "時";
                }
            },
            yaxes: [{
                    axisLabel: "Respiration Rate(次/分)",
                    min: 0,
                    max: 50,
                    tickFormatter: function (v, axis) { return v + " 次/分"; }
                }
            ],
            legend: { position: "nw" },
            grid: {
                hoverable: true,
                borderWidth: 2,
                borderColor: "#633200",
                backgroundColor: { colors: ["#ffffff", "#EDF5FF"] }
            },
            colors: ["#D9B300", "#984B4B", "#484891"]
        };

        var hasAlertedNoData = false;

        function GetData() {
            var targetIDP = "<?php echo isset($_GET['IDP']) ? htmlspecialchars($_GET['IDP']) : ''; ?>";

            if (!targetIDP) {
                alert("未指定病患 ID！");
                return; 
            }

            var startDateVal = $("#start_date").val();
            var endDateVal = $("#end_date").val();

            $.ajax({
                url: "flot/ajax/get_personal_breath.php", 
                type: "GET",
                data: {
                    IDP: targetIDP,
                    startdate: startDateVal,
                    enddate: endDateVal
                },
                dataType: "json",
                success: function(msg) {

                    // ----- 【Debug 解析與顯示】 -----
                    if (msg && msg.length > 0) {
                        var lastItem = msg[msg.length - 1];
                        
                        if (lastItem && lastItem.debug_target_table !== undefined) {
                            var dbName = lastItem.debug_target_table;
                            var warnMsg = lastItem.warning_msg; 
                            
                            var displayHtml = "🔍 目前讀取的資料表：<span style='color: blue;'>" + dbName + "</span>";
                            if (dbName.indexOf('union_table') !== -1 || dbName.indexOf(' + ') !== -1) {
                                displayHtml = "🔍 當前查詢模式：<span style='color: blue;'>跨越界線合併查詢 (" + dbName + ")</span>";
                            }
                            
                            if (warnMsg && warnMsg !== "") {
                                displayHtml += "<br><span style='color: #d9534f; font-size: 14px; margin-top: 5px; display: inline-block;'>" + warnMsg + "</span>";
                            }
                            
                            $("#debug_msg").html(displayHtml);
                            msg.pop(); 
                        } else {
                            $("#debug_msg").text("無資料表資訊");
                        }
                    } else {
                        $("#debug_msg").text("查無此時間區間的資料");
                    }
                    // ---------------------------------

                    var isVisible = true;
                    if (window.frameElement) {
                        isVisible = (window.frameElement.offsetParent !== null);
                    }

                    if(!msg || msg.length === 0) {
                        $.plot($("#flot-placeholder1"), [], options);
                        if (!hasAlertedNoData && isVisible) {
                            alert("這段期間內沒有該病患的資料！"); // 呼吸
                            hasAlertedNoData = true;
                        }
                        return;
                    }

                    hasAlertedNoData = false;

                    var data1 = [];
                    var dataMax = []; 
                    var dataMin = []; 

                    // 綁定呼吸 (breath) 的專屬資料欄位
                    for(var i = 0; i < msg.length; i++){
                        var time = new Date(msg[i]["hour_time"]).getTime();
                        data1.push([time, parseFloat(msg[i]["avg_breath"])]);
                        dataMax.push([time, parseFloat(msg[i]["max_breath"])]); 
                        dataMin.push([time, parseFloat(msg[i]["min_breath"])]); 
                    }

                    var dataset = [
                        { label: "平均值", data: data1, points: { symbol: "circle"} },
                        { label: "最大值", data: dataMax, points: { symbol: "circle"} },
                        { label: "最小值", data: dataMin, points: { symbol: "circle"} }
                    ];

                    $.plot($("#flot-placeholder1"), dataset, options);
                    $("#flot-placeholder1").UseTooltip();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX 發生錯誤:", error);
                    $("#debug_msg").html("❌ 資料讀取失敗");
                    alert("資料讀取失敗，請檢查 API 路徑或資料庫連線。");
                }
            });
        }

        // Tooltip 功能
        var previousPoint = null, previousLabel = null;
        var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        $.fn.UseTooltip = function () {
            $(this).bind("plothover", function (event, pos, item) {
                if (item) {
                    if ((previousLabel != item.series.label) || (previousPoint != item.dataIndex)) {
                        previousPoint = item.dataIndex;
                        previousLabel = item.series.label;
                        $("#tooltip").remove();

                        var x = item.datapoint[0];
                        var y = item.datapoint[1];
                        var color = item.series.color;
                        var dateObj = new Date(x);

                        if (item.seriesIndex == 0) {
                            showTooltip(item.pageX, item.pageY, color,
                                "<strong>" + item.series.label + "</strong><br>" +
                                (dateObj.getMonth()+1) + "/" + dateObj.getDate() + "<br>" +
                                dateObj.getHours() + ":00 : <strong>" + y + "</strong>(次/分)");
                        } else {
                            showTooltip(item.pageX, item.pageY, color,
                                "<strong>" + item.series.label + "</strong><br>" + 
                                monthNames[dateObj.getMonth()] + " : <strong>" + y + "</strong>(次/分)");
                        }
                    }
                } else {
                    $("#tooltip").remove();
                    previousPoint = null;
                }
            });
        };

        function showTooltip(x, y, color, contents) {
            $('<div id="tooltip">' + contents + '</div>').css({
                position: 'absolute', display: 'none', top: y - 30, left: x + 10,
                border: '2px solid ' + color, padding: '5px', 'font-size': '12px',
                'border-radius': '5px', 'background-color': '#fff', opacity: 0.9
            }).appendTo("body").fadeIn(200);
        }

        $(document).ready(function () {
            GetData();

            $("#btn_search").click(function() {
                var sDate = $("#start_date").val();
                var eDate = $("#end_date").val();

                if(sDate && eDate && sDate > eDate) {
                    alert("⚠️ 錯誤：開始日期不能晚於結束日期！");
                    return; 
                }

                var confirmMsg = "確定要查詢 ";
                confirmMsg += (sDate) ? sDate : "最早期";
                confirmMsg += " 到 ";
                confirmMsg += (eDate) ? eDate : "最新";
                confirmMsg += " 的資料嗎？";

                if (confirm(confirmMsg)) {
                    hasAlertedNoData = false;
                    
                    if (window.parent && typeof window.parent.syncDates === 'function' && !window.parent.isFirstSyncDone) {
                        window.parent.syncDates(sDate, eDate, window);
                    }
                    
                    $("#debug_msg").html("⏳ 資料庫深度撈取與整合中，請稍候...");
                    GetData();
                }
            });
        });

        window.applySyncDates = function(sDate, eDate) {
            $("#start_date").val(sDate);
            $("#end_date").val(eDate);
            hasAlertedNoData = false;
            $("#debug_msg").html("⏳ 同步資料整合中，請稍候...");
            GetData();
        };
    </script>
</body>
</html>