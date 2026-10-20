<?php
  $IDP=$_GET['IDP'];
  require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
  $sql_bit = "SELECT * FROM dropdata Where userid= '$IDP' AND pid = '07'";
  $result_bit = execute_sql($link, "drop_database", $sql_bit);
  if(mysqli_num_rows($result_bit) > 0){
    mysqli_data_seek($result_bit,mysqli_num_rows($result_bit)-1);
    $row_bit=mysqli_fetch_row($result_bit);
    if(($row_bit[5]-$row_bit[3])<=$row_bit[4]){
      echo "<tr><td>設定容量</td> <td>警示容量</td> <td style='background-color:yellow'>使用量</td></tr>
            <tr><td>".$row_bit[5]."</td> <td>".$row_bit[4]."</td><td style='background-color:yellow'>".$row_bit[3]."</td></tr>";
    }
    else {
      echo "<tr><td>設定容量</td> <td>警示容量</td> <td>使用量</td></tr>
            <tr><td>".$row_bit[5]."</td> <td>".$row_bit[4]."</td><td>".$row_bit[3]."</td></tr>";
    }
  }
  else{
    echo "<tr><td colspan='3' align='center'>尚無配對-點滴</td></tr>";
  }
  ?>
