<?php
/*
 * 單檔版尿褲庫存管理系統
 * - 一般開啟：顯示網頁
 * - 資料庫連線方式與 hub_management.php 相同 (dbtools.inc.php → create_connection → wetness_database2)
 * - ?action=inventory：讀取三張資料表並回傳 JSON
 * - ?action=update：更新機構或病患庫存
 */
$action = $_GET['action'] ?? '';

if ($action === 'inventory' || $action === 'update') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    // 與 hub_management.php 相同：使用 dbtools.inc.php 的 create_connection() 建立連線
    require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");

    mysqli_report(MYSQLI_REPORT_OFF);
    $link = create_connection();

    // 連線失敗（或 create_connection 沒有回傳連線物件）
    if (!$link) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '資料庫連線失敗：' . mysqli_connect_error()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 選擇資料庫 wetness_database2（等同 execute_sql($link, "wetness_database2", ...) 內部做的事）
    if (!mysqli_select_db($link, 'wetness_database2')) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '無法選擇資料庫 wetness_database2：' . mysqli_error($link)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 後面的查詢、交易 (transaction)、prepared statement 都沿用 $conn，
    // 它就是上面 create_connection() 建立的同一條連線
    $conn = $link;
    $conn->set_charset('utf8mb4');

    // 讀取資料：warddata + memberlogin + diaper inventory
    if ($action === 'inventory') {
        $sqlPatients = "
            SELECT
                d.id,
                w.ward,
                w.bed,
                m.IDP,
                m.Name,
                d.stock
            FROM `diaper inventory` AS d
            INNER JOIN memberlogin AS m ON d.IDP = m.IDP
            INNER JOIN warddata AS w ON w.IDP = m.IDP
            WHERE d.IDP IS NOT NULL
              AND d.IDP <> ''
              AND d.IDP <> '0'
            ORDER BY d.stock ASC, w.ward ASC, w.bed ASC
        ";

        $resultPatients = $conn->query($sqlPatients);
        if (!$resultPatients) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '病患庫存查詢失敗：' . $conn->error
            ], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        $patients = [];
        $allocatedStock = 0;
        while ($row = $resultPatients->fetch_assoc()) {
            $stock = (int)$row['stock'];
            $allocatedStock += $stock;
            $patients[] = [
                'id' => (int)$row['id'],
                'ward' => (string)$row['ward'],
                'bed' => (string)$row['bed'],
                'IDP' => (string)$row['IDP'],
                'name' => (string)$row['Name'],
                'stock' => $stock
            ];
        }

        $sqlTotal = "SELECT id, stock FROM `diaper inventory` WHERE IDP IS NULL LIMIT 1";
        $resultTotal = $conn->query($sqlTotal);
        if (!$resultTotal) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '機構總庫存查詢失敗：' . $conn->error
            ], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        $totalRow = $resultTotal->fetch_assoc();
        $totalStock = $totalRow ? (int)$totalRow['stock'] : 0;
        $totalStockId = $totalRow ? (int)$totalRow['id'] : null;
        $unallocatedStock = $totalStock - $allocatedStock;
        $conn->close();

        echo json_encode([
            'success' => true,
            'totalStock' => $totalStock,
            'totalStockId' => $totalStockId,
            'allocatedStock' => $allocatedStock,
            'unallocatedStock' => $unallocatedStock,
            'patients' => $patients
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 更新庫存：必須用 POST 傳送 JSON
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '請使用 POST 方法更新庫存。'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '收到的資料格式錯誤。'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_INT);
    if ($amount === false || $amount === null || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '增加數量必須是大於 0 的整數。'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $isTotal = !empty($input['total']);
    $conn->begin_transaction();

    if ($isTotal) {
        $selectSql = "SELECT id, IDP, stock FROM `diaper inventory` WHERE IDP IS NULL LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($selectSql);
        if (!$stmt || !$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '查詢機構總庫存失敗。'], JSON_UNESCAPED_UNICODE);
            $conn->rollback();
            $conn->close();
            exit;
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
    } else {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少有效的庫存資料 ID。'], JSON_UNESCAPED_UNICODE);
            $conn->rollback();
            $conn->close();
            exit;
        }

        $selectSql = "SELECT id, IDP, stock FROM `diaper inventory` WHERE id = ? LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($selectSql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '建立庫存查詢失敗：' . $conn->error], JSON_UNESCAPED_UNICODE);
            $conn->rollback();
            $conn->close();
            exit;
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '查詢庫存資料失敗。'], JSON_UNESCAPED_UNICODE);
            $stmt->close();
            $conn->rollback();
            $conn->close();
            exit;
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row && ($row['IDP'] === null || $row['IDP'] === '' || $row['IDP'] === '0')) {
            $row = null;
        }
    }

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => $isTotal ? '找不到 IDP = NULL 的機構總庫存資料。' : '找不到指定的病患庫存資料。'
        ], JSON_UNESCAPED_UNICODE);
        $conn->rollback();
        $conn->close();
        exit;
    }

    $oldStock = (int)$row['stock'];
    $newStock = $oldStock + (int)$amount;
    $updateStmt = $conn->prepare('UPDATE `diaper inventory` SET stock = ? WHERE id = ?');
    if (!$updateStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '建立庫存更新語句失敗：' . $conn->error], JSON_UNESCAPED_UNICODE);
        $conn->rollback();
        $conn->close();
        exit;
    }
    $recordId = (int)$row['id'];
    $updateStmt->bind_param('ii', $newStock, $recordId);
    if (!$updateStmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '更新庫存失敗：' . $updateStmt->error], JSON_UNESCAPED_UNICODE);
        $updateStmt->close();
        $conn->rollback();
        $conn->close();
        exit;
    }
    $updateStmt->close();
    $conn->commit();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => '庫存更新成功。',
        'data' => [
            'id' => $recordId,
            'IDP' => $row['IDP'],
            'old_stock' => $oldStock,
            'added' => (int)$amount,
            'stock' => $newStock
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>智慧護理系統</title>
  <link rel="stylesheet" href="css/style.css">
  
  <style>
/* ==========================================================================
   智慧護理系統樣式
   ========================================================================== */

/* ----------------------------------- */
/* 1. 全局變數與系統主題色調          */
/* ----------------------------------- */
:root {
  /* 暗色背景區 (導覽列/側邊欄) */
  --bg-dark: #0f172a;             /* 頂部導覽列 */
  --bg-sidebar: #1e293b;          /* 側邊欄背景 */
  --sidebar-text: #94a3b8;
  --sidebar-text-hover: #f8fafc;
  --sidebar-title: #64748b;
  --sidebar-border: #334155;

  /* 亮色內容區 */
  --bg-main: #f1f5f9;             /* 主要內容背景 */
  --card-bg: #ffffff;             /* 卡片背景 */
  --text-dark: #334155;
  --text-muted: #64748b;
  
  /* 品牌與功能主題色 */
  --primary-blue: #0284c7;        /* 醫療蔚藍 (主色) */
  --primary-blue-hover: #0369a1;
  --primary-blue-light: rgba(2, 132, 199, 0.12);
  
  --excel-green: #16a34a;        /* Excel 經典綠 */
  --excel-green-hover: #15803d;
  --excel-green-light: rgba(22, 163, 74, 0.1);

  --success-color: #16a34a;      /* 使用中/在勤綠 */
  --danger-color: #ef4444;       /* 刪除/警示紅 */
  --danger-hover: #dc2626;

  --border-color: #e2e8f0;
  --border-radius-card: 16px;
}

/* 基本重置 */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Noto Sans TC', -apple-system, sans-serif;
  background-color: var(--bg-main);
  color: var(--text-dark);
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* ----------------------------------- */
/* 3. 主架構與側邊欄 (Layout & Sidebar) */
/* ----------------------------------- */
.app-container {
  display: flex;
  flex: 1;
  overflow: hidden;
  position: relative;
}

.sidebar {
  width: 220px;
  background-color: var(--bg-sidebar);
  display: flex;
  flex-direction: column;
  padding: 1.25rem 0.75rem;
  overflow-y: auto;
  box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
  flex-shrink: 0;
}

.sidebar-section-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--sidebar-title);
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 0 0.75rem;
  margin-bottom: 0.5rem;
}

.sidebar-menu {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.sidebar-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0.85rem;
  border-radius: 8px;
  color: var(--sidebar-text);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.15s ease;
}

.sidebar-link:hover {
  background-color: rgba(255, 255, 255, 0.05);
  color: var(--sidebar-text-hover);
}

.sidebar-link.active {
  background-color: var(--primary-blue-light);
  color: #38bdf8;
  font-weight: 600;
}

/* ----------------------------------- */
/* 4. 主要內容與元件庫 (Main Content)   */
/* ----------------------------------- */
.main-content {
  flex: 1;
  padding: 1.5rem 2rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 1.35rem;
  color: #0f172a;
  font-weight: 700;
}

/* 按鈕模組 */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
}

.btn-primary { background-color: var(--primary-blue); color: #ffffff; }
.btn-primary:hover { background-color: var(--primary-blue-hover); }

.btn-excel {
  background-color: #ffffff;
  color: var(--excel-green);
  border: 1.5px solid var(--excel-green);
}
.btn-excel:hover { background-color: var(--excel-green); color: #ffffff; }

.btn-outline-danger {
  background-color: transparent;
  color: var(--danger-color);
  border: 1px solid var(--danger-color);
}
.btn-outline-danger:hover { background-color: var(--danger-color); color: #ffffff; }

.btn-sm {
  padding: 0.35rem 0.65rem;
  font-size: 0.8rem;
  border-radius: 6px;
}

/* 分類頁籤 (Tabs) */
.ward-tabs, .import-tabs {
  display: flex;
  gap: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0.5rem;
}

.tab-btn {
  background: none;
  border: none;
  padding: 0.5rem 1.1rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.tab-btn:hover { color: var(--text-dark); background-color: #e2e8f0; }

.tab-btn.active {
  background-color: var(--primary-blue);
  color: #ffffff;
}

.tab-badge {
  font-size: 0.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 10px;
  background-color: rgba(255, 255, 255, 0.25);
}

/* ----------------------------------- */
/* 5. 功能頁面特有結構                */
/* ----------------------------------- */

/* [病房管理] 病床網格 */
.ward-cards-container { display: flex; flex-direction: column; gap: 1.5rem; }

.ward-group-card {
  background: var(--card-bg);
  border-radius: var(--border-radius-card);
  border: 1px solid var(--border-color);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.ward-group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.75rem;
}

.ward-title-box { display: flex; align-items: center; gap: 0.75rem; }
.ward-name { font-size: 1.15rem; font-weight: 700; color: #0f172a; }
.ward-type-tag {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  background-color: #e0f2fe;
  color: #0284c7;
}

.beds-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(220px, 1fr));
  gap: 0.85rem;
}

@media (max-width: 1200px) {
  .beds-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}

.bed-item-card {
  border: 1.5px dashed var(--border-color);
  border-radius: 12px;
  padding: 0.85rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f8fafc;
  transition: all 0.2s ease;
}

.bed-item-card.occupied {
  border-style: solid;
  border-color: #cbd5e1;
  background-color: #ffffff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}

.bed-info { display: flex; align-items: center; gap: 0.6rem; }
.bed-icon { font-size: 1.2rem; color: var(--text-muted); }
.bed-item-card.occupied .bed-icon { color: var(--primary-blue); }
.bed-details { display: flex; flex-direction: column; }
.bed-number { font-family: 'Quicksand', sans-serif; font-size: 1rem; font-weight: 700; }
.bed-status-text { font-size: 0.75rem; color: var(--text-muted); }
.bed-status-text.in-use { color: var(--success-color); font-weight: 600; }

.add-bed-card {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 0.85rem 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: var(--primary-blue);
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
  background-color: rgba(2, 132, 199, 0.02);
  transition: all 0.2s ease;
}
.add-bed-card:hover { background-color: var(--primary-blue-light); border-color: var(--primary-blue); }

/* [資料匯入] 拖曳與預覽區 */
.import-container { display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; }
.upload-card, .instruction-card, .preview-card, .staff-card {
  background: var(--card-bg);
  border-radius: var(--border-radius-card);
  border: 1px solid var(--border-color);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.dropzone {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background-color: #f8fafc;
  cursor: pointer;
  transition: all 0.25s ease;
  text-align: center;
}
.dropzone:hover, .dropzone.dragover { border-color: var(--primary-blue); background-color: var(--primary-blue-light); }
.dropzone-icon { font-size: 2.8rem; color: var(--primary-blue); }
.dropzone-text { font-size: 1rem; font-weight: 700; }
.dropzone-hint { font-size: 0.8rem; color: var(--text-muted); }

/* 表格樣式 */
.table-container { overflow-x: auto; border: 1px solid var(--border-color); border-radius: 12px; }
.preview-table, .staff-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
.preview-table th, .staff-table th { background-color: #f8fafc; padding: 0.85rem 1rem; border-bottom: 2px solid var(--border-color); font-weight: 700; }
.preview-table td, .staff-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-color); }
.preview-table tr:hover, .staff-table tr:hover { background-color: #f1f5f9; }

/* 狀態徽章 */
.badge-status { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.78rem; font-weight: 600; }
.badge-ready, .badge-active { background-color: #dcfce7; color: #15803d; }
.badge-off { background-color: #f1f5f9; color: #64748b; }

/* ----------------------------------- */
/* 6. 遮罩與彈出視窗 (Modal & Overlay)  */
/* ----------------------------------- */
.modal-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex; justify-content: center; align-items: center;
  opacity: 0; visibility: hidden;
  transition: all 0.25s ease;
  z-index: 1000;
}
.modal-overlay.active { opacity: 1; visibility: visible; }

.modal-box {
  background-color: #ffffff;
  border-radius: 16px;
  width: 440px;
  max-width: 90vw;             /* 避免手機螢幕爆框 */
  max-height: 85vh;            /* 限制最大高度為視窗高度的 85% */
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;            /* 讓內部捲動，保持外框圓角 */
}

.modal-header { 
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #ffffff;
  flex-shrink: 0; 
 }
 
 .modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;            /* 內容過長時自動出現垂直捲動條 */
  display: flex;
  flex-direction: column;
  gap: 1rem;
  flex: 1;
}

.modal-title { font-size: 1.15rem; font-weight: 700; color: #0f172a; }
.modal-close { background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer; }
.modal-footer { 
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
  background-color: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0; 
 }

/* 管理員驗證鎖定層 */
.auth-overlay {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  display: flex; justify-content: center; align-items: center;
  z-index: 50;
  transition: all 0.3s ease;
}

.auth-box {
  background: #ffffff;
  border-radius: 16px;
  width: 400px;
  padding: 2rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
  display: flex; flex-direction: column; gap: 1.25rem;
}

/* 右下角雙 QR Code 容器 */
.ip-qrcode-container {
  position: absolute;
  bottom: 1.5rem; right: 1.5rem;
  display: flex; gap: 1rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 0.85rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.qrcode-card {
  background: #ffffff;
  padding: 0.6rem;
  border-radius: 8px;
  display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.qrcode-img { width: 90px; height: 90px; }
.qrcode-label { font-size: 0.72rem; font-weight: 700; color: var(--text-dark); }
.qrcode-ip { font-family: 'Quicksand', sans-serif; font-size: 0.7rem; color: var(--primary-blue); font-weight: 700; }

/* 表單通用欄位 */
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-dark); }
.form-input, .form-select {
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  font-size: 0.9rem;
  outline: none;
  font-family: inherit;
}
.form-input:focus, .form-select:focus {
  border-color: var(--primary-blue);
  box-shadow: 0 0 0 3px var(--primary-blue-light);
}
  </style>

  <style>
    /* ----------------------------------- */
    /* 2. 通用與庫存管理專用樣式           */
    /* ----------------------------------- */
    .stat-cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }

    .stat-card {
      background: #ffffff;
      border-radius: 12px;
      padding: 1.25rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.02);
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }

    .stat-icon.success { background-color: #dcfce7; color: #16a34a; }
    .stat-icon.danger { background-color: #fee2e2; color: #dc2626; }
    .stat-icon.warning { background-color: #fef3c7; color: #d97706; }
    .stat-icon.info { background-color: #e0f2fe; color: #0284c7; }

    .stat-info .stat-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #0f172a;
    }

    .stat-info .stat-label {
      font-size: 0.85rem;
      color: #64748b;
    }

    /* 全域警示 Banner */
    .alert-banner {
      background-color: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      padding: 0.9rem 1.25rem;
      border-radius: 8px;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 600;
      font-size: 0.95rem;
    }

    /* 庫存預警標籤 */
    .stock-badge {
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-weight: 600;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }
    .stock-badge.normal { background-color: #e0f2fe; color: #0284c7; }
    .stock-badge.low { background-color: #fee2e2; color: #dc2626; }

    .table-warning-row {
      background-color: #fff5f5 !important;
    }

    /* 搜尋與工具列通用樣式 */
    .toolbar-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      background-color: #ffffff;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .search-input {
      padding: 0.45rem 0.75rem;
      border: 1px solid var(--border-color, #cbd5e1);
      border-radius: 6px;
      font-size: 0.9rem;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .search-input:focus {
      border-color: var(--primary-blue, #0284c7);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    /* 身分證字號：只在「增加庫存」確認視窗顯示 */
    .patient-idp-confirm {
      background-color: #fff7ed;
      border: 1px solid #fdba74;
      border-radius: 8px;
      padding: 0.75rem 1rem;
      margin-top: 0.5rem;
    }

    .patient-idp-confirm .idp-label {
      display: block;
      font-size: 0.8rem;
      color: #9a3412;
      margin-bottom: 0.25rem;
      font-weight: 600;
    }

    .patient-idp-confirm .idp-value {
      display: block;
      font-family: monospace;
      font-size: 1.05rem;
      font-weight: 700;
      color: #7c2d12;
      letter-spacing: 0.03em;
    }

    .patient-idp-note {
      margin-top: 0.35rem;
      font-size: 0.78rem;
      color: #9a3412;
      line-height: 1.4;
    }
  </style>
  
</head>
<body>

<div class="app-container">
    
    <!-- 左側邊欄：管理選單 -->
    <aside class="sidebar">
      <div class="sidebar-section-title">設備與物料管理</div>
      <ul class="sidebar-menu">
        <li>
          <a href="frame_hub.php" class="sidebar-link">
            <span><i class="fa-solid fa-server"></i> HUB 管理</span>
          </a>
        </li>
        <li>
          <a href="#" class="sidebar-link active" onclick="switchSection('inventory', this)">
            <span><i class="fa-solid fa-boxes-stacked"></i> 庫存管理</span>
          </a>
        </li>
      </ul>
    </aside>

    <!-- 右側主要內容區 -->
    <main class="main-content">
      
      <!-- ========================================== -->
      <!-- 子頁面：庫存管理                         -->
      <!-- ========================================== -->
      <section id="section-inventory" class="content-section">
        <div class="page-header">
          <h1 class="page-title"><i class="fa-solid fa-boxes-stacked"></i> 尿褲庫存管理系統</h1>
          <button class="btn btn-primary" onclick="openAddTotalStockModal()">
            <i class="fa-solid fa-boxes-packing"></i> 補充機構總庫存
          </button>
        </div>

        <!-- 全域庫存不足警示 Banner -->
        <div id="globalStockAlert" class="alert-banner" style="display: none;">
          <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
          <div>
            <strong>全域庫存警示：</strong> 目前機構總庫存 (<span id="alertTotalStock">0</span> 片) 低於所有個人庫存之總和 (<span id="alertSumPersonal">0</span> 片)！請儘速補充總庫存。
          </div>
        </div>

        <!-- 總庫存與分配統計 -->
        <div class="stat-cards-grid">
          <div class="stat-card">
            <div class="stat-icon info">
              <i class="fa-solid fa-warehouse"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value" id="displayTotalStock">0</div>
              <div class="stat-label">機構總庫存 (片)</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon success">
              <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value" id="displayAllocatedStock">0</div>
              <div class="stat-label">病患個人庫存總計 (片)</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon warning">
              <i class="fa-solid fa-box-open"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value" id="displayUnallocatedStock">0</div>
              <div class="stat-label">剩餘可用庫存 (片)</div>
            </div>
          </div>
        </div>

        <!-- 病患個人庫存明細表格 -->
        <div class="upload-card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1.05rem; color: #0f172a;">
              <i class="fa-solid fa-hospital-user" style="color: var(--primary-blue);"></i> 病患個人尿褲庫存列表
              <span style="font-size: 0.8rem; font-weight: normal; color: #64748b; margin-left: 0.5rem;">(預設依庫存不足優先排序)</span>
            </h3>

            <!-- 病患庫存搜尋框 -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <input 
                type="text" 
                id="inventorySearchInput" 
                class="search-input" 
                placeholder="搜尋病房、床號或病患姓名..." 
                style="width: 200px;" 
                oninput="renderInventoryView()"
              >
            </div>
          </div>

          <div class="table-container">
            <table class="preview-table">
              <thead>
                <tr>
                  <th style="width: 80px;">病房</th>
                  <th style="width: 80px;">床號</th>
                  <th style="min-width: 120px;">病患姓名</th>
                  <th>個人尿褲數量</th>
                  <th>庫存狀態</th>
                  <th style="text-align: center; width: 160px;">操作</th>
                </tr>
              </thead>
              <tbody id="personalStockTableBody">
                <!-- JS 動態繪製與計算 -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- Modal 1: 補充總庫存 -->
  <div class="modal-overlay" id="addTotalStockModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-warehouse"></i> 補充機構總庫存</h3>
        <button class="modal-close" onclick="closeModal('addTotalStockModal')"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">請輸入欲新增的尿褲數量 (片)</label>
          <input type="number" id="inputAddTotalCount" class="form-input" value="100" min="1">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" style="background-color: #e2e8f0; color: #334155;" onclick="closeModal('addTotalStockModal')">取消</button>
        <button class="btn btn-primary" onclick="submitAddTotalStock()">確認增加</button>
      </div>
    </div>
  </div>

  <!-- Modal 2: 補充個人庫存 -->
  <div class="modal-overlay" id="addPersonalStockModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-box-tissue"></i> 補充病患個人庫存</h3>
        <button class="modal-close" onclick="closeModal('addPersonalStockModal')"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="targetPatientRecordId">
        <input type="hidden" id="targetPatientBed">
        <input type="hidden" id="targetPatientIDP">

        <div class="form-group">
          <label class="form-label">病患資訊</label>
          <input type="text" id="targetPatientInfo" class="form-input" readonly style="background-color: #f8fafc;">
        </div>

        <div class="patient-idp-confirm">
          <span class="idp-label">身分證字號（確認病患身分）</span>
          <span class="idp-value" id="targetPatientIDPDisplay"></span>
          <div class="patient-idp-note">
            請在補充庫存前確認此身分證字號與病患身分一致，避免增加到錯誤的病患。
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">欲增加數量 (片)</label>
          <input type="number" id="inputAddPersonalCount" class="form-input" value="10" min="1">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" style="background-color: #e2e8f0; color: #334155;" onclick="closeModal('addPersonalStockModal')">取消</button>
        <button class="btn btn-primary" onclick="submitAddPersonalStock()">確認補充</button>
      </div>
    </div>
  </div>

  <script>
    // API 網址：固定指向「本檔案自己」，即使被外層頁面 (如 frame_inventory.php) 包住也能取得 JSON
    const API_URL = <?= json_encode(basename(__FILE__)) ?>;

    // -----------------------------------
    // 庫存管理邏輯
    // -----------------------------------

    let totalStock = 0;
    let totalStockRecordId = null;
    let personalPatients = [];

    // 由本檔案的 action=inventory API 取得三張資料表的 JOIN 結果
    async function loadInventory() {
      try {
        const response = await fetch(API_URL + '?action=inventory', {
          method: 'GET',
          cache: 'no-store'
        });

        if (!response.ok) {
          throw new Error(`HTTP 錯誤：${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
          throw new Error(result.message || '取得庫存資料失敗');
        }

        totalStock = Number(result.totalStock) || 0;
        totalStockRecordId = result.totalStockId == null ? null : Number(result.totalStockId);

        personalPatients = Array.isArray(result.patients)
          ? result.patients.map(p => ({
              id: Number(p.id),
              ward: p.ward == null ? '' : String(p.ward),
              bed: p.bed == null ? '' : String(p.bed),
              IDP: p.IDP == null ? '' : String(p.IDP),
              name: p.name == null ? '未設定姓名' : String(p.name),
              count: Number(p.stock) || 0
            }))
          : [];

        renderInventoryView();
        console.log('庫存資料已從三張 Table 載入：', result);
      } catch (error) {
        console.error('取得庫存資料失敗：', error);
        alert(
          '無法取得資料庫庫存資料！\n\n' +
          '請確認：\n' +
          '1. XAMPP 的 Apache / MySQL 已啟動\n' +
          '2. 目前這個 PHP 頁面的 action=inventory API 可以正常開啟\n' +
          '3. warddata、memberlogin、diaper inventory 的資料可以關聯\n\n' +
          '錯誤：' + error.message
        );
      }
    }

    function renderInventoryView() {
      const tbody = document.getElementById('personalStockTableBody');
      const searchInput = document.getElementById('inventorySearchInput');
      const searchQuery = searchInput ? searchInput.value.trim().toLowerCase() : '';
      tbody.innerHTML = '';

      const sumPersonal = personalPatients.reduce((sum, p) => sum + p.count, 0);
      let sortedList = [...personalPatients].sort((a, b) => a.count - b.count);

      if (searchQuery) {
        sortedList = sortedList.filter(p =>
          p.ward.toLowerCase().includes(searchQuery) ||
          p.bed.toLowerCase().includes(searchQuery) ||
          p.name.toLowerCase().includes(searchQuery)
        );
      }

      if (sortedList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:#94a3b8;">未找到符合條件的病患庫存資料</td></tr>`;
      } else {
        sortedList.forEach(p => {
          const isLow = p.count < 40;
          const tr = document.createElement('tr');
          if (isLow) tr.className = 'table-warning-row';

          tr.innerHTML = `
            <td><strong>${escapeHtml(p.ward)}</strong></td>
            <td><strong>${escapeHtml(p.bed)}</strong></td>
            <td>${escapeHtml(p.name)}</td>
            <td style="font-size: 1.05rem; font-weight: 700; color: ${isLow ? '#dc2626' : '#0f172a'};">${p.count} 片</td>
            <td>
              ${isLow
                ? `<span class="stock-badge low"><i class="fa-solid fa-triangle-exclamation"></i> 庫存過低 (< 40)</span>`
                : `<span class="stock-badge normal"><i class="fa-solid fa-circle-check"></i> 庫存正常</span>`
              }
            </td>
            <td style="text-align: center;">
              <button class="btn btn-primary btn-sm" onclick="openAddPersonalModal(${p.id}, '${jsQuote(p.IDP)}', '${jsQuote(p.name)}', '${jsQuote(p.ward)}', '${jsQuote(p.bed)}')">
                <i class="fa-solid fa-plus"></i> 增加庫存
              </button>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }

      document.getElementById('displayTotalStock').innerText = totalStock;
      document.getElementById('displayAllocatedStock').innerText = sumPersonal;
      document.getElementById('displayUnallocatedStock').innerText = totalStock - sumPersonal;

      const globalAlert = document.getElementById('globalStockAlert');
      if (totalStock < sumPersonal) {
        globalAlert.style.display = 'flex';
        document.getElementById('alertTotalStock').innerText = totalStock;
        document.getElementById('alertSumPersonal').innerText = sumPersonal;
      } else {
        globalAlert.style.display = 'none';
      }
    }

    function escapeHtml(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function jsQuote(value) {
      return String(value)
        .replaceAll('\\', '\\\\')
        .replaceAll("'", "\\'")
        .replaceAll('\n', '\\n')
        .replaceAll('\r', '\\r');
    }

    // 切換主頁面 Section
    function switchSection(sectionName, element) {
      document.querySelectorAll('.content-section').forEach(sec => sec.style.display = 'none');
      document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));

      document.getElementById(`section-${sectionName}`).style.display = 'block';
      if (element) element.classList.add('active');

      if (sectionName === 'inventory') {
        loadInventory();
      }
    }

    // 彈窗控制
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function openAddTotalStockModal() {
      openModal('addTotalStockModal');
    }

    // 將庫存異動送到 PHP，再由 PHP 寫入 MySQL
    async function updateInventory(recordId, addVal, updateTotal = false) {
      const body = updateTotal
        ? { total: true, amount: Number(addVal) }
        : { id: Number(recordId), amount: Number(addVal) };

      const response = await fetch(API_URL + '?action=update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      });

      let result;
      try {
        result = await response.json();
      } catch (e) {
        throw new Error('本 PHP 檔案的更新 API 沒有回傳有效的 JSON。');
      }

      if (!response.ok || !result.success) {
        throw new Error(result.message || '更新庫存失敗');
      }

      return result;
    }

    async function submitAddTotalStock() {
      const addVal = parseInt(document.getElementById('inputAddTotalCount').value, 10) || 0;

      if (addVal <= 0) {
        alert('請輸入有效的數量！');
        return;
      }

      try {
        const result = await updateInventory(null, addVal, true);

        closeModal('addTotalStockModal');
        await loadInventory();

        alert(`成功補充總庫存 ${result.data.added} 片！\n目前機構總庫存：${result.data.stock} 片`);
      } catch (error) {
        console.error('更新機構總庫存失敗：', error);
        alert('更新機構總庫存失敗：\n' + error.message);
      }
    }

    function openAddPersonalModal(recordId, IDP, name, ward, bed) {
      document.getElementById('targetPatientRecordId').value = recordId;
      document.getElementById('targetPatientBed').value = bed;
      document.getElementById('targetPatientIDP').value = IDP;

      // 主頁面不顯示 IDP，只有在按下「增加庫存」後才顯示。
      document.getElementById('targetPatientInfo').value = `${ward}病房 ${bed}床 - ${name}`;
      document.getElementById('targetPatientIDPDisplay').textContent = IDP;

      document.getElementById('inputAddPersonalCount').value = 10;
      openModal('addPersonalStockModal');
    }

    async function submitAddPersonalStock() {
      const recordId = parseInt(document.getElementById('targetPatientRecordId').value, 10);
      const IDP = document.getElementById('targetPatientIDP').value;
      const addVal = parseInt(document.getElementById('inputAddPersonalCount').value, 10) || 0;

      if (!recordId) {
        alert('找不到病患庫存資料。');
        return;
      }

      if (addVal <= 0) {
        alert('請輸入有效的數量！');
        return;
      }

      try {
        const target = personalPatients.find(p => p.id === recordId);
        const result = await updateInventory(recordId, addVal, false);

        closeModal('addPersonalStockModal');
        await loadInventory();

        const displayName = target ? target.name : '病患';
        alert(`已成功為 ${displayName}（${IDP}）增加 ${result.data.added} 片尿褲！\n目前個人庫存：${result.data.stock} 片`);
      } catch (error) {
        console.error('更新病患個人庫存失敗：', error);
        alert('更新病患個人庫存失敗：\n' + error.message);
      }
    }

    // 初次載入庫存資料
    loadInventory();
  </script>
</body>
</html>