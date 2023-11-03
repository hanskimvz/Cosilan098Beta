<?PHP
session_start();
date_default_timezone_set ( "UTC" );
// print "<pre>"; print_r($_GET); print "</pre>";
include "./inc/query_functions.php";
$errors = array();

$getData = array(
	"api_key" =>"",
	"getCountingDB" =>"",
	"from" => "",
	"to" => "",
	"interval" => "",
	"square_code" => "",
	"store_code" => "",
	"camera_code" => "",
	"counter_label" => "",
	"group" => "",
	"order" => ""
);

foreach($_GET as $A=>$B){
	if ($A == 'square') {
		$A = 'square_code';
	}
	else if ($A == 'store') {
		$A = 'store_code';
	}
	else if ($A == 'camera') {
		$A = 'camera_code';
	}
	else if ($A == 'label') {
		$A = 'counter_label';
	}
	// else if ($A == 'reportfmt'){
	// 	continue;
	// }
	$getData[$A] = $B;
}
// print "<pre>"; print_r($getData); print "</pre>";
$getData['to'] = date("Y-m-d", strtotime($getData['to'])+3600*24);
$getData['order'] = 'asc';
$arr_ref = makeCategory($getData['from'], $getData['to'], $getData['interval']);
// print "<pre>";  print_r($arr_ref); print "</pre>";
if (strtoupper(PHP_OS) == "WINNT") {
	$_GET = $getData;
	require "./pubSVC.php";
	$arr = json_decode($json_str, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	if ($arr_rs['code'] != 1) {
		print "<pre>";  print_r($arr_rs); print "</pre>";
		exit;
	}

	$sq = "select varstr, eng, kor, chi, page from ".$DB_CUSTOM['language']." where page='export.php'"; 
	$rs = mysqli_query($connect, $sq);
	while ($assoc = mysqli_fetch_assoc($rs)){
		$arr_lang[$assoc['varstr']] = $assoc[$_COOKIE['selected_language']];
	}
}
else {
	$getString = http_build_query($getData);
	$opts = array('http' =>
		array(
			'method' => 'GET',
			'header' => 'Content-type: application/x-www-form-urlencoded',
		)
	);
	$context = stream_context_create($opts);
	$url = "http://".$_SERVER['HTTP_HOST']."/pubSVC.php?".$getString;
	$json_str = file_get_contents($url, false, $context);
	$arr_rs = json_decode($json_str, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	if ($arr_rs['code'] != 1) {
		print "<pre>";  print_r($arr_rs); print "</pre>";
		exit;
	}

	$url = "http://".$_SERVER['HTTP_HOST']."/pubSVC.php?getLanguage&api_key=".$getData['api_key']."&page=export.php";
	$json_str = file_get_contents($url, false, $context);
	$arr = json_decode($json_str, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	foreach($arr['data'] as $A =>$B){
		$arr_lang[$B['varstr']] = $B[$_COOKIE['selected_language']];
	}
}
// print "<pre>"; print_r($arr_lang); print "</pre>";

$arr_ref['struct'] = [];
$arr_ref['struct_name'] = [];
$arr_ref['counter_label'] = [];

for( $i=0; $i<sizeof($arr_rs['data']); $i++){
	$datetime = date($arr_ref['dateformat'], $arr_rs['data'][$i]['timestamp'] +3600*8);
	$dist = $arr_rs['data'][$i]['square_code'];
	$dist_name = $arr_rs['data'][$i]['square_name'];
	if (isset($arr_rs['data'][$i]['store_code']) && $arr_rs['data'][$i]['store_code']) {
		$dist = $arr_rs['data'][$i]['store_code'].'@'.$dist;
		$dist_name = $arr_rs['data'][$i]['store_name'].'@'.$dist_name;
	}
	if (isset($arr_rs['data'][$i]['camera_code']) && $arr_rs['data'][$i]['camera_code']) {
		$dist = $arr_rs['data'][$i]['camera_code'].'@'.$dist;
		$dist_name = $arr_rs['data'][$i]['camera_name'].'@'.$dist_name;
	}
	$counter_label = $arr_rs['data'][$i]['counter_label'];

	if (!in_array($dist, $arr_ref['struct'])) {
		array_push($arr_ref['struct'], $dist);
		array_push($arr_ref['struct_name'], $dist_name);
	}
	if (!in_array($counter_label, $arr_ref['counter_label'])) {
		array_push($arr_ref['counter_label'], $counter_label);
	}

	$arr_result[$datetime][$dist][$counter_label] = $arr_rs['data'][$i]['counter_val'];
}

for ($i=0; $i<$arr_ref['duration']; $i++){
	$datetime = $arr_ref['datetime'][$i];
	for ($j=0; $j<sizeof($arr_ref['struct']); $j++) {
		$dist = $arr_ref['struct'][$j];
		for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
			$counter_label = $arr_ref['counter_label'][$k];
			if(!isset($arr_result[$datetime][$dist][$counter_label]) || !$arr_result[$datetime][$dist][$counter_label]){
				$arr_result[$datetime][$dist][$counter_label] = 0;
			}
		}
	}
}
// print_r($arr_ref);
// print (sizeof($arr_result));
// print (date("Y-m-d H:i:s", 1643505000));
// print_r($arr_result);

unset($arr_rs);
// ksort($arr_result);
if($_GET['order'] == 'desc') {
	array_pop($arr_ref['datetime']);
	$arr_ref['datetime'] = array_reverse($arr_ref['datetime']);
}

if ($_GET['reportfmt'] == 'table') {
	$TABLE_BODY = '';
	for ($i=0; $i<$arr_ref['duration']-1; $i++) {
		$datetime = $arr_ref['datetime'][$i];
		$TABLE_BODY .= '<tr><td>'. $datetime.'</td>';
		for($j=0; $j<sizeof($arr_ref['struct']); $j++) {
			for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
				$TABLE_BODY .='<td align="right">'.($arr_result[$datetime][$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]]).'</td>';
			}
		}
		$TABLE_BODY .= '</tr>';
	}
	$h_line = [];
	for($i=0; $i<sizeof($arr_ref['struct']); $i++) {
		$exp_spot = explode('@', $arr_ref['struct_name'][$i]);
		$rowspan = sizeof($exp_spot);
		
		$colspan = sizeof($arr_ref['counter_label']);
		for($j=0; $j<$rowspan; $j++) {
			if (!isset($h_line[$j])) {
				$h_line[$j] = "";
			}
			$h_line[$j] .= '<th colspan="'.$colspan.'">'.$exp_spot[$rowspan-$j-1].'</th>';
		}
		for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
			if (!isset($h_line[$j])) {
				$h_line[$j] = "";
			}			
			$h_line[$j] .= '<th width="100px">'.$arr_lang[$arr_ref['counter_label'][$k]].'</th>';
		}
	}
	$TABLE_HEAD  = '<tr><th width="200px" rowspan="'.($rowspan+1).'">Date</th>'.$h_line[0].'</tr>';
	for($i=0; $i<$rowspan; $i++) {
		$TABLE_HEAD  .= '<tr>'.$h_line[$i+1].'</tr>'; //<tr>'.$h_line[2].'</tr><tr>'.$h_line[3].'</tr>';
	}
	$TABLE_HEAD = '<thead>'.$TABLE_HEAD.'</thead>';

	print "<HTML><HEAD><META http-equiv='Content-Type' content='text/html; charset=utf-8' /><META http-equiv='Content-Language' content='en' /><META http-equiv='Pragma' content='no-cache' /><META http-equiv='Cache-Control' content='no-cache' />
	<style type=\"text/css\">
		body {background-color: #fff; color: #222; font-family: sans-serif;}
		table {border-collapse: collapse; border: 0; box-shadow: 1px 2px 3px #eee;}
		td, th {border: 1px solid #aaa; font-size: 75%; vertical-align: middle; padding: 4px 5px;}
	</style></HEAD> ";
	print '<TABLE>'.$TABLE_HEAD.'<tbody>'.$TABLE_BODY.'</tbody></table>';
	print "</HTML>";
	exit();
}

else if($_GET['reportfmt'] == 'json') {
	Header("Content-type: text/json");
	$arr_rs =  array();
	for ($i=0; $i<$arr_ref['duration']-1; $i++) {
		$datetime = $arr_ref['datetime'][$i];
		for($j=0; $j<sizeof($arr_ref['struct']); $j++) {
			for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
				$arr_rs[$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]][$datetime] = $arr_result[$datetime][$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]];
			}
		}
	}
	// print_r($arr_rs);
	print json_encode($arr_rs, JSON_PRETTY_PRINT);
	exit();
}
else if($_GET['reportfmt']=='csv') {
	$TABLE_BODY = "";
	for ($i=0; $i<$arr_ref['duration']-1; $i++) {
		$datetime = $arr_ref['datetime'][$i];
		$TABLE_BODY .= $datetime.',';
		for($j=0; $j<sizeof($arr_ref['struct']); $j++) {
			for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
				$TABLE_BODY .= ($arr_result[$datetime][$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]]).',';
			}
		}
		$TABLE_BODY .= "\n";
	}
	$TABLE_HEAD = "datetime,";
	for($i=0; $i<sizeof($arr_ref['struct']); $i++) {
		for($j=0; $j<sizeof($arr_ref['counter_label']); $j++) {
			$TABLE_HEAD .= $arr_lang[$arr_ref['counter_label'][$j]].'@'.$arr_ref['struct_name'][$i].',';
		}
	}
	$TABLE_HEAD .= "\n";
	$TABLE_HEAD .= "datetime,";
	for($i=0; $i<sizeof($arr_ref['struct']); $i++) {
		for($j=0; $j<sizeof($arr_ref['counter_label']); $j++) {
			$TABLE_HEAD .= $arr_ref['counter_label'][$j].'@'.$arr_ref['struct'][$i].',';
		}
	}	
	$TABLE_HEAD .= "\n";
	$file_name = date("YmdHis").time().".csv";
	Header("Content-Type: text/csv; charset=UTF-8;");
	Header("Content-Disposition: attachment; filename=".$file_name);  
	Header("Content-Transfer-Encoding: binary");  
	Header("Expires: 0");
	echo "\xEF\xBB\xBF"; 
	print $TABLE_HEAD.$TABLE_BODY;
	exit();
}
else if($_GET['reportfmt']=='curve') {
	$x_labels = json_encode($arr_ref['datetime'], true);
	$arr_ds= array();
	for ($i=0; $i<$arr_ref['duration']-1; $i++) {
		$datetime = $arr_ref['datetime'][$i];
		for($j=0; $j<sizeof($arr_ref['struct']); $j++) {
			for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
				// $div = $arr_ref['counter_label'][$k].'@'.$arr_ref['struct'][$j];
				$div = $arr_lang[$arr_ref['counter_label'][$k]].'@'.$arr_ref['struct_name'][$j];
				$arr_ds[$div][$i] = ($arr_result[$datetime][$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]]);
			}
		}		
	}
	$arr_rs =  array();
	$arr_wcolor = array (
		'red' => 'rgb(255, 99, 132)', 
		'orange' => 'rgb(255, 159, 64)', 
		'yellow' => 'rgb(255, 205, 86)', 
		'green' => 'rgb(75, 192, 192)', 
		'blue' => 'rgb(54, 162, 235)', 
		'purple'=> 'rgb(153, 102, 255)', 
		'grey'=> 'rgb(201, 203, 207)', 
		'black'=> 'rgb(60, 60, 60)',
	);	
	foreach($arr_ds as $label =>$data) {
		// $rand_color = 'rgb('.rand(10,250).','.rand(10,250).','.rand(10,250).')';
		$rand_color = array_shift($arr_wcolor);
		array_push($arr_rs, ['label'=>$label, 'data'=>$data, 'borderWidth'=>3, 'borderColor'=>$rand_color, 'backgroundColor'=>$rand_color, 'fill'=>false, 'tension'=>0.5]);
	}
	
	$dataset = json_encode($arr_rs, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);

	// print "<pre>"; print_r($dataset); print "</pre>";

?>
<script src="../js/chart.js"></script>		
<canvas id="myChart" width="1200" height="400"></canvas>
<script>
	const ctx = document.getElementById('myChart').getContext('2d');
	let config =  {
		type: 'line',
		data: {},
		options: {
			responsive: true,
			plugins: {
				title: {
					display: true,
					// text: 'Chart.js Line Chart - Cubic interpolation mode'
				},
				legend:{
					display:true,
					position:'top',
					labels:{
						// color: 'rgb(255,99,132)'
					}
				}
			},
			interaction: {
				intersect: false,
				mode:'index',
				axis:'y'
			},
			scales: {
				x: {
					display: true,
					title: {
						display: true
					}
				},
				y: {
					display: true,
					title: {
						display: true,
						text: 'Count'
					},
					suggestedMin: 0,
					suggestedMax: 200
				}
			}
		},
	};
	const myChart = new Chart(ctx, config);
	let data = {
		labels: <?=$x_labels?>,
		datasets: <?=$dataset?>
	};
	myChart.data = data;
	myChart.update();
</script>
<?PHP	
	// print $TABLE_BODY;
}
// datasets: [
	// {"label":"\u5165@Out_Cam@yrdy@tttt","data":["17332","8734","9089","8105","9040","9718","12993","521"],"borderWidth":3,"borderColor":"rgb(255, 99, 132)","backgroundColor":"rgb(255, 99, 132)","fill":false,"tension":0.5},
	// {"label":"\u51fa@Out_Cam@yrdy@tttt","data":["31190","15232","16101","15602","14665","17426","19177","915"],"borderWidth":3,"borderColor":"rgb(255, 159, 64)","backgroundColor":"rgb(255, 159, 64)","fill":false,"tension":0.5},
	// {"label":"\u5165@ac@yrdy@tttt","data":["90","31","40","28","56","41","73",0],"borderWidth":3,"borderColor":"rgb(255, 205, 86)","backgroundColor":"rgb(255, 205, 86)","fill":false,"tension":0.5},
	// {"label":"\u51fa@ac@yrdy@tttt","data":["90","32","43","24","56","42","71",0],"borderWidth":3,"borderColor":"rgb(75, 192, 192)","backgroundColor":"rgb(75, 192, 192)","fill":false,"tension":0.5}]	};
	
exit();

// if($_GET['reportfmt'] == 'json') {
// 	Header("Content-type: text/json");
// 	for ($i=0; $i<$arr_ref['duration']-1; $i++) {
// 		$datetime = $arr_ref['datetime'][$i];
// 		for($j=0; $j<sizeof($arr_ref['struct']); $j++) {
// 			for($k=0; $k<sizeof($arr_ref['counter_label']); $k++) {
// 				$arr_rs[$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]][$datetime] = $arr_result[$datetime][$arr_ref['struct'][$j]][$arr_ref['counter_label'][$k]];
// 			}
// 		}
// 	}	
// 	print json_encode($arr_rs,true);
// }
// else { 
// 	$from_ts = strtotime($_GET['from']);
// 	$to_ts = strtotime($_GET['to']);

// 	$arr_rs = json_decode($json_str, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
// 	// print_r($arr_rs);
// 	$arr_list = array();
// 	$arr_ref = array();
// 	$arr_label = array();	

// 	for ($i=0; $i<$arr_rs['records']; $i++) {
// 		if ($_GET['interval'] == 'tenmin') {
// 			$dateformat = "Y-m-d H:i:s";
// 		}
// 		else if ($_GET['interval'] == 'hourly') {
// 			$dateformat = "Y-m-d H:i";
// 		}
// 		else if ($_GET['interval'] == 'daily') {
// 			$dateformat = "Y-m-d";
// 		}
// 		if ($_GET['interval'] == 'weekly') {
// 			$date_tag = sprintf("%04d-%02dW", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['week']);
// 		}
// 		else if ($_GET['interval'] == 'monthly') {
// 			$date_tag = sprintf("%04d-%02d", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['month']);
// 		}
// 		else {
// 			$date_tag= date($dateformat, mktime($arr_rs['data'][$i]['hour'], $arr_rs['data'][$i]['min'], 0, $arr_rs['data'][$i]['month'], $arr_rs['data'][$i]['day'], $arr_rs['data'][$i]['year']));
// 		}
		
// 		if (!isset($_GET['group'])) {
// 			$group_tag = 'ALL';
// 		}
// 		else if ($_GET['group'] == 'square') {
// 			$group_tag = $arr_rs['data'][$i]['square_code'];
// 			$group_tag = $arr_rs['data'][$i]['square_name'];
// 		}		
// 		else if ($_GET['group'] == 'store') {
// 			$group_tag = $arr_rs['data'][$i]['store_code'];
// 			$group_tag = $arr_rs['data'][$i]['store_name'];
// 		}
// 		else if ($_GET['group'] == 'camera') {
// 			$group_tag = $arr_rs['data'][$i]['camera_code'];
// 			$group_tag = $arr_rs['data'][$i]['camera_name'];
// 		}
// 		if(!in_array($group_tag, $arr_ref)) {
// 			array_push($arr_ref, $group_tag);
// 		}
		
// //		$group_tag = $arr_rs['data'][$i]['square_code']."/".$arr_rs['data'][$i]['store_code']."/".$arr_rs['data'][$i]['camera_code'];		
// 		$label_tag = $arr_rs['data'][$i]['counter_label'];
// 		if(!in_array($label_tag, $arr_label)) {
// 			array_push($arr_label, $label_tag);
// 		}		
// 		$arr_list[$date_tag][$group_tag][$label_tag] = $arr_rs['data'][$i]['counter_val'];
// 	}
// //	print "<pre>"; 	print_r($arr_rs);	print_r($arr_list);	print "</pre>";

// 	if ($_GET['reportfmt'] == 'table') {

// 		foreach($arr_list as $datetime =>$arr){
// 			$TABLE_BODY .= '<tr>';
// 			$TABLE_BODY .='<td>'.$datetime.'</td>';
// 			for($j=0; $j<sizeof($arr_ref); $j++) {
// 				for($k=0; $k<sizeof($arr_label); $k++) {
// 					$TABLE_BODY .='<td align="right">'.number_format($arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]]).'</td>';
// 				}
// 			}
// 			$TABLE_BODY .= '</tr>';
// 		}
		
// 		for($i=0; $i<sizeof($arr_ref); $i++) {
// 			if($_GET['label'] == 'all') {
// 				$colspan=4;
// 			}
// 			else {
// 				$colspan=sizeof($arr_label);
// 			}
// 			$first_line .= '<th colspan="'.$colspan.'">'.$arr_ref[$i].'</th>';
// 			for($j=0; $j<sizeof($arr_label); $j++) {
// 				$second_line .= '<th width="100px">'.$arr_label[$j].'</th>';
// 			}
// 		}
// 		$TABLE_HEAD  = '<tr><th width="200px" rowspan="2">Date</th>'.$first_line.'</tr><tr>'.$second_line.'</tr>';
// 		$TABLE_HEAD = '<thead>'.$TABLE_HEAD.'</thead>';

// 		print "<HTML><HEAD><META http-equiv='Content-Type' content='text/html; charset=utf-8' /><META http-equiv='Content-Language' content='en' /><META http-equiv='Pragma' content='no-cache' /><META http-equiv='Cache-Control' content='no-cache' /></HEAD> ";
// 		print '<TABLE border=1 cellpadding=0 cellspacing=0>'.$TABLE_HEAD.'<tbody>'.$TABLE_BODY.'</tbody></table>';
// 		print "</HTML>";
// 	}
	
// 	else if($_GET['reportfmt']=='csv') {
// 		for($i=0; $i<sizeof($arr_ref); $i++) {
// 			for($j=0; $j<sizeof($arr_label); $j++) {
// 				if($TABLE_HEAD) {
// 					$TABLE_HEAD .= ',';
// 				}
// 				$TABLE_HEAD .= $arr_label[$j].'@'.$arr_ref[$i];
// 			}
// 		}
// 		$TABLE_HEAD = "\n\r".'DATE,'.$TABLE_HEAD."\r\n";
// 		foreach($arr_list as $datetime =>$arr){
// 			$TABLE_BODY .=$datetime;
// 			for($j=0; $j<sizeof($arr_ref); $j++) {
// 				for($k=0; $k<sizeof($arr_label); $k++) {
// 					$TABLE_BODY .=','.$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]];
// 				}
// 			}
// 			$TABLE_BODY .= "\r\n";
// 		}		
	
// 		$TABLE_BODY = $TABLE_HEAD.$TABLE_BODY;
// 		$file_name = date("YmdHis").mktime().".csv";
// 		Header("Content-type: application/octet-stream");
// 		Header("Content-Length: ".filesize($file_dir.$file_micro));    
// 		Header("Content-Disposition: attachment; filename=".$file_name);  
// 		Header("Content-Transfer-Encoding: binary");  
// 		Header("Expires: 0");  
// 		print $TABLE_BODY;
// 	}
// 	else if($_GET['reportfmt']=='curve') {
// 		$TABLE_BODY = '<div id="chart_curve"></div>'.
// 			'<script src="../js/app.js"></script>'.
// 			'<script>'.
// 			'var options = {
// 				chart: {
// 					height: 500,
// 					type: "line",
// 					zoom:{enabled:false,},
// 				},
// 				var colors:[ "#008FFB", "#00E396", "#FEB019", "#FF4560", "#775DD0", "#546E7A", "#26a69a", "#D10CE8"];
// 				dataLabels: { enabled: true, },
// 				series: [],
// 				title: { text: "footfall", },
// 				legend: { position:"top", },

// 				noData: { text: "Loading..." },
// 				xaxis: {
// 					type:"datetime",
// 					labels:{
// 						show:true,
// 						showDuplicates: true,
// 						datetimeUTC: false,
// 						datetimeFormatter: {
// 							year: "yyyy",
// 							month: "yyyy-MM",
// 							day: "MM/dd",
// 							hour: "HH:mm",
// 						},
// 					},
// 				},
// 			};'.
// 			'var footfall_chart = new Chart(document.getElementById("chart_curve").getContext("2d"), options);'.
// 			'</script>';
// 		$TABLE_BODY .='<script>
// 			footfall_curve.updateSeries(response["curve_data"] );
// 			footfall_curve.updateOptions({
// 				chart: { height: 150,},	
// 				colors: ["rgb(153, 102, 255)"],	
// 				dataLabels: {enabled: false,},
// 				xaxis: {
// 					categories: timeToDatetime(response["curve_label"],"YYYY-MM-DD HH:mm"),
// 				},
// 				tooltip: {
// 					x: { format: tooltip_date,},
// 				},
// 			});
// 		</script>';
			
		
// 		print $TABLE_BODY;
// 	}

// }

// exit;

?>


