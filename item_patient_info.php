<?php
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	
	$link = create_connection();

	//取得病患 IDP (相容 GET 與 POST 傳遞)
	$IDP = isset($_REQUEST['IDP']) ? $_REQUEST['IDP'] : '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_nurses') {
		$nurses = isset($_POST['nurses']) ? $_POST['nurses'] : array();

		// 刪除該病患原本舊的護理人員紀錄
		$delete_sql = "DELETE FROM `patient_nurse_mapping` WHERE IDP = '$IDP'";
		execute_sql($link, "wetness_database2", $delete_sql);

		// 迴圈將前端勾選的新護理人員寫入資料庫
		if (!empty($nurses)) {
			foreach ($nurses as $nurse_username) {
				if (trim($nurse_username) != "") { // 確保不是空選單
					$insert_sql = "INSERT INTO `patient_nurse_mapping` (IDP, nurse_username) VALUES ('$IDP', '$nurse_username')";
					execute_sql($link, "wetness_database2", $insert_sql);
				}
			}
		}
	}

	//讀取病患基本資料
	$sql = "SELECT DISTINCT memberlogin.IDP, memberlogin.name,  memberlogin.birthday, memberlogin.sex, memberlogin.blood, warddata.ward, warddata.bed
					FROM memberlogin, warddata
					WHERE memberlogin.IDP = '$IDP' AND warddata.IDP='$IDP'";
	$result = execute_sql($link, "wetness_database2", $sql);
	$row_result = mysqli_fetch_array($result);

	$name = $row_result["name"];
	$birthday = $row_result["birthday"];
	$sex = $row_result["sex"];
	$blood = $row_result["blood"];
	$ward = $row_result["ward"];
	$bed = $row_result["bed"];

	//讀取護理人員選單資料，撈出所有護理人員帳號
	$nurse_sql = "SELECT username FROM `nurses_account`";
	$nurse_result = execute_sql($link, "wetness_database2", $nurse_sql);
	$nurses_list = array();
	while($n_row = mysqli_fetch_array($nurse_result)) {
		$nurses_list[] = $n_row['username'];
	}

	// 撈出此病患當前已經綁定的護理人員
	$current_nurses = array();
	$mapping_sql = "SELECT nurse_username FROM `patient_nurse_mapping` WHERE IDP = '$IDP'";
	$mapping_result = execute_sql($link, "wetness_database2", $mapping_sql);
	while($m_row = mysqli_fetch_array($mapping_result)) {
		$current_nurses[] = $m_row['nurse_username'];
	}

	$url = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<link rel="stylesheet" href="CSS/left_side.css">
		
		<style>
			/* --- 負責護理人員區塊的排版樣式 --- */
			#nurse-selectors {
				display: flex;
				flex-direction: column;
				align-items: center; /* 讓下拉選單整個區塊置中 */
				margin-top: 8px;
			}
			.nurse-select-group {
				margin-bottom: 8px;
				display: flex;
				align-items: center;
				justify-content: center; /* 讓選單與加減按鈕在行內置中 */
				width: 100%;
			}
			.nurse-select-group select {
				margin-right: 5px;
				padding: 3px 5px;
				border-radius: 4px;
				border: none;
				width: 100px; /* 固定寬度，讓多個選單對齊 */
				text-align: center;
				background-color: #fff;
			}
			.btn-add, .btn-remove {
				cursor: pointer;
				padding: 2px 8px;
				background-color: #e0e0e0;
				border: 1px solid #ccc;
				border-radius: 3px;
				font-weight: bold;
			}
			.btn-submit {
				margin-top: 5px;
				margin-bottom: 10px;
				padding: 5px 15px;
				background-color: #00c1c1; /* 調整成相近的藍綠色 */
				color: white;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
				letter-spacing: 2px;
			}
			.btn-submit:hover { background-color: #00a0a0; }
			
			.action-buttons {
				text-align: center;
				padding-top: 10px;
				padding-bottom: 10px;
			}
		</style>
	</head>

	<body>
		<div class="icon">
			<img src="pictureitem_user.png" alt="">
		</div>
		<div class="display_toggle">
			<table class="patient_data">
				<tr><th>姓名</th></tr>
				<tr><td><?php echo $name;?></td></tr>
				
				<tr><th>身分證字號</th></tr>
				<tr><td><?php echo $IDP;?></td></tr>
				
				<tr><th>生日</th></tr>
				<tr><td><?php echo $birthday;?></td></tr>
				
				<tr><th>性別</th></tr>
				<tr>
					<td><?php echo ($sex == "0") ? "女" : "男"; ?></td>
				</tr>
				
				<tr><th>血型</th></tr>
				<tr><td><?php echo $blood;?></td></tr>

				<tr>
					<th>負責護理人員</th>
				</tr>
				<tr>
					<td style="text-align: center;">
						<form action="" method="POST" style="margin: 0;">
							<input type="hidden" name="IDP" value="<?php echo $IDP; ?>">
							<input type="hidden" name="action" value="save_nurses">
							
							<div id="nurse-selectors">
								<?php 
								// 若無紀錄，預設給一組空白選單
								if (empty($current_nurses)) {
									$current_nurses = array("");
								}
								
								// 根據現有的負責人員數量產出選單
								foreach ($current_nurses as $index => $current_nurse) { 
								?>
									<div class="nurse-select-group">
										<select name="nurses[]">
											<option value="">選擇人員</option>
											<?php foreach($nurses_list as $nurse) { ?>
												<option value="<?php echo $nurse; ?>" <?php if($nurse == $current_nurse) echo 'selected'; ?>>
													<?php echo $nurse; ?>
												</option>
											<?php } ?>
										</select>
										
										<?php if ($index === 0) { ?>
											<button type="button" class="btn-add">+</button>
										<?php } else { ?>
											<button type="button" class="btn-remove">-</button>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
							<button type="submit" class="btn-submit">儲存人員</button>
						</form>
					</td>
				</tr>

				<tr>
					<td class="action-buttons">
						<?php
							if (stripos($url, "history")){
								echo "<a href='frame_patient.php?IDP=".$IDP."'>回上一頁</a>";
							} else{
								echo "<a href='frame_data_ward_change.php?IDP=".$IDP."'>更換床位</a>  ";
								echo "<a href='process_data_member_discharged.php?IDP=". $IDP."&ward=".$ward."&bed=". $bed."' onclick=\"return confirm('確認該病人出院嗎?');\">出 院</a>";
							}
						?>
					</td>
				</tr>
			</table>
		</div>

		<script>
			$(document).ready(function() {
				// 點擊 "+" 號
				$("#nurse-selectors").on('click', '.btn-add', function() {
					var newGroup = $(this).closest('.nurse-select-group').clone();
					newGroup.find('select').val('');
					newGroup.find('.btn-add').removeClass('btn-add').addClass('btn-remove').text('-');
					$("#nurse-selectors").append(newGroup);
				});

				// 點擊 "-" 號
				$("#nurse-selectors").on('click', '.btn-remove', function() {
					$(this).closest('.nurse-select-group').remove();
				});
			});
		</script>
	</body>
</html>