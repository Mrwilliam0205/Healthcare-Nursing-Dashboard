<?php
	if(isset($_GET['mac_address']))
		$mac_address=$_GET['mac_address'];
	else
		exit;
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();

	$arr = array();

	$sql_wet="SELECT wetness87.value ,wetness87.mac_address
						FROM wetness87
						WHERE wetness87.mac_address ='$mac_address' ORDER BY wetness87.time ASC";

	$result_wet = execute_sql($link,"wetness_database",$sql_wet);

	while($row = mysqli_fetch_assoc($result_wet)) {
		$value = $row["value"];
		$mac_address = $row["mac_address"];
	  $arr[] = array('value' => $value, 'mac_address' => $mac_address);
	}
     echo json_encode($arr);


?>
