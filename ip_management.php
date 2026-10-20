<?php
session_start();
// 設置網頁編碼
header("Content-Type:text/html; charset=utf-8");

// 引入資料庫連線工具
require_once($_SERVER['DOCUMENT_ROOT'] . "/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
$link = create_connection();

// 判斷是否為管理員，用來控制側邊欄顯示
$is_admin = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'superadmin']));

// 處理表單送出（更新 IP）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_ip') {
    // 增加權限判斷：一般 user 不執行更新
    if (!$is_admin) {
        echo "<script>alert('權限不足，無法修改 IP！');</script>";
    } else {
        $local_ip = trim($_POST['local_ip']);
        $trans_ip = trim($_POST['trans_ip']);

        if (empty($local_ip) || empty($trans_ip)) {
            echo "<script>alert('IP 位址不可為空！');</script>";
        } else {
            // 檢查是否已有紀錄 (這裡假設只維護 id = 1 的全域設定紀錄)
            $check_sql = "SELECT * FROM `server_ip` WHERE `id` = 1";
            $check_result = execute_sql($link, "wetness_database2", $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                // 更新現有紀錄
                $sql = "UPDATE `server_ip` SET `local_ip` = '$local_ip', `trans_ip` = '$trans_ip' WHERE `id` = 1";
            } else {
                // 新增第一筆紀錄
                $sql = "INSERT INTO `server_ip` (`id`, `local_ip`, `trans_ip`) VALUES (1, '$local_ip', '$trans_ip')";
            }

            if (execute_sql($link, "wetness_database2", $sql)) {
                echo "<script>alert('IP 設定更新成功！'); location.href='ip_management.php';</script>";
            } else {
                echo "<script>alert('系統錯誤，更新失敗！');</script>";
            }
        }
    }
}

// 取得資料庫中的當前 IP 設定供畫面顯示
$get_ip_sql = "SELECT * FROM `server_ip` WHERE `id` = 1";
$get_ip_result = execute_sql($link, "wetness_database2", $get_ip_sql);
$current_ips = mysqli_fetch_assoc($get_ip_result);

// 若資料庫沒有資料，給予預設值
$display_local_ip = $current_ips ? $current_ips['local_ip'] : "";
$display_trans_ip = $current_ips ? $current_ips['trans_ip'] : "";

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>智慧護理系統 - IP 管理</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="components/NavbarComponent.js" defer></script>
</head>
<body>

  <app-navbar active="settings"></app-navbar>

  <?php include ('menubar.php'); ?>

  <div class="app-container">
    
    <!-- 左側邊欄 -->
    <aside class="sidebar">
      <div class="sidebar-section-title">護理人員管理</div>
      <ul class="sidebar-menu">
        <li>
          <a href="staff_list.php" class="sidebar-link">
            <span>📋 人員列表</span>
          </a>
        </li>
        <!-- 僅加入這段 if 判斷，隱藏一般人員的註冊選單 -->
        <?php if ($is_admin): ?>
        <li>
          <a href="nurses_register.php" class="sidebar-link">
            <span>➕ 註冊人員</span>
          </a>
        </li>
        <?php endif; ?>
        <li>
          <a href="ip_management.php" class="sidebar-link active">
            <span>🌐 IP 管理</span>
          </a>
        </li>
      </ul>
    </aside>

    <!-- 右側內容區 -->
    <main class="main-content">
      
      <section id="section-ip" class="content-section" style="display: block;">
        <div class="page-header">
          <h1 class="page-title">電腦 IP 與 QR Code 管理</h1>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
          
          <!-- IP 設定表單區塊 -->
          <div class="upload-card">
            <form method="POST" action="" class="upload-card">
              <h3 style="font-size: 1.05rem; color: #0f172a;">IP 位址設定</h3>
              <p style="font-size: 0.82rem; color: var(--text-muted);">請輸入目前電腦 IP 與轉換 IP，系統將即時生成對應 QR Code。</p>
              
              <input type="hidden" name="action" value="update_ip">
              
              <div class="form-group">
                <label class="form-label">電腦 IP (實體網卡 / LAN IP)</label>
                <!-- 非管理員時加上 readonly，移除 required -->
                <input type="text" name="local_ip" id="inputLocalIp" class="form-input" value="<?php echo htmlspecialchars($display_local_ip); ?>" <?php echo !$is_admin ? 'readonly' : 'required'; ?>>
              </div>

              <div class="form-group">
                <label class="form-label">轉換 IP (對外 / 轉譯 IP)</label>
                <!-- 非管理員時加上 readonly，移除 required -->
                <input type="text" name="trans_ip" id="inputTransIp" class="form-input" value="<?php echo htmlspecialchars($display_trans_ip); ?>" <?php echo !$is_admin ? 'readonly' : 'required'; ?>>
              </div>

              <!-- 僅管理員顯示更新按鈕 -->
              <?php if ($is_admin): ?>
              <button type="submit" class="btn btn-primary" style="justify-content: center;">
                <i class="fa-solid fa-sync"></i> 更新並產生 QR Code
              </button>
              <?php endif; ?>
            </form>
          </div>

          <!-- QR Code 顯示區塊 -->
          <div class="upload-card" style="align-items: center; justify-content: center;">
            <div style="display: flex; gap: 1.5rem; justify-content: center; width: 100%;">
              <div class="qrcode-card" style="flex: 1; padding: 1rem; border: 1px solid var(--border-color);">
                <img id="qrLocalImg" class="qrcode-img" style="width: 130px; height: 130px;" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=http://<?php echo urlencode($display_local_ip); ?>:8080" alt="電腦 IP QR Code">
                <span class="qrcode-label" style="font-size: 0.85rem; margin-top: 0.5rem;">🖥️ 電腦 IP</span>
                <span id="labelLocalIp" class="qrcode-ip" style="font-size: 0.8rem;"><?php echo htmlspecialchars($display_local_ip); ?></span>
              </div>

              <div class="qrcode-card" style="flex: 1; padding: 1rem; border: 1px solid var(--border-color);">
                <img id="qrTransImg" class="qrcode-img" style="width: 130px; height: 130px;" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=http://<?php echo urlencode($display_trans_ip); ?>:8080" alt="轉換 IP QR Code">
                <span class="qrcode-label" style="font-size: 0.85rem; margin-top: 0.5rem;">🌐 轉換 IP</span>
                <span id="labelTransIp" class="qrcode-ip" style="font-size: 0.8rem;"><?php echo htmlspecialchars($display_trans_ip); ?></span>
              </div>
            </div>
          </div>

        </div>
      </section>

    </main>
  </div>

  <script>
    const localInput = document.getElementById('inputLocalIp');
    const transInput = document.getElementById('inputTransIp');
    
    function updatePreview() {
      document.getElementById('labelLocalIp').innerText = localInput.value;
      document.getElementById('labelTransIp').innerText = transInput.value;
      document.getElementById('qrLocalImg').src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=http://${localInput.value}:8080`;
      document.getElementById('qrTransImg').src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=http://${transInput.value}:8080`;
    }

    localInput.addEventListener('input', updatePreview);
    transInput.addEventListener('input', updatePreview);
  </script>

</body>
</html>