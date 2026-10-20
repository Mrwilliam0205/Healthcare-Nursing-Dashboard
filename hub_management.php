<?php
    // ==========================================
    // PHP 資料庫撈取區塊 (改由獨立的 hub 表單讀取)
    // ==========================================
    require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
    $link = create_connection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        // 檢查是不是帶有 'update_alert' 標籤的請求
        if (isset($input['action']) && $input['action'] === 'update_alert') {
            $id = mysqli_real_escape_string($link, $input['id']);
            $status = $input['status'] ? 1 : 0; 
            
            $sql = "UPDATE hub SET alert_enabled = '$status' WHERE id = '$id'";
            // 替換為 wetness_database2
            if (execute_sql($link, "wetness_database2", $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '資料庫更新失敗']);
            }
            exit(); // ⚠️ 執行完更新就立刻終止，不要往下輸出網頁 HTML
        }

        if (isset($input['action']) && $input['action'] === 'delete_hub') {
            $id = mysqli_real_escape_string($link, $input['id']);
            $name = mysqli_real_escape_string($link, $input['name']);
            // 處理空 IP (前端如果是空值會顯示為 '-')
            $ip = $input['ip'] === '-' ? '' : mysqli_real_escape_string($link, $input['ip']); 
            
            // 依照你的需求，利用 名稱 + IP 加上 ID 進行三重確認刪除，避免誤刪
            $sql = "DELETE FROM hub WHERE id = '$id' AND hub_name = '$name'";
            if ($ip !== '') {
                $sql .= " AND ip_address = '$ip'";
            } else {
                $sql .= " AND (ip_address IS NULL OR ip_address = '')";
            }
            
            // 替換為 wetness_database2
            if (execute_sql($link, "wetness_database2", $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '資料庫刪除失敗']);
            }
            exit(); // ⚠️ 執行完刪除就立刻終止
        }

        if (isset($input['action']) && $input['action'] === 'fetch_data') {
            $sql = "SELECT * FROM hub ORDER BY last_update_time DESC";
            // 替換為 wetness_database2
            $result = execute_sql($link, "wetness_database2", $sql);
            
            $latestData = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $latestData[] = [
                        'id' => $row['id'],
                        'name' => $row['hub_name'],
                        'ip' => $row['ip_address'] ? $row['ip_address'] : '-',
                        'lastTime' => $row['last_update_time'],
                        'alertEnabled' => $row['alert_enabled'] == 1
                    ];
                }
            }
            echo json_encode(['success' => true, 'data' => $latestData]);
            exit(); // 回傳完最新資料就立刻終止
        }
    }

    // 直接從新的 hub 表撈取所有設備，並依最後連線時間排序
    $sql = "SELECT * FROM hub ORDER BY last_update_time DESC";
    // 替換為 wetness_database2
    $result = execute_sql($link, "wetness_database2", $sql);
    
    $hubDataArray = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $hubDataArray[] = [
                'id' => $row['id'],                                // 使用獨立表專屬的自動遞增 ID
                'name' => $row['hub_name'],                        // HUB 名稱
                'ip' => $row['ip_address'] ? $row['ip_address'] : '-', // 顯示真實 IP，若為空則顯示 '-'
                'lastTime' => $row['last_update_time'],            // 最後更新時間
                'alertEnabled' => $row['alert_enabled'] == 1       // 警示開關狀態 (將 1 轉為 true，0 轉為 false)
            ];
        }
    }
    // 將 PHP 陣列轉為 JSON，餵給下方的 JavaScript 渲染畫面
    $jsonHubData = json_encode($hubDataArray);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>設備與物料管理 - 智慧護理系統</title>
  
  <!-- 引入 FontAwesome 圖示庫 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/device_page.css">
  <link rel="stylesheet" href="css/style.css">
  
</head>
<body style="margin: 0; display: flex; flex-direction: column; min-height: 100vh;">

  <!-- ⚠️ 這裡直接從主內容區塊開始，頂部選單由 frame_hub.php 處理 -->
  <div class="app-container" style="display: flex; flex: 1; width: 100%;">
    
    <!-- 左側邊欄：管理選單 -->
    <aside class="sidebar">
      <div class="sidebar-section-title">設備與物料管理</div>
      <ul class="sidebar-menu" style="margin-top: 0; margin-bottom: auto;">
        <li>
          <a href="#" class="sidebar-link active" onclick="switchSection('hub', this)">
            <span><i class="fa-solid fa-server"></i> HUB 管理</span>
          </a>
        </li>
        <li>
          <a href="frame_inventory.php" class="sidebar-link" onclick="switchSection('inventory', this)">
            <span><i class="fa-solid fa-boxes-stacked"></i> 庫存管理</span>
          </a>
        </li>
      </ul>
    </aside>

    <!-- 右側主要內容區 -->
    <main class="main-content" style="flex: 1;">
      
      <!-- ========================================== -->
      <!-- 子頁面 1：HUB 管理                         -->
      <!-- ========================================== -->
      <section id="section-hub" class="content-section">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
          <h1 class="page-title"><i class="fa-solid fa-server"></i> 系統裝置管理 (HUB)</h1>
        </div>

        <!-- HUB 工具列 (狀態 Tabs + 搜尋框) -->
        <div class="toolbar-bar">
          <div class="ward-tabs" style="margin-bottom: 0;">
            <button class="tab-btn active" onclick="switchHubFilter('all', this)">
              📡 全部 HUB <span class="tab-badge" id="count-all">0</span>
            </button>
            <button class="tab-btn" onclick="switchHubFilter('online', this)">
              🟢 正常連線 <span class="tab-badge" id="count-online">0</span>
            </button>
            <button class="tab-btn" onclick="switchHubFilter('alert', this)">
              🔴 斷線警示 (>10分) <span class="tab-badge" id="count-alert" style="background-color: #ef4444; color: #fff;">0</span>
            </button>
            <button class="tab-btn" onclick="switchHubFilter('disabled', this)">
              ⚪ 已關閉警示 <span class="tab-badge" id="count-disabled">0</span>
            </button>
          </div>

          <!-- HUB 關鍵字搜尋框 -->
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="hubSearchInput" style="font-size: 0.88rem; color: #475569; white-space: nowrap;">
              <i class="fa-solid fa-magnifying-glass"></i> 搜尋 HUB：
            </label>
            <input 
              type="text" 
              id="hubSearchInput" 
              class="search-input" 
              placeholder="搜尋名稱或 IP" 
              style="width: 220px;" 
              oninput="renderHubList()"
            >
          </div>
        </div>

        <!-- HUB 資料表格 -->
        <div class="preview-card" style="padding: 0; overflow: hidden;">
          <div class="table-container">
            <table class="preview-table">
              <thead>
                <tr>
                  <th>HUB 名稱</th>
                  <th>IP 位址</th>
                  <th>最後一筆資料時間</th>
                  <th>連線狀態</th>
                  <th style="text-align: center;">啟用警示</th>
                  <th style="text-align: center; width: 100px;">操作</th>
                </tr>
              </thead>
              <tbody id="hubTableBody">
                <!-- JS 動態繪製 -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- 子頁面 2：庫存管理 (預留區塊)                -->
      <!-- ========================================== -->
      <section id="section-inventory" class="content-section" style="display: none;">
        <div class="page-header">
          <h1 class="page-title"><i class="fa-solid fa-boxes-stacked"></i> 尿褲庫存管理系統</h1>
        </div>
        <div style="padding: 2rem; text-align: center; color: #64748b;">
          庫存管理功能開發中...
        </div>
      </section>

    </main>
  </div>

  <script>
    // ===================================
    // JavaScript 邏輯處理
    // ===================================
    let hubData = <?php echo $jsonHubData; ?>;
    let currentHubFilter = 'all';

    function isTimeout(lastTimeStr) {
      if (!lastTimeStr) return true;
      const last = new Date(lastTimeStr).getTime();
      const current = new Date().getTime();
      const diffMinutes = (current - last) / (1000 * 60);
      return diffMinutes > 10;
    }

    function switchHubFilter(status, element) {
      currentHubFilter = status;
      const tabs = element.parentElement.querySelectorAll('.tab-btn');
      tabs.forEach(tab => tab.classList.remove('active'));
      element.classList.add('active');
      renderHubList();
    }

    function renderHubList() {
      const tbody = document.getElementById('hubTableBody');
      const searchQuery = document.getElementById('hubSearchInput').value.trim().toLowerCase();
      tbody.innerHTML = '';

      let filteredList = hubData.filter(hub => {
        const timeout = isTimeout(hub.lastTime);
        
        if (currentHubFilter === 'online' && (timeout || !hub.alertEnabled)) return false;
        if (currentHubFilter === 'alert' && (!timeout || !hub.alertEnabled)) return false;
        if (currentHubFilter === 'disabled' && hub.alertEnabled) return false;

        if (searchQuery) {
          const matchName = hub.name.toLowerCase().includes(searchQuery);
          const matchIp = hub.ip.toLowerCase().includes(searchQuery);
          if (!matchName && !matchIp) return false;
        }

        return true;
      });

      if (filteredList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:#94a3b8;">沒有符合條件的 HUB 資料</td></tr>`;
      } else {
        filteredList.forEach(hub => {
          const timeout = isTimeout(hub.lastTime);
          const tr = document.createElement('tr');

          let statusBadge = '';
          if (!hub.alertEnabled) {
            statusBadge = `<span class="status-badge status-disabled"><i class="fa-solid fa-bell-slash"></i> 忽略警示</span>`;
          } else if (timeout) {
            statusBadge = `<span class="status-badge status-alert"><i class="fa-solid fa-triangle-exclamation"></i> 斷線警示</span>`;
            tr.classList.add('row-alert');
          } else {
            statusBadge = `<span class="status-badge status-online"><i class="fa-solid fa-circle-check"></i> 連線正常</span>`;
          }

          tr.innerHTML = `
            <td><strong style="color:#0f172a; font-size: 1.1rem;">${hub.name}</strong></td>
            <td><span class="ip-badge">${hub.ip}</span></td>
            <td>
              <span style="color: ${timeout && hub.alertEnabled ? '#dc2626' : '#334155'}; font-weight: ${timeout ? '700' : '400'};">
                ${hub.lastTime}
              </span>
            </td>
            <td>${statusBadge}</td>
            <td style="text-align: center;">
              <label class="switch">
                <input type="checkbox" ${hub.alertEnabled ? 'checked' : ''} onchange="toggleAlert('${hub.id}')">
                <span class="slider"></span>
              </label>
            </td>
            <td style="text-align: center;">
              <button class="btn-danger-outline" onclick="confirmDeleteHub('${hub.id}', '${hub.name}', '${hub.ip}')">
                <i class="fa-solid fa-trash-can"></i> 刪除
              </button>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }
      updateCounts();
    }

    function toggleAlert(id) {
      const target = hubData.find(h => h.id == id); 
      
      if (target) {
        // 1. 先立刻更新畫面，保持順暢體驗
        target.alertEnabled = !target.alertEnabled;
        renderHubList(); 
        
        // 2. 呼叫自己這支 PHP 檔案，並帶上暗號 (action: 'update_alert')
        fetch('hub_management.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'update_alert',
            id: target.id,
            status: target.alertEnabled
          })
        })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            console.error('更新失敗:', data.message);
            alert('系統異常：狀態更新失敗，請稍後再試！');
          }
        })
        .catch(error => {
          console.error('連線錯誤:', error);
        });
      }
    }

    function confirmDeleteHub(id, name, ip) {
      // 詢問確認，順便顯示 IP 讓使用者安心
      if (confirm(`確定要刪除「${name}」 (IP: ${ip}) 嗎？\n此動作無法復原！`)) {
        
        // 呼叫後端 PHP 執行資料庫刪除
        fetch('hub_management.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'delete_hub',
            id: id,
            name: name,
            ip: ip
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // 資料庫刪除成功後，再把前端畫面上的那筆資料濾除並重新渲染
            hubData = hubData.filter(h => h.id !== id);
            renderHubList();
          } else {
            console.error('刪除失敗:', data.message);
            alert('刪除失敗：' + (data.message || '未知錯誤'));
          }
        })
        .catch(error => {
          console.error('連線錯誤:', error);
          alert('系統異常：刪除請求失敗，請檢查網路連線。');
        });
      }
    }

    function fetchLatestHubData() {
      fetch('hub_management.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'fetch_data'
        })
      })
      .then(response => response.json())
      .then(res => {
        if (res.success) {
          // 將後端傳來的最新資料，覆蓋掉原本的 hubData
          hubData = res.data;
          
          // 重新繪製表格 (並且會自動套用目前的狀態過濾與搜尋關鍵字)
          renderHubList();
        }
      })
      .catch(error => {
        console.error('自動更新失敗:', error);
      });
    }

    // 設定定時器：每 1000 毫秒 (1秒) 呼叫一次 fetchLatestHubData
    setInterval(fetchLatestHubData, 1000);

    function updateCounts() {
      const total = hubData.length;
      let online = 0, alertCount = 0, disabled = 0;

      hubData.forEach(h => {
        const timeout = isTimeout(h.lastTime);
        if (!h.alertEnabled) disabled++;
        else if (timeout) alertCount++;
        else online++;
      });

      document.getElementById('count-all').innerText = total;
      document.getElementById('count-online').innerText = online;
      document.getElementById('count-alert').innerText = alertCount;
      document.getElementById('count-disabled').innerText = disabled;
    }

    function switchSection(sectionName, element) {
      document.querySelectorAll('.content-section').forEach(sec => sec.style.display = 'none');
      document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));

      document.getElementById(`section-${sectionName}`).style.display = 'block';
      if (element) element.classList.add('active');
    }

    renderHubList();
  </script>
</body>
</html>