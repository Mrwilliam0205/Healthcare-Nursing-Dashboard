<?php
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();

	$arr = array();
  $ward=$_GET['ward'];
	$sql="SELECT DISTINCT warddata.IDP, memberlogin.name, warddata.ward, warddata.bed
				FROM warddata, memberlogin
				WHERE warddata.ward = '$ward' AND warddata.IDP = memberlogin.IDP AND warddata.IDP IS NOT NULL ORDER BY  warddata.ward ASC, warddata.bed ASC";
  $result = execute_sql($link,"wetness_database",$sql);

	while($obj = mysqli_fetch_object($result)) {
           $IDP = $obj->IDP;
		   $name = $obj->name;

		   $sql_wet="SELECT DISTINCT  wetness87.time , wetness87.value
								 FROM wetness87, mac_correspond
								 WHERE wetness87.mac_address = mac_correspond.mac_address AND mac_correspond.id like 'W%' AND mac_correspond.IDP = '$IDP' ";
		   $sql_temp="SELECT DISTINCT wetness87.time , wetness87.value
									FROM wetness87, mac_correspond
									WHERE wetness87.mac_address = mac_correspond.mac_address AND mac_correspond.id like 'T%' AND mac_correspond.IDP = '$IDP' ";
		   $sql_pressure="SELECT `high_pressure`, `low_pressure`, `heartbeat`,`time`
											FROM `hospital_bloodpressure`
											WHERE paint_id = '$name' ";


		   $result_wet = execute_sql($link,"wetness_database",$sql_wet);
		   $result_temp=execute_sql($link,"wetness_database",$sql_temp);
			 $result_pressure=execute_sql($link,"wetness_database",$sql_pressure);

		    $row_result_wet = mysqli_fetch_assoc($result_wet);
		   	$row_result_temp = mysqli_fetch_assoc($result_temp);
				$row_result_pressure = mysqli_fetch_assoc($result_pressure);


		   $wet_value = checkdata($row_result_wet["value"],"%");
		   $temp_value = checkdata($row_result_temp["value"],"°C");
		   $high = checkdata($row_result_pressure["high_pressure"]," mmHg");
		   $low = checkdata($row_result_pressure["low_pressure"]," mmHg");
		   $heart = checkdata($row_result_pressure["heartbeat"],"次");



		   $arr[] = array('wet' => $wet_value, 'temp' => $temp_value, 'high' =>$high, 'low'=>$low, 'heart'=>$heart );

	}
     echo json_encode($arr);

function checkdata($value,$type){//確認資料是否存在
	if($value==""){
		return "無資料";
	}
	else
		return $value;
}












?>
