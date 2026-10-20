<?php
  $IDP = $_GET["IDP"];
  $patientNum = $_GET["patientNum"];
  require_once("time.php");
  require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
  $arr = array(
    'wet' => '濕度未配對', 
    'temp' => '溫度未配對', 
    'breath' => '呼吸未配對', 
    'spitmilk' => 'No', 
    'leavebed' => 'No', 
    'getup' => 'No',
    'pow02' => 'No', 
    'pow03' => 'No', 
    'pow05' => 'No', 
    'pow06' => 'No',
    'wet_time' => 45, 
    'temp_time' => 45, 
    'breath_time' => 45, 
    'spitmilk_time' => 45, 
    'leavebed_time' => 45,
    'getup_time' => 45, 
    'p02_time' => 45, 
    'p03_time' => 45, 
    'p05_time' => 45, 
    'p06_time' => 45
  ); 
  $value = '';

  // $sql_wtb ="SELECT DISTINCT valueimmediate.bodytemp, valueimmediate.wet, valueimmediate.breath, tag.lastPID, valueimmediate.time
  //             FROM tag , valueimmediate
  //             WHERE tag.owner = '$IDP' 
  //             AND tag.tagmac = valueimmediate.mac_address
  //             AND LEFT(valueimmediate.id, 2) = tag.lastPID
  //             ORDER BY tag.lastPID DESC";
  $sql_wet = "SELECT DISTINCT tag.lastPID, valueimmediate.wet, valueimmediate.time
                FROM tag , valueimmediate
                WHERE tag.owner = '$IDP'
                AND tag.tagmac = valueimmediate.mac_address
                AND LEFT(valueimmediate.id, 2) = tag.lastPID
                AND tag.lastPID IN ('02', '06')
                ORDER BY valueimmediate.time DESC";
  $sql_temp = "SELECT DISTINCT tag.lastPID, valueimmediate.bodytemp, valueimmediate.time
                FROM tag , valueimmediate
                WHERE tag.owner = '$IDP'
                AND tag.tagmac = valueimmediate.mac_address
                AND LEFT(valueimmediate.id, 2) = tag.lastPID
                AND tag.lastPID = '03'
                ORDER BY valueimmediate.time DESC";
  $sql_breath = "SELECT DISTINCT tag.lastPID, valueimmediate.breath, valueimmediate.time
                  FROM tag , valueimmediate
                  WHERE tag.owner = '$IDP' 
                  AND tag.tagmac = valueimmediate.mac_address 
                  AND LEFT(valueimmediate.id, 2) = tag.lastPID
                  AND tag.lastPID IN ('03', '05', '06')
                  ORDER BY tag.lastPID DESC, valueimmediate.time DESC LIMIT 1";
  $sql_Spitmilk = "SELECT tag.lastPID, valueimmediate.SpitMilk, valueimmediate.time
                    FROM valueimmediate, tag
                    WHERE valueimmediate.mac_address = tag.tagmac
                    AND LEFT(valueimmediate.id, 2) = tag.lastPID
                    AND tag.owner = '$IDP'
                    AND valueimmediate.id LIKE '05_%'";
  //LeaveBed&GetUp
  $sql_LG = "SELECT tag.lastPID, valueimmediate.LeaveBed, valueimmediate.GetUp, valueimmediate.time
              FROM valueimmediate, tag
              WHERE valueimmediate.mac_address = tag.tagmac
              AND LEFT(valueimmediate.id, 2) = tag.lastPID
              AND tag.owner = '$IDP'
              AND (valueimmediate.id LIKE '05_%' OR valueimmediate.id LIKE '03_%')";
  // $result_wtb = execute_sql($link, "wetness_database", $sql_wtb);
  $result_wet = execute_sql($link,"wetness_database",$sql_wet);
  $result_temp = execute_sql($link,"wetness_database",$sql_temp);
  $result_breath = execute_sql($link,"wetness_database",$sql_breath);
  $result_Spitmilk = execute_sql($link, "wetness_database", $sql_Spitmilk);
  $result_LG = execute_sql($link, "wetness_database", $sql_LG);
  // $wet = 'No'; $temp = 'No'; $breath = 'No';
  // $wet_time = 45; $temp_time = 45; $breath_time = 45;
    // if(mysqli_num_rows($result_wtb)>0){
    //   for ($j=0; $j <mysqli_num_rows($result_wtb) ; $j++) {
    //     mysqli_data_seek($result_wtb,$j);
    //     $row_wtb=mysqli_fetch_array($result_wtb);
    //     print_r($row_wtb); // 或者使用 var_dump($row_wtb);
    //     if(strpos($row_wtb['lastPID'],'02') !== false){
    //       $wet =  $row_wtb['wet'];
    //       $wet_time = strtotime($time) - strtotime($row_wtb['time']);
    //     }
    //     if(strpos($row_wtb['lastPID'],'03') !== false){
    //       $temp = $row_wtb['bodytemp'];
    //       $temp_time = strtotime($time) - strtotime($row_wtb['time']);
    //     }
    //     if((strpos($row_wtb['lastPID'],'05') !== false) || (strpos($row_wtb['lastPID'],'03') !== false)){
    //       $breath = $row_wtb['breath'];
    //       $breath_time = strtotime($time) - strtotime($row_wtb['time']);
    //     }
    //   }
    // }
    if(mysqli_num_rows($result_wet)>0){
      $row_wet = mysqli_fetch_assoc($result_wet); 
      $arr['wet'] = $row_wet['wet'];
      $arr['wet_time'] = strtotime($time) - strtotime($row_wet['time']);
    }
    if(mysqli_num_rows($result_temp)>0){
      $row_temp = mysqli_fetch_assoc($result_temp);
      $arr['temp'] = $row_temp['bodytemp'];
      $arr['temp_time'] = strtotime($time) - strtotime($row_temp['time']);
    }
    if(mysqli_num_rows($result_breath)>0){
      $row_breath = mysqli_fetch_assoc($result_breath);
      $arr['breath'] = $row_breath['breath'];
      $arr['breath_time'] = strtotime($time) - strtotime($row_breath['time']);
    }
    // $spitmilk = 0; $leavebed = 0; $getup = 0;
    // $sm_time = 45; $lb_time = 45; $gu_time = 45;
    // $pow02 = 'No'; $pow03 = 'No'; $pow05 = 'No'; $pow06 = 'No';
    // $p02_time = 45; $p03_time = 45; $p05_time = 45; $p06_time = 45;
    // if(mysqli_num_rows($result_Spitmilk)>0){
    //   $row_Spitmilk=mysqli_fetch_array($result_Spitmilk);
    //   $spitmilk = $row_Spitmilk['SpitMilk'];
    //   $sm_time = strtotime($time) - strtotime($row_Spitmilk['time']);
    // }
    if(mysqli_num_rows($result_Spitmilk)>0){
      $row_Spitmilk = mysqli_fetch_assoc($result_Spitmilk);
      $arr['spitmilk'] = $row_Spitmilk['SpitMilk'];
      $arr['spitmilk_time'] = strtotime($time) - strtotime($row_Spitmilk['time']);
    }
    // if(mysqli_num_rows($result_LG)>0){
    //   $row_LG=mysqli_fetch_array($result_LG);
    //   $leavebed = $row_LG['LeaveBed'];
    //   $lb_time = strtotime($time) - strtotime($row_LG['time']);
    //   $getup = $row_LG['GetUp'];
    //   $gu_time = strtotime($time) - strtotime($row_LG['time']);
    // }
    if(mysqli_num_rows($result_LG)>0){
      $row_LG = mysqli_fetch_assoc($result_LG);
      $arr['leavebed'] = $row_LG['LeaveBed'];
      $arr['getup'] = $row_LG['GetUp'];
      $arr['leavebed_time'] = strtotime($time) - strtotime($row_LG['time']);
      $arr['getup_time'] = strtotime($time) - strtotime($row_LG['time']);
    }
    for ($j=2; $j <7 ; $j++) {
      if($j != 4){
        $tag = '0'.$j.'_%';
        // $p = $j.'power';
        $sql_power = "SELECT valueimmediate.mac_address,valueimmediate.power, valueimmediate.time
                      FROM valueimmediate, tag
                      WHERE tag.tagmac = valueimmediate.mac_address
                      AND LEFT(valueimmediate.id, 2) = tag.lastPID
                      AND tag.owner = '$IDP' 
                      AND valueimmediate.id LIKE '$tag'
                      ORDER BY valueimmediate.time DESC LIMIT 1";
        $result_power = execute_sql($link, "wetness_database", $sql_power);
        // if(mysqli_num_rows($result_power)>0){
        //   $row_power=mysqli_fetch_array($result_power);
        //   switch($j){
        //     case 2:
        //       $pow02 = $row_power['power'];
        //       $p02_time = strtotime($time) - strtotime($row_power['time']);
        //       break;
        //     case 3:
        //       $pow03 = $row_power['power'];
        //       $p03_time = strtotime($time) - strtotime($row_power['time']);
        //       break;
        //     case 5:
        //       $pow05 = $row_power['power'];
        //       $p05_time = strtotime($time) - strtotime($row_power['time']);
        //       break;
        //     case 6:
        //       $pow06 = $row_power['power'];
        //       $p06_time = strtotime($time) - strtotime($row_power['time']);
        //       break;
        //   }
        // }
        if(mysqli_num_rows($result_power)>0){
          switch($j){
            case 2:
              $row_pow02 = mysqli_fetch_assoc($result_power);
              $arr['pow02'] = $row_pow02['power'];
              $arr['pow02_time'] = strtotime($time) - strtotime($row_pow02['time']);
              break;
            case 3:
              $row_pow03 = mysqli_fetch_assoc($result_power);
              $arr['pow03'] = $row_pow03['power'];
              $arr['ppw03_time'] = strtotime($time) - strtotime($row_pow03['time']);
              break;
            case 5:
              $row_pow05 = mysqli_fetch_assoc($result_power);
              $arr['pow05'] = $row_pow05['power'];
              $arr['pow05_time'] = strtotime($time) - strtotime($row_pow05['time']);
              break;
            case 6:
              $row_pow06 = mysqli_fetch_assoc($result_power);
              $arr['pow06'] = $row_pow06['power'];
              $arr['pow06_time'] = strtotime($time) - strtotime($row_pow06['time']);
              break;
          }
        }
      }
    }
    // $value = ['wet' =>$wet, 'temp' => $temp, 'breath' => $breath, 'spitmilk' => $spitmilk, 'leavebed' => $leavebed, 'getup' => $getup,
    //           'pow02' => $pow02, 'pow03' => $pow03, 'pow05' => $pow05, 'pow06' => $pow06,
    //           'wet_time' =>$wet_time, 'temp_time' => $temp_time, 'breath_time' => $breath_time, 'sm_time' => $sm_time, 'lb_time' => $lb_time,
    //           'gu_time' => $gu_time, 'p02_time' => $p02_time, 'p03_time' => $p03_time, 'p05_time' => $p05_time, 'p06_time' => $p06_time];
    // array_push($arr,$value);

    // $arr_json = json_encode($arr, JSON_UNESCAPED_UNICODE);
    // header('Content-Type: application/json'); // 設置響應標頭為 JSON
    // echo $arr_json;
    echo json_encode($arr);
 ?>