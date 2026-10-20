<?php
require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");

// 確保連線變數存在
if (!isset($link)) {$link = create_connection();
}
$database = 'wetness_database2';$overdue_list_file = "discharged_patient_data/overdue_patients.json";

// ==========================================
// 1. 商業邏輯區 (保留不動)
// ==========================================
if (isset($_POST['archive_action'])) {
    $safe_idp = mysqli_real_escape_string($link, $_POST['archive_idp']);$action = $_POST['archive_action'];$target_table = "`{$safe_idp}_valuehistory`";
    $three_months_ago = date('Y-m-d H:i:s', strtotime('-1 days')); 

    if ($action === 'export') {$check_queue = execute_sql($link,$database, "SELECT task_id FROM export_tasks WHERE patient_id = '$safe_idp' AND status IN ('pending', 'processing')");

        if (mysqli_num_rows($check_queue) == 0) {$insert_task = "INSERT INTO export_tasks (patient_id, status, progress) VALUES ('$safe_idp', 'pending', 0)";
            execute_sql($link, $database,$insert_task);
            echo "<script>alert('已成功提交打包申請！\\n系統已在背景開始處理，請稍後重新整理網頁查看進度或下載連結。');</script>";
        } else {
            echo "<script>alert('該病患的資料已經在打包佇列中，請勿重複提交。');</script>";
        }
    }

    if ($action === 'delete') {$check_table = execute_sql($link,$database, "SHOW TABLES LIKE '{$safe_idp}_valuehistory'");
        if (mysqli_num_rows($check_table) > 0) {$delete_sql = "DELETE FROM {$target_table} WHERE time <= '$three_months_ago'";
            execute_sql($link, $database,$delete_sql);
            if (file_exists($overdue_list_file)) {
                $overdue_list = json_decode(file_get_contents($overdue_list_file), true);
                if (($key = array_search($safe_idp,$overdue_list)) !== false) {
                    unset($overdue_list[$key]);
                    file_put_contents($overdue_list_file, json_encode(array_values($overdue_list)));
                }
            }
            echo "<script>alert('刪除成功！已清空超過 3 個月的歷史資料。');</script>";
        }
    }

    if ($action === 'finish') {$clear_task_sql = "DELETE FROM export_tasks WHERE patient_id = '$safe_idp' AND status = 'completed'";
        execute_sql($link, $database,$clear_task_sql);

        if (file_exists($overdue_list_file)) {
            $overdue_list = json_decode(file_get_contents($overdue_list_file), true);
            if (($key = array_search($safe_idp,$overdue_list)) !== false) {
                unset($overdue_list[$key]);
                file_put_contents($overdue_list_file, json_encode(array_values($overdue_list)));
            }
        }
        echo "<script>alert('已將病患從封存名單中移除。');</script>";
    }
}
?>

<!-- ========================================== -->
<!-- 2. 畫面呈現區 (全面套用新版 UI 元件) -->
<!-- ========================================== -->
<div style="background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    
    <!-- 標題與說明 -->
    <div class="page-header" style="margin-bottom: 10px; justify-content: center;">
        <h2 class="page-title" style="color: var(--danger-color);">系統封存管理</h2>
    </div>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 24px; font-size: 0.95rem;">
        以下病患的個人歷史數據已累積超過 6 個月，請選擇是否將 <b>超過 3 個月前</b> 的資料打包或刪除
    </p>

    <?php
    if (file_exists($overdue_list_file)) {
        $overdue_list = json_decode(file_get_contents($overdue_list_file), true);
        if (!empty($overdue_list)) {
            
            // --- 資料準備 ---
            $patient_data = [];
            foreach ($overdue_list as $pid) {$patient_name = "未知姓名";
                $patient_ward = "未安排";
                $patient_bed = "";

                $name_res = execute_sql($link,$database, "SELECT name FROM memberlogin WHERE IDP = '$pid' LIMIT 1");
                if ($name_res && mysqli_num_rows($name_res) > 0) {
                    $row = mysqli_fetch_assoc($name_res);
                    if (!empty($row['name'])) $patient_name =$row['name'];
                }

                $ward_res = execute_sql($link,$database, "SELECT ward, bed FROM warddata WHERE IDP = '$pid' LIMIT 1");
                if ($ward_res && mysqli_num_rows($ward_res) > 0) {
                    $row = mysqli_fetch_assoc($ward_res);
                    if (!empty($row['ward'])) $patient_ward =$row['ward'];
                    if (!empty($row['bed'])) $patient_bed =$row['bed'];
                }

                $ward_bed_display = $patient_ward . (!empty($patient_bed) && $patient_ward !== "未安排" ? " - " . $patient_bed : "");

                $patient_data[] = [
                    'idp' => $pid,
                    'name' => $patient_name,
                    'ward_bed' => $ward_bed_display
                ];
            }

            usort($patient_data, function($a,$b) {
                return strcmp($a['ward_bed'],$b['ward_bed']);
            });

            // --- 輸出表格 ---
            echo "<div class='table-container'>";
            echo "<table class='preview-table'>";
            echo "<thead>
                    <tr>
                        <th style='width: 25%;'>病房/床號</th>
                        <th style='width: 25%;'>姓名</th>
                        <th style='width: 50%; text-align: right;'>操作選項</th>
                    </tr>
                  </thead>
                  <tbody>";
            
            foreach ($patient_data as$patient) {
                $pid = htmlspecialchars($patient['idp']);
                $ward_bed = htmlspecialchars($patient['ward_bed']);
                $name = htmlspecialchars($patient['name']);

                // === 查詢背景任務狀態 ===
                $task_status = null;
                $task_progress = 0;
                $file_path = null;
                
                $task_sql = "SELECT status, progress, file_path FROM export_tasks WHERE patient_id = '$pid' ORDER BY task_id DESC LIMIT 1";
                $task_res = execute_sql($link, $database,$task_sql);
                if ($task_res && mysqli_num_rows($task_res) > 0) {
                    $t_row = mysqli_fetch_assoc($task_res);
                    $task_status =$t_row['status'];
                    $task_progress =$t_row['progress'];
                    $file_path =$t_row['file_path'];
                }

                echo "<tr>";
                echo "<td><strong>{$ward_bed}</strong></td>";
                echo "<td><strong>{$name}</strong></td>";
                
                // 套用 flexbox 讓右側按鈕整齊排列
                echo "<td style='display: flex; justify-content: flex-end; gap: 10px; align-items: center;'>";

                // === 依據任務狀態套用新版 UI 按鈕 ===
                if ($task_status === 'pending') {                     echo "<span style='padding: 0.35rem 0.65rem; background-color: #f1f5f9; color: var(--text-muted); border-radius: 6px; font-weight: 600; font-size: 0.8rem;'>⏳ 等待背景執行...</span>";                 } elseif ($task_status === 'processing') {
                    echo "<span style='padding: 0.35rem 0.65rem; background-color: #fef08a; color: #854d0e; border-radius: 6px; font-weight: 600; font-size: 0.8rem;'>⚙️ 打包中 ({$task_progress}%)</span>";
                } elseif ($task_status === 'completed' && !empty($file_path)) {
                    // 下載按鈕 (套用 btn-excel 樣式)
                    echo "<a href='{$file_path}' download class='btn btn-excel btn-sm'>📥 下載檔案</a>";
                    
                    // 結案按鈕
                    echo "<form method='POST' style='margin: 0;'>
                            <input type='hidden' name='archive_idp' value='{$pid}'>
                            <input type='hidden' name='archive_action' value='finish'>
                            <button type='submit' class='btn btn-sm' style='background-color: var(--text-muted); color: white;' onclick=\"return confirm('確定要將 {$name} 從清單中移除嗎？(已預設一份存進病患資料夾內)。');\">結案並清除</button>
                          </form>";
                } else {
                    // 打包排程按鈕 (套用 btn-primary)
                    echo "<form method='POST' style='margin: 0;'>
                            <input type='hidden' name='archive_idp' value='{$pid}'>
                            <input type='hidden' name='archive_action' value='export'>
                            <button type='submit' class='btn btn-primary btn-sm' onclick=\"return confirm('即將把 {$name} 提交至背景打包佇列，確定執行嗎？');\">加入打包排程</button>
                        </form>";
                    
                    // 刪除按鈕 (套用 btn-outline-danger)
                    echo "<form method='POST' style='margin: 0;'>
                            <input type='hidden' name='archive_idp' value='{$pid}'>
                            <input type='hidden' name='archive_action' value='delete'>
                            <button type='submit' class='btn btn-outline-danger btn-sm' onclick=\"return confirm('警告：確定要直接刪除 {$name} 超過3個月的資料嗎？此動作無法復原！');\">直接刪除</button>
                        </form>";
                }
                
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";

        } else {
            echo "<h3 style='text-align: center; color: var(--success-color); margin-top: 40px;'>目前沒有需要封存的病患資料。</h3>";
        }
    } else {
        echo "<h3 style='text-align: center; color: var(--success-color); margin-top: 40px;'>目前沒有需要封存的病患資料。</h3>";
    }
    ?>
    
    <!-- ========================================== -->
    <!-- 3. 前端腳本：自動重整檢查背景任務 -->
    <!-- ========================================== -->
    <?php
        $check_active_sql = "SELECT COUNT(*) as active_count FROM export_tasks WHERE status IN ('pending', 'processing')";
        $active_res = execute_sql($link, $database,$check_active_sql);
        $active_row = mysqli_fetch_assoc($active_res);
        $has_active_tasks = ($active_row['active_count'] > 0);

        if ($has_active_tasks) {
            echo "
            <script>
                setTimeout(function() {
                    window.location.href = window.location.href.split('?')[0];
                }, 5000);
            </script>
            ";
        }
    ?>
</div>