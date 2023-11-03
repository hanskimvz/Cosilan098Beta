<?PHP
date_default_timezone_set ( "UTC" );
//print "<pre>"; print_r($_GET); print "</pre>";

$_GET['getAgeDB'] = '';

if(!$_GET['reportfmt']) {
	$_GET['reportfmt'] = 'table';
}

if ($_GET['group']=='none') {
	unset($_GET['group']);
}

$_GET['store_code'] = $_GET['store'];

include "./pubSVC.php";

if($_GET['reportfmt'] == 'json') {
	$TABLE_BODY = $json_str;
	Header("Content-type: text/json");
	print $TABLE_BODY;
}
else { 
	$arr_rs = json_decode($json_str, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	$arr_list = array();
	$arr_ref = array();
	$arr_label = array();	
	for ($i=0; $i<$arr_rs['records']; $i++) {
		if ($_GET['interval'] == 'tenmin') {
			$date_tag = sprintf("%04d-%02d-%02d %02d:%02d", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['month'], $arr_rs['data'][$i]['day'], $arr_rs['data'][$i]['hour'], $arr_rs['data'][$i]['min']);
		}
		else if ($_GET['interval'] == 'hourly') {
			$date_tag = sprintf("%04d-%02d-%02d %02d:00", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['month'], $arr_rs['data'][$i]['day'], $arr_rs['data'][$i]['hour']);
		}
		else if ($_GET['interval'] == 'daily') {
			$date_tag = sprintf("%04d-%02d-%02d", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['month'], $arr_rs['data'][$i]['day']);
		}
		else if ($_GET['interval'] == 'weekly') {
			$date_tag = sprintf("%04d-%02dW", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['week']);
		}
		else if ($_GET['interval'] == 'monthly') {
			$date_tag = sprintf("%04d-%02d", $arr_rs['data'][$i]['year'], $arr_rs['data'][$i]['month']);
		}
		
		if (!isset($_GET['group'])) {
			$group_tag = 'ALL';
		}
		else if ($_GET['group'] == 'square') {
			$group_tag = $arr_rs['data'][$i]['square_code'];
		}		
		else if ($_GET['group'] == 'store') {
			$group_tag = $arr_rs['data'][$i]['store_code'];
		}
		else if ($_GET['group'] == 'camera') {
			$group_tag = $arr_rs['data'][$i]['camera_code'];
		}
		if(!in_array($group_tag, $arr_ref)) {
			array_push($arr_ref, $group_tag);
		}

		$arr_list[$date_tag][$group_tag]['age'] = $arr_rs['data'][$i]['age'];
	}
//	print "<pre>"; 	print_r($arr_rs); print "<pre>";
//	print "<pre>";  print_r($arr_list);	print "</pre>";

	if ($_GET['reportfmt'] == 'table') {
		foreach($arr_list as $datetime =>$arr){
			$TABLE_BODY .= '<tr>';
			$TABLE_BODY .='<td>'.$datetime.'</td>';
			for($j=0; $j<sizeof($arr_ref); $j++) {
				for($k=0; $k<100; $k++) {
					$TABLE_BODY .='<td align="right">'.number_format($arr_list[$datetime][$arr_ref[$j]]['age'][$k]).'</td>';
				}
			}
			$TABLE_BODY .= '</tr>';
		}
		
		for($i=0; $i<sizeof($arr_ref); $i++) {
			$first_line .= '<th colspan="100">'.$arr_ref[$i].'</th>';
			for($j=0; $j<100; $j++) {
				$second_line .= '<th width="100px">'.$j.'</th>';
			}
		}
		$TABLE_HEAD  = '<tr><th width="200px" rowspan="2">Date</th>'.$first_line.'</tr><tr>'.$second_line.'</tr>';
		$TABLE_HEAD = '<thead>'.$TABLE_HEAD.'</thead>';

		print "<HTML><HEAD><META http-equiv='Content-Type' content='text/html; charset=utf-8' /><META http-equiv='Content-Language' content='en' /><META http-equiv='Pragma' content='no-cache' /><META http-equiv='Cache-Control' content='no-cache' /></HEAD> ";
		print '<TABLE border=1 cellpadding=0 cellspacing=0>'.$TABLE_HEAD.'<tbody>'.$TABLE_BODY.'</tbody></table>';
		print "</HTML>";
	}
	
	else if($_GET['reportfmt']=='csv') {
		for($i=0; $i<sizeof($arr_ref); $i++) {
			$TABLE_HEAD .= $arr_ref[$i];
			for($j=1; $j<100; $j++) {
					$TABLE_HEAD .= ',';
			}
		}
		$TABLE_HEAD = 'DATE,'.$TABLE_HEAD."\r\n";
		for($i=0; $i<sizeof($arr_ref); $i++) {
			for($j=0; $j<100; $j++) {
				$TABLE_HEAD .= ",".$j;
			}
		}
//		$TABLE_HEAD .= "\n\r";
		foreach($arr_list as $datetime =>$arr){
			$TABLE_BODY .=$datetime;
			for($j=0; $j<sizeof($arr_ref); $j++) {
				for($k=0; $k<100; $k++) {
					$TABLE_BODY .=','.$arr_list[$datetime][$arr_ref[$j]]['age'][$k];
				}
			}
			$TABLE_BODY .= "\r\n";
		}		
	
		$TABLE_BODY = $TABLE_HEAD.$TABLE_BODY;
		$file_name = date("YmdHis").mktime().".csv";
		Header("Content-type: application/octet-stream");
		Header("Content-Length: ".filesize($file_dir.$file_micro));    
		Header("Content-Disposition: attachment; filename=".$file_name);  
		Header("Content-Transfer-Encoding: binary");  
		Header("Expires: 0");  
		print $TABLE_BODY;
	}
	else if($_GET['reportfmt']=='curve') {
		$TABLE_BODY = '<div id="chart_curve"></div>'.
			'<script src="../js/app.js"></script>'.
			'<script>'.
			'var options = {
				chart: {
					height: 500,
					type: "line",
					zoom:{enabled:false,},
				},
				var colors:[ "#008FFB", "#00E396", "#FEB019", "#FF4560", "#775DD0", "#546E7A", "#26a69a", "#D10CE8"];
				dataLabels: { enabled: true, },
				series: [],
				title: { text: "footfall", },
				legend: { position:"top", },

				noData: { text: "Loading..." },
				xaxis: {
					type:"datetime",
					labels:{
						show:true,
						showDuplicates: true,
						datetimeUTC: false,
						datetimeFormatter: {
							year: "yyyy",
							month: "yyyy-MM",
							day: "MM/dd",
							hour: "HH:mm",
						},
					},
				},
			};'.
			'var footfall_chart = new Chart(document.getElementById("chart_curve").getContext("2d"), options);'.
			'</script>';
		$TABLE_BODY .='<script>
			footfall_curve.updateSeries(response["curve_data"] );
			footfall_curve.updateOptions({
				chart: { height: 150,},	
				colors: ["rgb(153, 102, 255)"],	
				dataLabels: {enabled: false,},
				xaxis: {
					categories: timeToDatetime(response["curve_label"],"YYYY-MM-DD HH:mm"),
				},
				tooltip: {
					x: { format: tooltip_date,},
				},
			});
		</script>';
			
		
		print $TABLE_BODY;
	}

}








exit;

$help_message = ''.
	'<br>help: this help'.
	'<br>reportfmt: format of report , table/csv/json'.
	'<br>from: start time of query, YYYY-mm-dd HH:mm'.
	'<br>to: end time of query, YYYY-mm-dd HH:mm'.
	'<br>interval: time interval of query, 10min/hourly/daily/yearly'.
	'<br>api_key: api key of query, 32 characters of ascii, supplied from system'.
	'<br>squre: square name of database query, all/[square name]'.
	'<br>store: store name of database query, all/[store name]'.
	'<br>camera: camera name of database query, all/[camera name]'.
	'<br>mac: camea mac of database query, all/[mac]'.
	'<br>label: counter label of database query, all/[counter label]';
	
if((trim($_SERVER['QUERY_STRING']) == 'help') or !$_GET['api_key']) {
	print $help_message;
	exit;
}

if(!trim($_GET['store'])) {
	print $help_message;
	exit;
}


$ROOT_DIR = str_replace("/html", "", $_SERVER['DOCUMENT_ROOT']);
$configVars =  parse_ini_file($ROOT_DIR.'/TLSS/config.ini',TRUE);

$DB_TABLE['account'] = $configVars['DB_TABLE']['USER'];
$DB_TABLE['param'] = $configVars['DB_TABLE']['PARAM'];
$DB_TABLE['snapshot'] = $configVars['DB_TABLE']['SNAPSHOT'];
$DB_TABLE['face'] = $configVars['DB_TABLE']['FACE'];
$DB_TABLE['mac'] = $configVars['DB_TABLE']['MAC'];
$DB_TABLE['access_log'] = $configVars['DB_TABLE']['ACCESS_LOG'];
$DB_TABLE['message'] = $configVars['DB_TABLE']['MESSAGE'];

$DB_TABLE['count'] = $configVars['DB_TABLE']['COUNT'];
$DB_TABLE['count_merge'] = $configVars['DB_TABLE']['COUNT_MERGE'];
$DB_TABLE['heatmap'] = $configVars['DB_TABLE']['HEATMAP'];
$DB_TABLE['age_gender'] = $configVars['DB_TABLE']['AGE_GENDER'];
$DB_TABLE['age_gender_merge'] = $configVars['DB_TABLE']['AGE_GENDER_MERGE'];

$DB_TABLE['square'] = $configVars['DB_TABLE']['SQUARE'];
$DB_TABLE['store'] = $configVars['DB_TABLE']['STORE'];
$DB_TABLE['camera'] = $configVars['DB_TABLE']['CAMERA'];
$DB_TABLE['counter_label'] = $configVars['DB_TABLE']['COUNTER_LABEL'];



//$connect0 = @mysqli_connect("localhost", $mysql_user, $mysql_password, 'common');
$connect0 = @mysqli_connect($configVars['MYSQL']['HOST'], $configVars['MYSQL']['USER'], $configVars['MYSQL']['PASSWORD'], 'common');

$sq = "select ID, db_name, role from ".$DB_TABLE['account']." ";
$rs = mysqli_query($connect0, $sq);

while ($row = mysqli_fetch_row($rs)){
	if($_GET['api_key'] == strtoupper(md5($row[0].'@'.$row[1]))) {
		$ID = $row[0];
		$mysql_db_name = $row[1];
		$role = $row[2];
		break;
	}
}

if($mysql_db_name) {
	$connect  = @mysqli_connect($configVars['MYSQL']['HOST'], $configVars['MYSQL']['USER'], $configVars['MYSQL']['PASSWORD'], $mysql_db_name);
	if(!$connect) {
		echo "DB  Select Error";
		exit;
	}
}

else {
	echo "You don't have right to query database";
	exit;
}

if(!$_GET['from']) {
	$errstr =" <br>invalid 'from'"; 
}
if(!$_GET['to']) {
	$errstr .=" <br>invalid 'to'"; 
}


if(!$_GET['order']) {
	$_GET['order'] = 'asc';
}

if(!$_GET['lable']) {
	$_GET['lable'] = 'all';
}
if(!$_GET['reportfmt']) {
	$_GET['reportfmt'] = 'table';
}



if(trim($_GET['interval']) == 'tenmin') {
	$gt_sq = "year, month, day, hour, min ";
	$dateformat = "Y-m-d H:i";
	$interval = 600;
}
else if(trim($_GET['interval']) == 'hourly') {
	$gt_sq = "year, month, day, hour ";
	$dateformat = "Y-m-d H:00";
	$interval = 3600;
}
else if(trim($_GET['interval']) == 'daily') {
	$gt_sq = "year, month, day ";
	$dateformat = "Y-m-d";
	$interval = 3600*24;
}	
else if(trim($_GET['interval']) == 'weekly') {
	$gt_sq = "year, week ";
	$dateformat = "Y-m-d";
	$interval = 3600*24*7;
}	

else if(trim($_GET['interval']) == 'monthly') {
	$gt_sq = "year, month ";
	$dateformat = "Y-m";
	$interval = 3600*24*30;
}
else if(trim($_GET['interval']) == 'yearly') {
	$gt_sq = "year ";
	$dateformat = "Y";
	$interval = 3600*24;
}

$arr_label = array();

if($_GET['label'] == 'all') {
	$arr_label = array("entrance", "exit", "outside", "none");
}
else {
	$ex_label = explode(',', $_GET['label']);
	for($i=0; $i<sizeof($ex_label); $i++) {
		if($l_sq) {
			$l_sq .= "or ";
		}
		$l_sq .= "counter_label='".$ex_label[$i]."' ";
		array_push($arr_label, $ex_label[$i]);
	}
	if($l_sq) {
		$l_sq = '('.$l_sq.')';
	}
}

if($_GET['store']) {
	$ex_store = explode(',', $_GET['store']);
	for($i=0; $i<sizeof($ex_store); $i++) {
		if($st_sq) {
			$st_sq .= "or ";
		}
		$st_sq .= "store_code='".$ex_store[$i]."' ";
	}
	
	if($st_sq) {
		$st_sq = '('.$st_sq.') ';
	}
}

$ref_tag = "all";
if($_GET['group'] == 'square') {
	$gt_sq .= ",square_code ";
	$ref_tag = "square_code";
}
else if($_GET['group'] == 'store') {
	$gt_sq .= ",store_code ";
	$ref_tag = "store_code";
}
else if($_GET['group'] == 'camera') {
	$gt_sq .= ",camera_code ";
	$ref_tag = "camera_code";
}



$from_ts = strtotime($_GET['from']);
$to_ts = strtotime($_GET['to']);

$duration  = ceil(($to_ts - $from_ts)/$interval) ;

$sq  = "select  year, month, day, wday, hour, min, weekofyear(str_to_date(concat(year,'-', month,'-', day), '%Y-%m-%d')) as week, square_code, store_code, camera_code, counter_label, sum(counter_val) as sum from counting_merge ";
$sq .= "where timestamp >=".$from_ts." and timestamp <".$to_ts." ";

if($l_sq) {
	$sq .= "and ".$l_sq." ";
}
if($st_sq) {
	$sq .= "and ".$st_sq." ";
}

$sq .= "group by ".$gt_sq.", counter_label ";

print $sq;

$rs = mysqli_query($connect, $sq);

$arr_ref = array();

for($i=0; $i<($rs->num_rows); $i++) {
	$assoc = mysqli_fetch_assoc($rs);
	$datetimest = mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']);
	$datetime =  date($dateformat, $datetimest );
	$assoc['all'] = 'ALL';
	if(!in_array($assoc[$ref_tag], $arr_ref)) {
		array_push($arr_ref, $assoc[$ref_tag]);
	}
	$arr_list[$datetime][$assoc[$ref_tag]][$assoc['counter_label']] += $assoc['sum'];
}

$arr_list_t =  array();
for($i=0; $i<$duration; $i++) {
//	$datetimest = $from_ts + $i*$interval;
	if($_GET['order'] == 'desc') {
		$datetimest = $from_ts + ($duration-$i-1)*$interval;
	}
	else {
		$datetimest = $from_ts + $i*$interval;
	}	
	
	$datetime =  date($dateformat, $datetimest);
	for($j=0; $j<sizeof($arr_ref); $j++) {
		for($k=0; $k<sizeof($arr_label); $k++) {
			if(!$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]]) {
				$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]] = 0;
			}
			$arr_list_t[$datetime][$arr_ref[$j]][$arr_label[$k]] = $arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]];
		}
	}
}

$arr_list = $arr_list_t;

$arr_ref_tag = array("ALL");
for($i=0; $i<sizeof($arr_ref); $i++) {
	if($_GET['group'] == 'square') {
		$sq = "select name from square where code ='".$arr_ref[$i]."' ";
	}
	else if($_GET['group'] == 'store') {
		$sq = "select name from store where code ='".$arr_ref[$i]."' ";
	}
	else if($_GET['group'] == 'camera') {
		$sq = "select name from camera where code ='".$arr_ref[$i]."' ";
	}

	$rs = mysqli_query($connect, $sq);
	$arr_ref_tag[$i] = mysqli_fetch_row($rs)[0];
	
	
}

//print "<pre>"; print_r($arr_list); print "</pre>";

//exit;

if($_GET['reportfmt'] == 'json') {
	$TABLE_BODY = json_encode($arr_list,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	Header("Content-type: text/json");
	print $TABLE_BODY;
}

else if($_GET['reportfmt'] == 'table') {
	for($i=0; $i<sizeof($arr_ref); $i++) {
		if($_GET['label'] == 'all') {
			$colspan=4;
		}
		else {
			$colspan=sizeof($arr_label);
		}
		$first_line .= '<th colspan="'.$colspan.'">'.$arr_ref_tag[$i].'</th>';
		for($j=0; $j<sizeof($arr_label); $j++) {
			$second_line .= '<th width="100px">'.$arr_label[$j].'</th>';
		}
	}
	$TABLE_HEAD  = '<tr><th width="200px" rowspan="2">Date</th>'.$first_line.'</tr><tr>'.$second_line.'</tr>';
	$TABLE_HEAD = '<thead>'.$TABLE_HEAD.'</thead>';
	
	$TABLE_BODY = '';
	for($i=0; $i<$duration; $i++) {
		if($_GET['order'] == 'desc') {
			$datetimest = $from_ts + ($duration-$i-1)*$interval;
		}
		else {
			$datetimest = $from_ts + $i*$interval;
		}
		$datetime = date($dateformat, $datetimest);
		$TABLE_BODY .= '<tr>';
		$TABLE_BODY .='<td>'.$datetime.'</td>';
		for($j=0; $j<sizeof($arr_ref); $j++) {
			for($k=0; $k<sizeof($arr_label); $k++) {
				$TABLE_BODY .='<td align="right">'.number_format($arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]]).'</td>';
			}
		}
		$TABLE_BODY .= '</tr>';
	}

	print "<HTML><HEAD><META http-equiv='Content-Type' content='text/html; charset=utf-8' /><META http-equiv='Content-Language' content='en' /><META http-equiv='Pragma' content='no-cache' /><META http-equiv='Cache-Control' content='no-cache' /></HEAD> ";
	print '<TABLE border=1 cellpadding=0 cellspacing=0>'.$TABLE_HEAD.'<tbody>'.$TABLE_BODY.'</tbody></table>';
	print "</HTML>";

}

else if($_GET['reportfmt']=='csv') {
	for($i=0; $i<sizeof($arr_ref); $i++) {
		for($j=0; $j<sizeof($arr_label); $j++) {
			if($TABLE_HEAD) {
				$TABLE_HEAD .= ',';
			}
			$TABLE_HEAD .= $arr_label[$j].'@'.$arr_ref_tag[$i];
		}
	}
	$TABLE_HEAD = "\n\r".'DATE,'.$TABLE_HEAD."\r\n";
	$TABLE_BODY='';
	for($i=0; $i<$duration; $i++) {
		if($_GET['order'] == 'desc') {
			$datetimest = $from_ts + ($duration-$i-1)*$interval;
		}
		else {
			$datetimest = $from_ts + $i*$interval;
		}
		$datetime = date($dateformat, $datetimest);
		$TABLE_BODY .= $datetime;
		for($j=0; $j<sizeof($arr_ref); $j++) {
			for($k=0; $k<sizeof($arr_label); $k++) {
				$TABLE_BODY .=','.$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]];
			}
		}
		$TABLE_BODY .= "\r\n";
	}	
	
	$TABLE_BODY = $TABLE_HEAD.$TABLE_BODY;
	$file_name = date("YmdHis").mktime().".csv";
	Header("Content-type: application/octet-stream");
	Header("Content-Length: ".filesize($file_dir.$file_micro));    
	Header("Content-Disposition: attachment; filename=".$file_name);  
	Header("Content-Transfer-Encoding: binary");  
	Header("Expires: 0");  
	print $TABLE_BODY;
}

else if($_GET['reportfmt']=='curve') {
	$TABLE_BODY = '<div id="chart_curve"></div>'.
		'<script src="../js/app.js"></script>'.
		'<script>'.
		'var options = {
			chart: {
				height: 500,
				type: "line",
				zoom:{enabled:false,},
			},
			var colors:[ "#008FFB", "#00E396", "#FEB019", "#FF4560", "#775DD0", "#546E7A", "#26a69a", "#D10CE8"];
			dataLabels: { enabled: true, },
			series: [],
			title: { text: "footfall", },
			legend: { position:"top", },

			noData: { text: "Loading..." },
			xaxis: {
				type:"datetime",
				labels:{
					show:true,
					showDuplicates: true,
					datetimeUTC: false,
					datetimeFormatter: {
						year: "yyyy",
						month: "yyyy-MM",
						day: "MM/dd",
						hour: "HH:mm",
					},
				},
			},
		};'.
		'var footfall_chart = new Chart(document.getElementById("chart_curve").getContext("2d"), options);'.
		'</script>';
		
		
		
	
	print $TABLE_BODY;

}

exit;

?>





<?PHP
include  $_SERVER['DOCUMENT_ROOT'].'/libs/dbconnect.php';
// agegenderreport.php?
	$help_message = ''.
	'<br>reportfmt: format of report , [table/csv/json]'.
	'<br>from: start time of query, [YYYY-mm-dd] or [YYYY-mm-dd HH:mm]'.
	'<br>to: end time of query, [YYYY-mm-dd] or [YYYY-mm-dd HH:mm]'.
	'<br>interval: time interval of query, [tenmin/hourly/daily/yearly]'.
	'<br>api_key: api key of query, 32 characters of ascii, supplied from system'.
	'<br>group: data dividing group database , [none/square/store/camera]'.
	'<br>store: store_code of database query, [store name,{store....}]'.
	'<br><br>Example) <br>http://'.$CLOUD_SERVER.'/agegnderreport.php?reportfmt=json&from=2020-05-03&to=2020-05-10&interval=hourly&order=desc&group=camera&store=ST154798776431&api_key=F8F523CF8531C1FBABCE780F16A7A815';
	
	
if((trim($_SERVER['QUERY_STRING']) == 'help') or !$_GET['api_key']) {
	print $help_message;
	exit;
}

if(!trim($_GET['store'])) {
	print $help_message;
	exit;
}

$connect0 = @mysqli_connect("localhost", $mysql_user, $mysql_password, 'common');

$sq = "select ID, db_name, role from users";
$rs = mysqli_query($connect0, $sq);

while ($row = mysqli_fetch_row($rs)){
	if($_GET['api_key'] == strtoupper(md5($row[0].'@'.$row[1]))) {
		$ID = $row[0];
		$mysql_db_name = $row[1];
		$role = $row[2];
		break;
	}
}
if($mysql_db_name) {
	$connect  = @mysqli_connect("localhost", $mysql_user, $mysql_password, $mysql_db_name);
	if(!$connect) {
		echo "DB  Select Error";
		exit;
	}
}
else {
	echo "You don't have right to query database";
	exit;
}

if(!$_GET['from']) {
	$errstr =" <br>invalid 'from'"; 
}
if(!$_GET['to']) {
	$errstr .=" <br>invalid 'to'"; 
}


if(!$_GET['order']) {
	$_GET['order'] = 'asc';
}

if(!$_GET['lable']) {
	$_GET['lable'] = 'all';
}
if(!$_GET['reportfmt']) {
	$_GET['reportfmt'] = 'table';
}



if(trim($_GET['interval']) == 'tenmin') {
	$gt_sq = "year, month, day, hour, min ";
	$dateformat = "Y-m-d H:i";
	$interval = 600;
}
else if(trim($_GET['interval']) == 'hourly') {
	$gt_sq = "year, month, day, hour ";
	$dateformat = "Y-m-d H:00";
	$interval = 3600;
}
else if(trim($_GET['interval']) == 'daily') {
	$gt_sq = "year, month, day ";
	$dateformat = "Y-m-d";
	$interval = 3600*24;
}	
else if(trim($_GET['interval']) == 'weekly') {
	$gt_sq = "year, week ";
	$dateformat = "Y-m-d";
	$interval = 3600*24;
}	

else if(trim($_GET['interval']) == 'monthly') {
	$gt_sq = "year, month ";
	$dateformat = "Y-m";
	$interval = 3600*24*30;
}
else if(trim($_GET['interval']) == 'yearly') {
	$gt_sq = "year ";
	$dateformat = "Y";
	$interval = 3600*24;
}

if($_GET['store']) {
	$ex_store = explode(',', $_GET['store']);
	for($i=0; $i<sizeof($ex_store); $i++) {
		if($st_sq) {
			$st_sq .= "or ";
		}
		$st_sq .= "store_code='".$ex_store[$i]."' ";
	}
	
	if($st_sq) {
		$st_sq = '('.$st_sq.') ';
	}
}

$ref_tag = "all";
if($_GET['group'] == 'square') {
	$gt_sq .= ",square_code ";
	$ref_tag = "square_code";
}
else if($_GET['group'] == 'store') {
	$gt_sq .= ",store_code ";
	$ref_tag = "store_code";
}
else if($_GET['group'] == 'camera') {
	$gt_sq .= ",camera_code ";
	$ref_tag = "camera_code";
}



$from_ts = strtotime($_GET['from']);
$to_ts = strtotime($_GET['to']);

$duration  = ceil(($to_ts - $from_ts)/$interval) ;

$sq  = "select  year, month, day, wday, hour, min, weekofyear(str_to_date(concat(year,'-', month,'-', day), '%Y-%m-%d')) as week, square_code, store_code, camera_code, sum(age_1st) as age_1st, sum(age_2nd) as age_2nd, sum(age_3rd) as age_3rd, sum(age_4th) as age_4th, sum(age_5th) as age_5th, sum(age_6th) as age_6th, sum(age_7th) as age_7th, sum(male) as male, sum(female) as female from age_gender_merge ";
$sq .= "where timestamp >=".$from_ts." and timestamp <".$to_ts." ";


if($st_sq) {
	$sq .= "and ".$st_sq." ";
}

$sq .= "group by ".$gt_sq." ";
//print $sq;

$rs = mysqli_query($connect, $sq);

$arr_ref = array();
$arr_label = array('age_1st', 'age_2nd', 'age_3rd', 'age_4th', 'age_5th', 'age_6th', 'age_7th', 'male', 'female');
// arr_list[datetime][camera_code][sum(female)]
for($i=0; $i<($rs->num_rows); $i++) {
	$assoc = mysqli_fetch_assoc($rs);
	$datetimest = mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']);
	$datetime =  date($dateformat, $datetimest );
	$assoc['all'] = 'ALL';
	if(!in_array($assoc[$ref_tag], $arr_ref)) {
		array_push($arr_ref, $assoc[$ref_tag]);
	}
	for($j=0; $j<sizeof($arr_label); $j++) {
		$arr_list[$datetime][$assoc[$ref_tag]][$arr_label[$j]] += $assoc[$arr_label[$j]];
	}
}

$arr_list_t =  array();
for($i=0; $i<$duration; $i++) {
	if($_GET['order'] == 'desc') {
		$datetimest = $from_ts + ($duration-$i-1)*$interval;
	}
	else {
		$datetimest = $from_ts + $i*$interval;
	}
	$datetime =  date($dateformat, $datetimest);
	for($j=0; $j<sizeof($arr_ref); $j++) {
		for($k=0; $k<sizeof($arr_label); $k++) {
			if(!$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]]) {
				$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]] = 0;
			}
			$arr_list_t[$datetime][$arr_ref[$j]][$arr_label[$k]] = $arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]];
		}
	}
}

$arr_list = $arr_list_t;
//print "<pre>"; print_r($arr_list); print "</pre>";

$arr_ref_tag = array("ALL");
for($i=0; $i<sizeof($arr_ref); $i++) {
	if($_GET['group'] == 'square') {
		$sq = "select name from square where code ='".$arr_ref[$i]."' ";
	}
	else if($_GET['group'] == 'store') {
		$sq = "select name from store where code ='".$arr_ref[$i]."' ";
	}
	else if($_GET['group'] == 'camera') {
		$sq = "select name from camera where code ='".$arr_ref[$i]."' ";
	}

	$rs = mysqli_query($connect, $sq);
	$arr_ref_tag[$i] = mysqli_fetch_row($rs)[0];
	
	
}

//print "<pre>"; print_r($arr_list); print "</pre>";
if($_GET['reportfmt'] == 'json') {
	$TABLE_BODY = json_encode($arr_list,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	Header("Content-type: text/json");
	print $TABLE_BODY;
}

else if($_GET['reportfmt'] == 'table') {
	for($i=0; $i<sizeof($arr_ref); $i++) {
		if($_GET['label'] == 'all') {
			$colspan=4;
		}
		else {
			$colspan=sizeof($arr_label);
		}
		$first_line .= '<th colspan="'.$colspan.'">'.$arr_ref_tag[$i].'</th>';
		for($j=0; $j<sizeof($arr_label); $j++) {
			$second_line .= '<th width="100px">'.$arr_label[$j].'</th>';
		}
	}
	$TABLE_HEAD  = '<tr><th width="200px" rowspan="2">Date</th>'.$first_line.'</tr><tr>'.$second_line.'</tr>';
	$TABLE_HEAD = '<thead>'.$TABLE_HEAD.'</thead>';
	
	$TABLE_BODY = '';
	for($i=0; $i<$duration; $i++) {
		if($_GET['order'] == 'desc') {
			$datetimest = $from_ts + ($duration-$i-1)*$interval;
		}
		else {
			$datetimest = $from_ts + $i*$interval;
		}
		$datetime = date($dateformat, $datetimest);
		$TABLE_BODY .= '<tr>';
		$TABLE_BODY .='<td>'.$datetime.'</td>';
		for($j=0; $j<sizeof($arr_ref); $j++) {
			for($k=0; $k<sizeof($arr_label); $k++) {
				$TABLE_BODY .='<td align="right">'.number_format($arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]]).'</td>';
			}
		}
		$TABLE_BODY .= '</tr>';
	}

	print "<HTML><HEAD><META http-equiv='Content-Type' content='text/html; charset=utf-8' /><META http-equiv='Content-Language' content='en' /><META http-equiv='Pragma' content='no-cache' /><META http-equiv='Cache-Control' content='no-cache' /></HEAD> ";
	print '<TABLE border=1 cellpadding=0 cellspacing=0>'.$TABLE_HEAD.'<tbody>'.$TABLE_BODY.'</tbody></table>';
	print "</HTML>";

}

else if($_GET['reportfmt']=='csv') {
	for($i=0; $i<sizeof($arr_ref); $i++) {
		for($j=0; $j<sizeof($arr_label); $j++) {
			if($TABLE_HEAD) {
				$TABLE_HEAD .= ',';
			}
			$TABLE_HEAD .= $arr_label[$j].'@'.$arr_ref_tag[$i];
		}
	}
	$TABLE_HEAD = "\n\r".'DATE,'.$TABLE_HEAD."\r\n";
	$TABLE_BODY='';
	for($i=0; $i<$duration; $i++) {
		$datetimest = $from_ts + $i*$interval;
		$datetime = date($dateformat, $datetimest);
		$TABLE_BODY .= $datetime;
		for($j=0; $j<sizeof($arr_ref); $j++) {
			for($k=0; $k<sizeof($arr_label); $k++) {
				$TABLE_BODY .=','.$arr_list[$datetime][$arr_ref[$j]][$arr_label[$k]];
			}
		}
		$TABLE_BODY .= "\r\n";
	}	
	
	$TABLE_BODY = $TABLE_HEAD.$TABLE_BODY;
	$file_name = date("YmdHis").mktime().".csv";
	Header("Content-type: application/octet-stream");
	Header("Content-Length: ".filesize($file_dir.$file_micro));    
	Header("Content-Disposition: attachment; filename=".$file_name);  
	Header("Content-Transfer-Encoding: binary");  
	Header("Expires: 0");  
	print $TABLE_BODY;
}

else if($_GET['reportfmt']=='curve') {
	$TABLE_BODY = '<div id="chart_curve"></div>'.
		'<script src="../js/app.js"></script>'.
		'<script>'.
		'var options = {
			chart: {
				height: 500,
				type: "line",
				zoom:{enabled:false,},
			},
			var colors:[ "#008FFB", "#00E396", "#FEB019", "#FF4560", "#775DD0", "#546E7A", "#26a69a", "#D10CE8"];
			dataLabels: { enabled: true, },
			series: [],
			title: { text: "footfall", },
			legend: { position:"top", },

			noData: { text: "Loading..." },
			xaxis: {
				type:"datetime",
				labels:{
					show:true,
					showDuplicates: true,
					datetimeUTC: false,
					datetimeFormatter: {
						year: "yyyy",
						month: "yyyy-MM",
						day: "MM/dd",
						hour: "HH:mm",
					},
				},
			},
		};'.
		'var footfall_chart = new Chart(document.getElementById("chart_curve").getContext("2d"), options);'.
		'</script>';
		
	print $TABLE_BODY;

}


?>


