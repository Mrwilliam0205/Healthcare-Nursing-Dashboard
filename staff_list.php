<?php
session_start();
if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit;
}

header("Content-Type:text/html; charset=utf-8");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
$link = create_connection();

$sql = "SELECT * FROM `nurses_account` ORDER BY `emp_id` ASC";
$result = execute_sql($link, "wetness_database2", $sql);

$is_superadmin = ($_SESSION['role'] === 'superadmin');
$is_admin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>智慧護理系統 - 人員列表</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="components/NavbarComponent.js" defer></script>
</head>
<body>
  <app-navbar active="settings"></app-navbar>
  
  <?php include ('menubar.php'); ?>
  
  <div class="app-container">
    <aside class="sidebar">
      <div class="sidebar-section-title">護理人員管理</div>
      <ul class="sidebar-menu">
        <li><a href="staff_list.php" class="sidebar-link active"><span>📋 人員列表</span></a></li>
        
        <?php if ($is_admin): ?>
        <li><a href="nurses_register.php" class="sidebar-link"><span>➕ 註冊人員</span></a></li>
        <?php endif; ?>
        
        <li><a href="ip_management.php" class="sidebar-link"><span>🌐 IP 管理</span></a></li>
      </ul>
    </aside>

    <main class="main-content">
      <section id="section-list" class="content-section" style="display: block;">
        <div class="page-header">
          <h1 class="page-title">護理人員列表</h1>
          <?php if ($is_admin): ?>
          <button class="btn btn-primary" onclick="location.href='nurses_register.php'">
            <i class="fa-solid fa-user-plus"></i> 前往註冊人員
          </button>
          <?php endif; ?>
        </div>

        <div class="staff-card">
          <div class="table-container">
            <table class="staff-table">
              <thead>
                <tr>
                  <th>編號</th>
                  <th>姓名</th>
                  <th>帳號</th>
                  <th>密碼</th>
                  <th>權限</th>
                  <th style="width: 160px; text-align: center;">操作</th>
                </tr>
              </thead>
              <tbody id="staffTableBody">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $is_self = ($_SESSION['emp_id'] === $row['emp_id']);
                        $target_role = $row['role'];
                        
                        $role_display = '一般人員';
                        if ($target_role === 'superadmin') $role_display = '最高管理員';
                        else if ($target_role === 'admin') $role_display = '管理員';
                        
                        echo '<tr data-id="' . htmlspecialchars($row['emp_id']) . '">';
                        echo '  <td><strong class="col-id">' . htmlspecialchars($row['emp_id']) . '</strong></td>';
                        echo '  <td class="col-name">' . htmlspecialchars($row['name']) . '</td>';
                        echo '  <td class="col-acc">' . htmlspecialchars($row['username']) . '</td>';
                        echo '  <td class="col-pwd">••••••••</td>';
                        echo '  <td class="col-role" data-role="' . htmlspecialchars($target_role) . '">' . $role_display . '</td>';
                        echo '  <td style="text-align: center;">';
                        
                        if ($is_superadmin) {
                            echo '    <button class="btn btn-primary btn-sm" onclick="openEditModal(this)">修改</button>';
                            if (!$is_self) {
                                echo '    <button class="btn btn-outline-danger btn-sm" onclick="deleteStaff(this)">刪除</button>';
                            }
                        } else if ($is_admin) {
                            if ($target_role === 'user' || $is_self) {
                                echo '    <button class="btn btn-primary btn-sm" onclick="openEditModal(this)">修改</button>';
                                echo '    <button class="btn btn-outline-danger btn-sm" onclick="deleteStaff(this)">刪除</button>';
                            }
                        } else if ($is_self) {
                            // 將原本的「修改密碼」改為「修改」，一般人員也能修改自己
                            echo '    <button class="btn btn-primary btn-sm" onclick="openEditModal(this)">修改</button>';
                        }
                        
                        echo '  </td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center;">目前尚無人員資料</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div class="modal-overlay" id="editModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title">修改資料</h3>
        <button class="modal-close" onclick="closeEditModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <!-- 編號依然限制管理員才能改 -->
          <label class="form-label">編號</label>
          <input type="text" id="editId" class="form-input" <?php echo !$is_admin ? 'readonly style="background:#e2e8f0;"' : ''; ?>>
        </div>
        <div class="form-group">
          <!-- 解除姓名的 readonly 限制 -->
          <label class="form-label">姓名</label>
          <input type="text" id="editName" class="form-input">
        </div>
        <div class="form-group">
          <!-- 解除帳號的 readonly 限制 -->
          <label class="form-label">帳號</label>
          <input type="text" id="editAcc" class="form-input">
        </div>
        <div class="form-group">
          <!-- 權限依然限制管理員才能改 -->
          <label class="form-label">權限</label>
          <select id="editRole" class="form-input" <?php echo !$is_admin ? 'disabled style="background:#e2e8f0;"' : ''; ?>>
            <option value="user">一般人員</option>
            <option value="admin">管理員</option>
            <?php if ($is_superadmin): ?>
            <option value="superadmin">最高管理員</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">新密碼</label>
          <input type="password" id="editPwd" class="form-input" placeholder="若不修改請留空">
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn" style="background-color: #e2e8f0; color: #334155;" onclick="closeEditModal()">取消</button>
        <button class="btn btn-primary" onclick="saveEdit()">儲存修改</button>
      </div>
    </div>
  </div>

  <script>
    let currentEditRow = null;
    let oldEmpId = '';

    function deleteStaff(btn) {
      const row = btn.closest('tr');
      const name = row.querySelector('.col-name').innerText;
      const emp_id = row.querySelector('.col-id').innerText;

      if (confirm(`確定要刪除人員「${name}」嗎？此動作無法復原！`)) {
        const formData = new FormData();
        formData.append('emp_id', emp_id);

        fetch('nurses_delete.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
          if (data === 'success') {
            row.remove();
            alert(`✅ 已成功刪除人員「${name}」！`);
            if (emp_id === '<?php echo $_SESSION['emp_id']; ?>') {
                location.href = 'nurses_login.php';
            }
          } else { alert('❌ 刪除失敗：' + data); }
        })
        .catch(error => alert('❌ 系統連線發生錯誤！'));
      }
    }

    function openEditModal(btn) {
      currentEditRow = btn.closest('tr');
      oldEmpId = currentEditRow.querySelector('.col-id').innerText;
      const currentRole = currentEditRow.querySelector('.col-role').getAttribute('data-role');
      
      document.getElementById('editId').value = oldEmpId;
      document.getElementById('editName').value = currentEditRow.querySelector('.col-name').innerText;
      document.getElementById('editAcc').value = currentEditRow.querySelector('.col-acc').innerText;
      
      const roleSelect = document.getElementById('editRole');
      roleSelect.value = currentRole;
      
      if (currentRole === 'superadmin') {
        document.getElementById('editId').setAttribute('readonly', 'readonly');
        document.getElementById('editId').style.background = '#e2e8f0';
        roleSelect.setAttribute('disabled', 'disabled');
        roleSelect.style.background = '#e2e8f0';
      } else {
        <?php if ($is_admin): ?>
        document.getElementById('editId').removeAttribute('readonly');
        document.getElementById('editId').style.background = '#fff';
        roleSelect.removeAttribute('disabled');
        roleSelect.style.background = '#fff';
        <?php endif; ?>
      }

      document.getElementById('editPwd').value = '';
      document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.remove('active');
    }

    function saveEdit() {
      if (!currentEditRow) return;

      const newId = document.getElementById('editId').value;
      const newName = document.getElementById('editName').value;
      const newAcc = document.getElementById('editAcc').value;
      const newRole = document.getElementById('editRole').disabled 
        ? currentEditRow.querySelector('.col-role').getAttribute('data-role') 
        : document.getElementById('editRole').value;
      const newPwd = document.getElementById('editPwd').value;

      const formData = new FormData();
      formData.append('old_emp_id', oldEmpId);
      formData.append('emp_id', newId);
      formData.append('name', newName);
      formData.append('username', newAcc);
      formData.append('role', newRole);
      formData.append('password', newPwd);

      fetch('nurses_update.php', { method: 'POST', body: formData })
      .then(response => response.text())
      .then(data => {
        if (data === 'success') {
          currentEditRow.querySelector('.col-id').innerText = newId;
          currentEditRow.querySelector('.col-name').innerText = newName;
          currentEditRow.querySelector('.col-acc').innerText = newAcc;
          
          const roleCell = currentEditRow.querySelector('.col-role');
          roleCell.setAttribute('data-role', newRole);
          let displayRole = '一般人員';
          if (newRole === 'superadmin') displayRole = '最高管理員';
          else if (newRole === 'admin') displayRole = '管理員';
          roleCell.innerText = displayRole;

          if (newPwd.trim() !== '') {
            currentEditRow.querySelector('.col-pwd').innerText = '•'.repeat(newPwd.length);
          }

          currentEditRow.setAttribute('data-id', newId);
          closeEditModal();
          alert('✅ 資料已成功更新！');
        } else { alert('❌ 更新失敗：' + data); }
      })
      .catch(error => alert('❌ 系統連線發生錯誤！'));
    }
  </script>
</body>
</html>
<?php mysqli_close($link); ?>