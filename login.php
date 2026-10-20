<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Care System - 身分選擇</title>
  
  <!-- 載入 Font Awesome 圖示庫 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- 引入你的全局獨立 CSS 檔案 -->
  <!-- 注意：請確認 css/style.css 的路徑是否正確 -->
  <link rel="stylesheet" href="css/style.css">

  <style>
    /* ----------------------------------- */
    /* 首頁 (index.html) 專用的局部覆寫與卡片樣式 */
    /* ----------------------------------- */
    
    /* 覆寫 body 屬性，讓首頁垂直置中 */
    body.home-page {
      background-color: var(--bg-main);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 2rem 1.5rem;
      /* 移除全局 css 裡的 overflow: hidden 以防螢幕太小時無法滾動 */
      overflow: auto; 
    }

    .system-title {
      text-align: center;
      margin-bottom: 3rem;
    }

    .system-title .en-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--primary-blue);
      letter-spacing: 0.15em;
      text-transform: uppercase;
      margin-bottom: 0.35rem;
    }

    .system-title h1 {
      font-size: 2.25rem;
      color: #0f172a;
      font-weight: 800;
      letter-spacing: -0.025em;
      margin-bottom: 0.5rem;
    }

    .system-title p {
      color: var(--text-muted);
      font-size: 1.05rem;
    }

    /* 3個按鈕網格容器 */
    .button-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
      width: 100%;
      max-width: 1100px;
    }

    /* 大型按鈕卡片 (繼承 style.css 的卡片背景與圓角) */
    .nav-button {
      background: var(--card-bg);
      border: 2px solid var(--border-color);
      border-radius: var(--border-radius-card);
      padding: 2.5rem 1.75rem;
      text-decoration: none;
      color: var(--text-dark);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
      cursor: pointer;
    }

    .nav-button:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
    }

    /* 圖示容器 */
    .icon-wrapper {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.25rem;
      margin-bottom: 1.5rem;
      transition: transform 0.3s ease;
    }

    .nav-button:hover .icon-wrapper {
      transform: scale(1.1);
    }

    .btn-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      color: #0f172a;
    }

    .btn-desc {
      font-size: 0.95rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* 病人模組風格 (藍色) - 使用 style.css 的變數 */
    .btn-nursing .icon-wrapper {
      background-color: var(--primary-blue-light);
      color: var(--primary-blue);
    }
    .btn-nursing:hover {
      border-color: var(--primary-blue);
    }

    /* 病床模組風格 (綠色) */
    .btn-patients .icon-wrapper {
      background-color: var(--excel-green-light);
      color: var(--excel-green);
    }
    .btn-patients:hover {
      border-color: var(--excel-green);
    }

    /* 醫療裝置風格 (紫色/深灰) */
    .btn-devices .icon-wrapper {
      background-color: #f5f3ff;
      color: #8b5cf6;
    }
    .btn-devices:hover {
      border-color: #8b5cf6;
    }

    /* RWD 響應式：平板與手機切換為直排 */
    @media (max-width: 900px) {
      .button-grid {
        grid-template-columns: 1fr;
        max-width: 480px;
      }
    }
  </style>
</head>

<!-- 在 body 加上 home-page class，避免被全局 style.css 的排版干擾 -->
<body class="home-page">

  <!-- 系統標題區域 -->
  <div class="system-title">
    <div class="en-title">Smart Care System</div>
    <h1>智慧護理管理系統</h1>
    <p>請選擇欲前往的管理模組</p>
  </div>

  <!-- 3 個核心功能前往按鈕 -->
  <div class="button-grid">
    
    <!-- 按鈕 1：病人 -->
    <a href="Patient List/frame_patient_list.php" class="nav-button btn-nursing">
      <div class="icon-wrapper">
        <i class="fa-solid fa-hospital-user"></i>
      </div>
      <div class="btn-title">病人</div>
      <div class="btn-desc">管理病患基本資料與照護紀錄</div>
    </a>

    <!-- 按鈕 2：病床 -->
    <a href="frame_wardsel.php" class="nav-button btn-patients">
      <div class="icon-wrapper">
        <i class="fa-solid fa-bed"></i>
      </div>
      <div class="btn-title">病床</div>
      <div class="btn-desc">病房與床位安排管理</div>
    </a>

    <!-- 按鈕 3：裝置 -->
    <a href="frame_mac_list.php" class="nav-button btn-devices">
      <div class="icon-wrapper">
        <i class="fa-solid fa-server"></i>
      </div>
      <div class="btn-title">裝置</div>
      <div class="btn-desc">管理系統裝置連線與配對</div>
    </a>

  </div>

</body>
</html>