<?php
  //header("Access-Control-Allow-Origin：*");
  header('Content-Type: application/json; charset=UTF-8');
  if(isset($_GET["area"])){
    $area = $_GET["area"];
    $timezone_identifiers = DateTimeZone::listIdentifiers($area);
    // echo $_GET["area"];
    echo json_encode(Array('tis' => $timezone_identifiers));

    // echo $timezone_identifiers[0];

  }


?>
