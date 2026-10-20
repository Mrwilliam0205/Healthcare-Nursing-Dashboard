<?php
// ==========================================
// ★ 1. 共用資料庫連線設定 (請確認以下參數正確)
// ==========================================
$host = 'localhost';
$user = 'root';$pass = ''; // 若有密碼請填入
$db   = 'wetness_database'; // 你的資料庫名稱

// ==========================================
// ★ 2. 後端 API 處理區塊 (含重複病房檢查與防呆)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_start();
    error_reporting(0);
    
    $response = ['status' => 'error', 'message' => '未知的錯誤'];

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($host, $user,$pass, $db);$conn->set_charset("utf8mb4");

        $action =$_POST['action'];

        // 1. 新增病房
        if ($action === 'add_ward') {$new_ward = $conn->real_escape_string($_POST['new_ward']);
            $bed_count = (int)$_POST['bed_count'];
            
            // 【關鍵檢查】先查詢資料庫是否已經有這個病房名稱
            $check_sql = "SELECT COUNT(*) AS total FROM `warddata` WHERE `ward` = '{$new_ward}'";
            $check_res = $conn->query($check_sql);
            $row =$check_res->fetch_assoc();

            if ($row['total'] > 0) {
                // 病房已存在，回應友善提示
                $response = [
                    'status' => 'error', 
                    'message' => "病房「{$new_ward}」已經存在！\n請更換病房名稱，或是直接在列表中的「{$new_ward} 病房」點擊「新增病床」。"
                ];
            } else {
                // 病房不存在，正常寫入初始床位 1 ~ bed_count
                for ($i = 1; $i <=$bed_count; $i++) {$sql = "INSERT INTO `warddata`(`ward`, `bed`, `IDP`) VALUES ('{$new_ward}', '$i', '0')";
                    $conn->query($sql);
                }
                $response = ['status' => 'success'];
            }
        } 
        // 2. 刪除病房
        elseif ($action === 'delete_ward') {
            $delward =$conn->real_escape_string($_POST['delward']);$sql = "DELETE FROM `warddata` WHERE ward='{$delward}'";
            $conn->query($sql);$response = ['status' => 'success'];
        } 
        // 3. 新增單一病床
        elseif ($action === 'add_bed') {
            $newward =$conn->real_escape_string($_POST['newward']);$ward_bed = $conn->real_escape_string($_POST['ward_bed']);

            // 檢查該床號是否已存在，若存在則自動取得最大床號 + 1
            $check_bed = "SELECT MAX(CAST(`bed` AS UNSIGNED)) AS max_bed FROM `warddata` WHERE `ward` = '{$newward}'";
            $bed_res = $conn->query($check_bed);
            $bed_row =$bed_res->fetch_assoc();
            
            if ($bed_row && $bed_row['max_bed'] !== null) {$next_bed = (int)$bed_row['max_bed'] + 1; } else {$next_bed = 1;
            }

            $sql = "INSERT INTO `warddata`(`ward`, `bed`, `IDP`) VALUES ('{$newward}', '{$next_bed}', '0')";
            $conn->query($sql);
            $response = ['status' => 'success', 'new_bed' =>$next_bed];
        } 
        // 4. 刪除單一病床
        elseif ($action === 'delete_bed') {$delbed = $conn->real_escape_string($_POST['delbed']);
            $delwardbed =$conn->real_escape_string($_POST['delwardbed']);$sql = "DELETE FROM `warddata` WHERE ward='{$delbed}' AND bed='{$delwardbed}'";
            $conn->query($sql);$response = ['status' => 'success'];
        } 
        else {
            $response = ['status' => 'error', 'message' => '未知的 action 指令'];
        }

        $conn->close();
    } catch (Throwable $e) {
        $response = ['status' => 'error', 'message' => '資料庫錯誤：' . $e->getMessage()];
    }
    
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

// ==========================================
// ★ 3. 網頁載入時：從資料庫撈取病房與病床資料
// ==========================================
$wards_data = [];$conn = @new mysqli($host,$user, $pass,$db);

if (!$conn->connect_error) {$conn->set_charset("utf8mb4");
    $result =$conn->query("SELECT * FROM `warddata` ORDER BY `ward` ASC, CAST(`bed` AS UNSIGNED) ASC, `bed` ASC");
    
    if ($result) {
        while ($row =$result->fetch_assoc()) {
            $ward_name =$row['ward'];
            if (!isset($wards_data[$ward_name])) {
                $wards_data[$ward_name] = [];
            }
            $wards_data[$ward_name][] =$row;
        }
    }
}
$ward_count = count($wards_data);
?>

<!-- ========================================== -->
<!-- ★ 4. 前端 HTML 介面與 JavaScript 區塊        -->
<!-- ========================================== -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>智慧護理系統</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    .bed-item-card { position: relative; }
    .bed-delete-btn {
      position: absolute; top: 6px; right: 6px; width: 22px; height: 22px;
      border-radius: 50%; border: none; background-color: transparent; color: #94a3b8;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      font-size: 0.75rem; transition: all 0.2s ease; opacity: 0.6;
    }
    .bed-item-card:hover .bed-delete-btn { opacity: 1; }
    .bed-delete-btn:hover { background-color: #fee2e2; color: #ef4444; }
  </style>
  
  <script src="components/NavbarComponent.js" defer></script>
</head>
<body>

  <app-navbar active="settings"></app-navbar>

  <div class="app-container">
    <aside class="sidebar">
      <div class="sidebar-section-title">系統管理</div>
      <ul class="sidebar-menu">
        <li>
          <a href="#" class="sidebar-link active" onclick="switchSection('ward', this)"><span>🛏️ 病房管理</span></a>
        </li>
        <li>
          <a href="#" class="sidebar-link" onclick="switchSection('import', this)"><span>📥 資料匯入</span></a>
        </li>
      </ul>
    </aside>

    <main class="main-content">
      <section id="section-ward" class="content-section">
        <div class="page-header">
          <h1 class="page-title">病房與病床配置管理</h1>
          <button type="button" class="btn btn-primary" onclick="openAddWardModal()">
            <i class="fa-solid fa-plus"></i> 新增病房
          </button>
        </div>

        <div class="ward-tabs">
          <button class="tab-btn active">全部病房 <span class="tab-badge" id="wardCountBadge"><?php echo $ward_count; ?></span></button>
        </div>

        <div class="ward-cards-container" id="wardContainer">
          
          <?php if (!empty($wards_data)): ?>
            <?php foreach ($wards_data as $ward_name =>$beds): ?>
              <?php $ward_id_safe = 'ward-' . htmlspecialchars($ward_name); ?>
              <div class="ward-group-card" id="<?php echo $ward_id_safe; ?>">
                
                <div class="ward-group-header">
                  <div class="ward-title-box">
                    <span class="ward-name"><?php echo htmlspecialchars($ward_name); ?></span>
                  </div>
                  <div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteWard('<?php echo $ward_id_safe; ?>')">刪除病房</button>
                  </div>
                </div>

                <div class="beds-grid">
                  <?php foreach ($beds as$bed): ?>
                    <?php 
                      $isOccupied = ($bed['IDP'] !== '0');
                      $bedClass =$isOccupied ? 'occupied' : '';
                      $statusText =$isOccupied ? '使用中' : '空床';
                      $display_bed = htmlspecialchars($ward_name . '-' .$bed['bed']);
                    ?>
                    <div class="bed-item-card <?php echo $bedClass; ?>">
                      <button type="button" class="bed-delete-btn" onclick="deleteBed(this)" title="刪除此病床">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                      <div class="bed-info">
                        <i class="fa-solid fa-bed bed-icon"></i>
                        <div class="bed-details">
                          <span class="bed-number"><?php echo $display_bed; ?></span>
                          <span class="bed-status-text <?php echo $isOccupied ? 'in-use' : ''; ?>"><?php echo $statusText; ?></span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  
                  <div class="add-bed-card" onclick="addBed('<?php echo $ward_id_safe; ?>')">
                    <i class="fa-solid fa-circle-plus"></i> 新增病床
                  </div>
                </div>

              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: #64748b; margin-top: 2rem;">目前尚無病房資料，請點擊上方按鈕新增。</p>
          <?php endif; ?>

        </div>
      </section>

      <section id="section-import" class="content-section" style="display: none;">
        <div class="page-header"><h1 class="page-title">批次資料匯入</h1></div>
      </section>
    </main>
  </div>

  <!-- 新增病房 Modal -->
  <div class="modal-overlay" id="addWardModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title">新增病房</h3>
        <button type="button" class="modal-close" onclick="closeAddWardModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">病房名稱 / 編號</label>
          <input type="text" id="newWardName" class="form-input" placeholder="例如：01 或 401">
        </div>
        <div class="form-group">
          <label class="form-label">初始病床數量</label>
          <input type="number" id="newWardBedCount" class="form-input" value="2" min="1" max="10">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" style="background-color: #e2e8f0; color: #334155;" onclick="closeAddWardModal()">取消</button>
        <button type="button" class="btn btn-primary" onclick="submitAddWard()">建立病房</button>
      </div>
    </div>
  </div>

  <!-- JavaScript 控制邏輯 -->
  <script>
    function switchSection(sectionName, element) {
      document.querySelectorAll('.content-section').forEach(sec => sec.style.display = 'none');
      document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
      document.getElementById(`section-${sectionName}`).style.display = 'block';
      if (element) element.classList.add('active');
    }

    function openAddWardModal() { document.getElementById('addWardModal').classList.add('active'); }
    function closeAddWardModal() { document.getElementById('addWardModal').classList.remove('active'); }

    const apiUrl = window.location.pathname;

    // 1. 新增病房
    async function submitAddWard() {
      const name = document.getElementById('newWardName').value.trim();
      const count = parseInt(document.getElementById('newWardBedCount').value) || 1;

      if (!name) { alert('請輸入病房名稱！'); return; }

      const formData = new FormData();
      formData.append('action', 'add_ward');
      formData.append('new_ward', name);
      formData.append('bed_count', count);

      try {
        const response = await fetch(apiUrl, { method: 'POST', body: formData });
        const textData = await response.text(); 
        
        let result;
        try {
          result = JSON.parse(textData);
        } catch (e) {
          alert('伺服器回應非 JSON 格式！詳細回應內容如下：\n\n' + textData);
          return;
        }

        if (result.status === 'success') {
          const wardId = 'ward-' + name;
          const container = document.getElementById('wardContainer');
          
          let bedsHtml = '';
          for (let i = 1; i <= count; i++) {
            bedsHtml += `
              <div class="bed-item-card">
                <button type="button" class="bed-delete-btn" onclick="deleteBed(this)" title="刪除此病床">
                  <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="bed-info">
                  <i class="fa-solid fa-bed bed-icon"></i>
                  <div class="bed-details">
                    <span class="bed-number">${name}-${i}</span>
                    <span class="bed-status-text">空床</span>
                  </div>
                </div>
              </div>
            `;
          }

          const wardCard = document.createElement('div');
          wardCard.className = 'ward-group-card';
          wardCard.id = wardId;
          wardCard.innerHTML = `
            <div class="ward-group-header">
              <div class="ward-title-box">
                <span class="ward-name">${name}</span>
              </div>
              <div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteWard('${wardId}')">刪除病房</button>
              </div>
            </div>
            <div class="beds-grid">
              ${bedsHtml}
              <div class="add-bed-card" onclick="addBed('${wardId}')">
                <i class="fa-solid fa-circle-plus"></i> 新增病床
              </div>
            </div>
          `;

          const emptyMsg = container.querySelector('p');
          if (emptyMsg) emptyMsg.remove();

          container.appendChild(wardCard);

          const badge = document.getElementById('wardCountBadge');
          if (badge) badge.innerText = parseInt(badge.innerText) + 1;

          closeAddWardModal();
          document.getElementById('newWardName').value = '';
          alert(`🎉 成功建立「${name}」！`);
        } else {
          alert('⚠️ 新增病房失敗：\n' + result.message);
        }
      } catch (error) {
        alert('請求失敗：' + error.message);
      }
    }

    // 2. 刪除病房
    async function deleteWard(wardId) {
      const wardCard = document.getElementById(wardId);
      const wardName = wardCard.querySelector('.ward-name').innerText.trim();
      const occupiedBeds = wardCard.querySelectorAll('.bed-item-card.occupied');
      
      if (occupiedBeds.length > 0) {
        alert(`❌ 無法刪除「${wardName}」！\n該病房目前有病床正在使用中，請先辦理退床。`);
        return;
      }

      if (confirm(`確定要刪除「${wardName}」及其所有空病床嗎？`)) {
        const formData = new FormData();
        formData.append('action', 'delete_ward');
        formData.append('delward', wardName);

        try {
          const response = await fetch(apiUrl, { method: 'POST', body: formData });
          const textData = await response.text(); 
          
          let result;
          try {
            result = JSON.parse(textData);
          } catch (e) {
            alert('伺服器回應非 JSON 格式！詳細回應內容如下：\n\n' + textData);
            return;
          }

          if (result.status === 'success') {
            wardCard.remove(); 
            const badge = document.getElementById('wardCountBadge');
            if (badge) badge.innerText = parseInt(badge.innerText) - 1;
          } else {
            alert('刪除病房失敗：\n' + result.message);
          }
        } catch (error) {
          alert('請求失敗：' + error.message);
        }
      }
    }

    // 3. 新增單一病床
    async function addBed(wardId) {
      const wardCard = document.getElementById(wardId);
      if (!wardCard) { alert('找不到該病房卡片！'); return; }

      const grid = wardCard.querySelector('.beds-grid');
      const wardName = wardCard.querySelector('.ward-name').innerText.trim();

      const formData = new FormData();
      formData.append('action', 'add_bed');
      formData.append('newward', wardName);
      formData.append('ward_bed', '1'); // 後端會自動計算最新床號

      try {
        const response = await fetch(apiUrl, { method: 'POST', body: formData });
        const textData = await response.text(); 
        
        let result;
        try {
          result = JSON.parse(textData);
        } catch (e) {
          alert('伺服器回應非 JSON 格式！詳細回應內容如下：\n\n' + textData);
          return;
        }

        if (result.status === 'success') {
          const bedNumStr = result.new_bed;
          const addCard = wardCard.querySelector('.add-bed-card');
          const newBed = document.createElement('div');
          newBed.className = 'bed-item-card';
          newBed.innerHTML = `
            <button type="button" class="bed-delete-btn" onclick="deleteBed(this)" title="刪除此病床">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="bed-info">
              <i class="fa-solid fa-bed bed-icon"></i>
              <div class="bed-details">
                <span class="bed-number">${wardName}-${bedNumStr}</span>
                <span class="bed-status-text">空床</span>
              </div>
            </div>
          `;
          grid.insertBefore(newBed, addCard);
        } else {
          alert('新增病床失敗：\n' + result.message);
        }
      } catch (error) {
        alert('請求失敗：' + error.message);
      }
    }

    // 4. 刪除單一病床
    async function deleteBed(btn) {
      const bedCard = btn.closest('.bed-item-card');
      const wardCard = btn.closest('.ward-group-card');
      const wardName = wardCard.querySelector('.ward-name').innerText.trim();
      const bedNumFull = bedCard.querySelector('.bed-number').innerText.trim(); 
      const bedNumStr = bedNumFull.split('-')[1] || bedNumFull; 

      if (bedCard.classList.contains('occupied')) {
        alert(`❌ 無法刪除病床「${bedNumFull}」！\n目前有人使用中，請先辦理退床。`);
        return;
      }

      if (confirm(`確定要刪除病床「${bedNumFull}」嗎？`)) {
        const formData = new FormData();
        formData.append('action', 'delete_bed');
        formData.append('delbed', wardName);
        formData.append('delwardbed', bedNumStr);

        try {
          const response = await fetch(apiUrl, { method: 'POST', body: formData });
          const textData = await response.text(); 
          
          let result;
          try {
            result = JSON.parse(textData);
          } catch (e) {
            alert('伺服器回應非 JSON 格式！詳細回應內容如下：\n\n' + textData);
            return;
          }

          if (result.status === 'success') {
            bedCard.remove(); 
          } else {
            alert('刪除病床失敗：\n' + result.message);
          }
        } catch (error) {
          alert('請求失敗：' + error.message);
        }
      }
    }
  </script>
</body>
</html>