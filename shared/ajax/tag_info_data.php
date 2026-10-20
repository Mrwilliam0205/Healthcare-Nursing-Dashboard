<?php
	require_one("time.php");
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
	$arr = array();
  $IDP=$_GET['IDP'];
	$sql_wet="SELECT valueimmediate.wet , valueimmediate.time
						FROM mac_correspond , valueimmediate
						WHERE mac_correspond.IDP= '$IDP' AND mac_correspond.mac_address= valueimmediate.mac_address AND mac_correspond.id like '02%'";

	$sql_temp="SELECT valueimmediate.bodytemp , valueimmediate.time
						 FROM mac_correspond , valueimmediate
						 WHERE mac_correspond.IDP= '$IDP' AND mac_correspond.mac_address= valueimmediate.mac_address AND mac_correspond.id like '03%'";

	$sql_nfc_temp = "SELECT * FROM `nursing_temperature`
									 WHERE `Patient` = '$IDP' ORDER BY `Time` DESC LIMIT 1";

	$sql_nfc_bp = "SELECT * FROM `nursing_bloodpressure`
								 WHERE `Patient` = '$IDP' ORDER BY `Time` DESC LIMIT 1";    

	$result_wet = execute_sql($link,"wetness_database",$sql_wet);
	$result_temp = execute_sql($link,"wetness_database",$sql_temp);
	$result_nfc_temp = execute_sql($link,"nfc_hub",$sql_nfc_temp);
	$result_nfc_bp = execute_sql($link,"nfc_hub",$sql_nfc_bp);

	$count=0;//控制迴圈執行
	while($count<1) {



		$row_result_wet = mysqli_fetch_assoc($result_wet);
		$row_result_temp = mysqli_fetch_assoc($result_temp);
		$row_nfc_temp = mysqli_fetch_assoc($result_nfc_temp);
		$row_nfc_bp = mysqli_fetch_assoc($result_nfc_bp);

		$wet_value = $row_result_wet["wet"];
		$temp_value = $row_result_temp["bodytemp"];
		$nfc_temp = $row_nfc_temp["Temperature"];
		$nfc_sp = $row_nfc_bp["Systolic Pressure"];
		$nfc_dp = $row_nfc_bp["Diastolic Pressure"];
		$nfc_pulse = $row_nfc_bp["Pulse"];

		$wet_time = strtotime($time) - strtotime($row_result_wet["time"]);
		$temp_time = strtotime($time) - strtotime($row_result_temp["time"]);
		$nfc_temp_time = strtotime($time) - strtotime($row_nfc_temp["Time"]);
		$nfc_bp_time = strtotime($time) - strtotime($row_nfc_bp["Time"]);

	    $arr[] = array('wet' => $wet_value, 'temp' => $temp_value, 'nfc_temp' => $nfc_temp,
						'sp' => $nfc_sp, 'dp' => $nfc_dp, 'pulse' => $nfc_pulse,
						"wet_time" => $wet_time,"temp_time" => $temp_time,"nfc_temp_time" => $nfc_temp_time,
						"sp_time" => $nfc_bp_time,"dp_time" => $nfc_bp_time,"pulse_time" => $nfc_bp_time);
		$count++;
	}
     echo json_encode($arr);


?>
