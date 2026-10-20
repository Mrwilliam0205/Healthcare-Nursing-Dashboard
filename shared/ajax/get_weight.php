<?php
  $IDP=$_GET['IDP'];
  require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");	//導入此檔案意思
	$link = create_connection();
  //秤重
  $sql_weight = "SELECT * FROM weight Where userid = '$IDP'";
  $result_weight = execute_sql($link, "drop_database", $sql_weight);
  if(mysqli_num_rows($result_weight) > 0){
    mysqli_data_seek($result_weight,mysqli_num_rows($result_weight)-1);
    $row_weight=mysqli_fetch_row($result_weight);
    echo "<tr><td>秤重重量</td> <td colspan='2' align='center'>最後測量時間</td></tr>
          <tr><td>".$row_weight[2]."</td> <td colspan='2' align='center'>".$row_weight[3]."</td></tr>";
  }
  else{
    echo "<tr><td colspan='3' align='center'>尚無配對-秤重</td></tr>";
}
?>
