<?php
   $file_path = __DIR__ . "/key.txt";
   $myfile = fopen($file_path, "r") or die("Unable to open file!");
   
   $str = fread($myfile, filesize($file_path)); 
   
   fclose($myfile);
   $key = explode("$&", $str);

   $timezone = $key[2];
   $username = "root";
   $password = ""; // 如果有密碼，請填入雙引號裡面

   date_default_timezone_set($timezone);

   $time = date("Y-m-d H:i:s", time());
   $time1 = date("Y-m-d H:i:s", (time() - 45));

   function create_connection()
   {
     global $username, $password;
     $link = mysqli_connect("localhost", $username, $password)
       or die("無法建立資料連接: " . mysqli_connect_error());

     mysqli_query($link, "SET NAMES utf8");

     return $link;
   }

   function execute_sql($link, $database, $sql)
   {
     mysqli_select_db($link, $database)
       or die("開啟資料庫失敗: " . mysqli_error($link));

     $result = mysqli_query($link, $sql);

     return $result;
   }
?>