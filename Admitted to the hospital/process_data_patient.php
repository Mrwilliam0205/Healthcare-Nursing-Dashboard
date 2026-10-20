<?php
	header('Content-Type: text/html; charset=utf-8');
	session_start();
	require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
	$database = "wetness_database2";

	$patient_idp = $_POST['IDP'];
    $safe_idp = mysqli_real_escape_string($link, trim($patient_idp));

	$sql = "SELECT `IDP` FROM `memberlogin` WHERE `IDP`= '{$_POST['IDP']}'";
	$result = execute_sql($link,"wetness_database2",$sql);
	$room = explode("-",$_POST['wardroom'])[0]; 
	$bed = explode("-",$_POST['wardroom'])[1]; 
	echo " 房號  '$room' 床號 '$bed' ";
	$date = date("Y-m-d H:i:s");
	echo $date;

	if (mysqli_num_rows($result)>0){
		echo "'{$_POST['IDP']}'此病人已經存在";
	}
	else{
		//新增病人資料
		$Birthday = str_replace("-","",$_POST['Birthday']);
		$sql="INSERT INTO `memberlogin` (`IDP`, `Password`, `Admission_date`, `Name`, `Blood`, `Birthday`, `sex`)
					VALUES ('{$_POST['IDP']}','$Birthday','$date','{$_POST['Name']}','{$_POST['Blood']}','{$_POST['Birthday']}','{$_POST['sex']}')";
		$result_member = execute_sql($link,"wetness_database2",$sql);

		if($result_member){        //INSERT 會回傳boolen值(true=成功, false=失敗)
			echo '<h3>✅ 病患資料建立成功！</h3>';

            $table_valuehistory = "`{$safe_idp}_valuehistory`";
            $sql_create_history = "CREATE TABLE IF NOT EXISTS {$table_valuehistory} LIKE valuehistory";
            execute_sql($link, $database, $sql_create_history);

            $table_event = "`{$safe_idp}_Event`";
            $sql_create_event = "
                CREATE TABLE IF NOT EXISTS {$table_event} (
                    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
                    `event_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '事件發生時間',
                    `event_type` VARCHAR(50) NOT NULL COMMENT '事件類型',
                    `description` TEXT COMMENT '事件詳細說明'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            execute_sql($link, $database, $sql_create_event);

            $base_folder_path = "C:/xampp/htdocs/Nursemaid_pro_sitting/patient_data/"; 
            $patient_folder = $base_folder_path . $safe_idp;

            if (!file_exists($patient_folder)) {
                mkdir($patient_folder, 0777, true);
                echo "<p style='color:green;'>專屬資料表與資料夾已自動建置完成。</p>";
            }
			echo 'Success,Your QRcode is<br />';
		}else{
			echo 'fail<br />';
		}
		//更新病人Qrcode
		$input = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=". $_POST['Name'] ."=". $_POST['IDP'] ."&choe=UTF-8";
		$sql_qr="UPDATE `memberlogin` SET `QRcode`='$input' WHERE `IDP`='{$_POST['IDP']}'";
		$result = execute_sql($link,"wetness_database2",$sql_qr);
		echo "<img src=$input>  <br/>";
		//更新病床資料
		$sql_ward="UPDATE `warddata` SET `IDP`='{$_POST['IDP']}' WHERE `ward`='$room' AND `bed`='$bed'";
		$result_ward = execute_sql($link,"wetness_database2",$sql_ward);
		
	}
?>
<html>
	<head>
		<meta charset='utf-8'>
	</head>
	<body>
		<!-- <input type ="button" onclick="history.back()" value="Back"></input> -->
		<input type ="button" onclick="location.href='frame_data_patient.php'" value="Back"></input>
	</body>
</html>
