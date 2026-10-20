<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <title>智慧護理系統 - 醫療裝置管理</title>
    <link rel="stylesheet" href="css/device_page.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- 確保你有引入 FontAwesome 以顯示圖示，如果 style.css 裡沒包的話可以加上這行 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- 載入自訂導覽列元件 -->
    <script src="components/NavbarComponent.js" defer></script>
</head>

<body>

    <?php
    // ==========================================
    // 1. 建立資料庫連線與輔助函式
    // ==========================================
    require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
    $link = create_connection();

    function mysqli_result($res, $row = 0, $col = 0)
    {
        $numrows = mysqli_num_rows($res);
        if ($numrows && $row <= ($numrows - 1) && $row >= 0) {
            mysqli_data_seek($res, $row);
            $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
            if (isset($resrow[$col])) {
                return $resrow[$col];
            }
        }
        return false;
    }

    // ==========================================
    // 2. 接收 GET 參數 (狀態、類型、MAC搜尋)
    // ==========================================
    $item = isset($_GET['item']) ? $_GET['item'] : "all"; // all, paired, unpaired
    $device_type = isset($_GET['device_type']) ? $_GET['device_type'] : "all";
    $search_mac = isset($_GET['mac_search']) ? trim($_GET['mac_search']) : "";

    // ==========================================
    // 3. 計算側邊欄的數量 Badge
    // ==========================================
    $sql_count_all = "SELECT count(*) FROM tag";
    $sql_count_paired = "SELECT count(*) FROM tag WHERE owner != ''";
    $sql_count_unpaired = "SELECT count(*) FROM tag WHERE owner = ''";

    // 🔴 將這裡的 wetness_database 改為 wetness_database2
    $count_all = mysqli_result(execute_sql($link, "wetness_database2", $sql_count_all), 0, 0);
    $count_paired = mysqli_result(execute_sql($link, "wetness_database2", $sql_count_paired), 0, 0);
    $count_unpaired = mysqli_result(execute_sql($link, "wetness_database2", $sql_count_unpaired), 0, 0);

    // ==========================================
    // 4. 動態組合主要資料的 SQL 查詢
    // ==========================================
    $base_sql = "SELECT tag.tagmac, warddata.ward, warddata.bed, memberlogin.Name, tag.lastPID 
                 FROM tag 
                 LEFT JOIN warddata ON tag.owner = warddata.IDP 
                 LEFT JOIN memberlogin ON tag.owner = memberlogin.IDP";

    $conditions = [];

    // 狀態過濾
    if ($item == "unpaired") {
        $conditions[] = "tag.owner = ''";
    } else if ($item == "paired") {
        $conditions[] = "tag.owner != ''";
    }

    // 類型過濾 (對應 lastPID)
    if ($device_type != "all") {
        $conditions[] = "tag.lastPID = '" . mysqli_real_escape_string($link, $device_type) . "'";
    }

    // MAC 位址模糊搜尋
    if (!empty($search_mac)) {
        $conditions[] = "tag.tagmac LIKE '%" . mysqli_real_escape_string($link, $search_mac) . "%'";
    }

    $sql = $base_sql;
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // 🔴 將這裡的 wetness_database 改為 wetness_database2
    $result = execute_sql($link, "wetness_database2", $sql);
    $total_records = mysqli_num_rows($result);
    ?>

    <!-- 直接使用自訂標籤，並可透過 active 屬性高亮該頁面 -->
    <app-navbar active="device"></app-navbar>

    <!-- 主架構區域 -->
    <div class="app-container">

        <!-- 左側邊欄：裝置狀態選單 -->
        <aside class="sidebar">
            <div class="sidebar-section-title">裝置狀態</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="?item=all&device_type=<?php echo urlencode($device_type); ?>&mac_search=<?php echo urlencode($search_mac); ?>"
                        class="sidebar-link <?php if ($item == 'all')
                            echo 'active'; ?>">
                        <span>📡 所有裝置</span>
                        <span class="tab-badge" id="count-all"><?php echo $count_all; ?></span>
                    </a>
                </li>
                <li>
                    <a href="?item=paired&device_type=<?php echo urlencode($device_type); ?>&mac_search=<?php echo urlencode($search_mac); ?>"
                        class="sidebar-link <?php if ($item == 'paired')
                            echo 'active'; ?>">
                        <span>🔗 已配對裝置</span>
                        <span class="tab-badge" id="count-paired"><?php echo $count_paired; ?></span>
                    </a>
                </li>
                <li>
                    <a href="?item=unpaired&device_type=<?php echo urlencode($device_type); ?>&mac_search=<?php echo urlencode($search_mac); ?>"
                        class="sidebar-link <?php if ($item == 'unpaired')
                            echo 'active'; ?>">
                        <span>🔓 未配對裝置</span>
                        <span class="tab-badge" id="count-unpaired"><?php echo $count_unpaired; ?></span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- 右側內容區 -->
        <main class="main-content">

            <!-- 頁面標題 -->
            <div class="page-header">
                <h1 class="page-title">醫療裝置管理</h1>
            </div>

            <!-- 篩選與搜尋工具列 (改用 HTML Form 提交至 PHP 處理) -->
            <form action="" method="GET">
                <!-- 保留目前的側邊欄狀態 -->
                <input type="hidden" name="item" value="<?php echo htmlspecialchars($item); ?>">

                <div class="filter-bar">
                    <div class="filter-group">
                        <!-- 裝置類型篩選 -->
                        <div class="filter-item">
                            <label for="deviceTypeSelect" class="form-label"
                                style="margin-bottom: 0; white-space: nowrap;">
                                <i class="fa-solid fa-filter"></i> 裝置類型：
                            </label>
                            <!-- 加上 onchange="this.form.submit()" 讓使用者選完直接重整搜尋 -->
                            <select id="deviceTypeSelect" name="device_type" class="form-select"
                                style="width: auto; min-width: 200px;" onchange="this.form.submit()">
                                <option value="all" <?php if ($device_type == 'all')
                                    echo 'selected'; ?>>全部裝置類型</option>
                                <option value="03" <?php if ($device_type == '03')
                                    echo 'selected'; ?>>Nursemaid - 感溫衣
                                </option>
                                <option value="05" <?php if ($device_type == '05')
                                    echo 'selected'; ?>>Nursemaid - 腰帶
                                </option>
                                <option value="06" <?php if ($device_type == '06')
                                    echo 'selected'; ?>>Nursemaid - 電子尿布
                                </option>
                                <option value="02" <?php if ($device_type == '02')
                                    echo 'selected'; ?>>電子尿布</option>
                            </select>
                        </div>

                        <!-- MAC Address 搜尋 -->
                        <div class="filter-item">
                            <label for="macSearchInput" class="form-label"
                                style="margin-bottom: 0; white-space: nowrap;">
                                <i class="fa-solid fa-magnifying-glass"></i> MAC 位址搜尋：
                            </label>
                            <!-- 移除 oninput 事件，改為按下 Enter 時提交表單 -->
                            <input type="text" id="macSearchInput" name="mac_search" class="search-input"
                                placeholder="例如: AA:BB 或 11:22..." style="width: 220px;"
                                value="<?php echo htmlspecialchars($search_mac); ?>">
                            <!-- 隱藏的送出按鈕 (支援 Enter 鍵送出) -->
                            <button type="submit" style="display: none;"></button>
                        </div>
                    </div>

                    <div style="color: var(--text-muted, #64748b); font-size: 0.9rem;">
                        顯示數量：<strong id="displayCount" style="color: #0f172a;"><?php echo $total_records; ?></strong> 筆
                    </div>
                </div>
            </form>

            <!-- 裝置列表表格 -->
            <div class="preview-card" style="padding: 0; overflow: hidden;">
                <div class="table-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>裝置類型</th>
                                <th>MAC 位址</th>
                                <th>配對床位</th>
                                <th>配對病患姓名</th>
                                <th style="text-align: center;">操作選項</th>
                            </tr>
                        </thead>
                        <tbody id="deviceTableBody">
                            <?php
                            if ($total_records > 0) {
                                for ($j = 0; $j < $total_records; $j++) {
                                    $mac = mysqli_result($result, $j, 0);
                                    $ward = mysqli_result($result, $j, 1);
                                    $bed = mysqli_result($result, $j, 2);
                                    $name = mysqli_result($result, $j, 3);
                                    $lastPID = mysqli_result($result, $j, 4);

                                    // 判斷裝置類型
                                    $device_name = "未知裝置";
                                    if ($lastPID == "03") {
                                        $device_name = "Nursemaid - 感溫衣";
                                    } else if ($lastPID == "05") {
                                        $device_name = "Nursemaid - 腰帶";
                                    } else if ($lastPID == "06") {
                                        $device_name = "Nursemaid - 電子尿布";
                                    } else if ($lastPID == "02") {
                                        $device_name = "電子尿布";
                                    }

                                    $is_paired = ($bed != "");

                                    // 床位格式化
                                    $display_bed = "-";
                                    if ($is_paired) {
                                        $display_bed = ($ward != "") ? $ward . "-" . $bed : $bed;
                                    }

                                    // 輸出 HTML
                                    echo "<tr>";
                                    echo "  <td><div style='font-weight: 500; color: #0f172a;'>" . $device_name . "</div></td>";
                                    echo "  <td><span class='device-mac'>" . $mac . "</span></td>";

                                    if ($is_paired) {
                                        echo "  <td><strong style='color:#0f172a;'>" . $display_bed . "</strong></td>";
                                        echo "  <td><span style='font-weight: 500;'>" . ($name != "" ? $name : "-") . "</span></td>";
                                        // 改用 <a> 標籤，但套用新版的 button CSS 樣式，連接至原有的處理程式
                                        echo "  <td style='text-align: center;'>
                                      <a href='process_mac_unpair.php?mac=" . urlencode($mac) . "' class='btn btn-outline-danger btn-sm' onclick='return confirm(\"確定要解除配對嗎？\")'>
                                        <i class='fa-solid fa-link-slash'></i> 解除配對
                                      </a>
                                    </td>";
                                    } else {
                                        echo "  <td><span class='text-muted-placeholder'>-</span></td>";
                                        echo "  <td><span class='text-muted-placeholder'>-</span></td>";
                                        // 連接至原有的配對畫面
                                        echo "  <td style='text-align: center;'>
                                      <a href='frame_mac_pair.php?mac=" . urlencode($mac) . "' class='btn btn-primary btn-sm'>
                                        <i class='fa-solid fa-link'></i> 配對
                                      </a>
                                    </td>";
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr>
                            <td colspan='5' style='text-align: center; padding: 2rem; color: #94a3b8;'>
                              <i class='fa-solid fa-inbox' style='font-size: 2rem; margin-bottom: 0.5rem; display: block;'></i>
                              沒有符合條件的裝置資料
                            </td>
                          </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>

</html>