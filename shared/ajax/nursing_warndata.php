<?php
  require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
  $link = create_connection();
  date_default_timezone_set('Asia/Taipei');
	$time = date("Y-m-d H:i:s",time());
  $time_45 = date("Y-m-d H:i:s",(time()-45));

  $sql_warning="SELECT * FROM warning_standard";
  $result_warning = execute_sql($link, "wetness_database", $sql_warning);
  $warning=mysqli_fetch_array($result_warning);
  $wet_top = $warning['wet_top'];
  $temp_top = $warning['temp_top'];
  $temp_bot = $warning['temp_bot'];
  $breath_top = $warning['breath_top'];
  $breath_bot = $warning['breath_bot'];


  $sql = "SELECT DISTINCT warddata.ward, warddata.bed, warddata.IDP, memberlogin.name, memberlogin.blood, memberlogin.birthday
          FROM warddata, memberlogin
          WHERE warddata.IDP = memberlogin.IDP AND warddata.IDP IS NOT NULL
          ORDER BY warddata.ward ASC, warddata.bed ASC";
  $result = execute_sql($link, "wetness_database", $sql);

  $num_on = 0; $num_down = 0;
  echo "<div>";
  for ($i=0; $i <mysqli_num_rows($result) ; $i++) {
    // echo "i:".$i;
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
    mysqli_data_seek($result,$i);
    $row = mysqli_fetch_array($result);
    $IDP = $row['IDP'];
    $ward_num = $row['ward']."-".$row['bed'];

    // 獲取用戶的所有tag
    $sql_get_tags = "SELECT tagmac, lastPID 
    FROM tag 
    WHERE owner = '$IDP'
    AND lastPID IN ('03', '05', '06')
    ORDER BY FIELD(lastPID, '06', '05', '03')";

    $result_tag = execute_sql($link, "wetness_database", $sql_get_tags);

    $tag_list = [];
    while ($rowtag = mysqli_fetch_assoc($result_tag)) {
    $tag_list[] = $rowtag;
    }

    // 如果用戶連接了兩個或兩個以上的tag
    if (count($tag_list) > 1) {
      // 獲取優先級最高的tag
      $selected_tag = $tag_list[0]; // 因為已經按優先級排序
      $selected_lastPID = $selected_tag['lastPID'];
      // print_r($tag_list[0]);
      // echo "這邊：".$selected_lastPID;
      $sql_breath = "SELECT DISTINCT tag.tagmac, valueimmediate.breath, tag.lastPID, valueimmediate.time
                      FROM tag, valueimmediate
                      WHERE tag.owner = '$IDP'
                      AND tag.tagmac = valueimmediate.mac_address
                      AND valueimmediate.time BETWEEN '$time_45' AND '$time'
                      AND (valueimmediate.breath > '$breath_top' OR valueimmediate.breath < '$breath_bot')
                      AND tag.lastPID = '$selected_lastPID'
                      ORDER BY tag.lastPID DESC, valueimmediate.time DESC LIMIT 1";
    }else if(empty($tag_list)) {
      // echo "No tags found for this user.";
      $sql_breath ="SELECT DISTINCT tag.tagmac, valueimmediate.breath, tag.lastPID, valueimmediate.time
      FROM tag , valueimmediate
      WHERE tag.owner = '$IDP' 
      AND tag.tagmac = valueimmediate.mac_address
      AND valueimmediate.time BETWEEN '$time_45' AND '$time'
      AND (valueimmediate.breath > '$breath_top' OR valueimmediate.breath < '$breath_bot')
      AND tag.lastPID IN ('03', '05', '06')
      ORDER BY tag.lastPID DESC, valueimmediate.time DESC LIMIT 1";
    }else{
      // 獲取唯一的tag
      $selected_tag = $tag_list[0];
      $selected_lastPID = $selected_tag['lastPID'];
      $sql_breath = "SELECT DISTINCT tag.tagmac, valueimmediate.breath, tag.lastPID, valueimmediate.time
                      FROM tag, valueimmediate
                      WHERE tag.owner = '$IDP'
                      AND tag.tagmac = valueimmediate.mac_address
                      AND valueimmediate.time BETWEEN '$time_45' AND '$time'
                      AND (valueimmediate.breath > '$breath_top' OR valueimmediate.breath < '$breath_bot')
                      AND tag.lastPID = '$selected_lastPID'
                      ORDER BY tag.lastPID DESC, valueimmediate.time DESC LIMIT 1";
    }
    //     // 檢查 $tag_list 是否為空
    // if (empty($tag_list)) {
    //   echo "No tags found for this user.";
    //   // exit; // 終止腳本
    // }

    if($num_on == 4){
      $num_on = 0;
      echo "<table>";
    }else{
      $num_on++;
    }
    $sql_wet ="SELECT DISTINCT tag.tagmac, valueimmediate.wet, tag.lastPID, valueimmediate.time
                FROM tag , valueimmediate
                WHERE tag.owner = '$IDP' 
                AND tag.tagmac = valueimmediate.mac_address
                AND valueimmediate.time BETWEEN '$time_45' AND '$time'
                AND valueimmediate.wet > $wet_top
                AND LEFT(valueimmediate.id, 2) = tag.lastPID
                AND tag.lastPID IN ('02', '06')
                ORDER BY valueimmediate.time DESC";

    $sql_temp ="SELECT DISTINCT tag.tagmac, valueimmediate.bodytemp, tag.lastPID, valueimmediate.time
                  FROM tag , valueimmediate
                  WHERE tag.owner = '$IDP' 
                  AND tag.tagmac = valueimmediate.mac_address
                  AND valueimmediate.time BETWEEN '$time_45' AND '$time'
                  AND (valueimmediate.bodytemp > '$temp_top' OR valueimmediate.bodytemp < '$temp_bot')
                  AND tag.lastPID = '03'
                  ORDER BY valueimmediate.time DESC";

    $sql_spitmilk ="SELECT tag.lastPID, valueimmediate.SpitMilk, valueimmediate.time
                    FROM valueimmediate, tag
                    WHERE valueimmediate.mac_address = tag.tagmac
                    AND valueimmediate.time BETWEEN '$time_45' AND '$time'
                    AND valueimmediate.SpitMilk = '1'
                    AND tag.owner = '$IDP' 
                    AND valueimmediate.id LIKE '05_%'";
    //LeaveBed&GetUp
    $sql_LG ="SELECT tag.lastPID, valueimmediate.LeaveBed, valueimmediate.GetUp, valueimmediate.time
              FROM valueimmediate, tag
              WHERE valueimmediate.mac_address = tag.tagmac
              AND valueimmediate.time BETWEEN '$time_45' AND '$time'
              AND (valueimmediate.LeaveBed = '1' OR valueimmediate.GetUp = '1')
              AND tag.owner = '$IDP'
              AND (valueimmediate.id LIKE '05_%' OR valueimmediate.id LIKE '03_%')";
    $result_wet = execute_sql($link, "wetness_database", $sql_wet);
    $result_temp = execute_sql($link, "wetness_database", $sql_temp);
    $result_breath = execute_sql($link, "wetness_database", $sql_breath);
    $result_spitmilk = execute_sql($link, "wetness_database", $sql_spitmilk);
    $result_LG = execute_sql($link, "wetness_database", $sql_LG);

    $count = 0;
    for ($j=2; $j <7 ; $j++) {
      if($j != 4){
        $tag = '0'.$j.'_%';
        $sql_power = "SELECT valueimmediate.mac_address,valueimmediate.power, valueimmediate.time
                      FROM valueimmediate, tag
                      WHERE valueimmediate.mac_address = tag.tagmac
                      AND tag.owner = '$IDP' AND valueimmediate.id LIKE '$tag'
                      AND valueimmediate.time BETWEEN '$time_45' AND '$time'
                      AND valueimmediate.power <11
                      ORDER BY valueimmediate.time DESC LIMIT 1";
        $result_power = execute_sql($link, "wetness_database", $sql_power);
        if(mysqli_num_rows($result_power)>0){
          echo "123";
          $count ++;
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

    if(mysqli_num_rows($result_wet) > 0 || mysqli_num_rows($result_temp) > 0 || mysqli_num_rows($result_breath) > 0||
      mysqli_num_rows($result_spitmilk) > 0|| mysqli_num_rows($result_LG) > 0 || $count > 0){
        echo "<div id = 'patient' style='width:24%; text-align:center; margin:1px; border:3px solid #A9A9A9; display:inline-block; vertical-align:top; border-radius:10px;'>";
        echo "<a href='http://localhost:8080/test/newwww/ch/test/frame_patient.php?IDP=".$row['IDP']."' target='' style='text-decoration:none'>";
        echo $ward_num."　".$row['name']."<br></a>";

        if(mysqli_num_rows($result_wet) > 0){
          $row_wet = mysqli_fetch_assoc($result_wet); 
          $arr['wet'] = $row_wet['wet'];
          echo "<font class=wet color='red'>" . $arr['wet'] . '%　' . "</font>";
        }else{
          $sql_wet_additional = "SELECT DISTINCT tag.tagmac, valueimmediate.wet, tag.lastPID, valueimmediate.time
                                    FROM tag , valueimmediate
                                    WHERE tag.owner = '$IDP' 
                                    AND tag.tagmac = valueimmediate.mac_address
                                    AND LEFT(valueimmediate.id, 2) = tag.lastPID
                                    AND tag.lastPID IN ('02', '06')
                                    ORDER BY tag.lastPID DESC";

          $result_wet_additional = execute_sql($link, "wetness_database", $sql_wet_additional);

          if(mysqli_num_rows($result_wet_additional) > 0) {
            $row_wet = mysqli_fetch_assoc($result_wet_additional); 
            $arr['wet'] = $row_wet['wet'];
            $arr['wet_time'] = strtotime($time) - strtotime($row_wet['time']);
            if($arr['wet_time'] < 45 && $arr['wet'] <= $wet_top) {
              echo "<font class=wet color='#00DD00'>" . $arr['wet'] . '%　' . "</font>";
            }else {
              if($arr['wet'] == "濕度未配對") {
                echo "<font class=wet color='#000000'>" . $arr['wet'] . "　</font>";
              }else {
                echo "<font class=wet color='#000000'>" . $arr['wet'] . '%　' . "</font>";
              }
            }
          }else{
            echo "<font class=wet color='#000000'>" . $arr['wet'] . "　</font>";
          }
        }
        if(mysqli_num_rows($result_temp) > 0){
          $row_temp = mysqli_fetch_assoc($result_temp);
          $arr['temp'] = $row_temp['bodytemp'];
          echo "<font class='temp' color='red'>" .$arr['temp'].'度'. "</font>";
        }else{
          $sql_temp_additional = "SELECT DISTINCT tag.tagmac, valueimmediate.bodytemp, tag.lastPID, valueimmediate.time
                                    FROM tag , valueimmediate
                                    WHERE tag.owner = '$IDP' 
                                    AND tag.tagmac = valueimmediate.mac_address
                                    AND LEFT(valueimmediate.id, 2) = tag.lastPID
                                    AND tag.lastPID = '03'
                                    ORDER BY tag.lastPID DESC";

          $result_temp_additional = execute_sql($link, "wetness_database", $sql_temp_additional);

          if(mysqli_num_rows($result_temp_additional) > 0) {
            $row_temp = mysqli_fetch_assoc($result_temp_additional); 
            $arr['temp'] = $row_temp['bodytemp'];
            $arr['temp_time'] = strtotime($time) - strtotime($row_temp['time']);
            if($arr['temp_time'] < 45 && $arr['temp'] <= $temp_top && $arr['temp'] >= $temp_bot) {
              echo "<font class=temp color='#00DD00'>" . $arr['temp'] . '度' . "</font>";
            }else {
              if($arr['temp'] == "溫度未配對") {
                echo "<font class=temp color='#000000'>" . $arr['temp'] . "</font>";
              }else {
                echo "<font class=temp color='#000000'>" . $arr['temp'] . '度' . "</font>";
              }
            }
          }else{
            echo "<font class=temp color='#000000'>" . $arr['temp'] . "</font>";
          }
        }
        echo "</br>";
        if(mysqli_num_rows($result_breath) > 0){
          $row_breath = mysqli_fetch_assoc($result_breath);
          $arr['breath'] = $row_breath['breath'];
          echo "<font class=breath color='red'>" .$arr['breath'].' bpm'. "</font>";
        }else{
          $sql_breath_additional = "SELECT DISTINCT tag.tagmac, valueimmediate.breath, tag.lastPID, valueimmediate.time
                                    FROM tag , valueimmediate
                                    WHERE tag.owner = '$IDP' 
                                    AND tag.tagmac = valueimmediate.mac_address
                                    AND tag.lastPID IN ('03', '05', '06')
                                    AND (valueimmediate.breath <= '$breath_top' OR valueimmediate.breath >= '$breath_bot')
                                    ORDER BY tag.lastPID DESC";

          $result_breath_additional = execute_sql($link, "wetness_database", $sql_breath_additional);

          if(mysqli_num_rows($result_breath_additional) > 0) {
            $row_breath = mysqli_fetch_assoc($result_breath_additional); 
            $arr['breath'] = $row_breath['breath'];
            $arr['breath_time'] = strtotime($time) - strtotime($row_breath['time']);
            if($arr['breath_time'] < 45 && $arr['breath'] <= $breath_top && $arr['breath'] >= $breath_bot) {
              echo "<font class=breath color='#00DD00'>" . $arr['breath'] . 'bpm' . "</font>";
            }else {
              if($arr['breath'] == "溫度未配對") {
                echo "<font class=breath color='#000000'>" . $arr['breath'] . "</font>";
              }else {
                echo "<font class=breath color='#000000'>" . $arr['breath'] . 'bpm' . "</font>";
              }
            }
          }else{
            echo "<font class=breath color='#000000'>" . $arr['breath'] . "</font>";
          }
        }
        if(mysqli_num_rows($result_spitmilk) > 0){
          $row_spitmilk=mysqli_fetch_array($result_spitmilk);
          echo "<font class=spitmilk color='red'>吐奶</font>";
        }else{
          echo "　";
        }
        echo "</br>";
        if(mysqli_num_rows($result_LG) > 0){
          $row_LG=mysqli_fetch_array($result_LG);
          if($row_LG['GetUp'] == 1){
            echo "<img src='./picture/wakeY.png' width='50' height='80'>";
          }else{
            echo "<img src='./picture/wakeN.png' width='50' height='80'>";
          }
          if($row_LG['LeaveBed'] == 1){
            echo "<img src='./picture/escapeY.png' width='80' height='80'>";
          }else{
            echo "<img src='./picture/escapeN.png' width='80' height='80'>";
            echo "　";
          }
        }else{
          echo "<img src='./picture/wakeN.png' width='50' height='80'>";
          echo "　";
          echo "<img src='./picture/escapeN.png' width='80' height='80'>";
        }
        echo "</br>";
        if($arr['pow02'] != 'No'){
          echo "<font class= leavebed color='red'>電子尿布TAG電量過低</font>";
        }else{
          echo "<br>";
        }
        if($arr['pow03'] != 'No'){
          echo "<font class= leavebed color='red'>NM_感溫衣TAG電量過低</font>";
        }else{
          echo "<br>";
        }
        if($arr['pow05'] != 'No'){
          echo "<font class= leavebed color='red'>NM_電子圍兜TAG電量過低</font>";
        }else{
          echo "<br>";
        }
        if($arr['pow06'] != 'No'){
          echo "<font class= leavebed color='red'>NM_電子尿布TAG電量過低</font>";
        }else{
          echo "<br>";
        }
    //     echo $ward_num."　".$row['name']."　".$IDP."<br>";
    //     // echo "濕度：" . $arr['wet'] . "<br>";
    //     // echo "溫度：" . $arr['temp'] . "<br>";
    //     // echo "呼吸：" . $arr['breath'] . "<br>";
    //     print_r(array_intersect_key($arr, array_flip(['wet', 'temp', 'breath'])));

    //     // print_r($arr);
        echo "</div>";
    }
    if($num_down == 4){
      $num_down = 0;
      echo "</table>";
    }else{
      $num_on++;
    }
  }
  // echo "</div>";
?>