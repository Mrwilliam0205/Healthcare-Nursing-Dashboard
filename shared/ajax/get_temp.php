<?php
	require_once("time.php");
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();

	$arr = array();
  $IDP=$_GET['IDP'];
	// $sql_wet="SELECT valueimmediate.wet
	// 				FROM tag , valueimmediate
	// 				WHERE tag.owner= '$IDP' AND tag.tagmac= valueimmediate.mac_address AND (tag.lastPID = '02' OR tag.lastPID = '06') ORDER BY valueimmediate.time DESC";
	$sql_temp="SELECT valueimmediate.bodytemp
					FROM tag , valueimmediate
					WHERE tag.owner= '$IDP' AND tag.tagmac= valueimmediate.mac_address AND tag.lastPID = '03' ORDER BY valueimmediate.time DESC";
    // $result_wet = execute_sql($link,"wetness_database",$sql_wet);
	$result_temp = execute_sql($link,"wetness_database",$sql_temp);

	$count=0;
	while($count<1) {

		// $row_result_wet = mysqli_fetch_assoc($result_wet);
		$row_result_temp = mysqli_fetch_assoc($result_temp);

		// $wet_value = $row_result_wet["wet"];
		$temp_value =$row_result_temp["bodytemp"];

	    // $arr[] = array('wet' => $wet_value, 'temp' => $temp_value);
		$arr[] = array( 'temp' => $temp_value);
		$count++;
	}
    echo json_encode($arr);

?>
