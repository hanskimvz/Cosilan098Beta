<?PHP
/*
Copyright (c) 2022, Hans kim

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
1. Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// session_start();
for ($i=0; $i<3; $i++) {
	chdir("../");
	if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
} 

$fname = $ROOT_DIR."/bin/param.db";
$db = new SQLite3($fname);

$configVars = array();
$sq = "select groupPath, entryName, entryValue from param_tbl ";
$rs = $db->query($sq);
while ($row = $rs->fetchArray()) {
	$configVars[$row['groupPath'].".".$row['entryName']] = $row['entryValue'];
}
$db->close();
// print_r($configVars);

$DB_COMMON['account'] 	= 'common.'.$configVars['software.mysql.db_common.table.user'];
$DB_COMMON['param'] 	= 'common.'.$configVars['software.mysql.db_common.table.param'];
$DB_COMMON['counting'] 	= 'common.'.$configVars['software.mysql.db_common.table.counting'];
$DB_COMMON['count_event']='common.'.$configVars['software.mysql.db_common.table.count_event'];
$DB_COMMON['snapshot'] 	= 'common.'.$configVars['software.mysql.db_common.table.snapshot'];
$DB_COMMON['heatmap'] 	= 'common.'.$configVars['software.mysql.db_common.table.heatmap'];
$DB_COMMON['face'] 		= 'common.'.$configVars['software.mysql.db_common.table.face'];
$DB_COMMON['mac'] 		= 'common.'.$configVars['software.mysql.db_common.table.macsniff'];
$DB_COMMON['access_log']= 'common.'.$configVars['software.mysql.db_common.table.access_log'];
$DB_COMMON['message'] 	= 'common.'.$configVars['software.mysql.db_common.table.message'];
$DB_COMMON['language'] 	= 'common.'.$configVars['software.mysql.db_common.table.language'];
$DB_COMMON['weather'] 	= 'common.'.$configVars['software.mysql.db_common.table.weather'];

// $_GET['api_key'] = '0DB066F13D6C76EC59B73F5365112946'; // tsd_admin
// $_GET['api_key'] = 'F8F523CF8531C1FBABCE780F16A7A815'; // hanskim

$ts_start = microtime(true);
$arr_rs['code'] = 1;
$arr_rs['message'] = "OK";
$arr_rs['elaspe_time'] = 0;
$arr_rs['data'] = [];
$arr_rs['records'] = 0;
$arr_rs['total_record'] = 0;


$connect  = @mysqli_connect($configVars['software.mysql.host'], $configVars['software.mysql.user'], $configVars['software.mysql.password']);
if (!$connect){
	$arr_rs['code'] = 1002;
	$arr_rs['message'] =  "DB  Select Error";
	$arr_rs['elaspe_time'] = round(microtime(true)-$ts_start,4);
	$json_str = json_encode($arr_rs, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK );
	
	if($_SERVER['DOCUMENT_URI'] == '/pubSVC.php') {
		Header("Content-type: text/json");
		print $json_str;
	}
	exit();	
}

$sq = "select ID, db_name, role from ".$DB_COMMON['account']." ";
$rs = mysqli_query($connect, $sq);
$userData = array('ID'=>'', 'db_name'=>'', 'role'=>'', 'code'=>0);
while ($row = mysqli_fetch_row($rs)){
	if($_GET['api_key'] == strtoupper(md5($row[0].'@'.$row[1]))) {
		$userData['ID'] = $row[0];
		$userData['db_name'] = $row[1];
		$userData['role'] = $row[2];
		$userData['code'] = 1;
		break;
	}
}

if (!$userData['code']){
	$arr_rs['code'] = 1001;
	$arr_rs['message'] = "Invalid API KEY";
	$arr_rs['elaspe_time'] = round(microtime(true)-$ts_start,4);
	$json_str = json_encode($arr_rs, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK );
	
	if($_SERVER['DOCUMENT_URI'] == '/pubSVC.php') {
		Header("Content-type: text/json");
		print $json_str;
	}
	exit();	
}
$sq = "select version()";
$rs = mysqli_query($connect, $sq);
if (mysqli_fetch_row($rs)[0] == '8.0.27') {
	$sq = "set SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';";
	$rs = mysqli_query($connect, $sq);
}

$arr_rs['db_name'] = $userData['db_name'];

$DB_CUSTOM['account'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.account'];
$DB_CUSTOM['count'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.count'];
$DB_CUSTOM['heatmap'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.heatmap'];
$DB_CUSTOM['age_gender']	= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.age_gender'];
$DB_CUSTOM['square'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.square'];
$DB_CUSTOM['store'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.store'];
$DB_CUSTOM['camera'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.camera'];
$DB_CUSTOM['counter_label'] = $userData['db_name'].".".$configVars['software.mysql.db_custom.table.counter_label'];
$DB_CUSTOM['weather'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.weather'];
$DB_CUSTOM['language'] 		= $userData['db_name'].".".$configVars['software.mysql.db_custom.table.language'];
$DB_CUSTOM['web_config']	= $userData['db_name'].".webpage_config";


function Result2Array($rs)
{
	global $DB_CUSTOM;
	global $connect;

	$arr_result = array();
	$arr_field = array();
	$cols = $rs ->field_count;
	$rows = $rs->num_rows;
	
	for ($i=0; $i<$cols; $i++) {
		$fields = mysqli_fetch_field($rs);
		$arr_field[$i] = ($fields->name);
	}
		
	for($i=0; $i<$rows; $i++) {
		$row = mysqli_fetch_row($rs);
		for($j = 0; $j < $cols; $j++) {
			if($arr_field[$j] == 'age') {
				$arr_result[$i][$arr_field[$j]] = json_decode('['.$row[$j].']');
			}
			else if($arr_field[$j] == 'heatmapdata') {
				$arr = array();
				$h_row = explode("\r\n",$row[$j]);
				for($k=0; $k<sizeof($h_row)-1; $k++) {
					array_push($arr, json_decode('['.substr(trim($h_row[$k]),0,strlen(trim($h_row[$k]))-1).']'));
				}
				$arr_result[$i][$arr_field[$j]] = $arr;
			}			
			else if($arr_field[$j] == 'square_code') {
				$arr_result[$i]['square_code'] = $row[$j];
				$sq = "select name from ".$DB_CUSTOM['square']." where code = '".$arr_result[$i]['square_code']."' ";
				$rss =  mysqli_fetch_row(mysqli_query($connect, $sq));
				if ($rss) {
					$arr_result[$i]['square_name'] = $rss[0];
				}
			}
			else if($arr_field[$j] == 'store_code') {
				$arr_result[$i]['store_code'] = $row[$j];
				$sq = "select name from ".$DB_CUSTOM['store']." where code = '".$arr_result[$i]['store_code']."' ";
				$rss =  mysqli_fetch_row(mysqli_query($connect, $sq));
				if ($rss) {
					$arr_result[$i]['store_name'] =$rss[0];
				}
			}
			else if($arr_field[$j] == 'camera_code') {
				$arr_result[$i]['camera_code'] = $row[$j];
				$sq = "select name from ".$DB_CUSTOM['camera']." where code = '".$arr_result[$i]['camera_code']."' ";
				$rss =  mysqli_fetch_row(mysqli_query($connect, $sq));
				if($rss){
					$arr_result[$i]['camera_name'] =$rss[0];
				}
			}

			else {
				$arr_result[$i][$arr_field[$j]] = $row[$j];
			}
		}
	}
	return ($arr_result);
}


$sq ="";

// Query Info from square, store, camera
if (isset($_GET['getSquare']) || isset($_GET['getStore']) || isset($_GET['getCamera']) || isset($_GET['getCounter']) || isset($_GET['getLanguage'])) {
	if(isset($_GET['getSquare'])) {
		$sq = "select code, name,  concat(addr_state,' ', addr_city, ' ', addr_b) as address from ".$DB_CUSTOM['square']." ";
	}
	else if(isset($_GET['getStore'])) {
		$sq = "select code, name, square_code, concat(addr_state,' ', addr_city, ' ', addr_b) as address, phone, fax, contact_person as contact, contact_tel as tel, open_hour, close_hour from ".$DB_CUSTOM['store']." ";
	}
	else if(isset($_GET['getCamera'])) {
		$sq = "select code, name, store_code, square_code, mac, brand, model, usn, enable_countingline as countdb, enable_heatmap as heatmap, enable_snapshot as snapshot, enable_face_det as age_gender, enable_macsniff as macsniff from ".$DB_CUSTOM['camera']." ";
	}
	else if(isset($_GET['getCounter'])) {
		$sq = "select camera_code, counter_label as label, counter_name as name from ".$DB_CUSTOM['counter_label']." ";
	}
	else if(isset($_GET['getLanguage'])) {
		$sq = "select varstr, eng, kor, chi, page from ".$DB_CUSTOM['language']." "; 
		if ($_GET['page']) {
			$sq .= " where page = '".$_GET['page']."' ";
		}
		if ($_GET['var']) {
			$sq .=  $_GET['page'] ? " and " : " where ";
			$sq .= " varstr ='".$_GET['var']."' ";
		}
		// join($sqc);
	}

	if(isset($_GET['code'])) {
		$sq .= "where code = '".$_GET['code']."' ";
	}
	$rs = mysqli_query($connect, $sq);
	if (!$rs) {
		$arr_rs['code'] = 1003;
		$arr_rs['message'] = "Invalid Query";
		$arr_rs['sql'] = $sq;
	}
	else {	
		$arr_rs['data'] = Result2Array($rs);
		$arr_rs['code'] = 1;
		$arr_rs['message'] = "OK";
	}
	$arr_rs['elaspe_time'] = round(microtime(true)-$ts_start,4);
	$json_str = json_encode($arr_rs, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK );
	
	if($_SERVER['DOCUMENT_URI'] == '/pubSVC.php') {
		Header("Content-type: text/json");
		print $json_str;
	}
	exit();		
}

// Parameter from, to, square_code, store_code, camera_code, counter_label, interval, groupby, order 
$param = array();
$condition = array('from'=>0, 'to'=>0, 'sq_code'=>'', 'st_code'=>'', 'cam_code'=>'', 'ct_label'=>'',);
$group = array();
			
if(isset($_GET['from'])) {
	$from_ts = strtotime($_GET['from']);
	if(is_numeric($from_ts)) {
		$condition['from'] = "timestamp >=".strtotime(trim($_GET['from']))." ";
	}
	else {
		$arr_rs['code'] = 1004;
		$arr_rs['message'] = "invalid parameter: from=[string of datetime]";
	}				
}
if(isset($_GET['to'])) {
	$to_ts = strtotime($_GET['to']);
	if(is_numeric($to_ts)) {
		$condition['to'] = "timestamp <".strtotime(trim($_GET['to']))." ";
	}
	else {
		$arr_rs['code'] = 1004;
		$arr_rs['message'] = "invalid parameter: to=[string of datetime]";
	}					
}

if(isset($_GET['square_code']) && $_GET['square_code']) {
	$arr_code = explode(",",$_GET['square_code']);
	for($i=0; $i<sizeof($arr_code); $i++) {
		if(trim($arr_code[$i])) {
			if($condition['sq_code']) {
				$condition['sq_code'] .= "or ";
			}
			$condition['sq_code'] .=  "square_code = '".trim($arr_code[$i])."' ";
		}
	}
	$condition['sq_code'] = "(".$condition['sq_code'].") ";
}

if(isset($_GET['store_code']) && $_GET['store_code']) {
	$arr_code = explode(",",$_GET['store_code']);
	for($i=0; $i<sizeof($arr_code); $i++) {
		if(trim($arr_code[$i])) {
			if($condition['st_code']) {
				$condition['st_code'] .= "or ";
			}
			$condition['st_code'] .=  "store_code = '".trim($arr_code[$i])."' ";
		}
	}
	$condition['st_code'] = "(".$condition['st_code'].") ";
}			

if(isset($_GET['camera_code']) && $_GET['camera_code']) {
	$arr_code = explode(",",$_GET['camera_code']);
	for($i=0; $i<sizeof($arr_code); $i++) {
		if(trim($arr_code[$i])) {
			if($condition['cam_code']) {
				$condition['cam_code'] .= "or ";
			}
			$condition['cam_code'] .=  "camera_code = '".trim($arr_code[$i])."' ";
		}
	}
	$condition['cam_code'] = "(".$condition['cam_code'].") ";
}

if(isset($_GET['counter_label'])) {
	$arr_label = explode(",",$_GET['counter_label']);
	for($i=0; $i<sizeof($arr_label); $i++) {
		if($condition['ct_label']) {
			$condition['ct_label'] .= "or ";
		}
		$condition['ct_label'] .=  "counter_label = '".trim($arr_label[$i])."' ";
	}
	$condition['ct_label'] = "(".$condition['ct_label'].") ";
}
			
$param['datetime'] = "timestamp, year, month, week, day, wday, hour, min";
if (isset($_GET['interval'])) {
	if ($_GET['interval'] == 'tenmin') {
		$param['datetime'] = "timestamp, year, month, week, day, wday, hour, min";
		$group['datetime'] = "year, month, day, hour, min";
		
		if(!$to_ts) {
			$to_ts = time();
		}
		if(!$from_ts) {
			$from_ts =strtotime("2019-01-01")+3600*8;
		}
		if(($to_ts - $from_ts) > 3600*24*92) {
			$arr_rs['code'] = 1004;
			$arr_rs['message'] = "invalid parameter : interval=tenmin, (from ~ to) : less than 3 months";
		}
	}
	else if($_GET['interval'] == 'hourly') {
		$param['datetime'] = "timestamp, year, month, week, day, wday, hour";
		$group['datetime'] = "year, month, day, hour";
		if(!$to_ts) {
			$to_ts = time();
		}
		if(!$from_ts) {
			$from_ts =strtotime("2019-01-01")+3600*8;
		}
		if(($to_ts - $from_ts) > 3600*24*186) {
			$arr_rs['code'] = 1004;
			$arr_rs['message'] = "invalid parameter : interval=hourly, (from ~ to) : less than 6 months";
		}					
	}
	else if($_GET['interval'] == 'daily') {
		$param['datetime'] = "timestamp, year, month, week, day";
		$group['datetime'] = "year, month, day";
	}
	else if($_GET['interval'] == 'weekly') {
		$param['datetime'] = "timestamp, year, week";
		$group['datetime'] = "year, week";
	}
	else if($_GET['interval'] == 'monthly') {
		$param['datetime'] = "timestamp, year, month";
		$group['datetime'] = "year, month";
	}
	else { 
		$arr_rs['code'] = 1004;
		$arr_rs['message'] = "invalid parameter: interval=[tenmin/hourly/daily/weekly/monthly]";
	}
}
if(isset($_GET['group'])) {
	if ($_GET['group'] == 'none') {
		$param["spot"] = "'all' as square_code, 'all' as square_name";
	}
	else if ($_GET['group'] == 'square') {
		$param["spot"] = "square_code";
		$group['spot'] = "square_code";
	}
	else if ($_GET['group'] == 'store') {
		$param["spot"] = "square_code, store_code";
		$group['spot'] = "store_code";
	}			
	else if ($_GET['group'] == 'camera') {
		$param["spot"] = "square_code, store_code, camera_code";
		$group['spot'] = "camera_code";
	}
	else {
		$arr_rs['code'] = 1004;
		$arr_rs['message'] = "invalid parameter: group=[square/store/camera]";	
	}
}

$sq_param = '';
$sq_conidtion = '';
$sq_group = '';			
$sq_order = " order by timestamp asc ";
$sq_limit = "";
if(isset($_GET['order'])) {
	if($_GET['order'] == "asc" or $_GET['order'] =="desc") {
		$sq_order = " order by timestamp ".$_GET['order']." ";
	}
	else {
		$arr_rs['code'] = 1004;
		$arr_rs['message'] = "invalid parameter: order=[asc/desc]";
	}
}
if(isset($_GET['offset'])) {
	$limit = explode(",",$_GET['offset']);
	if (is_numeric($limit[0]) and is_numeric($limit[1]) and (sizeof($limit) ==2)) {
		$sq_limit = " limit ".$_GET['offset']." ";
	}
	else {
		$arr_rs['code'] = 1004;
		$arr_rs['message'] = "invalid parameter: offset=[int(s1),int(s2)]";
	}
}


foreach($param as $A=>$B) {
	if($sq_param) {
		$sq_param .= ", ";
	}
	$sq_param .= $B;
}
foreach($condition as $A=>$B) {
	if (!$B) {
		continue;
	}
	if(trim($sq_conidtion)) {
		$sq_conidtion .= "and ";
	}
	$sq_conidtion .= $B;
}
foreach($group as $A=>$B) {
	if($sq_group) {
		$sq_group .= ", ";
	}
	$sq_group .= $B;
}
			

##########################################  GET DATA from databases ####################################		
# Query data from counting, age, gender, heatmap, snapshot
if(isset($_GET['getCountingDB'])) {
	if($sq_param) {
		$sq_param .= ",";
	}
	$sq = "select ".$sq_param." counter_label, sum(counter_val) as counter_val from ".$DB_CUSTOM['count']."  ";
	
	if($sq_conidtion) {
		$sq .= "where ".$sq_conidtion." ";
	}
	$sq .= "group by counter_label";
	if($sq_group) {
		$sq .= ", ".$sq_group." ";
	}
}
else if(isset($_GET['getAgeDB'])) {
	$age_query_string = '';
	for($i=0; $i<100; $i++) {
		if($i>0) {
			$age_query_string .= ", ";
		}
		$age_query_string .= "sum(substring_index(substring_index(age, ',', ".($i+1)."),',',-1))";
		if($i<99) {
			$age_query_string .= ",','";
		}
	}
	$age_query_string  = "concat(".$age_query_string.") as age";		
	if($sq_param) {
		$sq_param .= ",";
	}
	$sq = "select ".$sq_param." ".$age_query_string." from ".$DB_CUSTOM['age_gender']." ";
	if($sq_conidtion) {
		$sq .= "where ".$sq_conidtion." ";
	}
	if($sq_group) {
		$sq .= "group by ".$sq_group." ";
	}
}
else if(isset($_GET['getGenderDB'])) {
	$gender_query_string = "sum(substring_index(substring_index(gender, ',', 1),',',-1)) as male, sum(substring_index(substring_index(gender, ',', 2),',',-1)) as female " ;
	if($sq_param) {
		$sq_param .= ",";
	}
	$sq = "select ".$sq_param." ".$gender_query_string." from ".$DB_CUSTOM['age_gender']." ";
	if($sq_conidtion) {
		$sq .= "where ".$sq_conidtion." ";
	}
	if($sq_group) {
		$sq .= "group by ".$sq_group." ";
	}
}
else if(isset($_GET['getHeatmap'])) {
	if($sq_param) {
		$sq_param = str_replace("min", "", $sq_param);
	}	
	$sq = "select device_info, ".$sq_param." body_csv as heatmapdata from ".$DB_CUSTOM['heatmap']." ";
	if($sq_conidtion) {
		$sq .= "where ".$sq_conidtion." ";
	}
	
}
else if(isset($_GET['getSnapshot'])) { // only camera_code
	$sq = "select A.body as snapshot, B.code as camera_code, A.device_info, A.regdate from ".$DB_COMMON['snapshot']." as A inner join ".$DB_CUSTOM['camera']." as B on A.device_info= B.device_info ";
	if ($condition['cam_code']) {
		$sq .=  "where ".str_replace("camera_code", "B.code", $condition['cam_code'])." ";
	}
	else if ($_GET['mac'] && $_GET['brand'] && $_GET['model']){
		$sq .=  "where A.device_info='mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model']."' order by A.regdate desc limit 1";
	}
	$sq_order = "";
}
else {
	$arr_rs['code'] = 1004;
	$arr_rs['message'] = "invalid Command:  [getSquare/getStore/getCamera/getCountingDB/getAgeDB/getGenderDB/getHeatmapDB/getSnapshot]";
}			

if($arr_rs['code'] == 1) {
	$rs = mysqli_query($connect, $sq);
	$arr_rs['total_record'] = (int)($rs->num_rows);
	$sq .= $sq_order;
	// print $sq;
	if($sq_limit) {
		$sq.= $sq_limit;
		$rs = mysqli_query($connect, $sq);
	}
	if (!$rs) {
		$arr_rs['code'] = 1003;
		$arr_rs['message'] = "Invalid Query";
		$arr_rs['sql'] = $sq;
	}
	else {	
		$arr_rs['records'] = (int)($rs->num_rows);
		$arr_rs['data'] = Result2Array($rs);
		$arr_rs['code'] = 1;
		$arr_rs['message'] = "OK";
	}
}
$arr_rs['elaspe_time'] = round(microtime(true)-$ts_start, 4);
$json_str = json_encode($arr_rs, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK );

if($_SERVER['DOCUMENT_URI'] == '/pubSVC.php') {
	Header("Content-type: text/json");
	print $json_str;
}
// print_r(json_decode($json_str, true));

?>