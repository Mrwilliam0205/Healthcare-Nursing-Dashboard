<?php
// 導入資料庫連線 (保留你原本的寫法)
require_once("../login_database/dbtools.inc.php");	
?>

<!-- 保留並優化你的 Ajax 撈取邏輯 -->
<script>
    $(document).ready(function(){
        // 網頁載入時先立刻執行一次，避免剛進畫面空白一秒
        gettime();
        getdata();

        var renewT = setInterval(function(){ gettime() }, 1000);
        var renewD = setInterval(function(){ getdata() }, 1000);
        
        function getdata(){
            $.ajax({
                url: "./ajax/nursing_warndata.php",  // 請求的頁面
                type: 'GET',
                success: function(response) {
                    // console.log(response);
                    // 將回傳的資料塞進新版的 Flex/Grid 容器中
                    $("#warning-data-container").html(response);
                },
                error: function() {
                    console.log('ajax error!');
                }
            });
        }
        
        function gettime(){
            var now = new Date();
            var date = now.getFullYear() +"-"+ ("0"+(now.getMonth()+1)).slice(-2) +"-"+ ("0"+now.getDate()).slice(-2) +" "+
                       ("0"+now.getHours()).slice(-2) +":"+ ("0"+now.getMinutes()).slice(-2) +":"+ ("0"+now.getSeconds()).slice(-2);
            // 改為綁定新版 CSS 準備好的時間顯示區塊
            $(".time-display").text("現在時間是 " + date);
        }
    });
</script>

<!-- ========================================== -->
<!-- 新版 HTML 結構：頁面標題與警示資料容器 -->
<!-- ========================================== -->

<!-- 頂部標題與時間狀態列 -->
<div class="page-header">
    <h2 class="page-title" style="color: var(--danger-color);">🚨 警示病床即時動態</h2>
    <div style="display:flex; align-items:center; gap: 1rem;">
        <!-- 給 JS 替換時間的錨點 -->
        <span class="time-display" style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;"></span>
        
        <!-- 取代舊版的三色點點，改用狀態徽章表示 -->
        <div class="badge-status badge-active" style="background-color: var(--danger-color); color: white;">
            警示監控中
        </div>
    </div>
</div>

<!-- 病床卡片網格容器 (這裡準備好外框，等待 Ajax 把資料塞進來) -->
<div class="ward-cards-container">
    <div class="ward-group-card" style="border-color: rgba(239, 68, 68, 0.3); background-color: rgba(239, 68, 68, 0.02);">
        <div class="ward-group-header">
            <div class="ward-title-box">
                <span class="ward-name">異常狀態病患</span>
                <span class="ward-type-tag" style="background-color: #fee2e2; color: #ef4444;">需優先處理</span>
            </div>
        </div>
        
        <!-- Ajax 的資料會全部被注入到這個 beds-grid 裡面 -->
        <div id="warning-data-container" class="beds-grid">
            <!-- 等待 nursing_warndata.php 回傳內容... -->
        </div>
    </div>
</div>