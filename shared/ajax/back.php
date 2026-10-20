<?php
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
	if(!isset($_GET['item'])){
		$item="02";
	}else{
		$item=$_GET['item'];
	}
	 $arr = array();
   $sql = "SELECT * FROM valueimmediate
					 WHERE mac_address NOT In (SELECT mac_address FROM mac_correspond WHERE IDP Not in ('NULL')) AND id LIKE '$item%'
					 ORDER BY time DESC;";
   $result = execute_sql($link,"wetness_database",$sql);

   while($obj = mysqli_fetch_object($result)) {
  	$arr[] = $obj;
   }

   echo json_encode($arr);	
?>
