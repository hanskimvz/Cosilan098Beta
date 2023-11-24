<?PHP
#last modify: 2020-10-26
session_start();
require_once $_SERVER['DOCUMENT_ROOT']."/libs/functions.php";
logincheck();

require_once ($_SERVER['DOCUMENT_ROOT']."/inc/query_functions.php");
$msg = q_language('footfall.php');

// function age_group($data, $range) { // data:[0,2,1,2,3...], range:[0,18,30,45,65]
// 	array_push($range, 100);
// 	for ($i=0; $i<sizeof($range) -1; $i++) {
// 		$a = $range[$i];
// 		$b = $range[$i+1]-$range[$i];
// 		$rs[$i] = array_sum(array_slice($data,$a,$b));
// 		if (!$rs[$i]) {
// 			$rs[$i] = 0;
// 		}
// 	}
// 	return $rs;
// }

function getViewByTime($time_ref, $view_by, $square_code=0, $store_code=0) {
	$arr = array();
	$to_e = explode("~", $time_ref);
	if(!isset($to_e[1])){
		$to_e[1] = $to_e[0];
	}
	if($view_by == 'tenmin') {
		$arr['g_sq'] = "group by year, month, day, hour, min ";
		$arr['dateformat'] = 'Y-m-d H:i';
		$arr['ts_from'] = strtotime($to_e[1]);
		$arr['ts_to'] = $arr['ts_from'] + 3600*24 -1;
		$arr['interval'] = 600;
	}
	else if($view_by == 'hour') {
		$arr['g_sq'] = "group by year, month, day, hour";
		$arr['dateformat'] = 'Y-m-d H:00';
		$arr['ts_from'] = strtotime($to_e[1]);
		$arr['ts_to'] = $arr['ts_from'] + 3600*24 -1;
		$arr['interval'] = 3600;
	}
	else if($view_by == 'day') {
		$arr['g_sq'] = "group by year, month, day ";
		$arr['dateformat'] = 'Y-m-d';
		$arr['ts_from'] = strtotime($to_e[0]);
		$arr['ts_to'] = strtotime($to_e[1]) + 3600*24 -1;
		$arr['interval'] = 3600*24;
	}
	else if($view_by == 'week') {
		$arr['g_sq'] = "group by year, week ";
		$arr['dateformat'] = 'Y-m-d';
		$arr['ts_from'] = strtotime(date("Y-m-1", strtotime($to_e[0])));
		$arr['ts_to'] = strtotime(date("Y-m-t", strtotime($to_e[1]))) + 3600*24 -1;	
		$arr['interval'] = 3600*24*7;
	}	
	else if($view_by == 'month') {
		$arr['g_sq'] = "group by year, month ";
		$arr['dateformat'] = 'Y-m';
		$arr['ts_from'] = strtotime(date("Y-m-1", strtotime($to_e[0])));
		$arr['ts_to'] = strtotime(date("Y-m-t", strtotime($to_e[1]))) + 3600*24 -1;			
		$arr['interval'] = 3600*24*31;
	}
	$arr['duration'] =  ceil(($arr['ts_to']-$arr['ts_from'])/$arr['interval']);

	$arr['p_sq'] = "";
	if($store_code) {
		$arr['p_sq'] =  "and store_code ='".$store_code."' ";
	}
	else if($square_code ) {
		$arr['p_sq'] =  "and square_code ='".$square_code."' ";
	}		
	return $arr;	
}

function Result2Json4Chart($rs, $ts_from, $interval, $duration, $dateformat, $format='json') {
	global $thistime;
	global $msg;
	$s1 = microtime(true);
	$arr_result =  array();
	$arr_rs = array();
	$arr_label = array();
	while($assoc = mysqli_fetch_assoc($rs)) {
		$datetime = date($dateformat, mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']));
		$arr_result[$datetime][$assoc['counter_label']] = $assoc['sum'];
		if(!in_array($assoc['counter_label'], $arr_label)) {
			array_push($arr_label, $assoc['counter_label']);
		}		
	}
	if (!$arr_label){
		$arr_label= [0,];
	}
	for($i=0; $i<$duration; $i++) { 
		$datetimest = $ts_from + $interval*$i;
		$datetime = date($dateformat, $datetimest);
		for ($j=0; $j<sizeof($arr_label); $j++) {
			$arr_result[$arr_label[$j]][$i] = $arr_result[$datetime][$arr_label[$j]] ? $arr_result[$datetime][$arr_label[$j]]:	($datetimest > $thistime ? null : 0);	
		}
		$arr_rs['category']['timestamps'][$i] = $datetimest;
		$arr_rs['category']['datetimes'][$i] = $datetime;
	}
	for ($i=0; $i<sizeof($arr_label); $i++) {
		$arr_rs['data'][$i]['name'] = $msg[strtolower($arr_label[$i])] ? $msg[strtolower($arr_label[$i])] : $arr_label[$i]  ;
		$arr_rs['data'][$i]['data'] = $arr_result[$arr_label[$i]];
	}
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	$arr_rs['title']['chart_title'] = $msg['footfall'];

	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);
	unset($arr_result);
	unset($arr_label);
	if($format == 'array'){
		return $arr_rs;
	}
	unset($arr_rs);
	return $json_str;
}


############### Functions for Admin #############

function getZoneFromParam($param){
	$zone = array();
	$lines = explode("\n",$param);
	$i=0;
	foreach($lines as $line) {
		// print "\n".$line;
		if(preg_match('/(VCA.Ch0.Zn)[0-9]/', $line )) {
			list($key, $val) = explode("=", $line);
			$ex_key = explode(".",$key);
			if(isset($zone[$i][$ex_key[3]])){
				$i++;
			}
			$zone[$i][$ex_key[3]] = trim($val);
		}
	}
	return $zone;
}

function getCounterFromParam($param){
	$ct_name = array();
	$lines = explode("\n", $param);
	foreach($lines as $line) {
		if(preg_match('/(VCA.Ch0.Ct)[0-9](.name=)[a-zA-Z]+/', $line, $ct_r)) {
			list($a, $b) = explode("=", $line);
			array_push($ct_name[trim($b)], "");
		}
	}
	return $ct_name;
}	

function getCounterTableFromParam($param, $camera_code){
	global $msg;
	global $connect0;
	global $DB_CUSTOM;
	$ct_names = array();
	$lines = explode("\n", $param);
	$ct_list ="";
	foreach($lines as $line) {
		if(preg_match('/(VCA.Ch0.Ct)[0-9](.name=)[a-zA-Z]+/', $line, $ct_r)) {
			list($a, $b) = explode("=", $line);
			$ct_list .=  addslashes(trim($b)).',';
			$ct_names[trim($b)] =  "";			
		}
	}
	$sq = "select counter_name as name, counter_label as label, flag from ".$DB_CUSTOM['counter_label']."  ";
	$sqa = $sq."where camera_code = '".$camera_code."'";
	// print $sqa;
	$rs = mysqli_query($connect0, $sqa);
	while ($assoc = mysqli_fetch_assoc($rs)) { 
		$ct_names[$assoc['name']] = $assoc['label'];
	}
	$label_list = ['none','entrance', 'exit', 'outside'];
	$sqa = $sq."group by counter_label";
	// print $sqa;
	$rs = mysqli_query($connect0, $sqa);
	while ($assoc = mysqli_fetch_assoc($rs)) { 
		if (in_array($assoc['label'], $label_list)){
			continue;
		}
		array_push($label_list, $assoc['label']);
	}	
	// print_r($ct_names);
	$counter_table ='';
	foreach($ct_names as $ct_name =>$ct_label){
		$sel_ct_label ='';
		foreach($label_list as $label){
			$sel_ct_label .= '<option value="'.$label.'" '.($ct_label == $label ? "selected": "").'>'.$msg[strtolower($label)].'</option>' ;
		}
		$sel_ct_label = '<select id="'.$ct_name.'" class="form-control">'.$sel_ct_label.'</select>';
		$counter_table .= '<tr><td>'.$ct_name.'</td><td>'.$sel_ct_label.'</td></tr>';
	}
	$counter_table = '<input type="hidden" id="ct_list" value="'.$ct_list.'">
		<table class="table table-striped table-sm table-bordered">
		<tr>
		<th>'.$msg['countername'].'</th>
		<th>'.$msg['counterlabel'].'<span class="ml-3 badge badge-success" onMouseOver="this.style.cursor=\'pointer\'" onClick="location.href=(\'/admin.php?fr=counter_label_set\')">'.$msg['manage'].'</span></th>
		</tr>'.$counter_table.'</table>';

	return $counter_table;
}



##### start ######## Main ---

if (!isset($_GET['f'])) {
	$_GET['f'] = '';
}

if($_GET['f'] == 'square') {
	$arr = array();
	$sq = "select code, name from ".$DB_CUSTOM['square'];
	$rs = mysqli_query($connect0, $sq);
	for($i=0; $i<($rs->num_rows) ;$i++) {
		$row = mysqli_fetch_row($rs);
		array_push($arr, array("code"=>$row[0], "name"=>$row[1]));
	}
	$json_str = json_encode($arr);
}
else if($_GET['f'] == 'store') {
	$arr= array();
	$sq = "select code, name from ".$DB_CUSTOM['store']." where square_code ='".$_GET['sq_code']."' ";
	$rs = mysqli_query($connect0, $sq);
	for($i=0; $i<($rs->num_rows); $i++) {
		$row = mysqli_fetch_row($rs);
		array_push($arr, array("code"=>$row[0], "name"=>$row[1]));
	}
	$json_str = json_encode($arr);
}

else if($_GET['f'] == 'camera') {
	$arr = array();
	$sq = "select code, name from ".$DB_CUSTOM['camera']." where store_code ='".$_GET['st_code']."' ";
	$rs = mysqli_query($connect0, $sq);
	for($i=0; $i<($rs->num_rows); $i++) {
		$row = mysqli_fetch_row($rs);
		array_push($arr, array("code"=>$row[0], "name"=>$row[1]));
	}
	$json_str = json_encode($arr);
}

else if($_GET['f'] == 'all') {
	$arr = array();
	$sq = "select A.code, A.name, B.code, B.name, C.code, C.name  from ".$DB_CUSTOM['square']." as A inner join ".$DB_CUSTOM['store']." as B inner join ".$DB_CUSTOM['camera']." as C on A.code = B.square_code and C.store_code = B.code where C.enable_countingline='y' order by A.code asc, B.code asc, C.code asc";
	$rs = mysqli_query($connect0, $sq);
	for($i=0; $i<($rs->num_rows); $i++) {
		$row = mysqli_fetch_row($rs);
		array_push($arr, array(
			"sq_code"=>$row[0],
			"sq_name"=>$row[1],
			"st_code"=>$row[2],
			"st_name"=>$row[3],
			"cam_code"=>$row[4],
			"cam_name"=>$row[5]
			)
		);
	}

	$json_str = json_encode($arr);
}

else if($_GET['f'] == 'device_tree') {
	$arr_rs = array();
	$sq = "select code, name from ".$DB_CUSTOM['square']." ";
	$rs = mysqli_query($connect0, $sq);
	$arr_rs['sq_size'] = $rs->num_rows;
	for($i=0; $i<$rs->num_rows; $i++){
		$assoc = mysqli_fetch_assoc($rs);
		$arr_rs['place'][$i]['sq_code'] = $assoc['code'];
		$arr_rs['place'][$i]['sq_name'] = $assoc['name'];
		$sq = "select code, name from ".$DB_CUSTOM['store']." where square_code = '".$assoc['code']."' ";
		$rsa = mysqli_query($connect0, $sq);
		$arr_rs['place'][$i]['st_size'] = $rsa->num_rows;
		for ($j=0; $j< $arr_rs['place'][$i]['st_size']; $j++){
			$assoc = mysqli_fetch_assoc($rsa);
			$arr_rs['place'][$i][$j]['st_code'] = $assoc['code'];
			$arr_rs['place'][$i][$j]['st_name'] = $assoc['name'];
			$sq = "select code, name from ".$DB_CUSTOM['camera']." where store_code = '".$assoc['code']."' ";
			$rsb = mysqli_query($connect0, $sq);
			$arr_rs['place'][$i][$j]['cam_size'] = $rsb->num_rows;
			for($k=0; $k<$arr_rs['place'][$i][$j]['cam_size']; $k++){
				$assoc = mysqli_fetch_assoc($rsb);
				$arr_rs['place'][$i][$j][$k]['cam_code'] = $assoc['code'];
				$arr_rs['place'][$i][$j][$k]['cam_name'] = $assoc['name'];
			}
		}
	}
	$sq = "select counter_label, counter_name  from ".$DB_CUSTOM['counter_label']." where counter_name is not NULL and counter_name !='' group by counter_label";
	$rs = mysqli_query($connect0, $sq);
	$arr_rs['ct_size'] = $rs->num_rows;
	for($i=0; $i<$arr_rs['ct_size']; $i++){
		$assoc = mysqli_fetch_assoc($rs); 
		$arr_rs['counter_label'][$i]['label'] = $assoc['counter_label'];
		$sq = "select ".$_COOKIE['selected_language']." from ".$DB_CUSTOM['language']." where varstr='".$assoc['counter_label']."' and  page='camera.php' limit 1";
        $arr_rs['counter_label'][$i]['name'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
	}

	// print_r($arr_rs);
	$json_str = json_encode($arr_rs);
}

else if ($_GET['fr'] == 'message') {
	if($_GET['href'] == 'admin') {
		if($_GET['act'] == 'list') {
			$sq = "select * from ".$DB_COMMON['message']." ";
			$rs = mysqli_query($connect0, $sq);
			for($i=0; $i<($rs->num_rows); $i++) {
				$assoc = mysqli_fetch_assoc($rs);
				if($i>0) {
					$str .= ',';
				}
				$str .= '{
					"pk":"'.$assoc['pk'].'", 
					"title":"'.$assoc['title'].'", 
					"body":"'.addslashes($assoc['body']).'", 
					"from":"'.$assoc['from_p'].'",
					"to":"'.$assoc['to_p'].'",
					"date":"'.$assoc['regdate'].'",
					"category":"'.addslashes($assoc['category']).'"
				}';
			}
			$json_str= '{"list":['.$str.'],"lang":{"number":"New messages"}}';
		}
		else if($_GET['act'] == 'write') {
			if($_POST['pk']) {
				$body = substr($_POST['body'], strlen('<div class="ql-editor" data-gramm="false" contenteditable="true">'), strpos($_POST['body'],'</div>')-strlen('<div class="ql-editor" data-gramm="false" contenteditable="true">'));
				$sq = "update ".$DB_COMMON['message']." set category = '".$_POST['category']."', title = '".addslashes(trim($_POST['title']))."', body = '".addslashes($body)."', from_p = '".$_SESSION['logID']."@".$_SESSION['db_name']."', to_p = '".$_POST['to_p']."' where pk = ".$_POST['pk'];
			}
			else {
				$body = substr($_POST['body'], 0, strpos($_POST['body'],'</div><div class="ql-clipboard"'));
				$body = substr($body,strpos($body,'>')+1,strlen($body));
				$body = trim($body);
				$sq = "insert into ".$DB_COMMON['message']."(regdate, category, title, body, from_p, to_p) values(now(), '".$_POST['category']."', '".addslashes(trim($_POST['title']))."', '".addslashes($body)."', '".$_SESSION['logID']."@".$_SESSION['db_name']."', '".$_POST['to_p']."') ";
			}
			print $sq;
//			$rs = mysqli_query($connect0, $sq);
			$str = '"'.$sq.'"';
			$json_str= '['.$str.']';
		}
		else if($_GET['act'] == 'view') {
			$sq = "select * from ".$DB_COMMON['message']." where pk= ".$_GET['pk'];
			$rs = mysqli_query($connect0, $sq);
			$assoc = mysqli_fetch_assoc($rs);
			
			$json_str .= '{
				"pk":"'.$assoc['pk'].'", 
				"title":"'.$assoc['title'].'", 
				"body":"'.addslashes($assoc['body']).'", 
				"from":"'.$assoc['from_p'].'",
				"to":"'.$assoc['to_p'].'",
				"date":"'.$assoc['regdate'].'",
				"category":"'.addslashes($assoc['category']).'"
			}';
			
		}
	}
	else {
		$sq = "select * from ".$DB_COMMON['message']." where to_p='".$_SESSION['logID']."@".$_SESSION['db_name']."' and flag='n'";
		$rs = mysqli_query($connect0, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$assoc = mysqli_fetch_assoc($rs);
			if($assoc['category'] == 'info') {
				$assoc['category'] = '<i class="align-middle fas fa-2x fa-info-circle"></i>';
			}
			if($i>0) {
				$str .= ',';
			}
			$str .= '{
				"title":"'.$assoc['title'].'", 
				"body":"'.addslashes($assoc['body']).'", 
				"from":"'.$assoc['from_p'].'",
				"date":"'.$assoc['regdate'].'",
				"category":"'.addslashes($assoc['category']).'"
			}';
		}
		$json_str= '{"newAlert":['.$str.'],"lang":{"number":"New messages"}}';
	}
}

else if ($_GET['fr'] == 'version') {
	
	$sq = "select * from ".$DB_COMMON['message']." where category='version' order by regdate desc ";
	$rs = mysqli_query($connect0, $sq);
	$alert_num = $rs->num_rows;
	for($i=0; $i<($rs->num_rows); $i++) {
		$assoc = mysqli_fetch_assoc($rs);
		if($i>0) {
			$str .= ',';
		}
		$str .= '{
			"title":"'.$assoc['title'].'", 
			"body":"'.addslashes($assoc['body']).'", 
			"date":"'.date("Y-m-d", strtotime($assoc['regdate'])).'",
			"category":"'.addslashes($assoc['category']).'"
		}';
	}
	$json_str= '['.$str.']';
}

else if ($_GET['fr'] == 'feedback') {
	if($_GET['act'] == "write") {
		$body = substr($_POST['body'],0,strpos($_POST['body'],'</div><div class="ql-clipboard"'));
		$body = substr($body,strpos($body,'>')+1,strlen($body));
		$body = trim($body);
		$sq = "insert into ".$DB_COMMON['message']."(regdate, category, title, body, from_p, to_p) values(now(), 'feedback', '".addslashes(trim($_POST['title']))."', '".addslashes($body)."', '".$_SESSION['logID']."@".$_SESSION['db_name']."', 'admin') ";
		$rs = mysqli_query($connect0, $sq);

		$str = '"'.$sq.'"';
		$json_str= '['.$str.']';
	}
	else {
		$sq = "select * from ".$DB_COMMON['message']." where category='feedback' order by regdate desc ";
		$rs = mysqli_query($connect0, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$assoc = mysqli_fetch_assoc($rs);
			if($i>0) {
				$str .= ',';
			}
			$str .= '{
				"title":"'.$assoc['title'].'", 
				"body":"'.addslashes($assoc['body']).'", 
				"date":"'.$assoc['regdate'].'",
				"category":"'.addslashes($assoc['category']).'",
				"from":"'.$assoc['from_p'].'"
			}';
		}
		$json_str= '['.$str.']';	
	}
	
}
########################################  MAIN PAGE ######################################
else if ($_GET['fr'] == 'card_dash') {
	$msg=q_language('dashboard.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'],'day', $_GET['sq'], $_GET['st']);
	$arr_cat = makeCategory($arr_sq['from'], $arr_sq['to'], 'day');

	$arr_cfg = [
		["title"=>"today", "display"=>$msg['today'], "badge"=>$msg['visitors'], "badge_color"=> "#47bac1", "label"=>"entrance"],
		["title"=>"yesterday", "display"=>$msg['yesterday'], "badge"=>$msg['visitors'], "badge_color"=> "#fcc100", "label"=>"entrance"],
		["title"=>"average", "display"=>$msg['average12weeks'], "badge"=>$msg['visitors'], "badge_color"=> "#5b7dff", "label"=>"entrance"],
		["title"=>"total", "display"=>$msg['total12weeks'], "badge"=>$msg['visitors'], "badge_color"=> "#5fc27e", "label"=>"entrance"],
	];		
	$arr_cfg = queryWebConfig('dashboard','card_banner', $arr_cfg);
	print_r($arr_cfg);

}

else if ($_GET['fr'] == 'card_small') {
	$msg=q_language('dashboard.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'],'day', $_GET['sq'], $_GET['st']);
	$arr_cat = makeCategory($arr_sq['from'], $arr_sq['to'], 'day');

	$arr_cfg = [
		["title"=>"today", "display"=>$msg['today'], "badge"=>$msg['visitors'], "badge_color"=> "#47bac1", "label"=>"entrance"],
		["title"=>"yesterday", "display"=>$msg['yesterday'], "badge"=>$msg['visitors'], "badge_color"=> "#fcc100", "label"=>"entrance"],
		["title"=>"average", "display"=>$msg['average12weeks'], "badge"=>$msg['visitors'], "badge_color"=> "#5b7dff", "label"=>"entrance"],
		["title"=>"total", "display"=>$msg['total12weeks'], "badge"=>$msg['visitors'], "badge_color"=> "#5fc27e", "label"=>"entrance"],
	];		
	$arr_cfg = queryWebConfig('dashboard','card_banner', $arr_cfg);
	print_r($arr_cfg);

}


else if ($_GET['fr'] == 'dashBoard') {
//	print_arr($_GET);
	$msg = q_language('dashboard.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'],'day', $_GET['sq'], $_GET['st']);
	$arr_cat = makeCategory($arr_sq['from'], $arr_sq['to'], 'day');
	
	$arr_rs = array();
	$arr_result= array();
	if($_GET['page'] == 'footfall0') {
		$arr_cfg = [
			["title"=>"today", 		"display"=>$msg['today'], 			"badge"=>$msg['visitors'], "badge_color"=> "#47bac1", "label"=>"entrance"],
			["title"=>"yesterday", 	"display"=>$msg['yesterday'], 		"badge"=>$msg['visitors'], "badge_color"=> "#fcc100", "label"=>"entrance"],
			["title"=>"average", 	"display"=>$msg['average12weeks'], 	"badge"=>$msg['visitors'], "badge_color"=> "#5b7dff", "label"=>"entrance"],
			["title"=>"total", 		"display"=>$msg['total12weeks'], 	"badge"=>$msg['visitors'], "badge_color"=> "#5fc27e", "label"=>"entrance"],
			["title"=>'footfall', 	"display"=>$msg['footfall']],
			["title"=>"lastweek", 	"display"=>$msg['lastweek'], 															  "label"=>"entrance"],
			["title"=>"thisweek", 	"display"=>$msg['thisweek'], 															  "label"=>"entrance"],
			["title"=>"last12weeks","display"=>$msg['last12weeks'], 														  "label"=>"entrance"],
		];
		$arr_cfg = queryWebConfig('dashboard','', $arr_cfg);
		// $arr_cfg = queryWebConfig('dashboard','footfall', $arr_cfg);
		// print_r($arr_cfg);


	}
	else if($_GET['page'] == 'card') {
		$arr_cfg = [
			["title"=>"today", 		"display"=>$msg['today'], 			"badge"=>$msg['visitors'], "badge_color"=> "#47bac1", "label"=>"entrance"],
			["title"=>"yesterday", 	"display"=>$msg['yesterday'], 		"badge"=>$msg['visitors'], "badge_color"=> "#fcc100", "label"=>"entrance"],
			["title"=>"average12week",  "display"=>$msg['average12weeks'], 	"badge"=>$msg['visitors'], "badge_color"=> "#5b7dff", "label"=>"entrance"],
			["title"=>"total12week",  	"display"=>$msg['total12weeks'], 	"badge"=>$msg['visitors'], "badge_color"=> "#5fc27e", "label"=>"entrance"],
		];		
		$arr_cfg = queryWebConfig('dashboard','card_banner', $arr_cfg);
		$sq_workinghour = queryWorkingHour();

		$sq = "select year, month, day, timestamp, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp <".$arr_cat['ts_to']." ".$arr_sq['p_sq']." ".$sq_workinghour." group by year, month, day, counter_label";
		// print $sq;
		$rs = mysqli_query($connect0, $sq);
		$arr_rs = array();
		for ($i=0; $i<$rs->num_rows; $i++){
			$assoc = mysqli_fetch_assoc($rs);
			$ts = $assoc['timestamp'] - ($assoc['timestamp']%(3600*24)); 
			$arr_rs[$ts][$assoc['counter_label']] = $assoc['sum'];
		}

		for ($i=0; $i<4; $i++){
			$arr_result[$i]['value'] = 0;
			$total = 0;
			$cnt = 0;
			if ($arr_cfg[$i]['title'] == 'today') {
				$ts = $arr_cat['ts_to']-3600*24;
				$arr_result[$i]['ref_date'] =  date("Y-m-d ", $ts);
				
				foreach($arr_cfg[$i]['labels'] as $ct_label) {
					$arr_result[$i]['value'] += $arr_rs[$ts][$ct_label];
					foreach($arr_rs as $ts=>$arr_label){
						$total += $arr_label[$ct_label];
						$cnt ++;
					}
				}
				if ($cnt) {
					$arr_result[$i]['percent'] = round($arr_result[$i]['value'] / $total * $cnt * 100,2);
				}

			}
			else if ($arr_cfg[$i]['title'] == 'yesterday') {
				$ts = $arr_cat['ts_to']-3600*24*2;
				$arr_result[$i]['ref_date'] =  date("Y-m-d ", $ts);
				foreach($arr_cfg[$i]['labels'] as $ct_label) {
					$arr_result[$i]['value'] += $arr_rs[$ts][$ct_label];
					foreach($arr_rs as $tss=>$arr_label){
						if ($tss > $ts) {
							continue;
						}
						$total += $arr_label[$ct_label];
						$cnt ++;
					}
				}
				if ($cnt) {
					$arr_result[$i]['percent'] = round($arr_result[$i]['value'] / $total * $cnt * 100,2);
				}
			}
			else if ($arr_cfg[$i]['title'] == 'average') {
				$ts = $arr_cat['ts_to']-3600*24;
				$ts_s = $ts;
				$arr_result[$i]['ref_date'] = "";
				foreach($arr_cfg[$i]['labels'] as $ct_label) {
					foreach($arr_rs as $tss=>$arr_label){
						if ($tss > $ts) {
							continue;
						}
						$total += $arr_label[$ct_label];
						$cnt ++;
						if ($tss < $ts_s) {
							$ts_s = $tss;
						}
					}
				}
				$arr_result[$i]['ref_date'] = date("Y-m-d ", $ts_s)."~".date("Y-m-d ", $ts);
				if ($cnt) {
					$arr_result[$i]['value']   =  round($total/$cnt,2);
					$arr_result[$i]['percent'] = round($arr_result[$i]['value'] / $total * $cnt * 100,2);
				}
			}
			else if ($arr_cfg[$i]['title'] == 'total') {
				$ts = $arr_cat['ts_to']-3600*24;
				$ts_s = $ts;
				$arr_result[$i]['ref_date'] =  "";
				foreach($arr_cfg[$i]['labels'] as $ct_label) {
					foreach($arr_rs as $tss=>$arr_label){
						if ($tss > $ts) {
							continue;
						}
						$total += $arr_label[$ct_label];
						$cnt ++;
						if ($tss < $ts_s) {
							$ts_s = $tss;
						}						
					}
				}
				$arr_result[$i]['ref_date'] = date("Y-m-d ", $ts_s)."~".date("Y-m-d ", $ts);
				$arr_result[$i]['value']   = $total;
				if ($cnt) {
					$arr_result[$i]['percent'] = round($arr_result[$i]['value'] / $total * 100,2);
				}
			}
			else if ($arr_cfg[$i]['title'] == 'total12week') {
				$ts = $arr_cat['ts_to']-3600*24;
				$ts_s = $ts;
				$arr_result[$i]['ref_date'] =  "";
				foreach($arr_cfg[$i]['labels'] as $ct_label) {
					foreach($arr_rs as $tss=>$arr_label){
						if ($tss > $ts) {
							continue;
						}
						if ($tss < $ts-3600*24*12) {
							continue;
						}
						$total += $arr_label[$ct_label];
						$cnt ++;
						if ($tss < $ts_s) {
							$ts_s = $tss;
						}						
					}
				}
				$arr_result[$i]['ref_date'] = date("Y-m-d ", $ts_s)."~".date("Y-m-d ", $ts);
				if ($cnt) {
					$arr_result[$i]['value']   =  round($total/$cnt,2);
					$arr_result[$i]['percent'] = round($arr_result[$i]['value'] / $total * 100,2);
				}
			}
			else if ($arr_cfg[$i]['title'] == 'average12week') {
				$ts = $arr_cat['ts_to']-3600*24;
				$ts_s = $ts;
				$arr_result[$i]['ref_date'] =  "";
				foreach($arr_cfg[$i]['labels'] as $ct_label) {
					foreach($arr_rs as $tss=>$arr_label){
						if ($tss > $ts) {
							continue;
						}
						if ($tss < $ts-3600*24*12) {
							continue;
						}
						$total += $arr_label[$ct_label];
						$cnt ++;
						if ($tss < $ts_s) {
							$ts_s = $tss;
						}						
					}
				}
				$arr_result[$i]['ref_date'] = date("Y-m-d ", $ts_s)."~".date("Y-m-d ", $ts);
				$arr_result[$i]['value']   = $total;
				if ($cnt) {
					$arr_result[$i]['percent'] = round($arr_result[$i]['value'] / $total * 100,2);
				}
			}

			$arr_result[$i]['display'] = $msg['card_banner'.$i.'_display'];
			$arr_result[$i]['badge'] = $msg['card_banner'.$i.'_badge'];
		}
		$arr_result['elaspe'] = microtime(true) - $s1;
		$json_str = json_encode($arr_result, JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	}
	else if($_GET['page'] == 'cardx') {
		$arr_cfg = [
			["title"=>"today", "display"=>$msg['today'], "badge"=>$msg['visitors'], "badge_color"=> "#47bac1", "label"=>"entrance"],
			["title"=>"yesterday", "display"=>$msg['yesterday'], "badge"=>$msg['visitors'], "badge_color"=> "#fcc100", "label"=>"entrance"],
			["title"=>"average", "display"=>$msg['average12weeks'], "badge"=>$msg['visitors'], "badge_color"=> "#5b7dff", "label"=>"entrance"],
			["title"=>"total", "display"=>$msg['total12weeks'], "badge"=>$msg['visitors'], "badge_color"=> "#5fc27e", "label"=>"entrance"],
		];		
		$arr_cfg = queryWebConfig('dashboard','card_banner', $arr_cfg);
		// print_r($arr_cfg);
		$sq_workinghour = queryWorkingHour();
		for($i=0; $i<4; $i++){
			$sq = "select sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".($arr_cat['ts_to']-3600*24*84)." and timestamp <".$arr_cat['ts_to']." ".$arr_cfg[$i]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour." group by year, month, day";
			// print $sq;
			$rs = mysqli_query($connect0, $sq);
			$ave_ref = 0;
			while($assoc=mysqli_fetch_row($rs)){
				$ave_ref += $assoc[0]/($rs->num_rows); // (a+b+c+d)/4 = a/4+b/4+c/4+d/4
			}
			$ave_ref = (int)$ave_ref;
			if (!$ave_ref) {
				$ave_ref =1;
			}

			if ($arr_cfg[$i]['title'] == 'today') {
				$sq = "select sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".($arr_cat['ts_to']-3600*24)." and timestamp <".$arr_cat['ts_to']." ".$arr_cfg[$i]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour;
				$rs = mysqli_query($connect0, $sq);
				$arr_result[$i]['value'] = mysqli_fetch_row($rs)[0];
				$arr_result[$i]['percent'] = round($arr_result[$i]['value']/$ave_ref*100,2);
			}
			else if ($arr_cfg[$i]['title'] == 'yesterday') {
					$sq = "select sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".($arr_cat['ts_to']-3600*48)." and timestamp <".($arr_cat['ts_to']-3600*24)." ".$arr_cfg[$i]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour;
				$rs = mysqli_query($connect0, $sq);
				$arr_result[$i]['value'] = mysqli_fetch_row($rs)[0];
				$arr_result[$i]['percent'] = round($arr_result[$i]['value']/$ave_ref*100,2);
			}
			else if ($arr_cfg[$i]['title'] == 'average') {
				$arr_result[$i]['value'] = $ave_ref;
				$arr_result[$i]['percent'] = 100;

			}							
			else if ($arr_cfg[$i]['title'] == 'total') {
				// $sq = "select sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".($arr_cat['ts_to']-3600*24*84)." and timestamp <".$arr_cat['ts_to']." ".$arr_cfg[$i]['sq_label']." ".$arr_sq['p_sq'];
				// $rs = mysqli_query($connect0, $sq);
				// $arr_result[$i]['value'] = mysqli_fetch_row($rs)[0];
				// $arr_result[$i]['percent'] = 100;
				$arr_result[$i]['value'] = $ave_ref*($rs->num_rows);
				$arr_result[$i]['percent'] = 100;
			}
			if (!$arr_result[$i]['value']){
				$arr_result[$i]['value']=0;
			}
			$arr_result[$i]['sql'] = $sq;
			// $arr_result[$i]['display'] = $arr_cfg[$i]['display'];
			// $arr_result[$i]['badge'] = $arr_cfg[$i]['badge'];
			$arr_result[$i]['display'] = $msg['card_banner'.$i.'_display'];
			$arr_result[$i]['badge'] = $msg['card_banner'.$i.'_badge'];

		}
		
		$arr_result['elaspe'] = microtime(true) - $s1;
		// print_r($arr_result);
		$json_str = json_encode($arr_result, JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT);
	}
	else if($_GET['page'] == 'footfall') {

		$arr_cfg = [
			["title"=>"lastweek", "display"=>$msg['lastweek'], "labels"=>["entrance"]],
			["title"=>"thisweek", "display"=>$msg['thisweek'], "labels"=>["entrance"]],
			["title"=>"last12weeks", "display"=>$msg['last12weeks'], "labels"=>["entrance"]],
			["title"=>"last12weeks", "display"=>$msg['last12weeks'], "labels"=>[]],
		];
		$arr_cfg = queryWebConfig('dashboard', 'footfall', $arr_cfg);
		$sq_workinghour = queryWorkingHour();

		for($i=0; $i<4; $i++){
			if($arr_cfg[$i]['title']=='thisweek'){
				$from_ts = $arr_sq['ts_to']-3600*24*7;
				$to_ts =  $arr_sq['ts_to'];
			}
			else if($arr_cfg[$i]['title']=='lastweek'){
				$from_ts = $arr_sq['ts_to']-3600*24*14;
				$to_ts =  $arr_sq['ts_to']-3600*24*7;
			}
			else if($arr_cfg[$i]['title']=='last12weeks'){
				$from_ts = $arr_sq['ts_to']-3600*24*84;
				$to_ts =  $arr_sq['ts_to'];
			}
			if (!$arr_cfg[$i]['sq_label']) {
				continue;
			}
			$sq  = "select year, month, day, wday, hour, min, timestamp, counter_name, 'ct_label' as counter_label, sum(counter_val) as sum  from ".$DB_CUSTOM['count']." where timestamp >=".$from_ts." and timestamp <".$to_ts." ".$arr_cfg[$i]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour." group by year, month, day";
			// print $sq."\n";
			$arr_result['bar'][$i]['sql'] = $sq;
			$rs = mysqli_query($connect0, $sq);
			$arr_rs[$i] = Result2Json4Curve($rs, date("Y-m-d", $from_ts ), date("Y-m-d", $to_ts-1 ), 'day', $format='array');
		}
		$arr_result['title']['display'] = $msg['footfall_title'];
		$arr_result['bar']['data'][0]['name'] =$msg['footfall_0_display'];
		$arr_result['bar']['data'][1]['name'] =$msg['footfall_1_display'];
		$arr_result['bar']['data'][0]['data'] = $arr_rs[0]['data'][0]['data'];
		$arr_result['bar']['data'][1]['data'] = $arr_rs[1]['data'][0]['data'];
				
		$arr_result['bar']['category']['timestamps'] = $arr_rs[0]['category']['timestamps'];
		for($i=0; $i<7; $i++) {
			$ts =  $arr_rs[0]['category']['timestamps'][$i];
			$arr_result['bar']['category']['datetimes'][$i] = [$msg[strtolower(date('D',$ts))],date("m-d", $ts)];
		}
		$arr_result['bar']['title']['chart_title'] = '';

		if (isset($arr_rs[2])) {
			$arr_result['curve']['data'][0]['name'] = $msg['footfall_2_display'];
			$arr_result['curve']['data'][0]['data'] = $arr_rs[2]['data'][0]['data'];
		}
		if (isset($arr_rs[3])) {
			$arr_result['curve']['data'][1]['name'] = $msg['footfall_3_display'];
			$arr_result['curve']['data'][1]['data'] = $arr_rs[3]['data'][0]['data'];
		}
		$arr_result['curve']['category'] = $arr_rs[2]['category'];
		$arr_result['curve']['title']['chart_title'] = '';

		// print_r($arr_result);
		$json_str = json_encode($arr_result, JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT);
	}
	// else if($_GET['page'] == 'curveByLabel') {
	else if($_GET['page'] == 'third_block') {
		$arr_cfg = queryWebConfig('dashboard', 'third_block');
		$third_block = $arr_cfg[0]['title'];
		$arr_rs['third_block'] = $third_block;
		$sq_workinghour = queryWorkingHour();
		if ($third_block == 'curve_by_label')  {
			$arr_rs['text_body'] = '<div class="col-lg-12 col-xl-12">
				<div class="card flex-fill w-100 d-flex">
					<div class="card-header"><h5 class="card-title mb-0">'.$msg['third_block_display'].'</h5></div>
					<div class="card-body  p-2">
						<div id="third_block_body"></div>
					</div>
				</div>
			</div>';

			$arr_cfg = queryWebConfig('dashboard', 'curve_by_label');
			// print_r($arr_cfg);
			for($i=0, $n=0; $i<6; $i++){
				if(!isset($arr_cfg[$i]['labels']) || !$arr_cfg[$i]['labels']) {
					continue;
				}
				$sq  = "select year, month, day, wday, hour, min, counter_name, 'ct_label' as counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
				$sq .= "where timestamp >=".($arr_sq['ts_to']-3600*24*84)." and timestamp <".$arr_sq['ts_to']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_cfg[$i]['sq_label']." ";
				$sq .= $arr_sq['g_sq']." ";
				// print $sq;
				$rs = mysqli_query($connect0, $sq);
				$arr_result[$n] = Result2Json4Curve($rs, date("Y-m-d", $arr_cat['ts_to']-3600*24*84 ), $arr_sq['to'], 'day', $format='array');
				$arr_rs['data'][$n]['name']= $msg['curve_by_label'.$i.'_display'];
				$arr_rs['data'][$n]['color']=  $arr_cfg[$i]['color'];
				$arr_rs['data'][$n]['data']= $arr_result[$i]['data'][0]['data'];
				$n++;
			}

			$arr_rs['category']['timestamps'] = $arr_result[0]['category']['timestamps'];
			$arr_rs['category']['datetimes'] = $arr_result[0]['category']['datetimes'];
			$arr_rs['time'] = round(microtime(true)-$s1,4);
			$arr_rs['title']['chart_title'] = '';	
			// print_r($arr_rs);
		}
		else if ($third_block == 'age_gender')  {
			$arr_rs['text_body'] = '<div class="col-12 col-lg-6 ">
				<div class="card flex-fill">
					<div class="card-header"><h5 class="card-title mb-0">'.$msg['agegroup'].'</h5></div>
					<div class="card-body w-100 d-flex">
						<div class="col-sm-6"><div id="age_ave_chart"></div></div>
						<div class="col-sm-6"><div id="age_today_chart"></div></div>
					</div>
					<div class="card-body">
						<div id="age_curve_chart"></div>
					</div>
				</div>
			</div>	
			<div class="col-12 col-lg-6 d-flex">
				<div class="card flex-fill w-100">
					<div class="card-header"><h5 class="card-title mb-0">'.$msg['gender'].'</h5></div>
					<div class="card-body d-flex w-100 text-center">
						<div class="col-sm-6" align="center" id="gender_ave"></div>
						<div class="col-sm-6" align="center" id="gender_today"></div>
					</div>
					<div class="card-body w-100">
						<div id="gender_curve_chart"></div>
					</div>
				</div>
			</div>';
		}
		$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);
		unset($arr_rs);
		unset($arr_result);

	}

}

else if( $_GET['fr'] == 'dataGlunt')  {
	$msg=q_language('footfall.php');
	$arr_sq = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);

	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	$arr_cfg = queryWebConfig('analysis', 'dataGlunt', $arr_cfg);
	// $arr_cfg = queryWebConfig('footfall', 'dataGlunt', $arr_cfg);
	// print_r($arr_cfg);
	$sq_workinghour = queryWorkingHour();
	$sq  = "select year, month, day, wday, hour, min, counter_name, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
	$sq .= "where timestamp >=".$arr_sq['ts_from']." and timestamp <".$arr_sq['ts_to']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_cfg[0]['sq_label'] ." and counter_label !='none' ";
	$sq .= $arr_sq['g_sq'].", counter_label ";
	$rs = mysqli_query($connect0, $sq);
	$arr_rs = Result2Json4Curve($rs, $arr_sq['from'], $arr_sq['to'],  $_GET['view_by'], $format='array');
	$arr_rs['sql'] = $sq;
	// $arr_rs['title']['chart_title'] = trim($arr_cfg[0]['display']) ? $arr_cfg[0]['display']  : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	// print_r($arr_rs);
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT);
	unset($arr_rs);
}

else if($_GET['fr'] == 'latestFlow') {
	$msg=q_language('footfall.php');
	$s1 = microtime(true);

	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	$arr_cfg = queryWebConfig('analysis', 'latestFlow', $arr_cfg);
	$sq_workinghour = queryWorkingHour();
	if($_GET['view_on']=='7day') {
		$dateFrom = date("Y-m-d", $thistime - 3600*24*7);
	}
	else if($_GET['view_on']=='4week') {
		$dateFrom = date("Y-m-d", $thistime - 3600*24*7*4);
	}		
	else if($_GET['view_on']=='12week') {
		$dateFrom = date("Y-m-d", $thistime - 3600*24*7*12);
	}

	$arr_sq = getViewByQuery($dateFrom."~".date("Y-m-d", $thistime), 'day', $_GET['sq'], $_GET['st']);

	$sq  = "select year, month, day, wday, hour, min, counter_name, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".$arr_sq['ts_from']." and timestamp <".$arr_sq['ts_to']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_cfg[0]['sq_label']." ".$arr_sq['g_sq'].", counter_label ";
	$rs = mysqli_query($connect0, $sq);

	$arr_rs = Result2Json4Curve($rs, $arr_sq['from'], $arr_sq['to'], 'day', $format='array');
	$arr_rs['sql'] = $sq;
	// $arr_rs['title']['chart_title'] = trim($arr_cfg[0]['display']) ? $arr_cfg[0]['display']  : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	// print_r($arr_rs);
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);
	unset($arr_rs);

}

else if ($_GET['fr'] == 'trendAnalysis') {
	$msg=q_language('footfall.php');

	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	$arr_cfg = queryWebConfig('analysis', 'trendAnalysis', $arr_cfg);	

	// $arrViewBy = getViewByTime($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);
	$arr_sq = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);
	// print_r($arrViewBy);
	// print_r($arr_sq);
	$sq_workinghour = queryWorkingHour();
	$arr_result = array();
	$arr_rs = array();
	$s1 = microtime(true);
	
	if(($_GET['view_by'] == 'tenmin') or ($_GET['view_by'] == 'hour')) {
		$ts_from[0] = $arr_sq['ts_from']; 																		$ts_to[0] = $ts_from[0] + 3600*24;
		$ts_from[1] = $ts_from[0] - 3600*24*7;																	$ts_to[1] = $ts_from[1] + 3600*24;
		$ts_from[2] = mktime(0,0,0, date("m",$ts_from[0]), date("d",$ts_from[0]), date("Y",$ts_from[0])-1); 	$ts_to[2] = $ts_from[2] + 3600*24;
		$arr_line = [
			$msg['current'].'('.date("Y-m-d", $ts_from[0]).')', 
			$msg['last'].'('.date("Y-m-d", $ts_from[1]).')', 
			$msg['before'].'('.date("Y-m-d", $ts_from[2]).')'
		];	
	}
	else if($_GET['view_by'] == 'day') {
		$ts_from[0] = $arr_sq['ts_from'] - 3600*24*6; 														$ts_to[0] = $ts_from[0] + 3600*24*7;
		$ts_from[1] = $ts_from[0] - 3600*24*7;																	$ts_to[1] = $ts_from[1] + 3600*24*7;
		$ts_from[2] = mktime(0,0,0, date("m",$ts_from[0]), date("d",$ts_from[0]), date("Y",$ts_from[0])-1);		$ts_to[2] = $ts_from[2] + 3600*24*7;
		// $arrViewBy['duration'] = 7;
		$arr_line = [
			$msg['current'].'('.date("Y-m-d", $ts_from[0]).'~'.date("Y-m-d", $ts_to[0]).')',
			$msg['last'].'('.date("Y-m-d", $ts_from[1]).'~'.date("Y-m-d", $ts_to[1]).')', 
			$msg['before'].'('.date("Y-m-d", $ts_from[2]).'~'.date("Y-m-d", $ts_to[2]).')'
		];	
	}


	for ($i=0, $n=0; $i<3; $i++) {
		$sq  = "select year, month, day, wday, hour, min, counter_name, 'label' as counter_label, sum(counter_val) as sum, timestamp from ".$DB_CUSTOM['count']." ";
		$sq .= "where timestamp >=".$ts_from[$i]." and timestamp <".$ts_to[$i]." ".$arr_cfg[0]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_sq['g_sq']." ";
		$rs = mysqli_query($connect0, $sq);

		$arr_result[$i] = Result2Json4Curve($rs, date("Y-m-d", $ts_from[$i]), date("Y-m-d", $ts_to[$i]-1), $_GET['view_by'], $format='array');
		$arr_rs['data'][$i]['name'] = $arr_line[$i];
		$arr_rs['data'][$i]['data'] =  $arr_result[$i]['data'][0]['data'];
		$arr_rs['sql'][$i] = $sq;
		
	}

	$arr_rs['category']['timestamps'] = $arr_result[0]['category']['timestamps'];
	$arr_rs['category']['datetimes'] = $arr_result[0]['category']['datetimes'];
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	// $arr_rs['title']['chart_title'] = $arr_cfg[0]['display'] ? $arr_cfg[0]['display'] : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	

	// print_r($arr_rs);
	// print_r($arr_result);
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);
	unset($arr_rs);
	unset($arr_result);
}

else if($_GET['fr'] == 'advancedAnalysis') {
	$msg=q_language('footfall.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);
	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	$arr_cfg = queryWebConfig('analysis', 'advancedAnalysis', $arr_cfg);
	$sq_workinghour = queryWorkingHour();
	
	$sq  = "select year, month, day, wday, hour, min, counter_name, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".$arr_sq['ts_from']." and timestamp <".$arr_sq['ts_to']."  ".$arr_cfg[0]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_sq['g_sq'].", counter_label ";
	$rs = mysqli_query($connect0, $sq);
	$arr_rs = Result2Json4Curve($rs, $arr_sq['from'], $arr_sq['to'], $_GET['view_by'], $format='array');
	// $arr_rs['title']['chart_title'] = $arr_cfg[0]['display'] ? $arr_cfg[0]['display'] : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	$arr_sq['sql'] = $sq;
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);

}
else if($_GET['fr'] == 'compareByLabel') {
	$msg=q_language('footfall.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);
	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	$arr_cfg = queryWebConfig('analysis', 'compareByLabel', $arr_cfg);
	$sq_workinghour = queryWorkingHour();
	
	$sq  = "select year, month, day, wday, hour, min, counter_name, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." where timestamp >=".$arr_sq['ts_from']." and timestamp <".$arr_sq['ts_to']."  ".$arr_cfg[0]['sq_label']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_sq['g_sq'].", counter_label ";
	$rs = mysqli_query($connect0, $sq);
	$arr_rs = Result2Json4Curve($rs, $arr_sq['from'], $arr_sq['to'], $_GET['view_by'], $format='array');
	// $arr_rs['title']['chart_title'] = $arr_cfg[0]['display'] ? $arr_cfg[0]['display'] : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	$arr_sq['sql'] = $sq;
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);

}

else if($_GET['fr'] == 'compareByTime') {
	$msg=q_language('footfall.php');
	$s1 = microtime(true);
	$arr_sq[0] = getViewByQuery($_GET['time_ref1'], 'hour', $_GET['sq'], $_GET['st']);
	$arr_sq[1] = getViewByQuery($_GET['time_ref2'], 'hour', $_GET['sq'], $_GET['st']);
	$arr_sq[2] = getViewByQuery($_GET['time_ref3'], 'hour', $_GET['sq'], $_GET['st']);

	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	// $arr_cfg = queryWebConfig('dataCompare', 'byTime', $arr_cfg);
	$arr_cfg = queryWebConfig('analysis', 'compareByTime', $arr_cfg);
	$sq_workinghour = queryWorkingHour();

	for($i=0, $n=0; $i<3; $i++) {
		$sq  = "select year, month, day, wday, hour, min, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
		$sq .= "where (timestamp >= ".$arr_sq[$i]['ts_from']." and timestamp <".$arr_sq[$i]['ts_to'].") and counter_label != 'none' ".$arr_cfg[0]['sq_label']." ".$arr_sq[$i]['p_sq']." ".$sq_workinghour." ".$arr_sq[$i]['g_sq'].", counter_label";	
		$rs = mysqli_query($connect0, $sq);
		$arr_result[$i] = Result2Json4Curve($rs, $arr_sq[$i]['from'], $arr_sq[$i]['to'], 'hour', $format='array');
		for($j=0; $j<sizeof($arr_result[$i]['data']); $j++) {
			$arr_rs['data'][$n]['name']= date("m-d", $arr_sq[$i]['ts_from'])." ".$arr_result[$i]['data'][$j]['name'];
			$arr_rs['data'][$n]['data']= $arr_result[$i]['data'][$j]['data'];
			$n++;
		}
		$arr_rs['sql'][$i] = $sq;
	}
	// $arr_rs['title']['chart_title'] = $arr_cfg[0]['display'] ? $arr_cfg[0]['display'] : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	$arr_rs['category']['timestamps'] = $arr_result[0]['category']['timestamps'];
	$arr_rs['category']['datetimes'] = $arr_result[0]['category']['datetimes'];
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	// print_r($arr_rs);
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);
}

else if($_GET['fr'] == 'compareByPlace') {
	$msg = q_language('footfall.php');
	$s1 = microtime(true);
	$arr_sq[0] = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq1'], $_GET['st1']);
	$arr_sq[1] = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq2'], $_GET['st2']);
	$arr_sq[2] = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq3'], $_GET['st3']);
	// print_r($arr_sq);

	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	// $arr_cfg = queryWebConfig('dataCompare', 'byPlace', $arr_cfg);
	$arr_cfg = queryWebConfig('analysis', 'compareByPlace', $arr_cfg);
	$sq_workinghour = queryWorkingHour();

	for($i=0, $n=0; $i<3; $i++) {
		if (! $_GET['sq'.($i+1)]) {
			continue;
		}
		$sq  = "select year, month, day, wday, hour, min, square_code, store_code, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
		$sq .= "where timestamp >= ".$arr_sq[$i]['ts_from']."  and timestamp < ".$arr_sq[$i]['ts_to']." ".$arr_cfg[0]['sq_label']." ".$arr_sq[$i]['p_sq']." ".$sq_workinghour." ".$arr_sq[$i]['g_sq'].", counter_label ";
		$rs = mysqli_query($connect0, $sq);
		$arr_result[$i] = Result2Json4Curve($rs, $arr_sq[$i]['from'], $arr_sq[$i]['to'], $_GET['view_by'], $format='array');

		if($_GET['st'.($i+1)]) {
			$sqa = "select name from ".$DB_CUSTOM['store']." where code ='".$_GET['st'.($i+1)]."' ";
		}
		else {
			$sqa = "select name from ".$DB_CUSTOM['square']." where code ='".$_GET['sq'.($i+1)]."' ";
		}
		$site_name = mysqli_fetch_row(mysqli_query($connect0, $sqa))[0];

		for ($j=0; $j<sizeof($arr_result[$i]['data']); $j++) {
			$arr_rs['data'][$n]= $arr_result[$i]['data'][$j];
			$arr_rs['data'][$n]['name']= $site_name.":".$arr_result[$i]['data'][$j]['name'];
			$n++;
		}
		$arr_rs['sql'][$i] = $sq;

	}
	$arr_rs['category'] = $arr_result[0]['category'];
	// $arr_rs['title']['chart_title'] = $arr_cfg[0]['display'] ? $arr_cfg[0]['display'] : $msg['footfall'];
	$arr_rs['title']['chart_title'] = isset($msg['analysis_'.strtolower($_GET['fr'])]) ? $msg['analysis_'.strtolower($_GET['fr'])] : $msg['footfall'];
	$arr_rs['time'] = round(microtime(true)-$s1,4);
	// print_r($arr_rs);
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);

}

else if($_GET['fr'] == 'kpi') {	
	$card_val[0] = 2500;
	$card_val[1] = 2300;
	$card_val[2] = 3.2457;
	$card_val[3] = 500;
	$card_val[4] = 30.1254;
	
	$json_str= '{
		"data":['.$s_str.'],
		"label":['.$l_str.'],
		"title":{
			"chart_title":"'.$chart_title.'"
		},
		"card_val":['.$card_val[0].','.$card_val[1].','.$card_val[2].','.$card_val[3].','.$card_val[4].']
	}';
}

else if($_GET['fr'] == 'trafficDistribution') {	
	$arr_rs = array();
	$msg = q_language('footfall.php');
	$s1 = microtime(true);
	$arrViewBy = getViewByQuery($_GET['time_ref'], 'hour', $_GET['sq'], $_GET['st']);
	$arrViewBy['ts_from'] = strtotime(explode("~", $_GET['time_ref'])[0]);
	// print_r($arrViewBy);

	$lines = ceil(($arrViewBy['ts_to'] - $arrViewBy['ts_from']) /3600/24);

	$arr_cfg = [["display"=> $msg['footfall'], "label" => "entrance"]];
	$arr_cfg = queryWebConfig('analysis', 'trafficDistribution', $arr_cfg);
	// print_r($arr_cfg);
	$sq_workinghour = queryWorkingHour();
	if ($_GET['view_on'] == 'visit') {
		$sqc = " ".$arr_cfg[0]['sq_label']." ".$arrViewBy['p_sq']." ".$arrViewBy['g_sq']." ";
	}
	else if ($_GET['view_on'] == 'occupy') {
		$sqc = " and (counter_label='entrance' or counter_label='exit') ".$arrViewBy['p_sq']." ".$arrViewBy['g_sq'].", counter_label ";
	}

	$sq = "select year, month, day, wday, hour, min, counter_label, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
	$sq.= " where timestamp >=".$arrViewBy['ts_from']." and  timestamp <".$arrViewBy['ts_to']." ".$sq_workinghour." ".$sqc;
	$arr_rs['sql'] = $sq;
	$rs = mysqli_query($connect0, $sq);

	$CLEANUP_HOUR = (queryWebConfig('analysis','traffic_reset_hour'))[0]['body'];
	// $CLEANUP_HOUR = 4;
	// print($CLEANUP_HOUR);
	for($i=0; $i<24; $i++){
		$arr_rs['label'][$i] = sprintf("%02d:00",  $i);
	}
	$arr_d = array();
	while ($assoc = mysqli_fetch_assoc($rs)){
		// print_r($assoc);
		$d_str = sprintf("%04d%02d%02d%02d00", $assoc['year'],$assoc['month'],$assoc['day'],$assoc['hour']);
		if ($_GET['view_on'] == 'visit') {
			$arr_d[$d_str] = $assoc['sum'];
		}
		else {
			$arr_d[$d_str][$assoc['counter_label']] = $assoc['sum'];
		}
	}
	// print_r($arr_d);
	for($i=0; $i<$lines; $i++){
		$ts = $arrViewBy['ts_from'] + 3600*24*$i;
		$arr_rs['data'][$i]['name'] = date("Y-m-d", $ts)." ".$msg[strtolower(date("D", $ts))];
		for($j=0; $j<24; $j++){
			$d_str = date("YmdHi", $ts+3600*$j);
			if ($_GET['view_on'] == 'visit') {
				$arr_rs['data'][$i]['data'][$j] = $arr_d[$d_str];
			}
			else {
				if(sprintf("%02d:00",$j+1) == $CLEANUP_HOUR) {
					$OCCUPY = 0;
				}
				$OCCUPY += $arr_d[$d_str]['entrance'] - $arr_d[$d_str]['exit'];
				$arr_rs['data'][$i]['data'][$j] = $OCCUPY;
			}
		}
	}
	$arr_rs['data'] = array_reverse($arr_rs['data']);
	$arr_rs['time'] = round(microtime(true)-$s1,4);	
	$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT);
}

else if ($_GET['fr'] == 'heatMap') {
	$s1 = microtime(true);
	// $arrViewBy = getViewByQuery(0, 'hour', $_GET['sq'], $_GET['st']);
	// print_r($arrViewBy);
	if($_GET['act'] == 'list') {
		$arrViewBy = getViewByQuery(0, 'hour', $_GET['sq'], $_GET['st']);
		$arr_rs = array();
		$sq = "select device_info from ".$DB_CUSTOM['camera'] ." where enable_heatmap = 'y' ".$arrViewBy['p_sq'];
		$rs = mysqli_query($connect0, $sq);
		for ($i=0; $i<($rs->num_rows); $i++) {
			$row = mysqli_fetch_row($rs);
			$sq = "select body from ".$DB_COMMON['snapshot']." where device_info='".$row[0]."'";
			$img = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
			array_push($arr_rs, ['device_info' => $row[0], 'image' => $img]);
		}
		// print_r($arr_rs);
		$json_str = json_encode($arr_rs, true);
	}
	
	else {
		$arrViewBy = getViewByQuery($_GET['time_ref'], 'hour', 0, 0);
		$arr_rs = array();
		$device_info = "mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model'];
		$arr_rs['title'] = $device_info;

		$arr_rs['scale'] = isset($_GET['scale']) ? : 10 ;
		if($_GET['view_by'] == 'hour') {
			$from_ts = strtotime($_GET['time_ref']);
			$to_ts = $from_ts + 3600 - 1;
			$arr_rs['subtitle'] = date("Y-m-d H:i", $from_ts)." ~ ".date("Y-m-d H:i", $to_ts);
		}
		else if($_GET['view_by'] == 'day') {
			$from_ts = strtotime($_GET['time_ref']);
			$to_ts = $from_ts + 3600*24 - 1;
			$arr_rs['subtitle'] = date("Y-m-d", $from_ts);
		}
		
		$sq = "select body_csv, concat(year,'/',month,'/',day,' ',hour,':00'), device_info,  timestamp from ".$DB_CUSTOM['heatmap']." where device_info = '".$device_info."' and timestamp >= ".$from_ts." and timestamp < ".$to_ts." ";
		
		$rs = mysqli_query($connect0, $sq);
		$val = array();
		while($assoc = mysqli_fetch_assoc($rs)) {
			$line = explode("\r\n",$assoc['body_csv']);
			for ($y=0; $y<sizeof($line); $y++){
				$col = explode(",",$line[$y]);
				for($x=0; $x <sizeof($col); $x++){
					if (!isset($val[$x][$y])) {
						$val[$x][$y] = 0;
					}
					$val[$x][$y] += (int)trim($col[$x]);
				}
			}
		}
		$arr_rs['max'] = 0;
		$arr_rs['data'] = [];
		for($y=0, $n=0; $y<44; $y++) {
			for($x = 0; $x<80; $x++) {
				if($val[$x][$y]) {
					$arr_rs['data'][$n] = ['x'=> $x * $arr_rs['scale'] , 'y'=>$y * $arr_rs['scale'] , 'value'=>$val[$x][$y]];
					if($val[$x][$y] > $arr_rs['max']){
						$arr_rs['max'] = $val[$x][$y];
					}
					$n++;
				}
			}
		}

		$sq = "select body from ".$DB_COMMON['snapshot']." where device_info='".$device_info."'";
		$arr_rs['image'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
		$arr_rs['time'] = round(microtime(true)-$s1,4);

		$json_str = json_encode($arr_rs, JSON_NUMERIC_CHECK);
		// print $json_str;
	}
}

else if ($_GET['fr'] == 'age') {
	$msg=q_language('dashboard.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);
	$arr_cat = makeCategory($arr_sq['from'], $arr_sq['to'], $_GET['view_by']);
	$sq_workinghour = queryWorkingHour();
	// $sq = "select body from ".$DB_CUSTOM['web_config']." where page='age_gender' and name ='age_group'";
	$sq = "select body from ".$DB_CUSTOM['web_config']." where page='analysis' and frame ='age_group'";
	$age_range_json = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
	if(!$age_range_json) {
		$age_range_json = '[0,18,30,45,65]';
	}
	$age_range = json_decode($age_range_json, true);
	$age_query_string = age_query_string();

	$sq =  "select square_code, store_code, camera_code, year, month, day, hour, min, ".$age_query_string." from ".$DB_CUSTOM['age_gender']." ";
	$sq .= "where timestamp >=".$arr_cat['ts_from']." and timestamp <".$arr_cat['ts_to']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_sq['g_sq'];
	// print $sq."\n";
	$rs = mysqli_query($connect0, $sq);
	if (!$rs->num_rows) {
		$arr_result['result'] = 'no data';
	}
	else {
		while($assoc = mysqli_fetch_assoc($rs)){
			$datetime =  date($arr_cat['dateformat'], mktime($assoc['hour'], $assoc['min'], 0, $assoc['month'], $assoc['day'], $assoc['year']));
			$arr_rs[$datetime] = age_group(json_decode('['.$assoc['age'].']'), $age_range);
		}

		for($i=0; $i<sizeof($age_range); $i++) {
			$arr_result['data'][$i]['name'] = $msg['agegroup'.$i] ? $msg['agegroup'.$i] : "AGE(".$age_range[$i].")";
			$arr_result['total']['name'][$i] = $msg['agegroup'.$i] ? $msg['agegroup'.$i] : "AGE(".$age_range[$i].")" ;
		}
		for ($i=0; $i<$arr_cat['duration']; $i++) {
			$sub_total = 0;
			for ($j=0; $j<sizeof($age_range); $j++){
				$sub_total += isset($arr_rs[$arr_cat['datetime'][$i]][$j]) ?$arr_rs[$arr_cat['datetime'][$i]][$j] : 0 ;
			}
			for ($j=0; $j<sizeof($age_range); $j++){
				if(!isset($arr_result['total']['data'][$j])) {
					$arr_result['total']['data'][$j] = 0;
				}
				if ($arr_cat['timestamp'][$i] > $thistime){
					$arr_result['data'][$j]['data'][$i] = null;
				}
				else if (!isset($arr_rs[$arr_cat['datetime'][$i]][$j]))  {
					$arr_result['data'][$j]['data'][$i] = 0;
				}
				else {
					$arr_result['data'][$j]['data'][$i] = round($arr_rs[$arr_cat['datetime'][$i]][$j] *100/ $sub_total) ;
				}
				$arr_result['total']['data'][$j] += $arr_result['data'][$j]['data'][$i];
			}
		}
		$arr_result['category']['timestamps'] = $arr_cat['timestamp'];
		$arr_result['category']['datetimes'] = $arr_cat['datetime'];
		$arr_result['title']['chart_title'] = $msg['agegroup'] ? $msg['agegroup'] : "AGE";
		$arr_result['title']['Average12Weeks'] = $msg['average12weeks'] ? $msg['average12weeks'] : "12 Weeks Average";
		$arr_result['title']['Average'] = $msg['average'] ? $msg['average'] : "Average";
		$arr_result['title']['Today'] = $msg['today'] ? $msg['today'] : "Today";
		$arr_result['time'] = round(microtime(true)-$s1,4);
	}
	// print_r($arr_result);
	$json_str = json_encode($arr_result, JSON_NUMERIC_CHECK);
}
else if ($_GET['fr'] == 'gender') {
	$msg=q_language('dashboard.php');
	$s1 = microtime(true);
	$arr_sq = getViewByQuery($_GET['time_ref'], $_GET['view_by'], $_GET['sq'], $_GET['st']);
	$arr_cat = makeCategory($arr_sq['from'], $arr_sq['to'], $_GET['view_by']);
	$sq_workinghour = queryWorkingHour();

	$gender_query_string = gender_query_string();
	$sq =  "select square_code, store_code, camera_code, year, month, day, hour, min, ".$gender_query_string." from ".$DB_CUSTOM['age_gender']." ";
	$sq .= "where timestamp >=".$arr_cat['ts_from']." and timestamp <".$arr_cat['ts_to']." ".$arr_sq['p_sq']." ".$sq_workinghour." ".$arr_sq['g_sq'];
	// print $sq."\n";

	$rs = mysqli_query($connect0, $sq);
	if (!$rs->num_rows) {
		$arr_result['result'] = 'no data';
	}
	else {	
		while($assoc = mysqli_fetch_assoc($rs)){
			$datetime =  date($arr_cat['dateformat'], mktime($assoc['hour'], $assoc['min'], 0, $assoc['month'], $assoc['day'], $assoc['year']));
			$arr_rs[$datetime] = json_decode('['.$assoc['gender'].']');
		}

		$arr_result['data'][0]['name'] = $msg['male'];
		$arr_result['data'][1]['name'] = $msg['female'];
		$arr_result['total']['name'][0] = $msg['male'];
		$arr_result['total']['name'][1] = $msg['female'];	

		for ($i=0; $i<$arr_cat['duration']; $i++) {
			$arr_result['data'][0]['data'][$i] = isset($arr_rs[$arr_cat['datetime'][$i]][0]) ?  round($arr_rs[$arr_cat['datetime'][$i]][0]*100 / ($arr_rs[$arr_cat['datetime'][$i]][0] + $arr_rs[$arr_cat['datetime'][$i]][1]),0) : 0;
			$arr_result['data'][1]['data'][$i] = isset($arr_rs[$arr_cat['datetime'][$i]][1]) ? round($arr_rs[$arr_cat['datetime'][$i]][1]*100 / ($arr_rs[$arr_cat['datetime'][$i]][0] + $arr_rs[$arr_cat['datetime'][$i]][1]),0) : 0;
			if ($arr_cat['timestamp'][$i] > $thistime){
				$arr_result['data'][0]['data'][$i] = null;
				$arr_result['data'][1]['data'][$i] = null;
			}
			else {
				if (!isset($arr_rs[$arr_cat['datetime'][$i]][0])) {  
					$arr_result['data'][0]['data'][$i] = 0;
				}
				if (!isset($arr_rs[$arr_cat['datetime'][$i]][1])) {  
					$arr_result['data'][1]['data'][$i] = 0;
				}

			}
			if(!isset($arr_result['total']['data'][0])) {
				$arr_result['total']['data'][0] = 0;
			}
			if(!isset($arr_result['total']['data'][1])){
				$arr_result['total']['data'][1] = 0;
			}
			$arr_result['total']['data'][0] += $arr_result['data'][0]['data'][$i];
			$arr_result['total']['data'][1] += $arr_result['data'][1]['data'][$i];
		}

		$arr_result['category']['timestamps'] = $arr_cat['timestamp'];
		$arr_result['category']['datetimes'] = $arr_cat['datetime'];
		$arr_result['title']['chart_title'] = $msg['gender'] ? $msg['gender'] : "GENDER";
		$arr_result['title']['Average12Weeks'] = $msg['average12weeks'] ? $msg['average12weeks'] : "12 Weeks Average";
		$arr_result['title']['Today'] = $msg['today'] ? $msg['today'] : "Today";
		$arr_result['time'] = round(microtime(true)-$s1,4);
	}
	// print_r($arr_result);
	$json_str = json_encode($arr_result, JSON_NUMERIC_CHECK);
}

else if($_GET['fr'] == 'macSniff') {
	$to_e = explode("~", $_GET['time_ref']);
	if($_GET['view_by'] == 'tenmin') {
		$g_sq = "group by year, month, day, hour, min ";
		$dateformat = 'Y-m-d H:i';
		$from_ts = strtotime($to_e[1]);
		$to_ts = $from_ts + 3600*24 -1;
		$interval = 600;
	}
	else if($_GET['view_by'] == 'hour') {
		$g_sq = "group by year, month, day, hour ";
		$dateformat = 'Y-m-d H:00';
		$from_ts = strtotime($to_e[1]);
		$to_ts = $from_ts + 3600*24 -1;
		$interval = 3600;
		$gender_title = date("Y-m-d", $from_ts);
		$age_title = date("Y-m-d", $from_ts);		
	}
	else if($_GET['view_by'] == 'day') {
		$g_sq = "group by year, month, day ";
		$dateformat = 'Y-m-d';
		$from_ts = strtotime($to_e[0]);
		$to_ts = strtotime($to_e[1]) + 3600*24 -1;
		$interval = 3600*24;
	}
	else if($_GET['view_by'] == 'month') {
		$g_sq = "group by year, month ";
		$dateformat = 'Y-m';
		$from_ts = strtotime(date("Y-m-1", strtotime($to_e[0])));
		$to_ts = strtotime(date("Y-m-t", strtotime($to_e[1]))) + 3600*24 -1;			
		$interval = 3600*24*31;
	}	

	$sq =  "select square_name, store_name, camera_name, device_info, year, month, day, hour, min, sum(male) as male, sum(female) as female, sum(age_1st) as age_1st, sum(age_2nd) as age_2nd, sum(age_3rd) as age_3rd, sum(age_4th) as age_4th, sum(age_5th) as age_5th, sum(age_6th) as age_6th, sum(age_7th) as age_7th from ".$DB_CUSTOM['mac']." ";
	$sq .= "where timestamp >= ".$from_ts." and timestamp < ".$to_ts." ";
	if($_GET['sq']) {
		$sq .=  "and square_code ='".$_GET['sq']."' ";
	}
	if($_GET['st']) {
		$sq .=  "and store_code ='".$_GET['st']."' ";
	}
	$sq .= $g_sq;
	$sq .= "order by timestamp asc ";
//	print $sq;
//	print Query2Table($connect0, $sq);
	
	$duration = ceil(($to_ts- $from_ts)/$interval);
	$rs = mysqli_query($connect0, $sq);
	
	$tag_div = array('age_1st','age_2nd','age_3rd','age_4th','age_5th','male','female');
	$title_div = array($msg['~17'],$msg['18~29'],$msg['30~44'],$msg['45~64'],$msg['65~'],$msg['male'], $msg['female']);	
	
	for($i=0; $i<($rs->num_rows); $i++) {
		$assoc = mysqli_fetch_assoc($rs);
		$datetimest = mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']);
		$datetime = date($dateformat, $datetimest);

		for($j=0; $j<sizeof($tag_div); $j++) {
			$arr_result[$datetime][$tag_div[$j]] = $assoc[$tag_div[$j]];
			$arr_result['total'][$tag_div[$j]] += $assoc[$tag_div[$j]];
		}
	}
	
	
	for($i=0; $i<sizeof($tag_div); $i++) {
		$d_str = '';
		for($j=0; $j<$duration; $j++) { 
			$datetimest = $from_ts + $interval*$j;
			$datetime = date($dateformat, $datetimest);
			if($i==0) {
				if($j>0){
					$l_str .=',';
				}
				$l_str .= $datetimest;
//				$l_str .= '"'.$datetime.'"';
			}
			if(!$arr_result[$datetime][$tag_div[$i]]) {
				$arr_result[$datetime][$tag_div[$i]] = 0;
			}
			if($datetimest > $thistime) {
				$arr_result[$datetime][$tag_div[$i]] = 'null';
			}
			if($j>0) {
				$d_str .= ',';
			}
			$d_str .=  $arr_result[$datetime][$tag_div[$i]];
		}
		if($i>0) {
			$s_str .= ',';
		}
		if(!$arr_result['total'][$tag_div[$i]]) {
			$arr_result['total'][$tag_div[$i]] = 0;
		}
		$s_str .= '{"name":"'.$tag_div[$i].'", "data":['.$d_str.'], "total":'.$arr_result['total'][$tag_div[$i]].'}';
	}
//	$s_str = '{"name":"'.$bubble_name.'", "data":[['.$x, $y, $size.'],],"';
	
	
//	print_arr($d_str);
//	print_arr($arr_result);
	$json_str= '{
		"data":['.$s_str.'],
		"label":['.$l_str.'],
		"title":{
			"gender_bar":"'.$gender_title.'",
			"age_bar":"'.$age_title.'"
		}
	}';

}

else if ($_GET['fr'] == 'summary') {
	$msg = q_language("summary.php");
	if($_GET['page'] == 'footfall') {
		$from_ts = strtotime($_GET['time_ref']) - 3600*24*27;
		$to_ts = strtotime($_GET['time_ref'])  + 3600*24 -1;
	
		$dateformat = "Y-m-d";
		$duration = 7;
		$interval =  3600*24;
	
		$arr_footfall_label = array($msg['3weeksbefore'], $msg['2weeksbefore'], $msg['lastweek'], $msg['thisweek']);
		
		$sq  = "select device_info, square_code, store_code, year, month, day, hour, min, wday, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
		$sq .= "where timestamp >=".$from_ts." and timestamp <".$to_ts." and counter_label='entrance' ";

		if($_GET['st'] ) {
			$sq .= "and store_code ='".$_GET['st']."' ";
		}
		if($_GET['sq'] ) {
			$sq .= "and square_code ='".$_GET['sq']."' ";
		}	

		$sq .= "group by year, month, day ";	
		$sq .= $g_sq." order by timestamp asc ";
		
	//	print $sq;
	//	print Query2Table($connect0, $sq);
		$rs = mysqli_query($connect0, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$assoc = mysqli_fetch_assoc($rs);
			$datetime = date($dateformat, mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']));
			$arr_result[$datetime] = $assoc['sum'];
		}
		for($i=0; $i<4; $i++){
			$d_str = '';
			for($j=0; $j<7; $j++) {
				$datetimest = $from_ts + $interval*($i*7+$j);
				$datetime = date($dateformat, $datetimest);

				if(!$arr_result[$datetime]) {
					$arr_result[$datetime] = 0;
				}
				if($i==3) {
					if($j>0) {
						$l_str .= ',';
					}
					$total_this_week += $arr_result[$datetime];
					$l_str .= '"'.$msg[strtolower(date("D",$datetimest))].' '.date("m-d",$datetimest).'"';
//					$l_str .= '["'.$msg[strtolower(date("D",$datetimest))].'", "'.date("m-d",$datetimest).'"]';
				}
				if($i==2) {
					$total_last_week += $arr_result[$datetime];
				}
				if($i==1) {
					$total_week_before += $arr_result[$datetime];
				}
				if($arr_result[$datetime] > $max_visit['count']) {
					$max_visit['date'] = $datetime;
					$max_visit['count'] = $arr_result[$datetime];
				}
				
				if($datetimest > $thistime) {
					$arr_result[$datetime] = 'NaN'; //NaN
				}
				if($j>0) {
					$d_str .=',';
				}
				$d_str .= $arr_result[$datetime];
			}
			if($i>0) {
				$s_str .= ',';
			}
			$s_str .= '{"name":"'.$arr_footfall_label[$i].'", "data":['.$d_str.']}';
		}
		$chart_title = '"'.$msg['footfall'].'"';
		
		$line1 = $msg['comparingto'].$msg['adaybefore'].': '.number_format($arr_result[date($dateformat,$to_ts-3600*24*2)],0);
		$val1 = number_format($arr_result[date($dateformat,$to_ts-3600*24*2)],0);
		$line2 = $msg['comparingto'].$msg['lastweek'].'('.$msg[strtolower(date("D", $to_ts-3600*24*8))].'): '.number_format($arr_result[date($dateformat,$to_ts-3600*24*8)],0);
		$val2 = number_format($arr_result[date($dateformat,$to_ts-3600*24*8)],0);
		$card[0] = '"'.$msg['yesterday'].'", "'.date($dateformat,$to_ts-3600*24).'", "'.number_format($arr_result[date($dateformat,$to_ts-3600*24)],0).'", "'.$line1.'", "'.$val1.'", "'.$line2.'", "'.$val2.'"';

		$line1 = $msg['comparingto'].$msg['adaybefore'].': '.number_format($arr_result[date($dateformat,$to_ts-3600*24)],0);
		$val1 = number_format($arr_result[date($dateformat,$to_ts-3600*24)],0);
		$line2 = $msg['comparingto'].$msg['lastweek'].'('.$msg[strtolower(date("D", $to_ts))].'): '.number_format($arr_result[date($dateformat,$to_ts-3600*24*7)],0);
		$val2 = number_format($arr_result[date($dateformat,$to_ts-3600*24*7)],0);
		$card[1] = '"'.$msg['today'].'", "'.date($dateformat,$to_ts).'", "'.number_format($arr_result[date($dateformat,$to_ts)],0).'", "'.$line1.'", "'.$val1.'", "'.$line2.'", "'.$val2.'"';
		
		$line1 = $msg['comparingto'].$msg['lastweek'].': '.number_format($total_last_week,0);
		$val1 = number_format($total_last_week,0);
		$line2 = $msg['comparingto'].$msg['2weeksbefore'].': '.number_format($total_week_before,0);
		$val2 = number_format($total_week_before,0);
		$card[2] = '"'.$msg['recent7days'].'", "'.date($dateformat,$to_ts-3600*24*7+1).' ~ '.date($dateformat,$to_ts).'", "'.number_format($total_this_week,0).'", "'.$line1.'", "'.$val1.'", "'.$line2.'", "'.$val2.'"';
		
		$line1 = $msg['comparingto'].$msg['today'].': ';//.number_format($max_visit['pre'],0);
		$line2 = $msg['comparingto'].$msg['yesterday'].': ';//.number_format($max_visit['post'],0);
		$card[3] = '"'.$msg['maxvisitday'].'", "'.$max_visit['date'].'", "'.number_format($max_visit['count'],0).'", "'.$line1.'", "'.$val1.'", "'.$line2.'", "'.$val2.'"';
		
		$json_str= '{
			"data":['.$s_str.'],
			"label":['.$l_str.'],
			"title":{
				"chart_title":'.$chart_title.'
			},
			"card":[
				['.$card[0].'],['.$card[1].'],['.$card[2].'],['.$card[3].']
			]
		}';
	}
	
	else if($_GET['page'] == 'ageGender') {
		$s1 = microtime(true);
		$sq = "select body from ".$DB_CUSTOM['web_config']." where page='age_gender' name ='age_group'";
		$age_range = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
		// $age_range = json_decode($configVars['software.webpage.age_group']);
		if(!$age_range) {
			$age_range = [0,18,30,45,65];
		}	
		$gender_div = array($msg['male'], $msg['female']);
		
		$from_ts = strtotime($_GET['time_ref']) - 3600*24*6;
		$to_ts = $from_ts + 3600*24*7 -1;
		$dateformat = "Y-m-d";
//		print date("Y-m-d H:i:s", $from_ts).'~'.date("Y-m-d H:i:s", $to_ts);
		
		$tag_div = array('age_1st','age_2nd','age_3rd','age_4th','age_5th','male','female');
		$title_div = array($msg['~17'],$msg['18~29'],$msg['30~44'],$msg['45~64'],$msg['65~'],$msg['male'], $msg['female']);	

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
		
		$gender_query_string = "sum(substring_index(substring_index(gender, ',', 1),',',-1)), ',', sum(substring_index(substring_index(gender, ',', 2),',',-1))" ;
		$gender_query_string = "concat(".$gender_query_string.") as gender";
		
		$sq =  "select square_code, store_code, camera_code, year, month, day, hour, min, ".$age_query_string.", ".$gender_query_string." from ".$DB_CUSTOM['age_gender']." ";
		$sq .= "where timestamp >=".$from_ts." and timestamp <".$to_ts." ";
		
		if($_GET['st'] ) {
			$sq .= "and store_code ='".$_GET['st']."' ";
		}
		if($_GET['sq'] ) {
			$sq .= "and square_code ='".$_GET['sq']."' ";
		}			
		
		$rs = mysqli_query($connect0, $sq);
		$assoc = mysqli_fetch_assoc($rs);
		
//		print_arr($assoc);
		$arr_result['total']['age'] = age_group(json_decode('['.$assoc['age'].']'), $age_range);
		$arr_result['total']['gender'] = json_decode('['.$assoc['gender'].']');		

		for($i=0; $i <sizeof($age_range); $i++) {
			if(!$arr_result['total']['age'][$i]) {
				$arr_result['total']['age'][$i] = 0;
			}
			if($i>0) {
				$s_str .= ',';
			}
			$s_str .= '{"name":"'.$msg['agegroup'.$i].'", "total":'.$arr_result['total']['age'][$i].'}';
		}
		for($i=0; $i <2; $i++) {
			if(!$arr_result['total']['gender'][$i]) {
				$arr_result['total']['gender'][$i] = 0;
			}
			$s_str .= ',';
			$s_str .= '{"name":"'.$gender_div[$i].'", "total":'.$arr_result['total']['gender'][$i].'}';
		}
		
		$chart_title = '["'.$msg['agegroup'].'", "'.$msg['gender'].'"]';
		
		if(!$chart_title) {
			$chart_title = '""';
		}
		$json_str= '{
			"data":['.$s_str.'],
			"title":{
				"chart_title":'.$chart_title.'
			}
		}';
	}
}

else if ($_GET['fr'] == 'standard') {
//	print_arr($_GET);
	$msg = q_language("standard.php");
	if($_GET['page'] == 'footfall_rising_rank') {
		$to_ts = strtotime($_GET['time_ref']) + 3600*24*(6-date("w",strtotime($_GET['time_ref'])))+ 3600*24 -1;
		$from_ts = $to_ts - 3600*24*21 +1;

		$dateformat = "Y-m-d";
		$duration = 21;
		$interval = 3600*24;
	
		$arr_footfall_label = array($msg['2weeksbefore'], $msg['lastweek'], $msg['thisweek']);
		
		$sq  = "select device_info, square_code, store_code, year, month, day, hour, min, wday, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
		$sq .= "where timestamp >=".$from_ts." and timestamp <".$to_ts." and counter_label='entrance' ";

		if($_GET['st'] ) {
			$sq .= "and store_code ='".$_GET['st']."' ";
		}
		if($_GET['sq'] ) {
			$sq .= "and square_code ='".$_GET['sq']."' ";
		}	

		$sq .= "group by year, month, day ";	
		$sq .= " order by timestamp asc ";
		
	//	print $sq;
	//	print Query2Table($connect0, $sq);
		$rs = mysqli_query($connect0, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$assoc = mysqli_fetch_assoc($rs);
			$datetime = date($dateformat, mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']));
			$arr_result[$datetime] = $assoc['sum'];
		}
		
		for($i=0; $i<3; $i++) {
			$d_str = '';
			for($j=0; $j<7; $j++) {
				$datetimest = $from_ts + ($i*7 + $j)*$interval;
				$datetime = date($dateformat, $datetimest);
				if($i==2) {
					if($j>0) {
						$l_str .= ',';
					}
//					$l_str .= '["'.$msg[strtolower(date("D", $datetimest))].'", "'.date("m-d", $datetimest).'"]';
					$l_str .= '"'.$msg[strtolower(date("D", $datetimest))].'"';
				}
				if(!$arr_result[$datetime]) {
					$arr_result[$datetime]= 0;
				}
				if($datetimest > $thistime) {
					$arr_result[$datetime]= 'null';
				}				
				if($j>0) {
					$d_str .= ',';
				}
				$d_str .= $arr_result[$datetime];
			}
			if($i>0) {
				$s_str .= ',';
			}
			$s_str .= '{"name":"'.$arr_footfall_label[$i].'", "data":['.$d_str.']}'; 
		}
		
		$chart_title = $msg['7dayscomparison'];
//		print_arr($arr_result);
	}
	else if($_GET['page'] == 'footfall_hourly') {
		$from_ts = strtotime($_GET['time_ref']) - 3600*24*7;
		$to_ts = strtotime($_GET['time_ref'])  + 3600*24*7 -1;
		$dateformat = "Y-m-d H:00";
		$interval = 3600;
	
		$arr_footfall_label = array($msg['lastweek'], $msg['thisweek']);
		
		$sq  = "select device_info, square_code, store_code, year, month, day, hour, min, wday, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
		$sq .= "where ((timestamp >=".$from_ts." and timestamp <".$to_ts.") or (timestamp >=".($from_ts+3600*24*7)." and timestamp <".($to_ts+3600*24*7)."))  and counter_label='entrance' ";

		if($_GET['st'] ) {
			$sq .= "and store_code ='".$_GET['st']."' ";
		}
		if($_GET['sq'] ) {
			$sq .= "and square_code ='".$_GET['sq']."' ";
		}	

		$sq .= "group by year, month, day, hour ";	
		$sq .=" order by timestamp asc ";
		
	//	print $sq;
	//	print Query2Table($connect0, $sq);
		$rs = mysqli_query($connect0, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$assoc = mysqli_fetch_assoc($rs);
			$datetime = date($dateformat, mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']));
			$arr_result[$datetime] = $assoc['sum'];
		}
		
		for($i=0; $i<2; $i++) {
			$d_str = '';
			for($j=0; $j<24; $j++) {
				$datetimest = $from_ts + ($i*7*24 + $j)*$interval;
				$datetime = date($dateformat, $datetimest);
				if($i==0) {
					if($j>0) {
						$l_str .= ',';
					}
					$l_str .= $datetimest;
//					$l_str .= '"'.date("H:00", $datetimest).'"';
				}
				if(!$arr_result[$datetime]) {
					$arr_result[$datetime]= 0;
				}
				if($datetimest > $thistime) {
					$arr_result[$datetime]= 'null';
				}
				if($j>0) {
					$d_str .= ',';
				}
				$d_str .= $arr_result[$datetime]; 
			}
			if($i>0) {
				$s_str .= ',';
			}
			$s_str .= '{"name":"'.$arr_footfall_label[$i].'('.date("Y-m-d", $from_ts+ $i*7*3600*24).')", "data":['.$d_str.']}'; 
		}
		
		$chart_title = $msg['7dayscomparison'];
//		print_arr($arr_result);
		
		
	}
	else if($_GET['page'] == 'footfall_device') {
		$from_ts = strtotime($_GET['time_ref']);
		$to_ts = strtotime($_GET['time_ref'])  + 3600*24 -1;
		$dateformat = "Y-m-d H:00";
		$interval = 3600;
		$duration = ceil(($to_ts-$from_ts)/$interval);
//		print date("Y-m-d H:i:s", $from_ts).'~'.date("Y-m-d H:i:s", $to_ts);
		$sq  = "select device_info, square_code, store_code, year, month, day, hour, min, wday, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
		$sq .= "where ((timestamp >=".$from_ts." and timestamp <".$to_ts.") or (timestamp >=".($from_ts+3600*24*7)." and timestamp <".($to_ts+3600*24*7)."))  and counter_label='entrance' ";
		if($_GET['st'] ) {
			$sq .= "and store_code ='".$_GET['st']."' ";
		}
		if($_GET['sq'] ) {
			$sq .= "and square_code ='".$_GET['sq']."' ";
		}	

		$sq .= "group by year, month, day, hour, device_info ";	
		$sq .=" order by timestamp asc ";
//		print $sq;
//		print Query2Table($connect0, $sq);
		$arr_device =  array();
		$rs = mysqli_query($connect0, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$assoc = mysqli_fetch_assoc($rs);
			$datetimest =  mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']);
			$datetime = date($dateformat,$datetimest);
			$arr_result[$datetime][$assoc['device_info']] = $assoc['sum'];
			if(!in_array($assoc['device_info'], $arr_device)) {
				array_push($arr_device, $assoc['device_info']);
			}
		}
//		print_arr($arr_device);
		for($i=0; $i<sizeof($arr_device); $i++) {
			$d_str = '';
			for($j=0; $j<$duration; $j++) {	
				$datetimest = $from_ts + $j*$interval;
				$datetime = date($dateformat, $datetimest);
				if($i==0) {
					if($j>0) {
						$l_str .= ',';
					}
//					$l_str .= '"'.date("H:00", $datetimest).'"';
					$l_str .= $datetimest;
				}				
				if(!$arr_result[$datetime][$arr_device[$i]]) {
					$arr_result[$datetime][$arr_device[$i]] =0;
				}
				if($datetimest > $thistime) {
					$arr_result[$datetime][$arr_device[$i]] = 'null';
				}
				if($j>0) {
					$d_str .= ',';
				}
				$d_str .= $arr_result[$datetime][$arr_device[$i]];
			}
			if($i>0) {
				$s_str .= ',';
			}
			$mac = array_pop(explode("=",explode("&", $arr_device[$i])[0]));
			$sq = "select name from ".$DB_CUSTOM['camera']." where mac = '".$mac."' ";
			$dev_name = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
			$dev_name .= '['.$mac.']';
			$s_str .= '{"name":"'.$dev_name.'", "data":['.$d_str.']}';
		}
		
//		print_arr($arr_result);	
	}
	$json_str = '{
		"data":['.$s_str.'],
		"label":['.$l_str.'],
		"title":{
			"chart_title":"'.$chart_title.'"
		}
	}';
	
}

else if ($_GET['fr'] == 'premium') {
//	print_arr($_GET);
	$msg = q_language("standard.php");

	$from_ts = strtotime($_GET['time_ref']) -3600*24*83;
	$to_ts = strtotime($_GET['time_ref']) + 3600*24 -1;
	
	$dateformat = "Y-m-d";
	$interval = 3600*24;
	$duration = ceil(($to_ts - $from_ts)/$interval);

	$arr_footfall_label = array($msg['2weeksbefore'], $msg['lastweek'], $msg['thisweek']);
	
//		print date("Y-m-d H:i:s D", $from_ts).'~'.date("Y-m-d H:i:s D", $to_ts);

	if($_GET['page'] == 'footfall') {
		
	}
	else if($_GET['page'] == 'footfall_square') {
		$div = 'square_code';
		$table_tag = $DB_CUSTOM['square'];
	}
	else if($_GET['page'] == 'footfall_store') {
		$div = 'store_code';
		$table_tag = $DB_CUSTOM['store'];
	}

	else if($_GET['page'] == 'footfall_device') {
		$div = 'camera_code';
		$table_tag = $DB_CUSTOM['camera'];
	}

	$sq  = "select device_info, square_code, store_code, camera_code, year, month, day, hour, min, wday, sum(counter_val) as sum from ".$DB_CUSTOM['count']." ";
	$sq .= "where timestamp >=".$from_ts." and timestamp <".$to_ts." and counter_label='entrance' ";

	if($_GET['st'] ) {
		$sq .= "and store_code ='".$_GET['st']."' ";
	}
	if($_GET['sq'] ) {
		$sq .= "and square_code ='".$_GET['sq']."' ";
	}	

	$sq .= "group by year, month, day ";
	if($div) {
		$sq.= ",".$div." ";	
	}
	$sq .= "order by timestamp asc ";
	
//		print $sq;
//		print Query2Table($connect0, $sq);
	$arr_div =  array();
	$rs = mysqli_query($connect0, $sq);
	for($i=0; $i<($rs->num_rows); $i++) {
		$assoc = mysqli_fetch_assoc($rs);
		$datetime = date($dateformat, mktime($assoc['hour'],$assoc['min'],0,$assoc['month'], $assoc['day'], $assoc['year']));
		if(!in_array($assoc[$div], $arr_div)) {
			array_push($arr_div, $assoc[$div]);
		}
		$arr_result[$datetime][$assoc[$div]] = $assoc['sum'];
	}
	$arr_div_tag = array();
	for($i=0; $i<sizeof($arr_div); $i++) {
		$sq = "select name from ".$table_tag." where code = '".$arr_div[$i]."' ";
		$rs = mysqli_query($connect0, $sq);
		$arr_div_tag[$i] = mysqli_fetch_row($rs)[0];
	}
	if(sizeof($arr_div_tag) == 1) {
		$arr_div_tag[0] = $msg['total'];
	}
	
	for($i=0; $i<sizeof($arr_div); $i++) {
		$d_str = '';
		for($j=0; $j<$duration; $j++) {
			$datetimest = $from_ts + $j*$interval;
			$datetime = date($dateformat, $datetimest);
			if($i == 0) {
				if($j>0) {
					$l_str .= ',';
				}
				$l_str .= $datetimest;
			}
			if(!$arr_result[$datetime][$arr_div[$i]]) {
				$arr_result[$datetime][$arr_div[$i]] = 0;
			}
			if($datetimest>$thistime) {
				$arr_result[$datetime][$arr_div[$i]] = '"NaN';
			}
			if($j>0) {
				$d_str .= ',';
			}
			$d_str .= $arr_result[$datetime][$arr_div[$i]];
		
		}
		if($i>0) {
			$s_str .= ',';
		}
		$s_str .= '{"name":"'.$arr_div_tag[$i].'", "data":['.$d_str.']}';
	}
//		print_arr($arr_result);
	

	
	
	$json_str = '{
		"data":['.$s_str.'],
		"label":['.$l_str.'],
		"title":{
			"chart_title":"'.$chart_title.'"
		}
	}';
	
}
	
else if ($_GET['fr'] == 'sensors') {
	$msg = q_language("sensors.php");
	$arr_result = array();
	$arr_rs = array();
	
	
	if($_GET['act'] == 'info') {
		$arr_rs['info'] = array();
		$tag_square = '&#9109;';
		$tag_check = '&#10004;';
		$tag_square = '<i class="align-middle fas fa-fw fa-1x fa-expand"></i>';
		$tag_check  = '<i class="align-middle fas fa-fw fa-1x fa-check"></i>';

		$sq  = "select A.code, A.store_code, A.square_code, A.usn, A.product_id, A.name, A.comment, 
			if(A.enable_countingline='y', '".$tag_check."','".$tag_square."') as enable_countingline, 
			if(A.enable_heatmap='y', '".$tag_check."', '".$tag_square."') as enable_heatmap, 
			if(A.enable_snapshot='y', '".$tag_check."', '".$tag_square."') as enable_snapshot, 
			if(A.enable_face_det='y', '".$tag_check."', '".$tag_square."') as enable_face_det, 
			if(A.enable_macsniff='y', '".$tag_check."', '".$tag_square."') as enable_macsniff, 
			A.flag, A.device_info, B.pk as fpk , 
			concat(if(B.lic_pro='y','PRO',''),' ', if(B.lic_surv='y','SURV',''),' ', if(B.lic_count='y','COUNT','')) as license, 
			if(B.face_det='y', '".$tag_check."','".$tag_square."') as face_det, 
			if(B.heatmap='y', '".$tag_check."','".$tag_square."') as heatmap, 
			if(B.countrpt='y', '".$tag_check."','".$tag_square."') as countrpt, 
			if(B.macsniff='y', '".$tag_check."','".$tag_square."') as macsniff, 
			B.initial_access, B.last_access, B.db_name, B.param, C.name as store_name from ".$DB_CUSTOM['camera']." as A inner join ".$DB_COMMON['param']." as B inner join ".$DB_CUSTOM['store']." as C on A.device_info = B.device_info and A.store_code = C.code where A.pk=".$_GET['pk'];
			// print $sq;

		$rs = mysqli_query($connect0, $sq);
		$assoc = mysqli_fetch_assoc($rs);
		$ex = explode("&", $assoc['device_info']);
		foreach ($ex as $A=>$B) {
			list($_key, $_val) = explode("=", $B);
			$assoc[$_key] = $_val;
		}
		
		$zone = array();
		$line = explode("\n",$assoc['param']);
		for($i=0; $i<count($line)-1; $i++) {
			if(strpos(" ".$line[$i], "VCA.Ch0.Zn")) {
				list($key,$val) = explode("=", $line[$i]);
				$ex_key = explode(".",$key);
				$p = substr($ex_key[2],2,strlen($ex_key[2]));
				$zone[$p][$ex_key[3]] = trim($val);
			}
		}
		// print_r($zone);
		unset($assoc['param']);

		$sq = "select body, regdate from ".$DB_COMMON['snapshot']." where device_info='".$assoc['device_info']."' order by regdate desc limit 1";
		// $x =  mysqli_fetch_row(mysqli_query($connect0, $sq));
		// $assoc['snapshot'] =$x[0];
		// $assoc['regdate'] = $x[1];

		list($assoc['snapshot'], $assoc['regdate']) = mysqli_fetch_row(mysqli_query($connect0, $sq));


		$assoc['functions'] = [$assoc['countrpt'], $assoc['heatmap'], $assoc['face_det'], $assoc['macsniff']];
		$assoc['features']  = [$assoc['enable_countingline'], $assoc['enable_heatmap'], $assoc['enable_face_det'], $assoc['enable_macsniff']];

		$arr_rs['info'] = [
			"zone" => $zone,
			"device_info" => $assoc['device_info'], 
			"code" => $assoc['code'],
			"regdate" => $assoc['regdate'],
			"name" => $assoc['name'],
			// "square_name" => $assoc['square_name'],
			"store_name" => $assoc['store_name'],
			"snapshot" => $assoc['snapshot'],
			"mac" => $assoc['mac'],
			"brand" => $assoc['brand'],
			"model" => $assoc['model'],
			"usn" => $assoc['mac'],
			"product_id" => $assoc['product_id'],
			"initial_access" => $assoc['initial_access'],
			"last_access" => $assoc['last_access'],
			"license" => $assoc['license'],
			"functions" => $assoc['functions'],
			"features" => $assoc['features'],
			"comment" => $assoc['comment']
		];
		
		// print_arr($arr_rs);
		$json_str = json_encode($arr_rs, True);
	}
	else { // list
		$arr_rs['list'] = array();
		$sq = "select A.pk, A.code, A.store_code, A.square_code, A.device_info, A.usn, A.product_id, A.name, A.comment, B.name as store_name, C.name as square_name from ".$DB_CUSTOM['camera']." as A inner join ".$DB_CUSTOM['store']." as B inner join ".$DB_CUSTOM['square']." as C on A.store_code = B.code and A.square_code = C.code ";
		if($_GET['st'] ) {
			$sq .= " and  A.store_code='".$_GET['st']."'";
		}	
		else if($_GET['sq'] ) {
			$sq .= " and A.square_code='".$_GET['sq']."'";
		}
		
		$rs = mysqli_query($connect0, $sq);
		while($assoc=mysqli_fetch_assoc($rs)){
			$sq = "select regdate, body from ".$DB_COMMON['snapshot']." where device_info='".$assoc['device_info']."' order by regdate desc limit 1";
			$as_snapshot = mysqli_fetch_assoc(mysqli_query($connect0, $sq));
			$assoc['regdate'] = $as_snapshot['regdate'];
			$assoc['snapshot'] = $as_snapshot['body'];
			array_push($arr_rs['list'], array(
				"device_info"=> $assoc['device_info'],
				"regdate" => $assoc['regdate'],
				"name" => $assoc['name'],
				"square_name" => $assoc['square_name'],
				"store_name" => $assoc['store_name'],
				"snapshot" => $assoc['snapshot'],
				"pk" => $assoc['pk']
			));
		}

		$arr_rs['lang'] = array(
			"store name" => $msg['storename'],
			"square name" => $msg['squarename'],
			"device info" => $msg['deviceinfo'],
			// "memo" => $msg['memo'],
			"detail" => $msg['detail']
		);
		// print_r($arr_rs);
		$json_str = json_encode($arr_rs, True);
	}	
}

else if ($_GET['fr'] == 'sitemap') {
	$arr_square =  array();
	$arr_store = array();
	$arr_camera = array();
	$arr_list = array();
	$cam_num = array();
	$sq = "select * from ".$DB_CUSTOM['square']." ";
	$arr_square = Query2Array($connect0,$sq);

	$tbody = '';
	for($i=0; $i<sizeof($arr_square); $i++) {
		$cam_num[$i] = 0;
		$sq = "select * from ".$DB_CUSTOM['store']." where square_code='".$arr_square[$i]['code']."'";
		$arr_store = Query2Array($connect0,$sq);
		if(!sizeof($arr_store)) {
			$arr_store[0]['name'] ='';
		}
		for($j=0; $j<sizeof($arr_store); $j++) {
			$sq = "select * from ".$DB_CUSTOM['camera']." where store_code = '".$arr_store[$j]['code']."'";
			$num = mysqli_query($connect0, $sq)->num_rows;
			if(!$num) {
				$num = 1;
			}
			$cam_num[$i] += $num;
		}
		for($j=0; $j<sizeof($arr_store); $j++) {
			$sq = "select * from ".$DB_CUSTOM['camera']." where store_code = '".$arr_store[$j]['code']."'";
			$arr_camera = Query2Array($connect0, $sq);
			if (!$arr_camera) {
				continue;
			}
			// print $sq;
			// print_arr($arr_camera);
			if(!sizeof($arr_camera)) {
				$arr_camera[0]['name'] ='';
			}
			for($k=0; $k<sizeof($arr_camera); $k++) {
				$sq = "select body from ".$DB_COMMON['snapshot']." where device_info = '".$arr_camera[$k]['device_info']."' order by regdate desc limit 1";
				// print $sq;
				$row = mysqli_fetch_row(mysqli_query($connect0, $sq));
				if($row[0]) {
					$img_b64 = '<img src="'.$row[0].'" height="50px" width="89px" data-toggle="modal" data-target="#modalSnapshot" OnClick="viewSnapshot(this,\''.$arr_camera[$k]['device_info'].'\')" onMouseOver="this.style.cursor=\'pointer\'">';
				}
				else {
					$img_b64 = '';
				}
				
				$sq = "select usn, lic_pro, lic_surv, lic_count, face_det, heatmap, countrpt, macsniff  from ".$DB_COMMON['param']." where device_info = '".$arr_camera[$k]['device_info']."' ";
				$assoc = mysqli_fetch_assoc(mysqli_query($connect0, $sq));
				
				
				$tbody .= '<tr>';
				if($j==0 and $k==0) {
					$tbody .= '<td rowspan="'.($cam_num[$i]).'">'.$arr_square[$i]['name'].'</td>';
				}
				if($k==0) {
					$tbody .= '<td rowspan="'.(sizeof($arr_camera)).'">'.$arr_store[$j]['name'].'</td>';
				}

				$tbody .= '<td style="padding-top:1px; padding-bottom:1px;" width="100px">'.$img_b64.'</td>';
				$tbody .= '<td>'.$arr_camera[$k]['name'].'</td>';
				$arr_camera[$k]['enable_countingline'] = ($arr_camera[$k]['enable_countingline'] == 'y' ) ? '<i class="align-middle fas fa-fw fa-1x fa-check"></i>' :'';
				$arr_camera[$k]['enable_heatmap'] = $arr_camera[$k]['enable_heatmap'] == 'y' ? '<i class="align-middle fas fa-fw fa-1x fa-check"></i>' :'';
				$arr_camera[$k]['enable_face_det'] = $arr_camera[$k]['enable_face_det'] == 'y' ? '<i class="align-middle fas fa-fw fa-1x fa-check"></i>' :'';
				$arr_camera[$k]['enable_macsniff'] = $arr_camera[$k]['enable_macsniff'] == 'y' ? '<i class="align-middle fas fa-fw fa-1x fa-check"></i>' :'';
				$tbody .= '<td align="center">'.$arr_camera[$k]['enable_countingline'].'</td>';
				$tbody .= '<td align="center">'.$arr_camera[$k]['enable_heatmap'].'</td>';
				$tbody .= '<td align="center">'.$arr_camera[$k]['enable_face_det'].'</td>';
				$tbody .= '<td align="center">'.$arr_camera[$k]['enable_macsniff'].'</td>';
				$tbody .= '</tr>';
			}
		}
	}

	$json_str = '{"tbody": "'.addslashes($tbody).'" }';
	$json_str = $tbody;
	
}
####################################################################################################################################################################
####################################################################################################################################################################
###################################################                             #####################################################################################
###################################################         ADMIN PAGE          ####################################################################################
###################################################                             ####################################################################################
####################################################################################################################################################################
####################################################################################################################################################################
// else if ($_GET['fr'] == 'square') {
// 	if($_GET['mode'] == 'modify') {
// 		if(!$_POST['pk']) { 
// 			$regdate = date("Y-m-d H:i:s"); 
// 			$sq = "insert into ".$DB_CUSTOM['square']."(regdate) values('".$regdate."')";
// 			$rs = mysqli_query($connect0, $sq);
// 			$sq = "select pk from ".$DB_CUSTOM['square']." where regdate = '".$regdate."'";
// 			$rs = mysqli_query($connect0, $sq);
// 			$_POST['pk']  = mysqli_fetch_row($rs)[0];
// 		}
// 		$sq = "update ".$DB_CUSTOM['square']." set code = '".trim($_POST['code'])."',  name = '".addslashes(trim($_POST['name']))."', comment = '".addslashes(trim($_POST['comment']))."', addr_state = '".addslashes(trim($_POST['addr_state']))."', addr_city = '".addslashes(trim($_POST['addr_city']))."', addr_b = '".addslashes(trim($_POST['addr_b']))."' ";
// 		$sq .="where pk=".$_POST['pk'];
// 		// print $sq;
// 		$rs = mysqli_query($connect0, $sq);
// 		$json_str = $rs ? "update OK {pk=".$_POST['pk']."}" : "update Fail";
// 	}
// 	else if($_GET['mode'] == 'delete') {
// 		$sq = "select ID from ".$DB_COMMON['account']." where ID = '".$_SESSION['logID']."' and (role='admin' or role='root') and passwd = '".$_POST['passwd']."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs->num_rows) {
// 			$sq = "select code from ".$DB_CUSTOM['store']." where pk=".$_GET['pk'];
// 			$rs = mysqli_query($connect0, $sq);
// 			$sq_code = mysqli_fetch_row($rs)[0];
// 			$sq = "select code from ".$DB_CUSTOM['store']." where store_code = '".$sq_code."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			if($rs->num_rows) {
// 				print "This square has Store(s)";
// 			}
// 			else {
// 				$sq = "delete from ".$DB_CUSTOM['square']." where pk=".$_GET['pk'];
// 				print $sq;
// 				$rs = mysqli_query($connect0, $sq);
// 				if($rs) {
// 					print "square ".$sq_code." delete OK";
// 				}
// 			}
// 		}
// 		else {
// 			print "No right to delete or password not match!";
// 		}		
// 	}	
	
// 	else if($_GET['mode'] == 'view') {
// 		$sq = "select pk, code, name, addr_state, addr_city, addr_b, comment from ".$DB_CUSTOM['square']." where pk = ".$_GET['pk'];
// 		// print $sq;
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs) {
// 			print '{"info":"Error in query"}';
// 			exit;
// 		}
// 		$arr_result = mysqli_fetch_assoc($rs);
// 		if(!$arr_result['code']) {
// 			$arr_result['code'] = 'SQ'.time().rand(0,9).rand(0,9).rand(0,9);
// 		}
// 		// print_arr($arr_result);
// 		$json_str = json_encode($arr_result);
// 	}
// }

// else if ($_GET['fr'] == 'store') {
// 	if($_GET['mode'] =='modify') {
// 		// print_r($_POST);
// 		if(!$_POST['pk']) { 
// 			$regdate = date("Y-m-d H:i:s"); 
// 			$sq = "insert into ".$DB_CUSTOM['store']."(regdate) values('".$regdate."')";
// 			$rs = mysqli_query($connect0, $sq);
// 			$sq = "select pk from ".$DB_CUSTOM['store']." where regdate = '".$regdate."'";
// 			$rs = mysqli_query($connect0, $sq);
// 			$_POST['pk'] = mysqli_fetch_row($rs)[0];
// 		}
		
// 		if(!is_numeric($_POST['area'])) {
// 			$_POST['area'] = 0;
// 		}

// 		$sq = "update ".$DB_CUSTOM['store']." set code = '".trim($_POST['code'])."', name = '".addslashes(trim($_POST['name']))."', comment = '".addslashes(trim($_POST['comment']))."', addr_state = '".addslashes(trim($_POST['addr_state']))."', addr_city = '".addslashes(trim($_POST['addr_city']))."', addr_b = '".addslashes(trim($_POST['addr_b']))."', square_code = '".trim($_POST['square_code'])."', phone = '".addslashes(trim($_POST['phone']))."', fax = '".addslashes(trim($_POST['fax']))."', contact_person = '".addslashes(trim($_POST['contact_person']))."', contact_tel = '".addslashes(trim($_POST['contact_tel']))."', open_hour = ".trim($_POST['open_hour']).", close_hour = ".trim($_POST['close_hour']).", sniffing_mac = '".trim($_POST['sniffing_mac'])."', area = ".$_POST['area']." where pk=".$_POST['pk'];
// 		// print $sq; 
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs) {
// 			print "update store_code OK";
// 		}
// 		else {
// 			print "update Fail";
// 		}	
// 		$sq = "update ".$DB_CUSTOM['camera']." set square_code = '".trim($_POST['square_code'])."' where store_code='".$_POST['code']."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs) {
// 			print "<br>update camera_code OK";
// 		}
// 		else {
// 			print "<br>update Fail";
// 		}	
// 		sleep(2);
// 	}
// 	else if($_GET['mode'] == 'delete') {
// 		print_r($_GET);
// 		$sq = "select ID from ".$DB_COMMON['account']." where ID = '".$_SESSION['logID']."' and (role='admin' or role='root') and passwd = '".$_POST['passwd']."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs->num_rows) {
// 			$sq = "select code from ".$DB_CUSTOM['store']." where pk=".$_GET['pk'];
// 			$st_code = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
// 			$sq = "select code from ".$DB_CUSTOM['camera']." where store_code = '".$st_code."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			if($rs->num_rows) {
// 				print "This store has Camera(s)";
// 			}
// 			else {
// 				$sq = "delete from ".$DB_CUSTOM['store']." where pk=".$_GET['pk'];
// 				print $sq;
// 				$rs = mysqli_query($connect0, $sq);
// 				if($rs) {
// 					print "store ".$st_code." delete OK";
// 				}
// 			}
// 		}
// 		else {
// 			print "No right to delete or password not match!";
// 		}		
// 	}

// 	else if($_GET['mode'] =='view') {
// 		$sq = "select pk, code, square_code, name, addr_state, addr_city, addr_b, phone, fax, contact_person, contact_tel, open_hour, close_hour, comment, sniffing_mac, area from ".$DB_CUSTOM['store']." where pk = ".$_GET['pk'];
// 		$rs = mysqli_query($connect0, $sq); 
// 		if(!$rs) {
// 			print '{"info":"Error in query"}';
// 			exit;
// 		}
// 		$arr_result = mysqli_fetch_assoc($rs);
// 		if(!$arr_result['code']) {
// 			$arr_result['code'] = 'ST'.time().rand(0,9).rand(0,9).rand(0,9);
// 			if (isset($_GET['sqpk'])){
// 				$sq = "select code from ".$DB_CUSTOM['square']." where pk=".$_GET['sqpk'];
// 				// print $sq;
// 				$arr_result['square_code'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
// 			}
// 		}
// 		// print_arr($arr_result);
// 		$json_str = json_encode($arr_result);

// 	}
// }

// else if ($_GET['fr'] == 'camera') {	
// 	$msg = q_language('camera.php');
// 	if($_GET['mode'] == 'modify') {
// 		print "<pre>"; 	print_r($_POST);	print "</pre>";
// 		$device_info = "mac=".trim($_POST['mac'])."&brand=".trim($_POST['brand'])."&model=".trim($_POST['model']);
// 		if (!$_GET['pk'] ) {
// 			$sq = "insert into ".$DB_CUSTOM['camera']."  (regdate, code) values(now(), '".$_POST['code']."')";
// 			$rs = mysqli_query($connect0, $sq);
// 			$sq = "select pk from ".$DB_CUSTOM['camera']."  where code='".$_POST['code']."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			$_GET['pk'] = mysqli_fetch_row($rs)[0];
// 		}
		
// 		$sq = "update ".$DB_COMMON['param']." set db_name='".$_SESSION['db_name']."' where device_info='".$device_info."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs) {
// 			print "param update OK</br>";
// 		}
// 		else {
// 			print "param update Fail</br>";
// 		}
		
// 		$sq = "select store_code from  ".$DB_CUSTOM['camera']." where pk=".$_GET['pk'];
// 		$old_store_code = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];

// 		$sq = "update ".$DB_CUSTOM['camera']." set name='".addslashes(trim($_POST['name']))."', device_info = '".$device_info."', mac='".trim($_POST['mac'])."', usn='".trim($_POST['usn'])."',  model='".trim($_POST['model'])."', brand='".trim($_POST['brand'])."', product_id='".trim($_POST['product_id'])."', enable_countingline='".($_POST['enable_countingline']=='true'?'y':'n')."', enable_heatmap='".($_POST['enable_heatmap']=='true'?'y':'n')."', enable_face_det='".($_POST['enable_face_det']=='true'?'y':'n')."', enable_macsniff='".($_POST['enable_macsniff']=='true'?'y':'n')."', flag='".($_POST['flag']=='true'?'y':'n')."', comment='".addslashes(trim($_POST['comment']))."' where pk=".$_GET['pk'];
// 		print $sq;
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs) {
// 			print "Camera update OK</br>";
// 		}
// 		else {
// 			print "Camrera update Fail</br>";
// 		}
		
// 		if($old_store_code != $_POST['store_code']) {
// 			$sq = "select square_code from  ".$DB_CUSTOM['store']." where code='".trim($_POST['store_code'])."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			$square_code = mysqli_fetch_row($rs)[0];

// 			$sq = "update ".$DB_CUSTOM['camera']." set square_code='".$square_code."', store_code='".trim($_POST['store_code'])."' where pk=".$_GET['pk'];
// 			$rs = mysqli_query($connect0, $sq);
// 			if(!$rs) {
// 				print "FAIL";
// 			}		
			
// 			$sq = "update ".$DB_CUSTOM['count']." set  square_code = '".$square_code."', store_code = '".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			if(!$rs) {
// 				print "FAIL";
// 			}		
// 			$sq = "update ".$DB_CUSTOM['heatmap']." set  square_code = '".$square_code."', store_code = '".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			if(!$rs) {
// 				print "FAIL";
// 			}	
// 			$sq = "update ".$DB_CUSTOM['age_gender']." set  square_code = '".$square_code."', store_code = '".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			if(!$rs) {
// 				print "FAIL";
// 			}	
// 		}
// 		if( ($_POST['enable_countingline']=='true') and $_POST['ct_labels']) {
// 			for($i=0; $i< sizeof($_POST['ct_labels']); $i++) {
// 				$sq = "select pk from ".$DB_CUSTOM['counter_label']." where camera_code='".$_POST['code']."' and counter_name = '".$_POST['ct_names'][$i]."'";
// 				print $sq."</br>";
// 				$rs = mysqli_query($connect0, $sq);
// 				if($rs->num_rows) {
// 					$sq = " update ".$DB_CUSTOM['counter_label']." set counter_label = '".trim($_POST['ct_labels'][$i])."' where camera_code='".$_POST['code']."' and counter_name = '".trim($_POST['ct_names'][$i])."'";
// 				}
// 				else {
// 					$sq = "insert into ".$DB_CUSTOM['counter_label']."(camera_code, counter_name, counter_label) values('".$_POST['code']."', '".trim($_POST['ct_names'][$i])."', '".trim($_POST['ct_labels'][$i])."')";
// 				}				
// 				print ($sq."... ");
// 				$rs = mysqli_query($connect0, $sq);
// 				if($rs) {
// 					print "\t Counter Label Update OK</br>";
// 				}
// 				if($_POST['update_old_count_data']=='y'){
// 					$sq = "update ".$DB_CUSTOM['count']." set counter_label = '".trim($_POST['ct_labels'][$i])."' where camera_code='".$_POST['code']."' and counter_name = '".trim($_POST['ct_names'][$i])."'";
// 					print $sq."... ";
// 					if (mysqli_query($connect0, $sq)){
// 						print "Update OK<br>";
// 					}
// 				}
// 			}
// 		}
// 		$json_str = "";		
// 	}

// 	else if($_GET['mode'] == 'delete') {
// 		$sq = "select ID from ".$DB_COMMON['account']." where ID = '".$_SESSION['logID']."' and (role='admin' or role='root') and passwd = '".$_POST['passwd']."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs->num_rows) {
// 			$sq = "select device_info, code as camera_code from ".$DB_CUSTOM['camera']." where pk=".$_GET['pk'];
// 			print $sq;
// 			$assoc = mysqli_fetch_assoc(mysqli_query($connect0, $sq));
// 			$sq = "update ".$DB_COMMON['param']." set db_name='none' where device_info = '".$assoc['device_info']."' ";
// 			$rs1 = mysqli_query($connect0, $sq);
// 			if($rs1) {
// 				print "Camera ".$assoc['device_info']." moves to db[none]";
// 			}			
// 			$sq = "delete from ".$DB_CUSTOM['camera']." where pk=".$_GET['pk'];
// 			$rs2 = mysqli_query($connect0, $sq);
// 			if($rs2) {
// 				print "<br>Camera ".$assoc['device_info']." deleted";
// 			}
// 			$sq = "delete from ".$DB_CUSTOM['counter_label']." where camera_code = '".$assoc['camera_code']."' ";
// 			$rs3 = mysqli_query($connect0, $sq);
// 			if($rs3) {
// 				print "<br>Counter label for  ".$assoc['device_info']." deleted";
// 			}
// 			if($rs1 && $rs2 && $rs3){
// 				print "<br>delete OK";
// 			}
// 		}
// 		else {
// 			print "no right to delete or password not match!";
// 		}
// 		$json_str = "";	
			
// 	}

// 	else if($_GET['mode'] == 'view') {
// 		// print_r($msg);
// 		$sq = "select A.pk as fpk, A.device_info, A.usn, A.product_id, A.lic_pro, A.lic_surv, A.lic_count, A.face_det, A.heatmap, A.countrpt, A.macsniff, A.param, A.initial_access, A.last_access, B.body as snapshot, B.regdate as regdate, C.pk as pk, C.code, C.name, C.store_code, C.square_code, C.enable_countingline, C.enable_heatmap, C.enable_face_det, C.enable_macsniff, C.flag from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B inner join ".$DB_CUSTOM['camera']." as C on A.device_info = B.device_info and A.device_info= C.device_info where C.pk=".$_GET['pk']." order by B.regdate desc limit 1" ;
// 		// print $sq;
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs) {
// 			print  "No Record";
// 			exit;
// 		}		
// 		$arr_result = mysqli_fetch_assoc($rs);

// 		$arr_result['license'] = '';
// 		// $exstr = explode("&", $arr_result['device_info']);
// 		// $x = explode("=", $exstr[0]);
// 		// $arr_result['mac'] = trim(array_pop($x));
// 		// $x = explode("=", $exstr[1]);
// 		// $arr_result['brand'] = trim(array_pop($x));
// 		// $x = explode("=", $exstr[2]);
// 		// $arr_result['model'] = trim(array_pop($x));
// 		$arr = exDevInfo($arr_result['device_info']);
// 		$arr_result['mac'] = $arr['mac'];
// 		$arr_result['brand'] = $arr['brand'];
// 		$arr_result['model'] = $arr['model'];

// 		$arr_result['license'] = (($arr_result['lic_pro'] =='y')? "PRO":"")." ".(($arr_result['lic_surv'] =='y')? "SURV":"")." ".(($arr_result['lic_count'] =='y')? "COUNT":"");
// 		$arr_result['license'] = trim($arr_result['license']);
// 		$arr_result['zone'] = getZoneFromParam($arr_result['param']);
// 		// $arr_result['ct_label'] = getCounterFromParam($arr_result['param']);

// 		$arr_result['counter_table'] = getCounterTableFromParam($arr_result['param'], $arr_result['code']);
	
// 		$sq = "select code, name from ".$DB_CUSTOM['store']." where square_code='".$arr_result['square_code']."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		$arr_result['store_options'] = "";
// 		while ($row= mysqli_fetch_row($rs)) {
// 			// for($i=0, $blnk=''; $i<10; $i++){
// 			// 	$blnk .= '&nbsp;';
// 			// }
// 			$arr_result['store_options'] .= '<option value="'.$row[0].'">'.$row[0].' : '.$row[1].'</option>';
// 		}		
// 		unset($arr_result['param']);
// 		unset($arr_result['lic_pro']);
// 		unset($arr_result['lic_surv']);
// 		unset($arr_result['lic_count']);
// 		// print_r	($arr_result);
// 		$json_str = json_encode($arr_result);
		
	
// 	}
// 	else if($_GET['mode'] == 'list') {


// 	}


// }


//else if ($_GET['fr'] == 'floating_camera') {
// 	$msg = q_language('camera.php');
// 	if($_GET['mode'] == 'list') {
// 		$sq = "select A.*, B.body as snapshot, B.regdate as regdate from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B on A.device_info = B.device_info where db_name ='none' or db_name is null order by last_access desc ";
// 		$rs = mysqli_query($connect0, $sq);
// 		$str_body = "";
// 		for($i=0; $i<($rs->num_rows); $i++) {
// 			$assoc = mysqli_fetch_assoc($rs);
// 			if(time() - strtotime($assoc['regdate']) <3600) {
// 				$assoc['regdate'] = '<span style="color:#00F">'.$assoc['regdate'].'</span>';
// 			}
// 			$str_body .='
// 			<div class="col-12 col-md-6 col-lg-6">
// 				<div class="card">
// 					<div class="card-header">
// 						<span class="float-right">'.$assoc['regdate'].'<br><span type="button" OnClick="addDeviceToStore(\''.$_GET['st_code'].'\',\''.$assoc['device_info'].'\')" class="btn btn-sm btn-primary float-right mt-2" >'.$msg['addtostore'].'</span></span>
// 						<h3 class="card-title mb-0"><b>'.str_replace("&","<br>",str_replace("=",": ",$assoc['device_info'])).'</b></h3>
// 					</div>
// 					<img class="card-img-top" src="'.$assoc['snapshot'].'"></img>		
// 				</div>
// 			</div>';		
// 		}
// 		$json_str = <<<EOPAGE
// 		<div class="col-12 col-md-12 col-lg-12" style="position:relative; ">
// 			<div class="row">
// 			$str_body
// 			</div>
// 		</div>
// EOPAGE;
// 	}
// 	else if ($_GET['mode'] == 'view_simple'){
// 		$sq = "select device_info, url, user_id, user_pw from ".$DB_COMMON['param']." where pk=".$_GET['fpk'];
// 		$rs = mysqli_query($connect0, $sq);
// 		$assoc = mysqli_fetch_assoc($rs);
// 		$json_str = json_encode($assoc);

// 	}
	// else if ($_GET['mode'] == 'view'){
	// 	// print_r($_GET);
	// 	$device_info = 'mac='.$_GET['mac'].'&brand='.$_GET['brand'].'&model='.$_GET['model'];
	// 	$sq = "select A.pk as fpk, A.device_info, A.usn, A.product_id, A.initial_access, A.last_access, A.lic_pro, A.lic_surv, A.lic_count, A.face_det, A.heatmap, A.countrpt, A.macsniff, A.db_name, A.url, A.param, B.body as snapshot from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B on A.device_info = B.device_info where A.device_info='".$device_info."' ";
	// 	$rs = mysqli_query($connect0, $sq);
	// 	$assoc = mysqli_fetch_assoc($rs);
	// 	$assoc['license'] = (($assoc['lic_pro'] =='y')? "PRO":"")." ".(($assoc['lic_surv'] =='y')? "SURV":"")." ".(($assoc['lic_count'] =='y')? "COUNT":"");
	// 	$assoc['license'] = trim($assoc['license']);
	// 	$assoc['zone'] = getZoneFromParam($assoc['param']);


	// 	$sq = "select square_code from ".$DB_CUSTOM['store']." where code = '".$_GET['st_code']."' ";
	// 	$rs = mysqli_query($connect0, $sq);
	// 	$assoc['square_code'] = mysqli_fetch_row($rs)[0];
	// 	$assoc['store_code'] = $_GET['st_code'];
	// 	$assoc['code'] = 'C'.time().rand(0,9).rand(0,9);
	// 	$assoc['mac'] = $_GET['mac'];
	// 	$assoc['brand'] = $_GET['brand'];
	// 	$assoc['model'] = $_GET['model'];

	// 	$sq = "select code, name from ".$DB_CUSTOM['store']." where square_code='".$assoc['square_code']."' ";
	// 	$rs = mysqli_query($connect0, $sq);
	// 	$assoc['store_options'] = "";
	// 	while ($row= mysqli_fetch_row($rs)) {
	// 		$assoc['store_options'] .= '<option value="'.$row[0].'">'.$row[0].' : '.$row[1].'</option>';
	// 	}
	// 	// $assoc['counter_table'] = getCounterFromParam($assoc['param'], $msg);
	// 	$assoc['counter_table'] = getCounterTableFromParam($assoc['param'], $assoc['code']);
	
	// 	unset($assoc['lic_pro']);
	// 	unset($assoc['lic_surv']);
	// 	unset($assoc['lic_count']);
	// 	unset($assoc['param']);
	// 	// print_r($assoc);
	// 	// print (json_encode(($assoc)));
	// 	$json_str = json_encode($assoc);

	// }
	// else if($_GET['mode'] == 'addToStore') {
	// 	print_r($_GET);
	// 	$device_info = 'mac='.$_GET['mac'].'&brand='.$_GET['brand'].'&model='.$_GET['model'];
		
	// 	$sq = "select device_info, usn, product_id from ".$DB_COMMON['param']." where device_info='".$device_info."' ";
	// 	$rs = mysqli_query($connect0, $sq);
	// 	$assoc = mysqli_fetch_assoc($rs);
		
	// 	$sq = "select square_code from ".$DB_CUSTOM['store']." where code = '".$_GET['st_code']."' ";
	// 	$rs = mysqli_query($connect0, $sq);
	// 	$assoc['square_code'] = mysqli_fetch_row($rs)[0];
		
	// 	$code = 'C'.time().rand(0,9).rand(0,9);
	// 	$sq = "insert into ".$DB_CUSTOM['camera']."(regdate, code, name, store_code, square_code, device_info, usn, product_id) values (now(), '".$code."', '".$code."', '".$_GET['st_code']."', '".$assoc['square_code']."', '".$device_info."', '".$assoc['usn']."', '".$assoc['product_id']."' )";
	// 	print $sq;
	// 	$rs = mysqli_query($connect0, $sq);
	// 	if($rs) {
	// 		$sq = "update ".$DB_COMMON['param']." set db_name = '".$_SESSION['db_name']."' where device_info = '".$device_info."' ";
	// 		print $sq;
	// 		$rs = mysqli_query($connect0, $sq);
	// 		if($rs) {
	// 			print "code=".$code." update OK";  
	// 		}
	// 	}		
	// }
	
// 	else if($_GET['mode'] == 'modifyParam') {
// //		print_r($_POST);
// 		$device_info = "mac=".trim($_POST['mac'])."&brand=".trim($_POST['brand'])."&model=".trim($_POST['model']);
// 		$_POST['enable_countingline'] = $_POST['enable_countingline'] == 'true' ? 'y' : 'n' ;
// 		$_POST['enable_heatmap'] = $_POST['enable_heatmap'] == 'true' ? 'y' : 'n' ;
// 		$_POST['enable_face_det'] = $_POST['enable_face_det'] == 'true' ? 'y' : 'n' ;
// 		$_POST['enable_macsniff'] = $_POST['enable_macsniff'] == 'true' ? 'y' : 'n' ;
// 		$_POST['flag'] = $_POST['flag'] == 'true' ? 'y' : 'n' ;
			
// 		$sq = "select square_code from ".$DB_CUSTOM['store']." where code='".$_POST['store_code']."'";
// 		$rs = mysqli_query($connect0, $sq);
// 		$_POST['square_code'] = mysqli_fetch_row($rs)[0];
			
// 		$sq = "update ".$DB_CUSTOM['camera']." set name = '".addslashes(trim($_POST['name']))."', device_info = '".$device_info."',usn='".trim($_POST['usn'])."', product_id='".trim($_POST['product_id'])."', enable_countingline='".$_POST['enable_countingline']."', enable_heatmap='".$_POST['enable_heatmap']."', enable_face_det='".$_POST['enable_face_det']."', enable_macsniff='".$_POST['enable_macsniff']."', square_code = '".$_POST['square_code']."', store_code = '".$_POST['store_code']."', flag='".$_POST['flag']."', comment='".addslashes(trim($_POST['comment']))."' where pk = ".$_POST['pk'];
// //		print $sq;
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs) {
// 			print "FAIL";
// 		}
		
// 		$sq = "update ".$DB_CUSTOM['count']." set  square_code = '".$_POST['square_code']."', store_code = '".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs) {
// 			print "FAIL";
// 		}		
// 		$sq = "update ".$DB_CUSTOM['heatmap']." set  square_code = '".$_POST['square_code']."', store_code = '".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs) {
// 			print "FAIL";
// 		}	
// 		$sq = "update ".$DB_CUSTOM['age_gender']." set  square_code = '".$_POST['square_code']."', store_code = '".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs) {
// 			print "FAIL";
// 		}	

		
// 		if( ($_POST['enable_countingline']== 'y') and $_POST['ct_label']) {
// 			$ex_label = explode('},', $_POST['ct_label']);
// 			for($i=0; $i< sizeof($ex_label); $i++) {
// 				$ex_label[$i] = substr($ex_label[$i],1,strlen($ex_label[$i]));
// 				if(!$ex_label[$i]) {
// 					continue;
// 				}
// 				list($ct_name, $ct_label) = explode(":", $ex_label[$i]);
// 				$sq = "select pk from ".$DB_CUSTOM['counter_label']." where camera_code='".$_POST['code']."' and counter_name = '".$ct_name."'";
// 				$rs = mysqli_query($connect0, $sq);
// 				if($rs->num_rows) {
// 					$sq = " update ".$DB_CUSTOM['counter_label']." set counter_label = '".$ct_label."' where camera_code='".$_POST['code']."' and counter_name = '".$ct_name."'";
// 				}
// 				else {
// 					$sq = "insert into ".$DB_CUSTOM['counter_label']."(camera_code, counter_name, counter_label) values('".$_POST['code']."', '".$ct_name."', '".$ct_label."')";
// 				}				
// //				print $sq;
// 				$rs = mysqli_query($connect0, $sq);
// 				if(!$rs) {
// 					print "FAIL";
// 				}
// 			}
// 		}
// 	}

// 	else if($_GET['mode'] == 'delete') {
// 		$sq = "select ID from ".$DB_COMMON['account']." where ID = '".$_SESSION['logID']."' and (role='admin' or role='root') and passwd = '".$_POST['passwd']."' ";
// 		$rs = mysqli_query($connect0, $sq);
// 		if($rs->num_rows) {
// 			$sq = "select device_info from ".$DB_CUSTOM['camera']." where pk=".$_GET['pk'];
// 			$rs = mysqli_query($connect0, $sq);
// 			$device_info = mysqli_fetch_row($rs)[0];
// //			print $device_info;
// 			$sq = "update ".$DB_COMMON['param']." set db_name='none' where device_info = '".$device_info."' ";
// 			$rs = mysqli_query($connect0, $sq);
// 			$sq = "delete from ".$DB_CUSTOM['camera']." where pk=".$_GET['pk'];
// 			$rs = mysqli_query($connect0, $sq);
// 			if($rs) {
// 				print "store ".$device_info." delete OK";
// 			}
// 		}
// 		else {
// 			print "no right to delete or password not match!";
// 		}
			
// 	}
	
// 	else if($_GET['mode'] == 'viewParam') {
// 		$device_info = "mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model'];
// 		$tag_square = '&#9109;';
// 		$tag_check = '&#10004;';
// 		$tag_square = '<i class="align-middle fas fa-fw fa-1x fa-expand"></i>';
// 		$tag_check  = '<i class="align-middle fas fa-fw fa-1x fa-check"></i>';
			
// 		$sq = "select A.pk as fpk, A.device_info, A.usn, A.product_id, A.lic_pro, A.lic_surv, A.lic_count, 
// 		if(A.face_det='y','".$tag_check."','".$tag_square."') as face_det , 
// 		if(A.heatmap='y','".$tag_check."','".$tag_square."') as heatmap, 
// 		if(A.countrpt='y','".$tag_check."','".$tag_square."') as countrpt, 
// 		if(A.macsniff='y','".$tag_check."','".$tag_square."') as macsniff, 
// 		A.initial_access, A.last_access, A.db_name, A.param, B.body as snapshot, B.regdate as regdate, C.pk as pk, C.code, C.name, C.store_code, C.square_code, 
// 		if(C.enable_countingline='y','checked','') as enable_countingline, 
// 		if(C.enable_heatmap='y','checked','') as enable_heatmap, 
// 		if(C.enable_snapshot='y','checked','') as enable_snapshot, 
// 		if(C.enable_face_det='y','checked','') as enable_face_det, 
// 		if(C.enable_macsniff='y','checked','') as enable_macsniff, 
// 		if(C.flag='y','checked','') as flag from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B inner join ".$DB_CUSTOM['camera']." as C on A.device_info = B.device_info and A.device_info= C.device_info where A.device_info ='".$device_info."' ";
// //		print $sq;
// 		$rs = mysqli_query($connect0, $sq);
// 		$arr_result = mysqli_fetch_assoc($rs);		

// 		$exstr = explode("&", $arr_result['device_info']);
// 		$arr_result['mac'] = trim(array_pop(explode("=", $exstr[0])));
// 		$arr_result['brand'] = trim(array_pop(explode("=", $exstr[1])));
// 		$arr_result['model'] = trim(array_pop(explode("=", $exstr[2])));
		
// 		$sq = "select code, name from ".$DB_CUSTOM['store']." ";
// 		$rs = mysqli_query($connect0, $sq);
// //		$option_store_name = "<option value= \"none\">none</option>";
// 		$option_store_name = "";
// 		while ($row= mysqli_fetch_row($rs)) {
// 			$option_store_name .= "<option value=\"".$row[0]."\" ".(($row[0] == $arr_result['store_code']) ? "selected" : "").">".$row[1]."</option>";
// 			if($row[0] == $arr_result['store_code']) {
// 				$arr_result['store_name'] = $row[1];
// 			}
// 		}
		
// 		$arr_result['disable_countingline'] = $arr_result['countrpt'] == $tag_square ? "disabled":"";
// 		$arr_result['disable_heatmap'] = $arr_result['heatmap'] == $tag_square ? "disabled":"";
// 		$arr_result['disable_face_det'] = $arr_result['face_det'] == $tag_square ? "disabled":"";
// 		$arr_result['disable_macsniff'] = $arr_result['macsniff'] == $tag_square ? "disabled":"";
		
// 		$ct_name = array();
// 		$ex_list_counter = explode("\n", $arr_result['param']);
// 		for($i = 0,$c = 0; $i<sizeof($ex_list_counter); $i++) {
// 			if( !strncmp("VCA.Ch0.Ct",$ex_list_counter[$i],10) and strpos($ex_list_counter[$i], ".name")) {
// 				$ct_name[$c] = trim(array_pop(explode("=", $ex_list_counter[$i])));
// 				$c++;
// 			}
// 		}
// 		for($i=0; $i<count($ct_name); $i++) {
// 			$sq = "select counter_label from ".$DB_CUSTOM['counter_label']." where counter_name='".$ct_name[$i]."' and camera_code='".$arr_result['code']."' ";
// 			$ct_label = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
// 			$camera_label_table_body .= '
// 				<input type="hidden" id="ct_name['.$i.']" value="'.$ct_name[$i].'">
// 				<tr>
// 					<td>'.$ct_name[$i].'</td>
// 					<td>
// 						<select id="ct_label['.$i.']" class="form-control" >
// 							<option value="none">'.$msg['none'].'</option>
// 							<option value="outside" '.($ct_label=='outside'? "selected": "").'>'.$msg['outside'].'</option>
// 							<option value="entrance" '.($ct_label=='entrance'? "selected": "").'>'.$msg['entrance'].'</option>
// 							<option value="exit" '.($ct_label=='exit'? "selected": "").'>'.$msg['exit'].'</option>
// 						</select>
// 					</td>
// 				</tr>' ;
// 		}
// 		$camera_label_table_body = '
// 			<table class="table table-striped table-sm table-bordered">
// 				<tr><th>'.$msg['countername'].'</th><th>'.$msg['counterlabel'].'</th></tr>'.
// 				$camera_label_table_body.
// 			'</table>';		
// 		$show_count_label = $arr_result['enable_countingline'] == "checked"? "":"none";
		
		
// 		if($arr_result['lic_pro'] =='y') {
// 			$arr_result['license'] = "/PRO";
// 		}
// 		if($arr_result['lic_surv'] =='y') {
// 			$arr_result['license'] .= "/SURV";
// 		}
// 		if($arr_result['lic_count'] =='y') {
// 			$arr_result['license'] .= "/COUNT";
// 		}
	
// 		$zone = array();
// 		$line = explode("\n",$arr_result['param']);
// 		for($i=0; $i<count($line)-1; $i++) {
// 			list($key,$val) = explode("=", $line[$i]);
// 			if(strpos(" ".$line[$i], "VCA.Ch0.Zn")) {
// 				$ex_key = explode(".",$key);
// 				$p = substr($ex_key[2],2,strlen($ex_key[2]));
// 				$zone[$p][$ex_key[3]] = trim($val);
// 			}
// 		}
// 		foreach($zone as $idx => $A) {
// 			$z_str = '';
// 			foreach($A as $key =>$val) {
// 				if($z_str) {
// 					$z_str.= ',';
// 				}
// 				$z_str .= '"'.$key.'":"'.$val.'"';
// 			}
// 			if($zone_str) {
// 				$zone_str .= ',';
// 			}
// 			$zone_str .= '{'.$z_str.'}';
// 		}			
		
// 		if($_GET['fmt'] == 'json') {
// 			$json_str= '{
// 				"info":{
// 					"zone":['.$zone_str.'],
// 					"device_info": "'.$arr_result['device_info'].'", 
// 					"code": "'.$arr_result['code'].'", 
// 					"regdate":"'.$arr_result['regdate'].'", 
// 					"name":"'.$arr_result['name'].'",
// 					"square_name":"'.$arr_result['square_name'].'",
// 					"store_name":"'.$arr_result['store_name'].'",
// 					"snapshot":"'.$arr_result['snapshot'].'",
// 					"mac":"'.$arr_result['mac'].'",
// 					"brand":"'.$arr_result['brand'].'",
// 					"model":"'.$arr_result['model'].'",
// 					"usn":"'.$arr_result['mac'].'",
// 					"product_id":"'.$arr_result['product_id'].'",
// 					"initial_access":"'.$arr_result['initial_access'].'",
// 					"last_access":"'.$arr_result['last_access'].'",
// 					"license":"'.$arr_result['license'].'",
// 					"functions":['.$arr_result['functions'].'],
// 					"features":['.$arr_result['features'].'],
// 					"comment":"'.$arr_result['comment'].'"
// 				}
// 			}';
			
// 		}
// 		else {
// 			$json_str = <<<EOPAGE
// 			<div class="card col-12 col-md-12 col-lg-12" style="position:relative; ">
// 				<div class="card-header">
// 					<span class="float-right">$arr_result[regdate]</span>
// 					<h3 class="card-title mb-0"><b>$arr_result[name]</b></h3>
// 				</div>
// 				<canvas id="zone_config" width="800" height="450"></canvas>
// 				<div class="card-body">
// 					<div class="form-row">
// 						<input type="hidden" id="pk" value="$arr_result[pk]">
// 						<input type="hidden" id="fr" value="$_GET[fr]">
// 						<div class="form-group col-md-4"><label>$msg[code]</label><input type="text" id="code" class="form-control" value="$arr_result[code]" readonly></div>
// 						<div class="form-group col-md-8"><label>$msg[name]</label><input type="text" id="name" class="form-control" value="$arr_result[name]"></div>
// 						<div class="form-group col-md-3"><label>$msg[mac]</label><input type="text" id="mac" class="form-control" value="$arr_result[mac]" readonly></div>
// 						<div class="form-group col-md-2"><label>$msg[brand]</label><input type="text" id="brand" class="form-control" value="$arr_result[brand]" readonly></div>
// 						<div class="form-group col-md-2"><label>$msg[model]</label><input type="text" id="model" class="form-control" value="$arr_result[model]" readonly></div>
// 						<div class="form-group col-md-3"><label>$msg[usn]</label><input type="text" id="usn" class="form-control" value="$arr_result[usn]" readonly></div>
// 						<div class="form-group col-md-2"><label>$msg[productid]</label><input type="text" id="product_id" class="form-control" value="$arr_result[product_id]" readonly></div>
// 						<div class="form-group col-md-3"><label>$msg[store]</label><select id="store_code" class="form-control">$option_store_name</select></div>
// 						<div class="form-group col-md-3"><label>$msg[installdate]</label><input type="text" class="form-control" value="$arr_result[initial_access]" readonly></div>
// 						<div class="form-group col-md-3"><label>$msg[lastaccess]</label><input type="text" class="form-control" value="$arr_result[last_access]" readonly></div>
// 						<div class="form-group col-md-3"><label>$msg[license]</label><input type="text" class="form-control" value="$arr_result[license]" readonly></div>
// 						<div class="form-group col-md-12"><label>$msg[function]</label>
// 							<div class="form-group mb-0">
// 								<label class="form-check-inline col-md-2 mb-0">$arr_result[countrpt]$msg[countdb]</label>
// 								<label class="form-check-inline col-md-2 mb-0">$arr_result[heatmap]$msg[heatmap]</label>
// 								<label class="form-check-inline col-md-2 mb-0">$arr_result[face_det]$msg[face]</label>
// 								<label class="form-check-inline col-md-2 mb-0">$arr_result[macsniff]$msg[macsniff]</label>
// 							</div>
// 						</div>
// 						<div class="form-group col-md-12 mt-0"><label>$msg[feature]</label>
// 							<div class="form-group mb-0">
// 								<label class="form-check-inline col-md-2 mb-0">
// 									<input class="form-check-input" type="checkbox" id="enable_countingline" OnChange="showCounterLabel()" $arr_result[enable_countingline] $arr_result[disable_countingline]>$msg[countingline]
// 								</label>
// 								<label class="form-check-inline col-md-2 mb-0">
// 									<input class="form-check-input" type="checkbox" id="enable_heatmap" $arr_result[enable_heatmap] $arr_result[disable_heatmap]>$msg[heatmap]
// 								</label>
// 								<label class="form-check-inline col-md-2 mb-0">
// 									<input class="form-check-input" type="checkbox" id="enable_face_det" $arr_result[enable_face_det] $arr_result[disable_face_det]>$msg[ageandgender]
// 								</label>
// 								<label class="form-check-inline col-md-2 mb-0">
// 									<input class="form-check-input" type="checkbox" id="enable_macsniff" $arr_result[enable_macsniff] $arr_result[disable_macsniff]>$msg[macsniffing]
// 								</label>
// 								<label class="form-check-inline col-md-2 mb-0">
// 									<input class="form-check-input" type="checkbox" id="flag" $arr_result[flag]>$msg[activate]
// 								</label>
// 							</div>
// 						</div>
// 						<div class="form-group col-md-12" id="counter_label" style="display:$show_count_label;">
// 							$camera_label_table_body
// 						</div>
// 						<div class="form-group col-md-12">
// 							<label>$msg[comment]</label>
// 							<textarea id="comment" class="form-control">$arr_result[comment]</textarea>
// 						</div>
// 					</div>
// 					<div class="float-right"><button type="button" class="btn  btn-sm btn-warning" OnClick="document.getElementById('delete_pad').style.display='block';">$msg[delete]</button></div>
// 					<div class="text-center"><button type="button" class="btn btn-primary" OnClick="modifyDeviceInfo()">$msg[save_changes]</button></div>					
// 					</div>
// 				</div>
// 			</div>
// EOPAGE;
// 		}
// 	}

//}

else if($_GET['fr'] == 'camera') {
	if($_GET['mode'] == 'view'){
		if(isset($_GET['fpk'])){

		}
		else if(isset($_GET['mac']) && isset($_GET['brand']) && isset($_GET['model']) ){

		}
	}
	else if ($_GET['mode'] == 'view_simple' && isset($_GET['fpk'])){
		$sq = "select device_info, url, user_id, user_pw from ".$DB_COMMON['param']." where pk=".$_GET['fpk'];
		$rs = mysqli_query($connect0, $sq);
		$assoc = mysqli_fetch_assoc($rs);
		$json_str = json_encode($assoc);

	}
	else if ($_GET['mode'] == 'modify' && $_GET['fpk']) {
		$sq = "update ".$DB_COMMON['param']." set url='".$_POST['IP']."', user_id='".$_POST['userid']."', user_pw='".$_POST['passwd']."' where pk=".$_GET['fpk'];
		// print $sq;
		$rs = mysqli_query($connect0, $sq);
		$json_str = $rs ? "... update OK": "...update Fail";
	}
	else if ($_GET['mode'] == 'add' && $_GET['fpk'] == 0) {
		$host = $_POST['IP']; 
		$port = 80; 
		$waitTimeoutInSeconds = 2; 
		if($fp = fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds)){   
		   print "OK";
		   fclose($fp);
		}
		else {
		   print "FAIL";
		   return false;
		} 		
		$url = 'http://'.$_POST['IP'].'/uapi-cgi/param.fcgi?action=list&group=Brand.brand,Brand.product.shortname,NETWORK.Eth0.mac,VERSION.serialno,BRAND.Model.productid';
		$auth = base64_encode($_POST['userid'].":".$_POST['passwd']);
	
		$context = stream_context_create([
			"http" => [
				"header" => "Authorization: Basic $auth"
			]
		]);
		$content = file_get_contents($url, true, $context );
		// print $content;
		if (!$content){
			print "no data from url: ".$_POST['IP'].", please check ip address, device online etc.";
			exit;
		}
		
		$lines = explode("\n", $content);
		print_r($lines);
		for ($i=0; $i<sizeof($lines); $i++){
			if (!trim($lines[$i])) {
				continue;
			}
			list($key, $val) = explode("=",$lines[$i]);
			if (strtolower($key) == 'brand.brand') {
				$brand = trim($val);
			}
			else if (strtolower($key) == 'brand.product.shortname') {
				$model = trim($val);
			}
			else if (strtolower($key) == 'network.eth0.mac') {
				$mac = str_replace(":","",trim($val));
			}
			else if (strtolower($key) == 'brand.model.productid') {
				$product_id = str_replace(":","",trim($val));
			}
			else if (strtolower($key) == 'version.serialno') {
				$usn = str_replace(":","",trim($val));
			}
		}
		$device_info = "mac=".$mac."&brand=".$brand."&model=".$model;
		$sq = "select * from ".$DB_COMMON['param']." where device_info='".$device_info."' ";
		$rs = mysqli_query($connect0, $sq);
		if ($rs->num_rows) {
			$json_str =  "Device:".$device_info." exists already!!\n\r";
		}
		else {
			$regdate = date("Y-m-d H:i:s");
			$sq = "insert into ".$DB_COMMON['param']."(device_info, usn, product_id, url, db_name, method, initial_access, last_access) values('".$device_info."', '".$usn."', '".$product_id."', '".$_POST['IP']."', 'none', 'manual', '".$regdate."', '".$regdate."')";
			// print $sq;
			$rs = mysqli_query($connect0, $sq);
			if($rs) {
				$json_str =  "Add device :".$device_info." OK!\n\r";
			}
		}
	
	}
}

else if ($_GET['fr'] == 'counter_label_set') {
	for($i=0; $i<10; $i++) {
		// print $_POST['labels_old'][$i]." : ".$_POST['labels'][$i]." : ".$_POST['displays'][$i]."</br>";
		if (!isset($_POST['labels_old'][$i])) {
			continue;
		}
		$sq = "select * from ".$DB_CUSTOM['counter_label']." where counter_label='".$_POST['labels_old'][$i]."' ";
		// print $sq."</br>";
		$rs = mysqli_query($connect0, $sq);
		if ($rs -> num_rows) {
			$sq = "update ".$DB_CUSTOM['counter_label']." set counter_label = '".$_POST['labels'][$i]."' where counter_label='".$_POST['labels_old'][$i]."' ";
		}
		else if($_POST['labels'][$i]) {
			$sq = "insert into ".$DB_CUSTOM['counter_label']." (counter_label) values('".$_POST['labels'][$i]."') ";
		}
		else {
			continue;
		}
		// print $sq."</br>";
		$rs = mysqli_query($connect0, $sq);

		$sq = "select ".$_COOKIE['selected_language']." from ".$DB_CUSTOM['language']." where varstr='".$_POST['labels_old'][$i]."' ";
		// print $sq."</br>";
		$rs = mysqli_query($connect0, $sq);
		if ($rs ->num_rows){
			$sq = "update ".$DB_CUSTOM['language']." set varstr = '".$_POST['labels'][$i]."', ".$_COOKIE['selected_language']." = '".$_POST['displays'][$i]."' where varstr='".$_POST['labels_old'][$i]."' and (page='footfall.php' or page='camera.php' or page='export.php') ";
		}
		else {
			if (!$_POST['displays'][$i]) {
				$_POST['displays'][$i] = $_POST['labels'][$i];
			}
			$sq = "insert into ".$DB_CUSTOM['language']." (varstr, ".$_COOKIE['selected_language'].", page) values ('".$_POST['labels'][$i]."', '".$_POST['displays'][$i]."', 'footfall.php'), ('".$_POST['labels'][$i]."', '".$_POST['displays'][$i]."', 'camera.php'), ('".$_POST['labels'][$i]."', '".$_POST['displays'][$i]."', 'export.php') ";

		}
		// print $sq;
		$rs = mysqli_query($connect0, $sq);
		if($rs) {
			print $_POST['labels'][$i].'... Update OK!</br>';
		}
		else {
			print '... FAIL!!</br>';
		}
	}
	$json_str = "";
	// footfall.php, camera.php, export.php
}




else if($_GET['fr'] == 'web_update') {
// for update web page from windows standalone	
	if($_GET['mode'] == 'update') {
		$body = file_get_contents("http://".$CLOUD_SERVER."/release.php?file_download=true");
		$lines = explode("\r\n", $body);
		for($i=0; $i<sizeof($lines); $i++) {
			if(!$lines[$i]) {
				continue;
			}
			if(strpos(" ".$lines[$i], "##########") ==1) {
				if($fp) {
					fclose($fp);
				}
				$fname = substr($lines[$i],11,strlen($lines[$i])-11);
				$fname = '../'.$fname;
				print "\r\n<br>".$fname." ";
				$fp = fopen($fname,"w");
			}
			else {
				print "#";
				fwrite($fp, $lines[$i]."\r\n");
			}
		}
		if($fp) {
			fclose($fp);
		}

		$fname = "language_v".$version.".tbl.sql";
		$rs = system("..\..\MariaDB\bin\mysql.exe -uroot -prootpass cnt_demo < ".$fname." --default-character-set utf8mb4"); 
		print_r($rs);
	}
	$fname = "version.ini";
	$fpp = fopen($fname,'w');
	fwrite($fpp, "current_version = ".$version);
	fclose($fpp);
}

else if($_GET['fr'] == 'webpageConfig') {
	if($_GET['db'] == 'basic' && $_GET['mode'] == 'update') {
		// print_r($_POST);
		$arr_rs =  array();
		$x = updateLang('common', '_host_title', $_POST['arr']['host_title']);
		array_push($arr_rs, $x);
		$x = updateLang('common', '_document_title', $_POST['arr']['document_title']);
		array_push($arr_rs, $x);
		$x = updateLang('common', '_title_logo', $_POST['arr']['title_logo']);
		array_push($arr_rs, $x);
		$x = updateLang('common', '_developer', $_POST['arr']['developer']);
		array_push($arr_rs, $x);
		$json_str = "";
		foreach($arr_rs as $rs){
			if (!$rs['rs']) {
				$json_str .= $rs['sql']." FAIL<br>";
			}
		}
		if (!$json_str) {
			$json_str = "... update OK";
		}
	}
	else if($_GET['db'] == 'sidemenu' && $_GET['mode'] == 'update') {
		$flag = $_GET['check'] == 'true' ? 'y': 'n';
		// $sq = "select * from ".$DB_CUSTOM['web_config']." where page='sidemenu' and name='".$_GET['name']."' " ;
		$sq = "select * from ".$DB_CUSTOM['web_config']." where page='main_menu' and body like '%\"id\":\"".$_GET['name']."\"%' " ;
		// print $sq;
		$rs = mysqli_query($connect0, $sq);
		if ($rs -> num_rows) {
			$sq = "update ".$DB_CUSTOM['web_config']." set flag = '".$flag."' where page='main_menu' and body like '%\"id\":\"".$_GET['name']."\"%' " ;
		}
		else {
			$sq = "insert into  ".$DB_CUSTOM['web_config']."( page, frame, flag) values('main_menu', '".$_GET['name']."', '".$flag."')";
		}
		// print $sq;
		$rs = mysqli_query($connect0, $sq);
		if($rs) {
			$json_str = "OK";
		}
	}
	else if($_GET['db'] == 'dashboard' && $_GET['mode'] == 'update') {
		// print_r($_POST);
		$arr_rs =  array();
		if($_GET['name'] == 'card_banner'){
			for($i=0; $i<4; $i++){
				$display = $_POST['postdata'][$i]['display'];
				$badge = $_POST['postdata'][$i]['badge'];
				unset($_POST['postdata'][$i]['display']);
				unset($_POST['postdata'][$i]['badge']);

				$x = updateWebConfig('dashboard','card_banner', $i, $_POST['postdata'][$i], 'y') ;
				array_push($arr_rs, $x);
				$x = updateLang('dashboard.php', 'card_banner'.$i.'_display', $display);
				array_push($arr_rs, $x);
				$x = updateLang('dashboard.php', 'card_banner'.$i.'_badge', $badge);
				array_push($arr_rs, $x);
			}
		}

		else if($_GET['name'] == 'footfall'){
			for($i=0; $i<4; $i++){
				$display = $_POST['postdata']['data'][$i]['display'];
				unset($_POST['postdata']['data'][$i]['display']);
				$x = updateWebConfig('dashboard','footfall', $i, $_POST['postdata']['data'][$i], 'y') ;
				array_push($arr_rs, $x);
				$x = updateLang('dashboard.php',  'footfall_'.$i.'_display', $display);
				array_push($arr_rs, $x);
			}
			$x = updateLang('dashboard.php', 'footfall_title', $_POST['postdata']['main_title']);
			array_push($arr_rs, $x);
		}
		else if($_GET['name'] == 'third_block') {
			$selection = $_POST['postdata']['selection'];
			$x = updateWebConfig('dashboard','third_block', 0, ["title" =>$selection], 'y') ;
			array_push($arr_rs, $x);
			$x = updateLang('dashboard.php', 'third_block_display', $_POST['postdata']['display']);
			array_push($arr_rs, $x);

			if ($selection == 'curve_by_label'){
				for ($i=0; $i<6; $i++){
					$display = $_POST['postdata']['data'][$i]['display'];
					// print_r($_POST['postdata']['data'][$i]);
					$x = updateWebConfig('dashboard','curve_by_label', $i, $_POST['postdata']['data'][$i], 'y') ;
					array_push($arr_rs, $x);
					$x = updateLang('dashboard.php', 'curve_by_label'.$i.'_display', $display);
					array_push($arr_rs, $x);
				}			
			}
		}
		// print_r($arr_rs);
		$json_str = "";
		foreach($arr_rs as $rs){
			if (!$rs['rs']) {
				$json_str .= $rs['sql']." FAIL<br>";
			}
		}
		if (!$json_str) {
			$json_str = "... update OK";
		}	
	}
	else if($_GET['db'] == 'analysis' && $_GET['mode'] == 'update') {
		// print_r($_GET); print_r($_POST);
		$arr_rs = array();
		foreach($_POST['postdata'] as $frame =>$B){
			// print_r($B);
			if($frame == 'age_group' || $frame == 'traffic_reset_hour'){
				$x = updateWebConfig('analysis', $frame, 0, $B['labels'], 'y') ;
				array_push($arr_rs, $x);
				continue;
			}

			$display = $B['display'];
			unset($B['display']);
			$x = updateWebConfig('analysis', $frame, 0, $B, 'y') ;
			array_push($arr_rs, $x);
			$x = updateLang('footfall.php', 'analysis_'.strtolower($frame), $display);
			array_push($arr_rs, $x);
		}
		$json_str = "";
		foreach($arr_rs as $rs){
			if (!$rs['rs']) {
				$json_str .= $rs['sql']." FAIL<br>";
			}
		}
		if (!$json_str) {
			$json_str = "... update OK";
		}
	}
	else if($_GET['db'] == 'report' && $_GET['mode'] == 'update') {

	}
	else if($_GET['db'] == 'realtime_screen' && $_GET['mode'] == 'update') {
		// print_r($_POST['arr']);
		for ($i=0; $i<sizeof($_POST['arr']); $i++) {
			$cfgs = $_POST['arr'][$i];
			$name = $_GET['name'];
			$depth= $cfgs['depth'] ;
			$flag = $cfgs['enable'];

			unset($cfgs['depth']);
			unset($cfgs['enable']);

			
			if ($_GET['name'] == 'title') {
				unset($cfgs['rule']);
			}
			else if ($_GET['name'] == 'label') {
				unset($cfgs['rule']);
			}
			// else if ($_GET['name'] == 'number') {
			// 	unset($cfgs['padding']);
			// }
			// print_r($cfgs);
			// print ("name:".$name.", depth:".$depth.", text:".$cfgs['text'].", body:".json_encode($cfgs).",  flag:".$flag);
			// print ("\n");
			$x = updateWebConfig('realtime_screen', $name, $depth, $cfgs, $flag);
			if ($x['rs']) {
				print "... update OK";
			}
			else {
				print "... update FAIL";
			}
		}

	}
 
}

Header("Content-type: text/json");
print $json_str;
	
	
	
	
?>