<?php
  $IDP=$_GET['IDP'];
  require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
  $sql_bag = "SELECT * FROM dropdata Where userid = '$IDP' AND pid = '08'";
  $result_bag = execute_sql($link, "drop_database", $sql_bag);
  if(mysqli_num_rows($result_bag) > 0){
    mysqli_data_seek($result_bag,mysqli_num_rows($result_bag)-1);
    $row_bag=mysqli_fetch_row($result_bag);
    if($row_bag[3]>=$row_bag[4]){
      echo "<tr><td>設定容量</td> <td>警示容量</td> <td style='background-color:yellow'>使用量</td></tr>
            <tr><td>".$row_bag[5]."</td> <td>".$row_bag[4]."</td><td style='background-color:yellow'>".$row_bag[3]."</td></tr>";
    }
    else {
      echo "<tr><td>設定容量</td> <td>警示容量</td> <td>使用量</td></tr>
            <tr><td>".$row_bag[5]."</td> <td>".$row_bag[4]."</td><td>".$row_bag[3]."</td></tr>";
    }
  }
  else{
    echo "<tr><td colspan='3' align='center'>尚無配對-尿袋</td></tr>";
  }

?>
