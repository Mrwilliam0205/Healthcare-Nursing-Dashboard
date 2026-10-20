<?php
// 取得目前的網址參數，用來判斷哪個按鈕要亮起 (active)
// 如果沒有參數，預設為 all_ward
$current_ward = isset($_GET['ward']) ?$_GET['ward'] : 'all_ward';

// 引入資料庫與相容性函式 (保留你原本的邏輯)
require_once("../login_database/dbtools.inc.php");
$link = create_connection();
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
?>
<!-- 🔥 專屬樣式：確保寫在 PHP 內也能有 Hover 與閃爍動畫 -->
<style>
    .hub-status-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.65rem 0.85rem;
        margin-bottom: 1.25rem;
        border-radius: 8px;
        background-color: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #10b981;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }
    .hub-status-btn:hover {
        background-color: rgba(16, 185, 129, 0.22);
        transform: translateY(-1px);
    }
    .hub-status-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .hub-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 6px #10b981;
    }
    /* 🔴 異常狀態警示 (套用 hub-error 即可觸發) */
    .hub-status-btn.hub-error {
        background-color: rgba(239, 68, 68, 0.15);
        border-color: rgba(239, 68, 68, 0.4);
        color: #f87171;
        animation: hub-alert-pulse 1.5s infinite;
    }
    .hub-status-btn.hub-error .hub-dot {
        background-color: #ef4444;
        box-shadow: 0 0 8px #ef4444;
    }
    @keyframes hub-alert-pulse {
        0%, 100% { border-color: rgba(239, 68, 68, 0.4); }
        50% { border-color: rgba(239, 68, 68, 0.9); }
    }
</style>

<!-- 🔥 新增：HUB 狀態按鈕 (點擊跳轉至裝置頁面) -->
<!-- 提示：未來若需呈現異常，可透過 PHP 判斷後在 class 補上 'hub-error' -->
<a href="../frame_hub.php" class="hub-status-btn" title="點擊前往裝置管理頁面">
    <div class="hub-status-left">
        <span class="hub-dot"></span>
        <span>HUB 狀態正常</span>
    </div>
    <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; opacity: 0.7;"></i>
</a>

<!-- 區塊 1：即時狀態監控 -->
<div class="sidebar-section-title">即時狀態監控</div>
<ul class="sidebar-menu">
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF']."?ward=warning_ward"; ?>" 
           class="sidebar-link alert-item <?php echo ($current_ward == 'warning_ward') ? 'active' : ''; ?>">
            <span>🚨 警示病床</span>
        </a>
    </li>
    <li>
        <a href="<?php echo $_SERVER['PHP_SELF']."?ward=all_ward"; ?>" 
           class="sidebar-link <?php echo ($current_ward == 'all_ward') ? 'active' : ''; ?>">
            <span>🛏️ 全病床</span>
        </a>
    </li>
</ul>

<!-- 增加區塊間距 (使用新版 CSS 的分隔線) -->
<div class="sidebar-divider"></div>

<!-- 區塊 2：病房清單 -->
<div class="sidebar-section-title">病房清單</div>
<ul class="sidebar-menu">
    <?php
        // 從資料庫撈取病房清單
        $sql = "SELECT DISTINCT `ward` FROM `warddata`";
        $result = execute_sql($link, "wetness_database", $sql);

        for($j=0; $j<mysqli_num_rows($result); $j++) {$ward = mysqli_result($result,$j, 0);
            
            // 判斷該病房是否為目前點擊的病房，若是則加上 active 類別
            $active_class = ($current_ward ==$ward) ? 'active' : '';
    ?>
        <li>
            <a href="<?php echo $_SERVER['PHP_SELF']."?ward=".$ward; ?>" 
               class="sidebar-link <?php echo $active_class; ?>">
                <span><?php echo $ward; ?> 病房</span>
            </a>
        </li>
    <?php
        }
    ?>
</ul>