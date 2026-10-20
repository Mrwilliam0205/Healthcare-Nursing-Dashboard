<?php
	require_once("time.php");
	require_once("../../login_database/dbtools.inc.php");
	$link = create_connection();

	$arr = array();
	$IDP = $_GET['IDP'];
	$sql_breath_first = "SELECT valueimmediate.breath
					FROM tag , valueimmediate
					WHERE tag.owner= '$IDP' AND tag.tagmac= valueimmediate.mac_address AND(tag.lastPID = '05' OR tag.lastPID = '06') ORDER BY valueimmediate.time DESC";
	$result_breath_first = execute_sql($link, "wetness_database", $sql_breath_first);

	$count = 0;
	while($count < 1) {
		$row_result_breath = mysqli_fetch_assoc($result_breath_first);

		// 【新增防呆判斷】：檢查有沒有撈到資料
		if ($row_result_breath) {
			// 有資料，正常讀取
			$breath_value = $row_result_breath["breath"];
		} else {
			// 沒資料，給予預設值 0，避免 PHP 報錯破壞 JSON
			$breath_value = 0; 
		}

		$arr[] = array('breath' => $breath_value);
		$count++;
	}
	
	echo json_encode($arr);
?>