<html>
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.0/jquery.min.js"></script>
		<script type="text/javascript" language="javascript">
			google.load("jquery", "1.3");
    	</script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            // 獲取當前頁面的URL
            var currentUrl = window.location.href;

            // 遍歷所有<a>標籤，查找與當前URL相匹配的href屬性
            $('.device-menu li a').each(function(){
                var linkUrl = $(this).attr('href');
                // 如果當前URL與href屬性相匹配，則將父級li元素添加active類
                if (currentUrl.indexOf(linkUrl) !== -1) {
                    $(this).parent('li').addClass('active');
                }
            });
        });
    </script>
	<!-- 左側欄CSS -->
	<link rel="stylesheet" href="css/left_side.css">
	<!-- <style>
		@import url('http://fonts.googleapis.com/css?family=Source+Code+Pro');
		@import url(css/ward_item_style.css);
	</style> -->

	<body>
		<div class="icon">
                <img src="picture/item_user.png" alt="user">
            </div>
            <div class="display_toggle" >
                <h2>病患列表</h2>
                <ul class="device-menu ">
					<li><a href="frame_patient_list.php">住院中病患列表</a></li>
					<li><a href="discharged_member_list.php">出院病患歷史列表</a></li>
                    <!-- <li><a href="#PID02">電子尿布</a></li>
                    <li><a href="#PID03">Nursemaid-感溫衣</a></li>
                    <li><a href="#PID05">Nursemaid-電子腰帶</a></li>
                    <li><a href="#PID06">Nursemaid-電子尿布</a></li> -->
                </ul>
            </div>

		<!-- <img src="picture/ward_select.png" width="90%" style="display:block; margin:auto;"> -->
		<!--<br>-->
		<!-- <div class="item" >
			<a href="<?php echo $_SERVER['PHP_SELF']."?item=Hospitalized"; ?>" target="">住院中</a>
			<a href="<?php echo $_SERVER['PHP_SELF']."?item=Discharged"; ?>" target="">已出院</a>
		</div>  -->
	</body>
</html>