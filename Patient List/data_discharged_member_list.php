<?php
    // ==========================================
    // 1. 商業邏輯區：資料庫連線與資料整理
    // ==========================================
    require_once("../login_database/dbtools.inc.php");
    $link = create_connection();
    mysqli_select_db($link, 'wetness_database2');

    // 查詢 discharged_patient 表中的資料
    $sql = "SELECT dp.IDP, dp.name, dp.discharge_date, dp.Admission_date,
            CASE WHEN ml.IDP IS NULL THEN '已出院' ELSE '住院中' END AS status
            FROM discharged_patient dp
            LEFT JOIN memberlogin ml ON dp.IDP = ml.IDP
            ORDER BY dp.IDP, dp.discharge_date ASC";

    $result = execute_sql($link, "wetness_database2", $sql);

    // 將資料整理成結構化的陣列，把同一個 IDP 的出入院紀錄統整在一起
    $patients = array();
    while ($row = mysqli_fetch_array($result)) {
        $idp = $row[0];
        if (!isset($patients[$idp])) {
            $patients[$idp] = array(
                'name' => $row[1],
                'status' => $row[4],
                'records' => array()
            );
        }
        // 將每一筆歷史紀錄推入該病患的 records 陣列中
        $patients[$idp]['records'][] = array(
            'discharge_date' => $row[2],
            'admission_date' => $row[3]
        );
    }
?>

<!-- ========================================== -->
<!-- 2. 畫面呈現區：乾淨的 HTML 結構與現代化搜尋框 -->
<!-- ========================================== -->
<div style="background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    
    <!-- 標題與搜尋列 (使用 Flexbox 左右排開) -->
    <div class="page-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <h2 class="page-title" style="margin: 0;">出院病患歷史列表</h2>
        
        <!-- 現代化搜尋框 -->
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="idpInput" onkeyup="searchPatients()" placeholder="搜尋身分證字號..." 
                   style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; outline: none;">
            <input type="text" id="nameInput" onkeyup="searchPatients()" placeholder="搜尋姓名..." 
                   style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; outline: none;">
        </div>
    </div>

    <!-- 歷史病患表格 -->
    <div class="patienttable table-container">
        <table class="preview-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 20%;">身分證字號</th>
                    <th style="width: 20%;">姓名</th>
                    <th style="width: 45%;">出院與入院日期 (歷史紀錄)</th>
                    <th style="width: 15%;">狀態</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    // 依照原邏輯：先顯示 "住院中"，再顯示 "已出院"
                    $display_order = array('住院中', '已出院');
                    
                    foreach ($display_order as $target_status) {
                        foreach ($patients as $idp => $data) {
                            if ($data['status'] === $target_status) {
                                
                                // 預設拿第一筆紀錄作為初始連結的參數
                                $first_discharge = $data['records'][0]['discharge_date'];
                                $first_admission = $data['records'][0]['admission_date'];
                                
                                echo "<tr>";
                                echo "<td>{$idp}</td>";
                                
                                // 帶有匯入功能的病患名稱連結
                                $link_url = "frame_discharged_patient.php?IDP={$idp}&status=" . urlencode($data['status']) . "&discharge_date=" . urlencode($first_discharge) . "&admission_date=" . urlencode($first_admission);
                                echo "<td><a href='{$link_url}' class='patientLink' style='color: var(--primary-blue); font-weight: bold; text-decoration: none;'>{$data['name']} (匯入)</a></td>";
                                
                                // 下拉選單：列出該病患所有的出入院紀錄
                                echo "<td>";
                                echo "<select name='discharge_and_admission_dates' onchange='updateLink(this)' style='width: 100%; padding: 6px; border: 1px solid var(--border-color); border-radius: 4px; font-family: inherit;'>";
                                foreach ($data['records'] as $record) {
                                    $opt_val = urlencode($record['discharge_date']) . "|" . urlencode($record['admission_date']);
                                    $opt_text = "出院日期：" . $record['discharge_date'] . "，入院日期：" . $record['admission_date'];
                                    echo "<option value='{$opt_val}'>{$opt_text}</option>";
                                }
                                echo "</select>";
                                echo "</td>";
                                
                                // 狀態標籤 (依照狀態給予不同顏色)
                                if ($data['status'] === '住院中') {
                                    echo "<td><span style='color: var(--success-color); font-weight: 600;'>{$data['status']}</span></td>";
                                } else {
                                    echo "<td><span style='color: var(--text-muted); font-weight: 600;'>{$data['status']}</span></td>";
                                }
                                
                                echo "</tr>";
                            }
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. 前端互動腳本 (保留並優化) -->
<!-- ========================================== -->
<script>
    // 搜尋功能 (比對 IDP 與 姓名)
    function searchPatients() {
        var idpInput = document.getElementById("idpInput").value.toUpperCase();
        var nameInput = document.getElementById("nameInput").value.toUpperCase();
        var table = document.getElementsByClassName('patienttable')[0];
        // 確保只抓 tbody 裡的 tr，避免把 thead 隱藏掉
        var rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");

        for (var i = 0; i < rows.length; i++) {
            var tdIDP = rows[i].getElementsByTagName("td")[0]; 
            var tdName = rows[i].getElementsByTagName("td")[1]; 
            if (tdIDP && tdName) {
                var idpText = tdIDP.textContent || tdIDP.innerText;
                var nameText = tdName.textContent || tdName.innerText;
                
                if (idpText.toUpperCase().indexOf(idpInput) > -1 && nameText.toUpperCase().indexOf(nameInput) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    }

    // 當下拉選單改變時，即時更新「姓名 (匯入)」超連結裡的 GET 參數
    function updateLink(select) {
        var vals = select.value.split('|');
        var discharge_date = vals[0];
        var admission_date = vals[1];
        
        var patientLink = select.parentNode.parentNode.getElementsByClassName('patientLink')[0];
        var currentHref = patientLink.getAttribute('href');
        
        var urlParts = currentHref.split('?');
        var baseUrl = urlParts[0];
        var queryParams = urlParts[1] ? urlParts[1].split('&') : [];

        var updatedParams = [];
        for (var i = 0; i < queryParams.length; i++) {
            var paramPair = queryParams[i].split('=');
            if (paramPair[0] !== 'discharge_date' && paramPair[0] !== 'admission_date') {
                updatedParams.push(queryParams[i]);
            }
        }
        updatedParams.push('discharge_date=' + discharge_date);
        updatedParams.push('admission_date=' + admission_date);

        var newHref = baseUrl + '?' + updatedParams.join('&');
        patientLink.setAttribute('href', newHref);
    }
</script>