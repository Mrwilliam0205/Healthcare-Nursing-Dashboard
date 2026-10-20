<html lang="zh-TW">
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript" language="javascript">
	google.load("jquery", "1.3");
</script>
</script>
<head>
<title>旅遊系統</title>
</head>
<script type="text/javascript">
var check=function(){
	
    var select_1=$('#select_1').val();

    $.ajax({
        url: "trip-ajax.php",
        data: "select_1="+select_1,
        type:"POST",
        dataType:'json',

        success: function(message){
			var len = message.length;//紀錄資料長度
			document.getElementById("select_2").options[0]=new Option("共"+len+"筆相關結果");
			for(var i=1;i<message.length;i++){
				var data = message[1]["mac_address"];
				document.getElementById("select_2").options[i]=new Option(data);
			}
        },

        error:   function(jqXHR, textStatus, errorThrown){ 
            document.getElementById("select_2").options[1]=new Option('error');
        }
    });
}
</script>
<body>

<form method='get' action='process_mac_correspond.php'>
    選項：
    <select id="select_1" onchange="check()">
		<option value="" selected="selected">請選擇</option>
        <option value="Wet11">Wet11</option>
        <option value="2">2</option>
        <option value="3">3</option>
    </select>
     
	細節：	
	 <select id="select_2" >
		<option value="" selected="selected">請選擇</option>
        <option></option>
	</select>		
</form>	




</html>