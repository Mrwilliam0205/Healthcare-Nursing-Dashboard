<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
<script type="text/javascript" src="flot/jquery.flot.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.time.js"></script>    
<script type="text/javascript" src="flot/jquery.flot.symbol.js"></script>
<script>
	var data_temp = [];
	var dataset;
	var totalPoints = 100;
	var updateInterval = 1000;
	var now = new Date().getTime();

	var options = {
		series: { lines: { lineWidth: 1.2 }, bars: { align: "center", barWidth: 500, lineWidth: 1 } },
		xaxis: {
			mode: "time", tickSize: [60, "second"],
			tickFormatter: function (v, axis) {
				var date = new Date(v);
				if (date.getSeconds() % 20 == 0) {
					var hours = date.getHours() < 10 ? "0" + date.getHours() : date.getHours();
					var minutes = (date.getMinutes()-2) < 10 ? "0" + (date.getMinutes()-2) : (date.getMinutes()-2);
					var seconds = date.getSeconds() < 10 ? "0" + date.getSeconds() : date.getSeconds();
					return hours + ":" + minutes + ":" + seconds;
				} else { return ""; }
			},
			axisLabel: "Time", axisLabelUseCanvas: true, axisLabelFontSizePixels: 12, axisLabelFontFamily: 'Verdana, Arial', axisLabelPadding: 10
		},
		yaxes: [
			{ min :20, max: 45, axisLabel: "Temp", axisLabelUseCanvas: true, axisLabelFontSizePixels: 12, axisLabelFontFamily: 'Verdana, Arial', axisLabelPadding: 6 }
		],
		legend: { noColumns: 0, position:"nw" },
		grid: { backgroundColor: { colors: ["#FFFFFF", "#EDF5FF"] } }
	};

	function initData() {
		for (var i = 0; i < totalPoints; i++) {
			var initVal = [now += updateInterval, 0];
			data_temp.push(initVal);
		}
	}

	function GetData() {
		$.ajaxSetup({ cache: false });
		$.ajax({
			url: "ajax/get_temp.php?IDP=<?php echo $_GET['IDP'];?>",
			dataType: 'json',
			success: update,
			error: function () { setTimeout(GetData, updateInterval); }
		});
	}

	function update(_data) {
		data_temp.shift();
		now += updateInterval;

		var currentTemp = parseFloat(_data[0]["temp"]);
		if(isNaN(currentTemp)) currentTemp = 0;

		data_temp.push([now, currentTemp]);
		
		// 刪除了導致報錯的 yaxis: 2
		dataset = [
			{ label: "Temp:" + currentTemp + "°C", data: data_temp, color: "#0044FF", lines: { fill: true, lineWidth: 1.2 } }
		];

		$.plot($("#flot-placeholder1"), dataset, options);
		setTimeout(GetData, updateInterval);
	}

	$(document).ready(function () {
		initData();
		// 刪除了導致報錯的 yaxis: 2
		dataset = [ { label: "Temp:", data: data_temp, color: "#0044FF", lines: { fill: true, lineWidth: 1.2 } } ];
		$.plot($("#flot-placeholder1"), dataset, options);
		setTimeout(GetData, updateInterval);
	});
</script>
<div id="flot-placeholder1" style="width:100%; height:230px;"></div>