<?php
require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
header("Content-type: text/html; charset=utf-8");

// 嘗試建立資料庫連線
$link = create_connection();
if (!$link) {
    die("無法建立資料連接: " . mysqli_connect_error());
}

date_default_timezone_set("Asia/Taipei");
set_time_limit(0);

// --- 修正部分 1：安全接收參數 ---
// 使用三元運算子確保變數被定義，避免 "Undefined variable" 警告
$getall = isset($_GET['all']) ? $_GET['all'] : "";
$getsave = isset($_GET['save']) ? $_GET['save'] : "";

// 如果沒有收到資料，記錄錯誤並停止執行，避免後續 explode 出錯
if ($getall == "" || $getsave == "") {
    echo "未接收到資料 (all 或 save 參數為空)<br>";
    exit(); 
}
// ------------------------------

$hubtime = date("Y-m-d H:i:s"); // server 寫入資料的時間
echo "顯示getall:" . $getall;

// 寫入 log 檔
$myfile = fopen("newfile.txt", "w+") or die("Unable to open file!");
fwrite($myfile, $getall . ",");
fclose($myfile); // 記得關閉檔案

$myfile = fopen("newfilexxxxx.txt", "w+") or die("Unable to open file!");
fwrite($myfile, $getsave);
fclose($myfile); // 記得關閉檔案

// 切割字串
$value = explode(",", $getsave);
$a = explode(",", $getall);

// --- 取得目前最大的 tagid ---
$id = 0;
$datetime = date("Y-m-d H:i:s") . " ";

$sql = "SELECT * FROM tag WHERE tagid!=''";
$rresult = execute_sql($link, "wetness_database", $sql);
$rrowcount = mysqli_num_rows($rresult);

while ($row = mysqli_fetch_assoc($rresult)) {
    $kk = $row['tagid'];
    if ($kk > $id) {
        $id = $kk;
    }
}
$tagid = $id + 1; // 新的 id 為目前最大 id + 1


// --- 函數定義 ---

function substr_temp($temp){
    // 處理溫度的 16 進位轉換
    $a = base_convert(substr($temp,0,2),16,10) & 0xFF;
    $b = base_convert(substr($temp,2,2),16,10) & 0xFF;
    $c = base_convert(substr($temp,4,2),16,10) & 0xFF;
    $d = base_convert(substr($temp,6,2),16,2);
    
    // 判斷正負號
    if($d[0]=="1"){
        $d = base_convert(substr($temp,6,2),16,10);
        $d = -256 + $d;
    }
    $ans = bytesToFloat($a,$b,$c,$d);
    return $ans;
}

function bytesToFloat($b0,$b1,$b2,$b3){
    $mantissa = unsignedToSigned($b0 + ($b1 << 8) + ($b2 << 16), 24);
    return (float)$mantissa * pow(10, $b3);
}

function unsignedToSigned($unsigned, $size){
    if(($unsigned & (1 << $size -1)) != 0){
        $unsigned = -1 * ((1 << $size - 1) - ($unsigned & ((1 << $size - 1 ) - 1)));
    }
    return $unsigned;
}

// --- 主要邏輯：拆解封包並寫入資料庫 ---
/*
Structure:
all = tag mac, tag 31byte, hub名, 時間
save = value, rssi
*/

for($i = 0; $i < count($a); $i += 4) { 
    // 定義索引位置
    $dataIndex = $i + 1; // 原本的 bitch
    $hubIndex = $i + 2;  // d
    $timeIndex = $i + 3; // f

    // 確保陣列索引存在且不為空
    if(isset($a[$i]) && isset($a[$dataIndex]) && isset($a[$hubIndex]) && isset($a[$timeIndex]) &&
       $a[$i]!="" && $a[$dataIndex]!="" && $a[$hubIndex]!="" && $a[$timeIndex]!="") {
        
        $p = $i / 2; 
        $pppp = $p + 1; // 對應 save 陣列的索引
        
        $data = $a[$dataIndex];
        $hubNum = $a[$hubIndex];
        $timeData = $a[$timeIndex];
        
        // 檢查 value 陣列索引是否存在
        $rssi = isset($value[$pppp]) ? $value[$pppp] : 0;

        $hub_ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $hub_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // 如果經過代理，可能會有多個 IP，取第一個真實 IP
            $hub_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $hub_ip = $_SERVER['REMOTE_ADDR'];
        }
        $hub_ip = trim($hub_ip);

        // 將 HUB 資訊寫入資料庫
        if ($hubNum != "") {
            $checkHubSql = "SELECT * FROM hub WHERE hub_name='$hubNum'";
            $checkHubResult = execute_sql($link, "wetness_database", $checkHubSql);
            
            if (mysqli_num_rows($checkHubResult) > 0) {
                // HUB 已存在：更新最後時間與 IP
                $updateHubSql = "UPDATE hub SET last_update_time='$timeData', ip_address='$hub_ip' WHERE hub_name='$hubNum'";
                execute_sql($link, "wetness_database", $updateHubSql);
            } else {
                // 全新 HUB：新增紀錄 (預設開啟警示 alert_enabled=1)
                $insertHubSql = "INSERT INTO hub (hub_name, ip_address, last_update_time, alert_enabled) 
                                 VALUES ('$hubNum', '$hub_ip', '$timeData', 1)";
                execute_sql($link, "wetness_database", $insertHubSql);
            }
        }

        // 查詢 tag 表
        $sql = "SELECT * FROM tag WHERE tagmac='$a[$i]'";
        $resultt = execute_sql($link, "wetness_database", $sql);
        $rowcountt = mysqli_num_rows($resultt);
        $checkrs = mysqli_fetch_row($resultt);

        if($rowcountt > 0) {
            // --- 更新現有 Tag 資料 ---
            // 判斷時間差是否大於 30 秒 或 RSSI 訊號更強且換了 HUB
            $needUpdate = false;
            
            if((strtotime($datetime) - strtotime($checkrs[7])) > 30) {
                $needUpdate = true;
            } else {
                if($rssi > $checkrs[3] && $hubNum != $checkrs[6]) {
                    $needUpdate = true;
                } elseif ($hubNum == $checkrs[6]) {
                    $needUpdate = true;
                }
            }

            if($needUpdate) {
                $sql = "UPDATE `tag` SET `tagvalue`='$data', `hubnum`='$hubNum', `time`='$timeData', `rssi`='$rssi' WHERE `tagmac`='$a[$i]'";
                execute_sql($link, "wetness_database", $sql);
            }

        } else {
            // --- 新增 Tag 資料 ---
            // 修正：原本代碼中有 $alls[$tagrssi] 這是未定義的變數，已改為正確的 $rssi
            // 注意：SQL 欄位 `multple` 若資料庫拼字確實如此則保留，否則應改為 `multiple`
            $sql = "INSERT INTO `tag`(`tagid`, `tagmac`, `tagvalue`, `rssi`, `lastPID`, `multple`, `hubnum`, `time`)
                    VALUES ('$tagid', '$a[$i]', '$data', '$rssi', 'NULL', '0', '$hubNum', '$timeData')";
            execute_sql($link, "wetness_database", $sql);
            $tagid++;
        }

        // --- 解析 31 Byte Data ---
        $batteryPower = base_convert(substr($data, 56, 2), 16, 10);
        $pi = substr($data, 18, 2); // PID
        $pid = $pi;
        
        // 初始化變數
        $breath = 0;
        $bt = 0;     // 體溫
        $rt = 0;     // 室溫
        $wet = 0;    // 濕度
        $statusWet = 0;
        $status = 0; // 姿勢
        $leavebed = 0;
        $getup = 0;
        $spit = 0;
        $x = 0;
        $y = 0;
        $z = 0;

        if($pid == "02") {
            $wet = base_convert(substr($data, 24, 2), 16, 10);
            // Pid 02 的 XYZ 解析若需要可在此取消註解

        } elseif($pid == "03" || $pid == "05") {
            $breath = base_convert(substr($data, 30, 2), 16, 10);
            $status = substr($data, 21, 1);
            
            if($status == "2") $leavebed = 1;
            elseif($status == "5") $getup = 1;

            $statusWet = substr($data, 20, 1);
            if($statusWet == 1) $spit = 1;

            if($pid == "03") {
                $bt = substr_temp(substr($data, 40, 8)); // 體溫
            }
            $rt = substr_temp(substr($data, 22, 8));     // 室溫

        } elseif($pid == "06") {
            $breath = base_convert(substr($data, 30, 2), 16, 10);
            $status = substr($data, 21, 1);
            if($status == "2") $leavebed = 1;
            elseif($status == "5") $getup = 1;
            $wet = base_convert(substr($data, 24, 2), 16, 10);

            // 解析 XYZ 加速度計數據
            $xHex = substr($data, 34, 4);
            $yHex = substr($data, 38, 4);
            $zHex = substr($data, 42, 4);

            // unpack("s") 解碼為有號 16-bit 整數，需處理 Endian
            if(strlen($xHex) == 4 && strlen($yHex) == 4 && strlen($zHex) == 4) {
                 $x = unpack("s", pack("H*", substr($xHex, 2, 2) . substr($xHex, 0, 2)))[1];
                 $y = unpack("s", pack("H*", substr($yHex, 2, 2) . substr($yHex, 0, 2)))[1];
                 $z = unpack("s", pack("H*", substr($zHex, 2, 2) . substr($zHex, 0, 2)))[1];

                 $log = "X=$x Y=$y Z=$z\n";
                 file_put_contents("pid06_xyz_log.txt", $log, FILE_APPEND);
            }
        }

        echo '<br>pid='.$pid.' mac='.$a[$i].'<br>';
        echo 'wet='.$wet.' spit='.$spit. ' leavebed='.$leavebed.' getup='.$getup.'<br>';

        // --- 更新或新增 valueimmediate ---
        $sql = "SELECT * FROM valueimmediate WHERE mac_address='$a[$i]' AND id LIKE '$pid%'";
        $result = execute_sql($link, "wetness_database", $sql);
        $rowcount = mysqli_num_rows($result);

        if($rowcount > 0) {
            $row = mysqli_fetch_assoc($result);
            $pidnum = $row['id'];
            $sql = "UPDATE valueimmediate SET 
                    data='$data', wet='$wet', bodytemp='$bt', roomtemp='$rt', 
                    breath='$breath', SpitMilk='$spit', LeaveBed='$leavebed', GetUp='$getup', 
                    time='$timeData', hubnum='$hubNum', power='$batteryPower', 
                    x_data='$x', y_data='$y', z_data='$z' 
                    WHERE mac_address='$a[$i]' AND id='$pidnum'";
            execute_sql($link, "wetness_database", $sql);
        } else {
            // 生成新的 ID
            $sql = "SELECT * FROM `valueimmediate` WHERE `id` LIKE '$pi%' ORDER BY `id` DESC";
            $searchpid = execute_sql($link, "wetness_database", $sql);
            $resultpid = mysqli_fetch_assoc($searchpid);
            
            if($resultpid) {
                $lastNum = (int)substr($resultpid['id'], 3);
                $pidnum = $pi . "_" . sprintf("%03d", $lastNum + 1);
            } else {
                $pidnum = $pi . "_001"; // 如果沒有既有資料，從 001 開始
            }

            $sql = "INSERT INTO valueimmediate(id, mac_address, data, wet, bodytemp, roomtemp, breath, SpitMilk, LeaveBed, GetUp, time, hubnum, power, x_data, y_data, z_data) 
                    VALUES ('$pidnum', '$a[$i]', '$data', '$wet', '$bt', '$rt', '$breath', '$spit', '$leavebed', '$getup', '$timeData', '$hubNum', '$batteryPower', '$x', '$y', '$z')";
            execute_sql($link, "wetness_database", $sql);

            // 檢查 mac_correspond
            $sql = "SELECT * FROM mac_correspond WHERE mac_address='$a[$i]' AND id LIKE '$pi%'";
            $result3 = execute_sql($link, "wetness_database", $sql);
            if(mysqli_num_rows($result3) == 0) {
                $sql = "INSERT INTO mac_correspond(id, mac_address, IDP, time) VALUES ('$pidnum', '$a[$i]', 'NULL', '0000-00-00 00:00:00')";
                execute_sql($link, "wetness_database", $sql);
            }
        }

        // 更新 Tag 表的 lastPID
        $sql = "UPDATE tag SET lastPID='$pid' WHERE tagmac='$a[$i]'";
        execute_sql($link, "wetness_database", $sql);

        // --- 寫入 valuehistory ---
        $insertHistory = "INSERT INTO valuehistory(id, mac_address, data, wet, bodytemp, roomtemp, breath, SpitMilk, LeaveBed, GetUp, time, hubnum, power, rssi, hubtime, x_data, y_data, z_data) 
                          VALUES ('$pidnum', '$a[$i]', '$data', '$wet', '$bt', '$rt', '$breath', '$spit', '$leavebed', '$getup', '$timeData', '$hubNum', '$batteryPower', '$rssi', '$hubtime', '$x', '$y', '$z')";
        execute_sql($link, "wetness_database", $insertHistory);

        // 引入外部更新檔 (維持原路徑)
        include_once("C:/AppServ/www/drop_php/update_beused.php");
    }
}
?>