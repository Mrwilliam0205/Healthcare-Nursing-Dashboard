<!DOCTYPE html>
<html>
	<title>
        <?php include ('title.php'); ?>
    </title>
	<!-- 主架構CSS -->
    <link rel="stylesheet" href="css/Main_Frame.css">
	<body>
		<?php
			
			if(!isset($_GET['IDP'])){
				echo "<script>alert('警告：查無入住資料');location.href = 'login.php';</script>";
				exit;
			}else{
				$IDP=$_GET['IDP'];
			}

		?>
		<div class="top_side" id="top-menu">
			<!-- 寫作區塊開始 -->
			<?php include ('menubar.php'); ?>
			<!-- 寫作區塊結束 -->
    	</div>
    	<div class="content" >
        	<div class="left_side" data-width="100">
				<!-- 寫作區塊開始 -->
				<?php include ('item_patient_info.php'); ?>
				<!-- 寫作區塊結束 -->
        	</div>
        	<div class="middle_side" data-width="400">
				<!-- 寫作區塊開始 -->
				<?php include ('flot/personal_history.php'); ?>
				<?php //include ('flot/test.php'); ?>
				<!-- 寫作區塊結束 -->
        	</div>
			<!-- <div class="right_side" data-width="200">
				<?php //include ('/tag_info.php'); ?>
			</div> -->
		</div>
	</body>
</html>
