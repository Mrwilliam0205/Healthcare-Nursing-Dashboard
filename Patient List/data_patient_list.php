<?php
    // 1. 商業邏輯區：建立資料庫連線與撈取「所有」資料
    require_once("../login_database/dbtools.inc.php");
    $link = create_connection();

    // 保留原始的相容性函數
    function mysqli_result($res, $row=0,$col=0){
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

    // 撈取全院病患資料，交給前端 JavaScript 去做即時過濾
    $sql = "SELECT DISTINCT memberlogin.IDP, memberlogin.name, warddata.ward, warddata.bed
            FROM warddata, memberlogin
            WHERE warddata.IDP = memberlogin.IDP
            ORDER BY warddata.ward ASC, warddata.bed ASC";

    $result = execute_sql($link, "wetness_database2", $sql);
    $num_rows = mysqli_num_rows($result);

    // 將資料庫結果轉換為 PHP 陣列，準備交給 JavaScript
    $patients_array = array();
    for($j = 0; $j <$num_rows; $j++){$patients_array[] = array(
            'idp'  => mysqli_result($result,$j, 0),
            'name' => mysqli_result($result,$j, 1),
            'ward' => mysqli_result($result,$j, 2),
            'bed'  => mysqli_result($result,$j, 3)
        );
    }
?>

<!-- 2. 畫面呈現區：單欄乾淨的 HTML 結構 -->
<div style="background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    
    <div class="page-header" style="margin-bottom: 20px;">
        <h2 class="page-title">住院中病患列表</h2>
    </div>

    <!-- 搜尋列與總筆數 -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #eee;">
        
        <div style="width: 50%; min-width: 300px;">
            <!-- 即時搜尋輸入框 -->
            <input type="text" id="searchInput" placeholder="搜尋病患姓名、身分證號或床位..." style="width: 100%; padding: 10px 15px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; outline: none; transition: border-color 0.2s;">
        </div>

        <div style="color: #555; font-size: 15px;">
            總筆數：<span id="totalCount" style="font-weight: bold; font-size: 18px; color: #333;"><?php echo $num_rows; ?></span> 人
        </div>
    </div>

    <!-- 病患列表區塊 (單欄設計，三項資訊) -->
    <div class="patienttable table-container">
        <table class="preview-table" style="width: 100%; text-align: left;">
            <thead>
                <tr>
                    <!-- 欄位擴充為三個，並分配寬度 -->
                    <th style="width: 30%; padding: 12px;">病房 (床號)</th>
                    <th style="width: 40%; padding: 12px;">身分證字號</th>
                    <th style="width: 30%; padding: 12px;">姓名</th>
                </tr>
            </thead>
            <tbody id="patientTableBody">
                <!-- 初始載入與搜尋結果，全部由下方 JavaScript 繪製 -->
            </tbody>
        </table>
    </div>
</div>

<!-- 3. 前端即時搜尋邏輯區 (JavaScript) -->
<script>
    // 將 PHP 撈出的陣列，無縫接軌轉成 JavaScript 陣列
    const allPatients = <?php echo json_encode($patients_array); ?>;
    
    // 取得 DOM 元素
    const searchInput = document.getElementById('searchInput');
    const patientTableBody = document.getElementById('patientTableBody');
    const totalCount = document.getElementById('totalCount');

    // 定義負責畫表格的函數
    function renderTable(keyword) {
        // 將關鍵字轉小寫，方便不分大小寫比對
        const lowerKeyword = keyword.toLowerCase().trim();
        
        // 1. 進行資料篩選
        const filteredPatients = allPatients.filter(patient => {
            const roomBed = patient.ward + '-' + patient.bed;
            return patient.name.toLowerCase().includes(lowerKeyword) || 
                   patient.idp.toLowerCase().includes(lowerKeyword) || 
                   roomBed.toLowerCase().includes(lowerKeyword);
        });

        // 2. 即時更新總筆數
        totalCount.innerText = filteredPatients.length;

        // 3. 開始組合新的 HTML 結構
        let htmlContent = '';
        
        if (filteredPatients.length === 0) {
            // 注意這裡 colspan 變成 3 (因為有三個欄位)
            htmlContent = "<tr><td colspan='3' style='text-align: center; padding: 30px; color: #888;'>查無符合條件的病患資料</td></tr>";
        } else {
            // 一筆資料畫一列 (<tr>)，裡面放三個 <td>
            for (let i = 0; i < filteredPatients.length; i++) {
                let p = filteredPatients[i];
                htmlContent += "<tr style='border-bottom: 1px solid #eee;'>";
                htmlContent += `<td class='data' style='padding: 12px;'>${p.ward} - ${p.bed}</td>`;
                // 新增的身分證欄位
                htmlContent += `<td class='data middle' style='padding: 12px;'>${p.idp}</td>`;
                // 原本的姓名欄位 (保留超連結功能)
                htmlContent += `<td class='data middle' style='padding: 12px;'><a href='../shared/frame_patient.php?IDP=${p.idp}' style='color: var(--primary-blue, #0056b3); font-weight: bold; text-decoration: none;'>${p.name}</a></td>`;
                htmlContent += "</tr>";
            }
        }
        
        // 將組合好的 HTML 寫入表格主體中
        patientTableBody.innerHTML = htmlContent;
    }

    // 網頁剛載入時，先畫出全部資料 (傳入空字串)
    renderTable('');

    // 監聽輸入框的「打字 (input)」事件
    searchInput.addEventListener('input', function(e) {
        renderTable(e.target.value);
    });
</script>