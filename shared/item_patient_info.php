<?php
	require_once("../login_database/dbtools.inc.php");
	$link = create_connection();

	//取得病患 IDP
	$IDP = isset($_REQUEST['IDP']) ?$_REQUEST['IDP'] : '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_nurses') {$nurses = isset($_POST['nurses']) ?$_POST['nurses'] : array();

		$delete_sql = "DELETE FROM `patient_nurse_mapping` WHERE IDP = '$IDP'";
		execute_sql($link, "wetness_database", $delete_sql);

		if (!empty($nurses)) {
			foreach ($nurses as$nurse_username) {
				if (trim($nurse_username) != "") { 
					$insert_sql = "INSERT INTO `patient_nurse_mapping` (IDP, nurse_username) VALUES ('$IDP', '$nurse_username')";
					execute_sql($link, "wetness_database", $insert_sql);
				}
			}
		}
	}

	//讀取病患基本資料
	$sql = "SELECT DISTINCT memberlogin.IDP, memberlogin.name,  memberlogin.birthday, memberlogin.sex, memberlogin.blood, warddata.ward, warddata.bed
					FROM memberlogin, warddata
					WHERE memberlogin.IDP = '$IDP' AND warddata.IDP='$IDP'";
	$result = execute_sql($link, "wetness_database", $sql);
	$row_result = mysqli_fetch_array($result);

	$name =$row_result["name"];
	$birthday =$row_result["birthday"];
	$sex =$row_result["sex"];
	$blood =$row_result["blood"];
	$ward =$row_result["ward"];
	$bed =$row_result["bed"];

	//讀取護理人員選單資料
	$nurse_sql = "SELECT username FROM `nurses_account`";
	$nurse_result = execute_sql($link, "wetness_database", $nurse_sql);$nurses_list = array();
	while($n_row = mysqli_fetch_array($nurse_result)) {
		$nurses_list[] =$n_row['username'];
	}

	// 撈出此病患當前已經綁定的護理人員
	$current_nurses = array();$mapping_sql = "SELECT nurse_username FROM `patient_nurse_mapping` WHERE IDP = '$IDP'";
	$mapping_result = execute_sql($link, "wetness_database", $mapping_sql);
	while($m_row = mysqli_fetch_array($mapping_result)) {
		$current_nurses[] =$m_row['nurse_username'];
	}

	$url =$_SERVER['PHP_SELF'];
?>

<!-- ========================================== -->
<!-- 新版 HTML 結構：病患基本資料側邊欄 -->
<!-- ========================================== -->

<!-- 病患大頭貼 -->
<div style="text-align: center; padding: 20px 0 10px 0;">
    <img src="../picture/item_user.png" alt="User Icon" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--border-color);">
</div>

<div class="sidebar-section-title">基本資料</div>

<div style="padding: 0 15px;">
    <!-- 改用 Flex 排版的清單 -->
    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
            <span style="color: var(--text-muted); font-size: 0.9rem;">姓名</span>
            <span style="font-weight: bold; color: var(--text-dark);"><?php echo $name;?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
            <span style="color: var(--text-muted); font-size: 0.9rem;">身分證字號</span>
            <span style="font-weight: bold; color: var(--text-dark);"><?php echo $IDP;?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
            <span style="color: var(--text-muted); font-size: 0.9rem;">生日</span>
            <span style="font-weight: bold; color: var(--text-dark);"><?php echo $birthday;?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
            <span style="color: var(--text-muted); font-size: 0.9rem;">性別</span>
            <span style="font-weight: bold; color: var(--text-dark);"><?php echo ($sex == "0") ? "女" : "男"; ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
            <span style="color: var(--text-muted); font-size: 0.9rem;">血型</span>
            <span style="font-weight: bold; color: var(--text-dark);"><?php echo $blood;?></span>
        </div>
    </div>
</div>

<div class="sidebar-section-title">負責護理人員</div>

<div style="padding: 0 15px;">
    <form action="" method="POST" style="margin: 0;">
        <input type="hidden" name="IDP" value="<?php echo $IDP; ?>">
        <input type="hidden" name="action" value="save_nurses">
        
        <div id="nurse-selectors" style="display: flex; flex-direction: column; gap: 8px;">
            <?php 
            if (empty($current_nurses)) {$current_nurses = array("");
            }
            
            foreach ($current_nurses as $index =>$current_nurse) { 
            ?>
                <div class="nurse-select-group" style="display: flex; gap: 5px; align-items: center;">
                    <!-- 套用新版 form-input 類別 -->
                    <select name="nurses[]" class="form-input" style="flex: 1; padding: 6px;">
                        <option value="">選擇人員</option>
                        <?php foreach($nurses_list as$nurse) { ?>
                            <option value="<?php echo $nurse; ?>" <?php if($nurse ==$current_nurse) echo 'selected'; ?>>
                                <?php echo $nurse; ?>
                            </option>
                        <?php } ?>
                    </select>
                    
                    <?php if ($index === 0) { ?>
                        <button type="button" class="btn btn-primary btn-add" style="padding: 6px 12px; font-weight: bold;">+</button>
                    <?php } else { ?>
                        <button type="button" class="btn btn-secondary btn-remove" style="padding: 6px 12px; font-weight: bold;">-</button>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">儲存人員</button>
    </form>
</div>

<!-- 底部動作按鈕區 (語法錯誤已修復，雙引號改為單引號) -->
<div style="padding: 20px 15px; margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
    <?php
        if (stripos($url, "history")){
            echo "<a href='frame_patient.php?IDP=".$IDP."' class='btn btn-secondary' style='text-align: center; text-decoration: none;'>回上一頁</a>";
        } else{
            echo "<a href='../frame_data_ward_change.php?IDP=".$IDP."' class='btn btn-secondary' style='text-align: center; text-decoration: none;'>更換床位</a>";
            echo "<a href='../process_data_member_discharged.php?IDP=". $IDP."&ward=".$ward."&bed=". $bed."' onclick=\"return confirm('確認該病人出院嗎?');\" class='btn btn-danger' style='text-align: center; text-decoration: none; background-color: var(--danger-color); color: white;'>出 院</a>";
        }
    ?>
</div>

<!-- 保留 jQuery 邏輯 -->
<script>
    $(document).ready(function() {$("#nurse-selectors").on('click', '.btn-add', function() {
            var newGroup = $(this).closest('.nurse-select-group').clone();
            newGroup.find('select').val('');
            
            // 替換按鈕外觀
            var addBtn = newGroup.find('.btn-add');
            addBtn.removeClass('btn-add btn-primary')
                  .addClass('btn-remove btn-secondary')
                  .text('-');
                  
            $("#nurse-selectors").append(newGroup);
        });

        $("#nurse-selectors").on('click', '.btn-remove', function() {
            $(this).closest('.nurse-select-group').remove();
        });
    });
</script>