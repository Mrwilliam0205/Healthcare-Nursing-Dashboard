<?php
  require_once("encryption.php");

  $un = $_POST["username"];
  $pw = $_POST["password"];
  $location = $_POST["YourLocation"];

  $link = mysqli_connect("localhost", $un,$pw)or die("無法建立資料連接: " . mysqli_connect_error());

  $result= mysqli_query($link, "SET NAMES utf8");

  if($result){
    //$aes = new AES();
    //$enUser =$aes->encrypt($un);
    //$enPw = $aes->encrypt($pw);

    $myfile = fopen("key.txt", "w+") or die("Unable to open file!");
    // fwrite($myfile, $enUser."$&");
    // fwrite($myfile, $enPw."$&");
    fwrite($myfile , $un."$&");
    fwrite($myfile , $pw."$&");
    fwrite($myfile, $location);

    echo "寫入成功";

    mysqli_close($link);

  }else{
    echo "寫入失敗";
  }






?>
