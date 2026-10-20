<!-- menubar.php -->
<nav class="navbar">
    <!-- 左側品牌 Logo 與名稱 -->
    <div class="navbar-brand">
        <!-- 若你有 icon 圖片可解除下方註解並替換路徑 -->
        <!-- <img src="/test2/icon.png" alt="Logo" style="height:24px;"> -->
        智慧護理<span>系統</span>
    </div>

    <!-- 右側功能選單 -->
    <ul class="nav-list">
        <!-- 單一連結：護理站 (已進入 Nurse Station 資料夾) -->
        <li class="nav-item">
            <a href="/test2/Nurse Station/nursingStation.php?ward=all_ward" class="nav-link">護理站</a>
        </li>

        <!-- 下拉選單：病患 -->
        <li class="nav-item">
            <a class="nav-link">病患 <span class="arrow">▼</span></a>
            <ul class="dropdown-menu">
                <!-- 入院登記 (已進入 Admitted to the hospital 資料夾) -->
                <li class="dropdown-item"><a href="/test2/Admitted to the hospital/frame_data_patient.php">入院</a></li>
                <!-- <li class="dropdown-item"><a href="#">出院</a></li> -->
                <li class="dropdown-item"><a href="/test2/Patient List/frame_patient_list.php">病患列表</a></li>
                <li class="dropdown-item"><a href="/test2/Archive Management/frame_archive_management.php">封存管理</a></li>
            </ul>
        </li>

        <!-- 單一/下拉選單：裝置 -->
        <li class="nav-item">
            <a class="nav-link">裝置 <span class="arrow">▼</span></a>
            <!-- 未來若要開啟下拉功能，這裡的路徑也幫你加好絕對路徑了 -->
            <ul class="dropdown-menu">  
                <li class="dropdown-item"><a href="/test2/frame_device.php">醫療裝置</a></li>
                <li class="dropdown-item"><a href="/test2/frame_hub.php">設備與物料管理</a></li>
            </ul> 
        </li>

        <!-- 下拉選單：設定 -->
        <li class="nav-item">
            <a href="#" class="nav-link">設定 <span class="arrow">▼</span></a>
            <ul class="dropdown-menu">
                <li class="dropdown-item"><a href="/test2/frame_data_ward.php">系統設定</a></li>
                <li class="dropdown-item"><a href="/test2/nurses_login.php">人員管理</a></li>
            </ul>
        </li>
    </ul>
</nav>