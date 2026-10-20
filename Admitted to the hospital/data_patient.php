<link rel="stylesheet" href="../css/data_patient.css" type="text/css" />

<!-- 1. 將標題移到白色卡片外部，並設定向下偏移 (margin-top: 15vh) 讓整個區塊往下沉 -->
<div style="max-width: 900px; width: 100%; margin: 15vh auto 20px auto;">
    
    <!-- 標題放在這裡，靠左對齊 -->
    <h2 style="margin-bottom: 20px; color: #0f172a; font-size: 1.25rem; font-weight: bold;">病患入院登記</h2>

    <!-- 2. 這是白底卡片容器 -->
    <div style="background: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        
        <div class="form_content">
            <form method='POST' action='process_data_patient.php' onsubmit='return sub();'>	
                
                <!-- 3. 三欄式網格排版 (上3下3) -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; align-items: start;">
                    
                    <div class="wardroom" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="wardroomList" style="font-weight: bold; font-size: 0.9rem;">床位 <span style="color:red;">*</span></label>
                        <select id="wardroomList" name="wardroom" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="">-- 請選擇 --</option>
                        </select>
                    </div>
                    
                    <div class="name" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="Name" style="font-weight: bold; font-size: 0.9rem;">姓名 <span style="color:red;">*</span></label>
                        <input name="Name" id="Name" maxlength="16" type="text" placeholder="請輸入姓名" class="uidIcon" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>
                    
                    <div class="IDP" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="IDP" style="font-weight: bold; font-size: 0.9rem;">身分證字號 <span style="color:red;">*</span></label>
                        <input name="IDP" id="IDP" maxlength="16" type="text" placeholder="例如 : A123456789" class="uidIcon" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>
                    
                    <div class="Birthday" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="Birthday" style="font-weight: bold; font-size: 0.9rem;">生日 <span style="color:red;">*</span></label>
                        <input name="Birthday" id="Birthday" type="date" placeholder="yyyy/mm/dd" class="uidIcon" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>
                    
                    <div class="Blood" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="Blood" style="font-weight: bold; font-size: 0.9rem;">血型 <span style="color:red;">*</span></label>
                        <select id="Blood" name="Blood" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="">-- 請選擇 --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="O">O</option>
                            <option value="AB">AB</option>
                        </select>
                    </div>
                    
                    <div class="sex" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="sex" style="font-weight: bold; font-size: 0.9rem;">性別 <span style="color:red;">*</span></label>
                        <select id="sex" name="sex" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="">-- 請選擇 --</option>
                            <option value="0">女</option>
                            <option value="1">男</option>
                            <option value="2">其他</option>
                        </select>
                    </div>

                </div> <!-- 網格結束 -->

                <!-- 底部按鈕區 -->
                <div class="submit" style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                    <button type="button" style="padding: 8px 16px; border-radius: 4px; border: 1px solid #ccc; background: #e2e8f0; font-weight: bold; cursor: pointer;" onclick="history.back();">取消</button>
                    <input type="submit" value="完成登記並檢視個人頁面" id="form_button" style="padding: 8px 16px; border-radius: 4px; border: none; background: #0284c7; color: white; font-weight: bold; cursor: pointer;" />
                </div>
            </form>
        </div>
    </div> <!-- 白色卡片結束 -->
</div> <!-- 最外層容器結束 -->

<?php
require_once("C:/xampp/htdocs/Nursemaid_pro_sitting/login_database/dbtools.inc.php");
$link = create_connection();

$item = isset($item) ?$item : "data_member";

$sql = "SELECT * FROM warddata
        WHERE IDP
        NOT In (SELECT IDP FROM mac_correspond WHERE IDP Not In ('NULL') AND id LIKE '$item%')
        ORDER BY `ward`,`bed` ASC";
$IDP_query = execute_sql($link, "wetness_database", $sql);
if(!$IDP_query){
    echo "Execute SQL failed : ". mysqli_error($link);
    exit;
}
$IDP_Arr = array();$IDP_Count = 0;
while($IDP_rows = mysqli_fetch_array($IDP_query)){
    if($IDP_rows[2] == "0"){
        $IDP_Arr[$IDP_Count][0] =$IDP_rows[0]."-".$IDP_rows[1];$IDP_Count++;
    }
}
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript">
    var bedSelect = document.getElementById("wardroomList");
    <?php for($i=0; $i<$IDP_Count; $i++){ ?>
        bedSelect.options[<?php echo $i + 1 ?>] = new Option('<?php echo $IDP_Arr[$i][0]?>', '<?php echo $IDP_Arr[$i][0]?>');
    <?php } ?>

    function sub(){
        var wardroom = $("#wardroomList").val();
        var Name = $("#Name").val();
        var IDP = $("#IDP").val();
        var Birthday = $("#Birthday").val();
        var Blood = $("#Blood").val();
        var sex = $("#sex").val();
        
        if(wardroom === "" || Name.trim() === "" || IDP.trim() === "" || Birthday === "" || Blood === "" || sex === ""){
            alert("請確實填寫所有欄位");
            if(wardroom === "") $("#wardroomList").focus();
            else if(Name.trim() === "") $("#Name").focus();
            else if(IDP.trim() === "") $("#IDP").focus();
            else if(Birthday === "") $("#Birthday").focus();
            else if(Blood === "") $("#Blood").focus();
            else if(sex === "") $("#sex").focus();
            return false;
        }else{
            return true;
        }
    }
</script>