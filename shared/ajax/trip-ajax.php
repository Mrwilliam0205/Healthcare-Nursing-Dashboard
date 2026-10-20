<?php
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();

	$arr = array();
  $select=$_POST['select_1'];
	$sql="SELECT  mac_correspond.mac_address
				FROM mac_correspond
				WHERE mac_correspond.id='$select'";
  $result = execute_sql($link,"wetness_database",$sql);

	while($obj = mysqli_fetch_object($result)) {
           $arr[] = $obj;
	}
  echo json_encode($arr);
?>
