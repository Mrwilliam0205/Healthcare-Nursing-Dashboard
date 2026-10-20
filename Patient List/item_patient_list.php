<!-- ========================================== -->
<!-- 新版 HTML 結構：左側邊欄 (病患列表) -->
<!-- ========================================== -->

<!-- 圖示區塊 (位於根目錄，直接讀取 picture 資料夾) 
<div style="text-align: center; padding: 20px 0 10px 0;">
    <img src="picture/item_user.png" alt="user" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--border-color); background-color: #fff; object-fit: contain;">
</div>
-->
<!-- 側邊欄標題 -->
<div class="sidebar-section-title" style="text-align: start; margin-bottom: 20px; font-size: 20px;">病患狀態分類</div>

<!-- 選單區塊 -->
<div style="padding: 0 15px;">
    <ul class="sidebar-menu">
        <li>
            <a href="frame_patient_list.php" class="sidebar-link">住院中病患列表</a>
        </li>
        <li>
            <a href="discharged_member_list.php" class="sidebar-link">出院病患歷史列表</a>
        </li>
    </ul>
</div>

<!-- 保留並優化原本的腳本，用來自動判斷當前頁面並加上 active 樣式 -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function(){
        // 獲取當前頁面的 URL
        var currentUrl = window.location.href;

        // 遍歷所有 sidebar-link，查找與當前 URL 相匹配的 href 屬性
        $('.sidebar-menu .sidebar-link').each(function(){
            var linkUrl = $(this).attr('href');
            
            // 如果當前 URL 包含該連結的路徑，則將該 <a> 標籤加上 active 樣式
            if (currentUrl.indexOf(linkUrl) !== -1) {
                $(this).addClass('active'); // 注意：新版設計的 active 是加在 <a> 上面，不是 <li>
            }
        });
    });
</script>