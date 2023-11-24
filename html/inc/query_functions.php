<?PHP

/* Copyright (c) 2022, Hans kim
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */



function exDevInfo($device_info) {
	$exstr = explode("&", $device_info);
	$x = explode("=", $exstr[0]);
	$arr_result['mac'] = trim(array_pop($x));
	$x = explode("=", $exstr[1]);
	$arr_result['brand'] = trim(array_pop($x));
	$x = explode("=", $exstr[2]);
	$arr_result['model'] = trim(array_pop($x));

	return $arr_result;
}

function age_group($data, $range) { // data:[0,2,1,2,3...], range:[0,18,30,45,65]
	array_push($range, 100);
	for ($i=0; $i<sizeof($range) -1; $i++) {
		$a = $range[$i];
		$b = $range[$i+1]-$range[$i];
		$rs[$i] = array_sum(array_slice($data,$a,$b));
		if (!$rs[$i]) {
			$rs[$i] = 0;
		}
	}
	return $rs;
}

function getViewByQuery($time_ref, $viewby, $square_code=0, $store_code=0) {
	$arr = array();
	$to_e = explode("~", $time_ref);
	if(!isset($to_e[1])){
		$to_e[1] = $to_e[0];
	}
    $to_e[0] = trim($to_e[0]);
    $to_e[1] = trim($to_e[1]);

	if($viewby == 'tenmin') {
		$arr['g_sq'] = "group by year, month, day, hour, min ";
		$arr['from'] = date("Y-m-d", strtotime($to_e[1]));
		$arr['to'] = $arr['from'];
	}
	else if($viewby == 'hour') {
		$arr['g_sq'] = "group by year, month, day, hour";
		$arr['from'] = date("Y-m-d", strtotime($to_e[1]));
		$arr['to'] = $arr['from'];
	}
	else if($viewby == 'day') {
		$arr['g_sq'] = "group by year, month, day ";
		$arr['from'] = date("Y-m-d", strtotime($to_e[0]));
		$arr['to'] = date("Y-m-d", strtotime($to_e[1]));
	}
	else if($viewby == 'week') {
		$arr['g_sq'] = "group by year, week ";
		$arr['from'] = date("Y-m-d", strtotime($to_e[0]) - date("w", strtotime($to_e[0]))*3600*24);
		$arr['to'] = date("Y-m-d", strtotime($to_e[1]) + (7 - date("w", strtotime($to_e[1])))*3600*24 -1);		
	}	
	else if($viewby == 'month') {
		$arr['g_sq'] = "group by year, month ";
		$arr['from'] = date("Y-m-1", strtotime($to_e[0]));
		$arr['to'] = date("Y-m-t", strtotime($to_e[1]));			
	}
	$arr['ts_from'] = strtotime($arr['from']);
	$arr['ts_to'] = strtotime($arr['to']) +3600*24;

    $arr['p_sq'] = "";
	if($store_code) {
		$arr['p_sq'] =  "and store_code ='".$store_code."' ";
	}
	else if($square_code ) {
		$arr['p_sq'] =  "and square_code ='".$square_code."' ";
	}		
	return $arr;	
}

function makeCategory($from, $to, $viewby) {
	$ts_from = strtotime($from);
	$ts_to = strtotime($to)+3600*24-1;

	if($viewby == 'tenmin') {
        $arr['dateformat'] = 'Y-m-d H:i';
		$arr['step'] = 600;
        $arr['ts_from'] = $ts_from;
	}
	else if($viewby == 'hour' || $viewby == 'hourly') {
        $arr['dateformat'] = 'Y-m-d H:00';
		$arr['step'] = 3600;
        $arr['ts_from'] = $ts_from;
	}
	else if($viewby == 'day' || $viewby == 'daily') {
        $arr['dateformat'] = 'Y-m-d';
		$arr['step'] = 3600*24;
        $arr['ts_from'] = $ts_from;
	}
	else if($viewby == 'week' || $viewby == 'weekly') {
        $arr['dateformat'] = 'Y-W, m-d';
		$arr['ts_from'] = strtotime(date("Y-m-d", $ts_from - date("w", $ts_from)*3600*24));
		$ts_to = strtotime(date("Y-m-d", $ts_to)) + (7-date("w", $ts_to))*3600*24 -1;	
		$arr['step'] = 3600*24*7;
	}	
	else if($viewby == 'month' || $viewby == 'monthly') {
        $arr['dateformat'] = 'Y-m-d';
		$arr['ts_from'] = strtotime(date("Y-m-1", $ts_from));
		$ts_to = strtotime(date("Y-m-t", $ts_to)) + 3600*24 -1;
		$arr['step'] = 3600*24*31;
	}
	$duration =  ceil(($ts_to-$ts_from)/$arr['step']);
	for ($i=0, $ts=$arr['ts_from']; $i<($duration+10); $i++){
		if ($ts > $ts_to){
            $arr['ts_to'] = $ts;
            $arr['duration'] = $i;
			break;
		}
		$arr['datetime'][$i] = date( $arr['dateformat'], $ts);
		$arr['timestamp'][$i] = $ts;
		if($viewby == 'month') {
			$ts = strtotime($arr['datetime'][$i]." +1 month");
		}
		else {
			$ts += $arr['step'];
		}
	}

	return $arr;	
}

function queryWorkingHour(){
	global $DB_CUSTOM, $connect0;
	$sq = "select code, open_hour, close_hour, apply_open_hour from ".$DB_CUSTOM['store']." where apply_open_hour='y'";
	$rs = mysqli_query($connect0, $sq);
	if (!$rs->num_rows) {
		return "";
	}
	$sq = "select code, open_hour, close_hour, apply_open_hour from ".$DB_CUSTOM['store']." ";
	$rs = mysqli_query($connect0, $sq);
	$arr = array();
	while($assoc = mysqli_fetch_assoc($rs)){
		if ($assoc['apply_open_hour']=='n') {
			array_push($arr, "(store_code='".$assoc['code']."')");
		}
		else {
			if( $assoc['open_hour'] < $assoc['close_hour']) {
				array_push($arr, "(store_code='".$assoc['code']."' and hour>=".$assoc['open_hour']." and hour <".$assoc['close_hour'].")");
			}
		}
	}
	// print_r($arr);
	$sq_work_hour = "";
	if ($arr){
		$sq_work_hour = join(" or ", $arr);
		$sq_work_hour = " and (".$sq_work_hour .")";
	}
	return $sq_work_hour;

}
function Result2Json4Curve($rs, $from, $to,  $viewby, $format='json') {
	global $thistime;
	global $msg;
	$s1 = microtime(true);

    $arr_cat = makeCategory($from, $to, $viewby);
    // print_r($arr_cat);
	$arr_result =  array();
	$arr_rs = array();
	$arr_label = array();
	while($assoc = mysqli_fetch_assoc($rs)) {
		if ($viewby=='month' || $viewby=='monthly'){
			$assoc['hour'] = 0;
			$assoc['min'] = 0; 
			$assoc['day'] = 1;
		}
		else if ($viewby=='day' || $viewby=='daily'){
			$assoc['hour'] = 0;
			$assoc['min'] = 0; 
		}
		else if ($viewby=='hour' || $viewby=='hourly'){
			$assoc['min'] = 0; 
		}

		$datetime = date($arr_cat['dateformat'], mktime($assoc['hour'], $assoc['min'], 0, $assoc['month'], $assoc['day'], $assoc['year']));
		$arr_rs[$assoc['counter_label']][$datetime] = $assoc['sum'];

		if(!in_array($assoc['counter_label'], $arr_label)) {
			array_push($arr_label, $assoc['counter_label']);
		}		
	}
	if (!$arr_label){
		$arr_label= [0,];
	}
    // print_r($arr_rs);
	for ($i=0; $i<sizeof($arr_label); $i++) {
		$arr_result['data'][$i]['name'] = isset($msg[strtolower($arr_label[$i])]) && $msg[strtolower($arr_label[$i])] ? $msg[strtolower($arr_label[$i])] : $arr_label[$i]  ;
        for ($j=0; $j<$arr_cat['duration']; $j++){
            if ($arr_cat['timestamp'][$j] > $thistime){
                $arr_result['data'][$i]['data'][$j] = null;
            }
            else if( !isset($arr_rs[$arr_label[$i]][$arr_cat['datetime'][$j]]) || !$arr_rs[$arr_label[$i]][$arr_cat['datetime'][$j]]) {
                $arr_result['data'][$i]['data'][$j] = 0;
            }
            else {
                $arr_result['data'][$i]['data'][$j] = $arr_rs[$arr_label[$i]][$arr_cat['datetime'][$j]];
            }
        }
	}
	
    $arr_result['category']['timestamps'] = $arr_cat['timestamp'];
    $arr_result['category']['datetimes'] = $arr_cat['datetime'];
	$arr_result['title']['chart_title'] = $msg['footfall'];
    $arr_result['time'] = round(microtime(true)-$s1,4);

    unset($arr_rs);
	unset($arr_label);
	if($format == 'array'){
		return $arr_result;
	}
	$json_str = json_encode($arr_result, JSON_NUMERIC_CHECK);
    unset($arr_result);
	return $json_str;
}

function age_query_string(){
	$str = '';
	for($i=0; $i<100; $i++) {
		if($i>0) {
			$str .= ", ";
		}
		$str .= "sum(substring_index(substring_index(age, ',', ".($i+1)."),',',-1))";
		if($i<99) {
			$str .= ",','";
		}
	}
	$str  = "concat(".$str.") as age";
    return $str;
}

function gender_query_string(){
    $str = "sum(substring_index(substring_index(gender, ',', 1),',',-1)), ',', sum(substring_index(substring_index(gender, ',', 2),',',-1))" ;
	$str = "concat(".$str.") as gender";
    return $str;
}

function card($rs, $from, $to,  $viewby, $format='json') {



}

function card_small($title, $badge, $value, $arr_line) {
	$str = 	'<div class="col-12 col-md-6 col-xl d-flex">'.
		'<div class="card flex-fill">'.
			'<div class="card-body py-4">'.
				'<div class="float-right text-info">'.$date_tag.'</div>'.
				'<h4 class="mb-2">'.$title.'</h4>'.
				'<div class="mb-1"><strong>'.$value.'</strong></div>';
	for ($i=0; $i<sizeof($arr_line); $i++) {
		$str .= '<div class="float-right">'.$arr_line[$i]['val'].'</div><div>'.$arr_line[0]['key'].'</div>';
	}
	$str .= '</div>'.
		'</div>'.
	'</div>';
	return $str;
}

// $viewby = 'day';
// $time_ref= '2021-05-15~2021-12-31';

// $arr_sq = getViewByQuery($time_ref, $viewby, $square_code=0, $store_code=0);
// $arr_cat = makeCategory($arr_sq['from'], $arr_sq['to'], $viewby);

// $sq = "select year, month, day, wday, hour, min, counter_name, counter_label, sum(counter_val) as sum from cnt_demo.count_tenmin where timestamp >=".$arr_cat['ts_from']."  and timestamp < ".$arr_cat['ts_to']." and counter_label !='none' ".$arr_sq['g_sq'].", counter_label ";


// print $sq;
// $rs = mysqli_query($connect0, $sq);


// print "<pre>";
// print_r($rs);
// $yy = Result2Json4Curve($rs, $arr_sq['from'], $arr_sq['to'],  $viewby, $format='json');
// print_r($yy);
// print "</pre>";

function queryLang($page, $varstr){
	global $DB_CUSTOM;
	global $connect0;
	$sq = "select eng, kor, chi from ".$DB_CUSTOM['language']." where varstr='".$varstr."'";
	$rs = mysqli_query($connect0, $sq);
	$assoc = mysqli_fetch_assoc($rs);
	if (isset($assoc[$_COOKIE['selected_language']])) {
		return $assoc[$_COOKIE['selected_language']];
	}
	return '{'.$varstr.'}';

}

function updateLang($page, $varstr, $val) {
	global $DB_CUSTOM;
	global $connect0;
	$sq = "select pk, eng, kor, chi from ".$DB_CUSTOM['language']." where page='".$page."' and varstr='".$varstr."'";
	$rs = mysqli_query($connect0, $sq);
	$assoc = mysqli_fetch_assoc($rs);
	if ($assoc) {
		$sq = "update ".$DB_CUSTOM['language']." set ".$_COOKIE['selected_language']."= '".$val."' where pk=".$assoc['pk'];
	}
	else {
		$sq = "insert into ".$DB_CUSTOM['language']."(page, varstr, ".$_COOKIE['selected_language'] .") values('".$page."', '".$varstr."', '".$val."') ";
	}
	$arr['rs'] = mysqli_query($connect0, $sq);
	$arr['sql'] = $sq;

	return $arr;
}
function queryWebConfig($page, $frame='', $arr_cfg=[]){
	global $DB_CUSTOM;
	global $connect0;

	$sq = "select page, frame, depth, body, flag from ".$DB_CUSTOM['web_config']." where page='".$page."' ";
	if ($frame) {
		$sq .= " and frame='".$frame."' ";
	}
	$sq .= " order by depth asc ";
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	while ($assoc = mysqli_fetch_assoc($rs)){
		$arr_cfg[$assoc['depth']] = json_decode($assoc['body'], true); // true : array, false: stdObject
		$arr_cfg[$assoc['depth']]['page']  = $assoc['page'];
		$arr_cfg[$assoc['depth']]['frame'] = $assoc['frame'];
		$arr_cfg[$assoc['depth']]['body']  = $assoc['body'];
		$arr_cfg[$assoc['depth']]['flag']  = $assoc['flag'];
	}

	// print_r($arr_cfg);
	for($i=0; $i<sizeof($arr_cfg); $i++) {
		$arr_cfg[$i]['sq_label'] = '';
		if (!isset($arr_cfg[$i]['labels'])) {
			continue;
		}
		$arr = array();
		foreach($arr_cfg[$i]['labels'] as $label){
			array_push($arr, "counter_label='".trim($label)."'" );
		}
		$arr_cfg[$i]['sq_label'] = join (" or ", $arr);
		if($arr_cfg[$i]['sq_label']) {
			$arr_cfg[$i]['sq_label'] = " and (".$arr_cfg[$i]['sq_label'] .")";
		}
	}
	$arr_cfg['code'] = $arr_cfg ? 1: 0;
	return $arr_cfg;
}


function queryWebConfig3($page, $frame, $arr_cfg){
	global $DB_CUSTOM;
	global $connect0;

	$sq = "select page, frame, depth, body, flag from ".$DB_CUSTOM['web_config']." where page='".$page."' ";
	if ($frame) {
		$sq .= " and frame='".$frame."' ";
	}
	$sq .= " order by depth asc ";
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	while ($assoc = mysqli_fetch_assoc($rs)){
		$arr_cfg[$assoc['depth']] = json_decode($assoc['body'], true); // true : array, false: stdObject
		$arr_cfg[$assoc['depth']]['page']  = $assoc['page'];
		$arr_cfg[$assoc['depth']]['frame'] = $assoc['frame'];
		$arr_cfg[$assoc['depth']]['body'] = $assoc['body'];
		$arr_cfg[$assoc['depth']]['flag']  = $assoc['flag'];
	}
	
	for($i=0; $i<sizeof($arr_cfg); $i++) {
		$ex = explode(",", $arr_cfg[$i]['label']);
		$arr_cfg[$i]['sq_label'] = '';
		foreach($ex as $label){
			if (!trim($label)) {
				continue;
			}
			if($arr_cfg[$i]['sq_label']) {
				$arr_cfg[$i]['sq_label'] .= " or ";
			}
			$arr_cfg[$i]['sq_label'] .= "counter_label='".trim($label)."'";
		}
		if($arr_cfg[$i]['sq_label']) {
			$arr_cfg[$i]['sq_label'] = " and (".$arr_cfg[$i]['sq_label'] .")";
		}
	}
	$arr_cfg['code'] = $arr_cfg ? 1: 0;
	return $arr_cfg;
}


function queryWebConfig2($page, $frame, $arr_cfg){
	global $DB_CUSTOM;
	global $connect0;

	$sq = "select page, frame, depth, body, flag from ".$DB_CUSTOM['web_config']." where page='".$page."' ";
	if ($frame) {
		$sq .= " and frame='".$frame."' ";
	}
	$sq .= " order by depth asc ";
	print $sq;
	$rs = mysqli_query($connect0, $sq);
	while ($assoc = mysqli_fetch_assoc($rs)){
		$arr_cfg[$assoc['depth']] = json_decode($assoc['body'], true); // true : array, false: stdObject
		$arr_cfg[$assoc['depth']]['page']  = $assoc['page'];
		$arr_cfg[$assoc['depth']]['frame'] = $assoc['frame'];
		$arr_cfg[$assoc['depth']]['body'] = $assoc['body'];
		$arr_cfg[$assoc['depth']]['flag']  = $assoc['flag'];
	}
	
	for($i=0; $i<sizeof($arr_cfg); $i++) {
		$ex = explode(",", $arr_cfg[$i]['label']);
		foreach($ex as $label){
			if (!trim($label)) {
				continue;
			}
			if($arr_cfg[$i]['sq_label']) {
				$arr_cfg[$i]['sq_label'] .= " or ";
			}
			$arr_cfg[$i]['sq_label'] .= "counter_label='".trim($label)."'";
		}
		if($arr_cfg[$i]['sq_label']) {
			$arr_cfg[$i]['sq_label'] = " and (".$arr_cfg[$i]['sq_label'] .")";
		}
	}
	$arr_cfg['code'] = $arr_cfg ? 1: 0;
	return $arr_cfg;
}

function updateWebConfig($page, $frame, $depth, $arr_body, $flag='n') {
	global $DB_CUSTOM;
	global $connect0;
	$arr = array('rs'=>0, 'sql'=>'');
	$json_str = is_array($arr_body) ? json_encode($arr_body, JSON_NUMERIC_CHECK) : $arr_body;
	// $json_str = is_array($arr_body) ? json_encode($arr_body) : addslashes($arr_body);
	$sq = "select pk  from ".$DB_CUSTOM['web_config']." where page='".$page."' and frame='".$frame."' and depth=".$depth." ";
	$rs = mysqli_query($connect0, $sq);
	if ($rs->num_rows){
		$sq = "update ".$DB_CUSTOM['web_config']." set body= '".addslashes($json_str)."',  flag='".$flag."'  where page='".$page."' and frame='".$frame."' and depth=".$depth." ";
	}
	else {
		$sq = "insert into ".$DB_CUSTOM['web_config']."( page, frame, depth, body, flag) values('".$page."', '".$frame."', ".$depth.", '".addslashes($json_str)."', '".$flag."')";
	}
	// print $sq;
	$arr['rs'] = mysqli_query($connect0, $sq);
	$arr['sql'] = $sq;

	return $arr;
}


// function curve_chart($title, $arr_line,$arr_color, $arr_label, $arr_score, $str_option)
// {
// 	$str_js ='var config={'.
// 		'type:"line",'.
// 		'data:{'.
// 			'labels:[],datasets:[]'.
// 		'},'.
// 		'options:{'.
// 			'responsive:true,'.
// 			'title:{'.
// 				'display:false,'.
// 				'text:""'.
// 			'},'.
// 			'tooltips:{enabled:true, mode:\'index\',intersect:false},'.
// 			'legend:{position:"top",display:true},'.
// 			'hover: {intersect: true},'.
// 			'scales:{'.
// 				'xAxes:[{gridLines:{display:true}}],'.
// 				'yAxes:[{display:true, ticks:{beginAtZero:false}}]'.
// 			'},'.
// 		'}'.
// 	'};';

// 	if($title) {
// 		$str_js .= 'config.options.title.text = "'.$title.'";config.options.title.display = true;';
// 	}
// 	$pointRadius = 0.5;
// 	if($str_option) {
// 		$ex_option = explode(';',$str_option);
// 		for($i=0; $i<sizeof($ex_option); $i++) {
// 			if(!strncmp(trim($ex_option[$i]),'height',6)) {
// 				$height = $ex_option[$i];
// 			}
// 			else if(!strncmp(trim($ex_option[$i]),'pointRadius',11)){
// 				$pointRadius = trim(array_pop(explode("=",$ex_option[$i])));
// 			}
// 			else if(trim($ex_option[$i])){
// 				$str_js .= 'config.options.'.trim($ex_option[$i]).';';
// 			}
// 		}
		
// 	}
	
	
// 	$chart_inst = 'Chart'.time().rand(0,9).rand(0,9).rand(0,9);
	
// 	for($i=0; $i<sizeof($arr_line); $i++) {
// 		$ex_color = explode('/',$arr_color[$i]);
// 		$fill = 'true';
// 		if(!$ex_color[1]) {
// 			$ex_color[1] = $ex_color[0];
// 			$fill = 'false';
// 		}
// 		$str_js .= 'config.data.datasets.push({label: "'.$arr_line[$i].'", backgroundColor: "'.$ex_color[1].'", borderColor: "'.$ex_color[0].'", data: [], pointRadius: '.$pointRadius.', lineTension: 0.5, fill:'.$fill.'});';
// 	}
// 	for($i=0; $i<sizeof($arr_label); $i++) {
// 		if(is_array($arr_label[$i])) {
// 			$str_js .='config.data.labels.push(["'.$arr_label[$i][0].'","'.$arr_label[$i][1].'"]);';
// 		}
// 		else {
// 			$str_js .='config.data.labels.push("'.$arr_label[$i].'");';
// 		}
		
// 		for($j=0; $j<sizeof($arr_line); $j++) {
// 			$str_js .= 'config.data.datasets['.$j.'].data.push('.$arr_score[$j][$i].');';
// 		}
// 	}
// 	$str_js .= 'new Chart(document.getElementById("'.$chart_inst.'").getContext("2d"), config);';
// 	$str_js = '<canvas id="'.$chart_inst.'" '.$height.' ></canvas><script>'.$str_js.'</script>';
// 	return $str_js;

// }

// function gender_graph($title, $male, $female, $option) 
// {
// //	$path = '/css/gender_graph/';
// 	require $_SERVER['DOCUMENT_ROOT']."/libs/gender_per_png.php";
// 	if($male+$female) {
// 		$male_per = $male / ($male+$female) *100;
// 		$female_per =$female / ($male+$female)*100;
// 	}
// 	else {
// 		$male_per =0;
// 		$female_per = 0;
// 	}
	
// 	$male_e = number_format($male_per,0);
// 	$female_e = number_format($female_per,0);
	
// 	$male_e = $MALE[$male_e];
// 	$female_e = $FEMALE[$female_e];
	
// 	$rand_instance = time().rand(0,9).rand(0,9).rand(0,9);
// 	$imm_instance = "imm".$rand_instance;
// 	$imf_instance = "imf".$rand_instance;
// 	$ttm_instance = "ttm".$rand_instance;
// 	$ttf_instance = "ttf".$rand_instance;

// 	if($option) {
// 		$ex_option =  explode(';',$option);
// 		for($i=0; $i<sizeof($ex_option); $i++) {
// 			list($_key, $_val) = explode('=',$ex_option[$i]);
// 			if(trim($_key) =='width') {
// 				$width = trim(str_replace('px','',$_val));
// 			}
// 			else if(trim($_key) =='height') {
// 				$height = trim(str_replace('px','',$_val));
// 			}
// 			else if(trim($_key) =='displayvalue') {
// 				$dis_value = 1;
// 			}
// 		}
// 	}
	 
// 	if(!$width) {
// 		$width= 120;
// 	}
// 	if(!$height) {
// 		$height= 100;
// 	}
// 	$width = ceil($width/2);
	
// 	$str =	'<tr>'.
// 				'<th colspan="2" style="text-align:center; font-size:15px; font-weight:700;" >'.$title.'</th>'.
// 			'</tr>'.
// 			'<tr>'.
// 				'<th width="'.$width.'px" style="text-align:center; font-size:15px; font-weight:700; color:#2db9c2">'.number_format($male_per,2).'%</th>'.
// 				'<th width="'.$width.'px" style="text-align:center; font-size:15px; font-weight:700; color:#ef4c3b">'.number_format($female_per,2).'%</th>'.
// 			'</tr>'.
// 			'<tr>'.
// 				'<td class="text-center"><img src="'.$male_e.'" height="'.$height.'px" width="'.number_format($height/2.2,0).'px" id="'.$imm_instance.'"><div class="tooltips" id="'.$ttm_instance.'">0</div></img></td>'.
// 				'<td class="text-center"><img src="'.$female_e.'" height="'.$height.'px" width="'.number_format($height/2.2,0).'px" id="'.$imf_instance.'"><div class="tooltips" id="'.$ttf_instance.'">0</div></img></td>'.
// 			'</tr>'.
// 			'<tr>'.
// 				'<td class="text-center">'.msg('male').'</td>'.
// 				'<td class="text-center">'.msg('female').'</td>'.
// 			'</tr>';
	
// 	if($dis_value) {
// 		$str .=	''.
// 			'<tr>'.
// 				'<td class="text-center">'.number_format($male).'</td>'.
// 				'<td class="text-center">'.number_format($female).'</td>'.
// 			'</tr>';
// 	}
			
// 	$str = '<table>'.$str.'</table>';
	

// 	$str .= '

// 		<script>
// 			var ttm_'.$rand_instance.' = document.getElementById("'.$ttm_instance.'");
// 			var ttf_'.$rand_instance.' = document.getElementById("'.$ttf_instance.'");
// 			var imm_'.$rand_instance.' = document.getElementById("'.$imm_instance.'");
// 			var imf_'.$rand_instance.' = document.getElementById("'.$imf_instance.'");
			
			
// 			imm'.$rand_instance.'.onmousemove = function(ev) {
// 				var x = ev.layerX;
// 				var y = ev.layerY;
// 				ttm'.$rand_instance.'.style.display = "block";
// 				updateTooltip(ttm'.$rand_instance.', x, y, "'.number_format($male).'");
// 			};
// 			imf'.$rand_instance.'.onmousemove = function(ev) {
// 				var x = ev.layerX;
// 				var y = ev.layerY;
// 				ttf'.$rand_instance.'.style.display = "block";
// 				updateTooltip(ttf'.$rand_instance.', x, y, "'.number_format($female).'");
// 			};			
// 			imm'.$rand_instance.'.onmouseout = function() {
// 				ttm'.$rand_instance.'.style.display = "none";
// 			};
// 			imf'.$rand_instance.'.onmouseout = function() {
// 				ttf'.$rand_instance.'.style.display = "none";
// 			};
			
// 		</script>';
// 	return $str;
// }




// function bar_chart($title, $arr_bar, $arr_color, $arr_label, $arr_score, $str_option)
// {
// 	$str_js ='var config={'.
// 		'type:"bar",'.
// 		'data:{'.
// 			'labels:[],datasets:[]'.
// 		'},'.
// 		'options:{'.
// 			'responsive:true,'.
// 			'title:{'.
// 				'display:false,'.
// 				'text:""'.
// 			'},'.
// 			'tooltips: {mode:\'index\',intersect:false},'.
// 			'legend:{position:"top",display:true},'.
// 			'tooltips: {intersect:true},'.
// 			'scales:{'.
// 				'xAxes:[{stacked:false,gridLines:{display:true}}],'.
// 				'yAxes:[{stacked:false,ticks:{beginAtZero:false}}]'.
// 			'},'.
// 		'}'.
// 	'};';

// 	if($title) {
// 		$str_js .= 'config.options.title.text = "'.$title.'";config.options.title.display = true;';
// 	}
// 	if($str_option) {
// 		$ex_option = explode(';',$str_option);
// 		for($i=0; $i<sizeof($ex_option); $i++) {
// 			if(!strncmp(trim($ex_option[$i]),'height',6)) {
// 				$height = $ex_option[$i];
// 			}
// 			else if(trim($ex_option[$i])){
// 				$str_js .= 'config.options.'.trim($ex_option[$i]).';';
// 			}
// 		}
		
// 	}
	
// 	$chart_inst = 'Chart'.time().rand(0,9).rand(0,9).rand(0,9);	
// 	for($i=0; $i<sizeof($arr_bar); $i++) {
// 		$ex_color = explode('/',$arr_color[$i]);
// 		$fill = 'true';
// 		if(!$ex_color[1]) {
// 			$ex_color[1] = $ex_color[0];
// 			$fill = 'false';
// 		}
// 		$str_js .= 'config.data.datasets.push({label: "'.$arr_bar[$i].'", backgroundColor: "'.$ex_color[1].'", borderColor: "'.$ex_color[0].'", data: [], pointRadius: 0.5,lineTension: 0.5, fill:'.$fill.'});';
// 	}
// 	for($i=0; $i<sizeof($arr_label); $i++) {
// 		if(is_array($arr_label[$i])) {
// 			$str_js .='config.data.labels.push(["'.$arr_label[$i][0].'","'.$arr_label[$i][1].'"]);';
// 		}
// 		else {
// 			$str_js .='config.data.labels.push("'.$arr_label[$i].'");';
// 		}
// 		for($j=0; $j<sizeof($arr_bar); $j++) {
// 			$str_js .= 'config.data.datasets['.$j.'].data.push('.$arr_score[$j][$i].');';
// 		}
// 	}
// 	$str_js .= 'new Chart(document.getElementById("'.$chart_inst.'").getContext("2d"), config);';
// 	$str_js = '<canvas id="'.$chart_inst.'" '.$height.' ></canvas><script>'.$str_js.'</script>';
// 	return $str_js;

// }





// function age_bar_chart($title, $arr_label, $arr_score, $str_option)
// {
// 	$str_js = ''.
// 		'var config = {'.
// 			'type: "bar",'.
// 			'data: {'.
// 				'labels:[],'.
// 				'datasets:['.
// 					'{label: "", backgroundColor: "rgb(201, 203, 207)", borderColor: "rgb(201, 203, 207)", data: [], fill: false},'.
// 					'{label: "", backgroundColor: "rgb(255, 99, 132)", borderColor: "rgb(255, 99, 132)", data: [], fill: false},'.
// 				']'.
// 			'},'.
// 			'options:{'.
// 				'responsive: true,'.
// 				'title:{display:true,text:"'.$title.'",},'.
// 				'legend:{position:"top", display: false,},'.
// 				'tooltips:{intersect:false},'.
// 				'hover:{intersect: true},'.
// 				'plugins:{'.
// 					'filler: {propagate: false}'.
// 				'},'.
// 				'scales:{'.
// 					'xAxes: [{stacked: true,}],'.
// 					'yAxes: [{'.
// 						'ticks:{ beginAtZero:false },'.
// 						'display : true,'.
// 						'borderDash:[5,5],'.
// 						'gridLines:{color: "rgba(0,0,0,0)",fontColor: "#fff"},'.
// 						'stacked: true,'.
// 					'}]'.
// 				'}'.
// 			'}'.
// 		'};';

// 	if($str_option) {
// 		$ex_option = explode(';',$str_option);
// 		for($i=0; $i<sizeof($ex_option); $i++) {
// 			if(!strncmp($ex_option[$i],'height',6)) {
// 				$height = $ex_option[$i];
// 			}
// 			else if(trim($ex_option[$i])){
// 				$str_js .= 'config.options.'.trim($ex_option[$i]).';';
// 			}
// 		}
// 	}
	
// 	$chart_inst = 'Chart'.time().rand(0,9).rand(0,9).rand(0,9);
// 	$max = max(array_merge($arr_score));
// 	$sum = array_sum($arr_score);
// 	for($i=0; $i<sizeof($arr_label); $i++) {
// 		$str_js .='config.data.labels.push("'.$arr_label[$i].'");';
// 		if($sum) {
// 			$arr_score_per[$i] = number_format($arr_score[$i]/$sum *100,2);
// 		}
// 		if($arr_score[$i] == $max) {
// 			$str_js .= 'config.data.datasets[0].data.push("NaN");';
// 			$str_js .= 'config.data.datasets[1].data.push('.$arr_score_per[$i].');';
// 		}
// 		else {
// 			$str_js .= 'config.data.datasets[0].data.push('.$arr_score_per[$i].');';
// 			$str_js .= 'config.data.datasets[1].data.push("NaN");';
// 		}
// 	}
// 	$str_js .= 'new Chart(document.getElementById("'.$chart_inst.'").getContext("2d"), config);';
// 	$str_js = '<canvas id="'.$chart_inst.'" '.$height.' ></canvas><script>'.$str_js.'</script>';
// 	return $str_js;
	
// }

?>