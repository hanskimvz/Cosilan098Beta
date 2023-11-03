<?PHP
date_default_timezone_set ( "UTC" );
$thistime = time()+8*3600;
$today = date("Y-m-d", $thistime);

//CONNECT DB
include  $_SERVER['DOCUMENT_ROOT'].'/libs/dbconnect.php';

// $arr_wcolor = array (
// 	'red' => 'rgb(255, 99, 132)', 
// 	'orange' => 'rgb(255, 159, 64)', 
// 	'yellow' => 'rgb(255, 205, 86)', 
// 	'green' => 'rgb(75, 192, 192)', 
// 	'blue' => 'rgb(54, 162, 235)', 
// 	'purple'=> 'rgb(153, 102, 255)', 
// 	'grey'=> 'rgb(201, 203, 207)', 
// 	'black'=> 'rgb(60, 60, 60)',
// 	'white'=> 'rgb(255,255,255)',
// );

function logincheck($t=1)
{
	global $LOGIN_PAGE;
	global $connect0;
	global $thistime;
	global $DB_COMMON;
	if(!isset($_SESSION['logID']) || !$_SESSION['logID'] || !$_COOKIE['userseq'] || (md5($_SESSION['logID']."test") != $_COOKIE['userseq']) )	{
		if($t == 1) {
			echo "<script>location.href=('".$LOGIN_PAGE."')</script>";
			exit;
		}
		else return false;
	}
	else {
		$sq = "select pk from ".$DB_COMMON['access_log']." where ID='".$_SESSION['logID']."' and PHPSESSID = '".$_COOKIE['PHPSESSID']."' order by pk desc limit 1";
		$rs = mysqli_query($connect0, $sq);
		$row = mysqli_fetch_row($rs);
		if ($row) {
			$sq = "update ".$DB_COMMON['access_log']." set last_session_time = '".date('Y-m-d H:i:s', $thistime)."' where pk = ".$row[0];
			$rs = mysqli_query($connect0, $sq);
		}
//		($_COOKIE['PHPSESSID'])
		return true;
	}
}

function access_log()
{
	global $connect0;
	$str = $_SERVER['DOCUMENT_URI'].":";
	while (list($key,$val) = each($_GET)) {
		$str .= "".$key."=".$val."\n";
	}
	$str = addslashes($str);
	$sq = "insert ".$DB_COMMON['access_log']."(IP_addr, regdate, ID, act) values('".$_SERVER['REMOTE_ADDR']."', '".date('Y-m-d H:i:s', time()+3600*8)."', '".$_SESSION['logID']."', '".$str."')";
	mysqli_query($connect0, $sq);
}

function option_db_str($selected_db_name, $excluding_db) {
	global $connect0;
	$arr_ex = array("","information_schema", "mysql", "performance_schema","test");
	$ex_exc_db = explode(",", $excluding_db);
	foreach($ex_exc_db as $db ){
		if(trim($db)) {
			array_push($arr_ex, trim($db));
		}
	}	
	$sq = "show databases";
	$rs = mysqli_query($connect0, $sq);
	$option_db_name= '';
	while($row=mysqli_fetch_row($rs)) {
		if(in_array($row[0], $arr_ex)) {
			continue;
		}
		$option_db_name .= $row[0] == $selected_db_name ? 
			'<option value="'.$row[0].'" selected>'.$row[0].'</option>' : '<option value="'.$row[0].'">'.$row[0].'</option>';
	}
	return $option_db_name;
}

function Query2Array($connect, $sq)
{
	$arr_result = array();
	$arr_field = array();
	if(	$rs = mysqli_query($connect, $sq)) {
		$cols = $rs ->field_count;
		$rows = $rs->num_rows;
		if(!$rows) {
//			return 0;
		}
	
		for ($i=0; $i<$cols; $i++) {
			$fields = mysqli_fetch_field($rs);
			$arr_field[$i] = ($fields->name);
		}
		
		for($i = 0; $i<$rows; $i++) {
			$row = mysqli_fetch_row($rs);
			for($j = 0; $j < $cols; $j++) {
				$arr_result[$i][$arr_field[$j]] = $row[$j];
			}
		}
	}
	return $arr_result;
}

function Result2Array($rs)
{
	$arr_result = array();
	$arr_field = array();
	$cols =  $rs ->field_count;
	
	$i=0;
	while($fields = mysqli_fetch_field($rs)) {
		$arr_field[$i] = ($fields->name);
		$i++;
	}
		
	$i = 0;
	while($row = mysqli_fetch_row($rs)) {
		for($j = 0; $j < $cols; $j++) {
			$arr_result[$i][$arr_field[$j]] = $row[$j];
		}
		$i++;
	}
	return $arr_result;
}


function msg($str)
{
	global $connect;
	global $this_page;
	global $DB_CUSTOM;
	if(!$connect) {
		return $str;
	}
//	$tmp = str_replace(" ","",$str);
//	$tmp = strtoupper($tmp);

	$sq = "select ".$_COOKIE['selected_language']." from ".$DB_COUSTOM['language']." where  varstr = '".trim($str)."' and page='".$this_page."'";
	$rs = mysqli_query($connect, $sq); 
	if(!($rs->num_rows)) {
		return '{'.$str.'}';
	}
	else {
		$row = mysqli_fetch_row($rs);
		return $row[0];
	}

}

function q_language($page='common') {
	global $connect;
	global $DB_CUSTOM;
	global $DB_COMMON;
	$msg=array();
	if (!isset($_COOKIE['selected_language'])) {
		$_COOKIE['selected_language'] = 'chi';
	}
	$sq = "select varstr, ".$_COOKIE['selected_language']." from ".$DB_COMMON['language']." where page='".$page."' ";
//	print $sq;
	$rs = mysqli_query($connect, $sq); 
	if($rs->num_rows) {
		while ($row = mysqli_fetch_row($rs)) {
			$row[0] = preg_replace("/[!#$%^&*:;.,()?+=\/ ]/","", $row[0]);
			$msg[strtolower($row[0])] = $row[1];
		}
	}
	$sq = "select varstr, ".$_COOKIE['selected_language']." from ".$DB_CUSTOM['language']." where page='".$page."' ";
	// print $sq;
	$rs = mysqli_query($connect, $sq); 
	if($rs->num_rows) {
		while ($row = mysqli_fetch_row($rs)) {
			$row[0] = preg_replace("/[!#$%^&*:;.,()?+=\/ ]/","", $row[0]);
			$msg[strtolower($row[0])] = $row[1];
		}
	}
	return $msg;
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function str_in_array($arr, $line){
	// search  elements in array exist in $line
	foreach($arr as $str){
		// print $str;
		if (startsWith($line, $str)) {
			return true;
		}
	}
	return false;
}
function random_appha($len)
{
	$str ='';
	for($i=0;$i<100;$i++) {
		$asc = rand()%122;
		if( (($asc>=ord('0')) and ($asc<=ord('9'))) or (($asc>=ord('A')) and ($asc<=ord('Z'))) or (($asc>=ord('a')) and ($asc<=ord('z')))) {
			$str .= chr($asc);
		}
	}
	
	$str = rand(0,19);
	return md5($str);
}

function print_arr($arr)
{
	if(!$arr) {
		$arr= $_POST;
	}
	print "<pre>";
	print_r($arr);
	print "</pre>";
}

function draw_heatmap($point_data, $max, $scale=10, $img) {
	$rand_instance = time().rand(0,9).rand(0,9).rand(0,9);
	$hm_instance = "heatmap".$rand_instance;
	$tt_instance = "tooltip".$rand_instance;

	$img_x = ceil(80*$scale);
	$img_y = ceil(45*$scale);
			
	$str  = '<div id="'.$hm_instance.'" style="width:'.$img_x.'px; height: '.$img_y.'px;" ><div class="tooltips" id="'.$tt_instance.'"></div></div>';
	$str .= '
		<script>
			var width = '.$img_x.';
			var height = '.$img_y.';

			var heatmapInstance'.$rand_instance.' = h337.create({
				container:'.$hm_instance.',
				radius: height / 10,
				maxOpacity: .7
			});

			var hmc'.$rand_instance.' = document.getElementById("'.$hm_instance.'");
			// var tooltip'.$rand_instance.' = document.getElementById("'.$tt_instance.'");
			';
			
	if($img) {
		$str .='
			hmc'.$rand_instance.'.style.background = "url('.$img.') no-repeat";
			hmc'.$rand_instance.'.style.backgroundSize = width + "px "+ height + "px";';
	}
	$str .= '
			hmc'.$rand_instance.'.style.height = height + "px";
			var data = { 
				max: '.$max.', 
				data: ['.$point_data.'] 
			};
			heatmapInstance'.$rand_instance.'.setData(data);';
	
	// $str .= '
	// 		hmc'.$rand_instance.'.onmousemove = function(ev) {
	// 			var x = ev.layerX;
	// 			var y = ev.layerY;
	// 			var value = heatmapInstance'.$rand_instance.'.getValueAt({
	// 				x: x,
	// 				y: y
	// 			});
	// 			tooltip'.$rand_instance.'.style.display = "block";
	// 			updateTooltip(tooltip'.$rand_instance.', x, y, value);
	// 		};
	// 		hmc'.$rand_instance.'.onmouseout = function() {
	// 			tooltip'.$rand_instance.'.style.display = "none";
	// 		};';
	$str .= '	
		</script>';
	
	return $str;
}

function heatmap_from_query($connect, $sq, $scale, $img) 
{
	$val = array();
//	print $sq;
	$rs = mysqli_query($connect, $sq);

	$MAX = 0;
	$img_x = 80*$scale;
	$img_y = 45*$scale;

	$xp =  $img_x/80;
	$yp =  $img_y/45;
	 
	$xp=$scale;
	$yp=$scale;
	
	while($row = mysqli_fetch_assoc($rs)) {
		$line = explode("\r\n",$row['body_csv']);
		for($y =0; $y<45; $y++) {
			$col = explode(",",$line[$y]);
			for($x=0; $x<80; $x++){
				$col[$x] = trim($col[$x]);
				$val[$x][$y] += (int)$col[$x];
				if($val[$x][$y] > $MAX) {
					$MAX = $val[$x][$y];
				}
			}
		}	
	}
	
	$str = '';
	for($y = 0; $y< 45; $y++) {
		for($x =0; $x<80; $x++) {
			if($val[$x][$y]) {
				$str .= "{x:".($x*$xp).", y:".($y*$yp).", value:".$val[$x][$y]." },";
			}
		}
	}
 	return draw_heatmap( $str, $MAX, $scale, $img);
}

function Query2Table($connect, $sq, $str= 'class="table table-striped table-bordered table-hover no-margin"')
{	
	$TABLE_HEAD = '';
	$TABLE_BODY = '';

	$rs = mysqli_query($connect, $sq);
	$cols =  $rs ->field_count;

	while($fields = mysqli_fetch_field($rs)) {
		$TABLE_HEAD .= '<th>'.($fields->name).'</th>';
	}
	$TABLE_HEAD = '<thead><tr>'.$TABLE_HEAD.'</tr></thead>';	
	
	while($row = mysqli_fetch_row($rs)){
		$TABLE_BODY .= '<tr>';
		for($i=0; $i<$cols; $i++) {
			if(!strncmp($row[$i],"data:image",10)) {
				$row[$i] = "<img src=".$row[$i]." height=\"100px\">";
			}
			$TABLE_BODY .= '<td>'.$row[$i].'</td>';
		}
		$TABLE_BODY .= '</tr>';	
	}
	$TABLE_BODY = '<tbody>'.$TABLE_BODY.'</tbody>';
	$TABLE_BODY = '<table '.$str.'>'.$TABLE_HEAD.$TABLE_BODY.'</table>';
	
	return $TABLE_BODY;
}

function Pagination($uri, $TOTAL_RECORD, $page_no, $page_max, $theme='none')
{
	$Pagination = '';
	if(strpos($uri,'?')) {
		$uri .= '&';
	}
	else {
		$uri .= '?';
	}

	$total_page = ceil($TOTAL_RECORD / $page_max); 
	$start = ((int)$page_no < 5) ? 1 : ($page_no - 5);
	
	if($start < 1) {
		$start = 1;
	}
	$end = $start + 9;

	if($end > $total_page) {
		$end = $total_page;
		$start = ($end <= 9) ? 1 : ($end - 9); 
	}

	if($theme == "appstack") {
		for($i=$start; $i<=$end; $i++) {
			if($i == $page_no) {
				$Pagination .='<li class="page-item active"><a class="page-link"><font color="#FF0000">'.$i.'</font></a></li>';
			}
			else {
				$Pagination .='<li class="page-item"><a class="page-link" href="'.$uri.'page_no='.$i.'&page_max='.$page_max.'">'.$i.'</a></li>';
			}
		}
		$Pagination = '<nav><ul class="pagination pagination-sm">'.$Pagination.'</ul></nav>';
	}
	else {
		for($i=$start; $i<=$end; $i++) {
			if($i == $page_no) {
				$Pagination .='<li><a><font color="#FF0000">'.$i.'</font></a></li>';
			}
			else {
				$Pagination .='<li><a href="'.$uri.'page_no='.$i.'&page_max='.$page_max.'" style="cursor:pointer" onMouseOver="this.style.backgroundColor=\'orange\';" onMouseOut="this.style.backgroundColor=\'\';">'.$i.'</a></li>';
			}
		}
		$Pagination = ' <ul class="pagination pull-left">'.$Pagination.'</ul>';
	}
	
	return $Pagination;
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

// function card_dash($title,$label,$num, $percent, $color)
// {
// 	if(strpos(' '.$color,'warning')) {$color = '#fcc100';}
// 	else if(strpos(' '.$color,'danger')) {$color = '#f44455';}
// 	else if(strpos(' '.$color,'primary')) {$color = '#47bac1';}
// 	else if(strpos(' '.$color,'secondary')) {$color = '#a180da';}
// 	else if(strpos(' '.$color,'success')) {$color = '#5fc27e';}
// 	else if(strpos(' '.$color,'info')) {$color = '#5b7dff';}	
// 	else if(strpos(' '.$color,'danger')) {$color = '#f44455';}
// 	else if(strpos(' '.$color,'light')) {$color = '#f8f9fa';}
// 	else if(strpos(' '.$color,'dark')) {$color = '#354052';}		
// 	else if(strpos(' '.$color,'white')) {$color = '#fff';}

// 	if(!$color) {
// 		$color = '#5fc27e';
// 	}
// 	$str = 	'<div class="card flex-fill">'.
// 				'<div class="card-header">'.
// 					'<span class="badge float-right" style="background-color:'.$color.';">'.$label.'</span>'.
// 					'<h5 class="card-title mb-0">'.$title.'</h5>'.
// 				'</div>'.
// 				'<div class="card-body my-2">'.
// 					'<div class="row d-flex align-items-center mb-4">'.
// 						'<div class="col-8"><h2 class="d-flex align-items-center mb-0 font-weight-light">'.$num.'</h2></div>'.
// 						'<div class="col-4 text-right"><span class="text-muted">'.$percent.'%</span></div>'.
// 					'</div>'.
// 					'<div class="progress progress-sm shadow-sm mb-1">'.
// 						'<div class="progress-bar" role="progressbar" style="width:'.$percent.'%; background-color:'.$color.';"></div>'.
// 					'</div>'.
// 				'</div>'.
// 			'</div>';
			
// 	return $str;
// }
// function card_small($title, $date_tag, $value, $line1, $line1_val, $line2, $line2_val)
// {
// 	if(abs($line1_val)<1) {
// 		$line1_val *= 100;
// 	}
// 	if(abs($line2_val)<1) {
// 		$line2_val *= 100;
// 	}	
// 	$line1_val = ($line1_val<0) ? '<font color="FF0000">'.number_format($line1_val,2).'%</font>':'<font color="0000FF">+'.number_format($line1_val,2).'%</font>';
// 	$line2_val = ($line2_val<0) ? '<font color="FF0000">'.number_format($line2_val,2).'%</font>':'<font color="0000FF">+'.number_format($line2_val,2).'%</font>';
	
// 	$str = 	'<div class="col-12 col-md-6 col-xl d-flex">'.
// 				'<div class="card flex-fill">'.
// 					'<div class="card-body py-4">'.
// 						'<div class="float-right text-info">'.$date_tag.'</div>'.
// 						'<h4 class="mb-2">'.$title.'</h4>'.
// 						'<div class="mb-1"><strong>'.$value.'</strong></div>'.
// 						'<div class="float-right">'.$line1_val.'</div>'.
// 						'<div>'.$line1.'</div>'.
// 						'<div class="float-right">'.$line2_val.'</div>'.
// 						'<div>'.$line2.'</div>'.
// 					'</div>							'.
// 				'</div>'.
// 			'</div>';
// 	return $str;
// }

function mkTable($arr_head, $arr_score, $str_option= 'class="table table-striped table-bordered table-hover no-margin"')
{	
	$TABLE_HEAD = '';
	$TABLE_BODY = '';

	$cols = sizeof($arr_head);
	for($i=0; $i<$cols; $i++) {
		if(is_array($arr_head[$i])) {
			$TABLE_HEAD .= '<th '.$arr_head[$i][1].' '.$arr_head[$i][2].'>'.$arr_head[$i][0].'</th>';
		}
		else {
			$TABLE_HEAD .= '<th>'.$arr_head[$i].'</th>';
		}
	}
	$TABLE_HEAD = '<thead><tr>'.$TABLE_HEAD.'</tr></thead>';
	
	for($i=0; $i<sizeof($arr_score); $i++) {
		$TABLE_BODY .= '<tr>';
		for($j=0; $j<$cols; $j++) {
			if(is_array($arr_head[$j])) {
				$TABLE_BODY .= '<td '.$arr_head[$j][1].'>'.$arr_score[$i][$j].'</td>';
			}
			else {
				$TABLE_BODY .= '<td>'.$arr_score[$i][$j].'</td>';
			}
		}
		$TABLE_BODY .= '</tr>';	
	}
	$TABLE_BODY = '<tbody>'.$TABLE_BODY.'</tbody>';
	$TABLE_BODY = '<table '.$str_option.'>'.$TABLE_HEAD.$TABLE_BODY.'</table>';
	
	return $TABLE_BODY;
}

function draw_zone($zone, $snapshot, $option_str='') {
	$zone_id = 'Z'.time().rand(0,9).rand(0,9);
	$width = 800;
	$height = 450;
	
	$ex_str = explode(";", $option_str);
	for($i=0; $i<sizeof($ex_str); $i++) {
		list($_key, $_val) = explode("=", $ex_str[$i]);
		if(trim($_key) == "height") {
			$height = $_val;
		}
		else if(trim($_key) == "width") {
			$width = $_val;
		}
	}
	
	$jstr ="";
	for ($i=0; $i<sizeof($zone); $i++) {
		if(!isset($zone[$i])) {
			$zone[$i] = [];
			continue;
		}
		$P = explode(",",$zone[$i]['points']);
		if($zone[$i]['style'] == 'polygon') {
			array_push($P, $P[0]);
		}
		for($j=0; $j<sizeof($P); $j++) {
			if(!trim($P[$j])){
				continue;
			}
			list($xa, $ya) = explode(":", $P[$j]);
			$x[$j] = round(($width*(int)$xa)/65535);
			$y[$j] = round(($height*(int)$ya)/65535);
			
			if($j == 0) {
				$jstr .= 'context.beginPath(); context.moveTo('.$x[0].', '.$y[0].');';
			}
			else {
				$jstr .=  'context.lineTo('.$x[$j].', '.$y[$j].');';
			}
			
		}
		if($zone[$i]['style'] == 'polygon') {
			$jstr .= 'context.lineWidth = 0;';
			$jstr .= 'context.closePath();';
			if($zone[$i]['type'] == 'nondetection') {
				$jstr .= 'context.fillStyle = "rgba(100,100,100,0.6)";';
			}
			else {
				$jstr .= 'context.fillStyle = "rgba('.trim($zone[$i]['color']).',0.3)";';
			}
			$jstr .= 'context.fill();';
		}
		else {
			$jstr .= 'context.lineWidth = 3;';
			$jstr .= 'context.strokeStyle = "rgba('.trim($zone[$i]['color']).',0.5)";';
			$jstr .= 'context.stroke();';
		}
		
		$jstr .= 'context.font = "14pt Calibri";';
		$jstr .= 'context.fillStyle = "rgba('.trim($zone[$i]['color']).',0.8)";';
		$jstr .= 'context.fillText("'.trim($zone[$i]['name']).'", '.$x[0].', '.($y[0]-10).');';
	}
	return '<canvas id="'.$zone_id.'" width="'.$width.'" height="'.$height.'"></canvas>
			<script>
				z = document.getElementById("'.$zone_id.'");
				z.style.background = \'url("'.$snapshot.'") no-repeat\';
				z.style.backgroundSize = "'.$width.'px '.$height.'px";
				var context = z.getContext("2d");
				'.$jstr.'
			</script>';
	
}

function nav_time_slice_single($t=1)
{
	global $thistime;
	$arr_rs = array();
//	print_arr($_GET);
	if(!$_GET['refdate']) {
		$_GET['refdate'] = date('Y-m-d', $thistime);
	}
	
	$refts = strtotime($_GET['refdate']);
	
	if($_GET['date_change'] == 'prev') {
		$refts -=  3600*24;
	}
	else if($_GET['date_change'] == 'next') {
		$refts +=  3600*24;
	}
	if($refts >$thistime) {
		$refts = $thistime;
	}
	$from = strtotime(date('Y-m-d',$refts));;
	$to = strtotime(date('Y-m-d  23:59:59',$refts));
	
	$arr_rs['from'] = $from;
	$arr_rs['to'] = $to;
	$refdate = date('Y-m-d', $refts);
	$arr_rs['str'] = '
		<input type = "hidden" name="fr" value="'.$_GET['fr'].'">
		<input type = "hidden" name="refts" value="'.$refts.'">
		<button type="submit" class="form-control form-control mr-sm-2"  name="date_change" OnClick="submit()" value="prev" onMouseOver="this.style.backgroundColor=\'skyblue\';" onMouseOut="this.style.backgroundColor=\'\';"><i class="fa fa-chevron-left"></i></button>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" value="'.$refdate.'" size="10" OnChange="submit()"></input>
		<button type="submit" name="date_change" class="form-control form-control mr-sm-2"  OnClick="submit()" value="next" onMouseOver="this.style.backgroundColor=\'skyblue\';" onMouseOut="this.style.backgroundColor=\'\';"><i class="fa fa-chevron-right"></i></button>
	';
	if($t) {
		$arr_rs['str'] = '<form  class="form-inline d-none d-sm-inline-block" method="GET">'.$arr_rs['str'].'</form>';
	}
	$arr_rs['str'] .= '		
		<script>
			document.addEventListener("DOMContentLoaded", function(event) {
				$("input[name=\"refdate\"]").daterangepicker({
					singleDatePicker: true,
					showDropdowns: true,
					"locale": date_picker_option_locale
				});
			});
		</script>';
	
	
	return $arr_rs;
}


function nav_time_slice_multi($t=1)
{
	global $thistime;
	$arr_rs = array();
//	print_arr($_GET);
	if(!$_GET['refdate_to']) {
		$_GET['refdate_to'] = date('Y-m-d 23:59:59', $thistime);
	}
	if(!$_GET['refdate_from']) {
		$_GET['refdate_from'] = date('Y-m-d', $thistime-3600*24);
	}
	
	$refts_to = strtotime($_GET['refdate_to']);
	$refts_from = strtotime($_GET['refdate_from']);

	if($_GET['view_by'] == 'tenmin') {
		if($refts_from <= $refts_to-3600*24*7) {
			$refts_from = $refts_to-3600*24*7;
		}
	}
	
	else if($_GET['view_by'] == 'hour') {
		if($refts_from>= $refts_to) {
			$refts_from = $refts_to-3600*24;
		}
	}
	else if($_GET['view_by'] == 'day') {
		if($refts_from >= $refts_to-3600*24*7) {
			$refts_from = $refts_to-3600*24*7;
		}
	}	
	if($_GET['date_change'] == 'prev_to') {
		$refts_to -=  3600*24;
	}
	else if($_GET['date_change'] == 'next_to') {
		$refts_to +=  3600*24;
	}
	else if($_GET['date_change'] == 'prev_from') {
		$refts_from -=  3600*24;
	}
	else if($_GET['date_change'] == 'next_from') {
		$refts_from +=  3600*24;
	}	
	
	if($refts_to >$thistime) {
		$refts_to = $thistime;
	}
	$from = strtotime(date('Y-m-d',$refts_from));;
	$to = strtotime(date('Y-m-d  23:59:59',$refts_to));
	
	$arr_rs['from'] = $from;
	$arr_rs['to'] = $to;
	
	$refdate_to = date('Y-m-d', $refts_to);
	$refdate_from = date('Y-m-d', $refts_from);
	
	$arr_rs['str'] = '
		<input type = "hidden" name="fr" value="'.$_GET['fr'].'">
		<input type = "hidden" name="refts_from" value="'.$refts_from.'">
		<input type = "hidden" name="refts_to" value="'.$refts_to.'">
		<button type="submit" class="form-control form-control mr-sm-2"  name="date_change" OnClick="submit()" value="prev_from" onMouseOver="this.style.backgroundColor=\'skyblue\';" onMouseOut="this.style.backgroundColor=\'\';"><i class="fa fa-chevron-left"></i></button>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" value="'.$refdate_from.'" size="10" OnChange="submit()"></input>
		<button type="submit" name="date_change" class="form-control form-control mr-sm-2"  OnClick="submit()" value="next_from" onMouseOver="this.style.backgroundColor=\'skyblue\';" onMouseOut="this.style.backgroundColor=\'\';"><i class="fa fa-chevron-right"></i></button>
		<span class="form-control form-control-no-border mr-sm-2" >~</span>
		<button type="submit" class="form-control form-control mr-sm-2"  name="date_change" OnClick="submit()" value="prev_to" onMouseOver="this.style.backgroundColor=\'skyblue\';" onMouseOut="this.style.backgroundColor=\'\';"><i class="fa fa-chevron-left"></i></button>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_to" value="'.$refdate_to.'" size="10" OnChange="submit()"></input>
		<button type="submit" name="date_change" class="form-control form-control mr-sm-2"  OnClick="submit()" value="next_to" onMouseOver="this.style.backgroundColor=\'skyblue\';" onMouseOut="this.style.backgroundColor=\'\';"><i class="fa fa-chevron-right"></i></button>
		';
	if($t) {
		$arr_rs['str'] = '<form  class="form-inline d-none d-sm-inline-block" method="GET">'.$arr_rs['str'].'</form>';
	}
	$arr_rs['str'] .= '		
		<script>
			document.addEventListener("DOMContentLoaded", function(event) {
				$("input[name=\"refdate_from\"]").daterangepicker({
					singleDatePicker: true,
					showDropdowns: true,
					"locale": date_picker_option_locale
				});
				$("input[name=\"refdate_to\"]").daterangepicker({
					singleDatePicker: true,
					showDropdowns: true,
					"locale": date_picker_option_locale
				});
				
			});
		</script>';
	
	
	return $arr_rs;
}

function nav_spot($t=1)
{
	global $connect;
	global $square_table;
	global $store_table;
	global $DB_CUSTOM;
//	print_arr($_GET);
	$arr_rs = array();
	
	if($_GET['square'] != $_GET['sq_old']) {
		$_GET['store'] = '0';
	}
	$OPTION_SQUARE = '<option value="0">'.msg('All Square').'</option>';
	$OPTION_STORE  = '<option value="0">'.msg('All Store').'</option>';

	$sq = "select code, name from ".$DB_CUSTOM['square'];
	$rs = mysqli_query($connect, $sq);
	for($i=0; $i<($rs->num_rows) ;$i++) {
		$row = mysqli_fetch_row($rs);
		$arr_rs['SQ'][$i]['code'] = $row[0];
		$arr_rs['SQ'][$i]['name'] = $row[1];
		
		$OPTION_SQUARE .= '<option value="'.$row[0].'" '.(($_GET['square'] == $row[0]) ? "selected" :"").'>'.$row[1].'</option>';
	}
	
	if($_GET['square']) {
		$sq = "select code, name from ".$DB_CUSTOM['store']." where square_code = '".$_GET['square']."'";
		$rs = mysqli_query($connect, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$row = mysqli_fetch_row($rs);
			$arr_rs['ST'][$i]['code'] = $row[0];
			$arr_rs['ST'][$i]['name'] = $row[1];
			
			$OPTION_STORE .= '<option value="'.$row[0].'" '.(($_GET['store'] == $row[0]) ? "selected" :"").'>'.$row[1].'</option>';
		}
	}
	else {
		$sq = "select code, name from ".$DB_CUSTOM['store']." ";
		$rs = mysqli_query($connect, $sq);
		for($i=0; $i<($rs->num_rows); $i++) {
			$row = mysqli_fetch_row($rs);
			$arr_rs['ST'][$i]['code'] = $row[0];
			$arr_rs['ST'][$i]['name'] = $row[1];
		}
	}		
	$arr_rs['str']= '<input type="hidden" name="sq_old" value="'.$_GET['square'].'"><select class="form-control mr-sm-2" name="square" OnChange="submit()">'.$OPTION_SQUARE.'</select>'.
					'<select class="form-control mr-sm-2" name="store" OnChange="submit()">'.$OPTION_STORE.'</select>';
	if($t) {
		$arr_rs['str'] = '<form  class="form-inline d-none d-sm-inline-block" method="GET">'.$arr_rs['str'].'</form>';
	}
			
	
	return $arr_rs;
}

function nav_spot_js()
{
	global $connect;
	global $square_table;
	global $store_table;
	global $DB_CUSTOM;
	$arr_rs = array();

	$OPTION_SQUARE = '<option value="0">'.msg('All Square').'</option>';
	$OPTION_STORE  = '<option value="0">'.msg('All Store').'</option>';
	
//	$sq = "select square.code as square_code, square.name as square_name,  store.code as store_code, store.name as store_name from square inner join store on square.code = store.square_code order by square_code asc";
	$str = 'var sq_code = new Array();var sq_name = new Array();var st_code = new Array();var st_name = new Array();';
	$sq = "select code, name from ".$DB_CUSTOM['square'];
	$rs = mysqli_query($connect, $sq);
	for($i=0; $i<($rs->num_rows) ;$i++) {
		$row = mysqli_fetch_row($rs);
		$sq_code[$i] = $row[0];
		$sq_name[$i] = $row[1];
		$str .= 'sq_code.push("'.$row[0].'");'."\n\r";
		$str .= 'sq_name.push("'.$row[1].'");'."\n\r";
		$str .= 'st_code['.$i.'] = new Array();'."\n\r";
		$str .= 'st_name['.$i.'] = new Array();'."\n\r";
//		$str .= 'sq_code['.$i.']="'.$row[0].'";'."\n\r";
//		$str .= 'sq_name['.$i.']="'.$row[1].'";'."\n\r";
		
		$sqa = "select code, name from ".$DB_CUSTOM['store']." where square_code ='".$row[0]."' ";
		$rsa = mysqli_query($connect, $sqa);
		for($j=0; $j<($rsa->num_rows); $j++) {
			$rowa = mysqli_fetch_row($rsa);
//			$arr_rs[$i][$j]['st_code'] = $row[0];
//			$arr_rs[$i][$j]['st_name'] = $row[1];
			$str .= 'st_code['.$i.'].push("'.$rowa[0].'");'."\n\r";
			$str .= 'st_name['.$i.'].push("'.$rowa[1].'");'."\n\r";
		}
	}
	print '<script>'.$str.'</script>';
//	print '<script>alert(st_code)</script>';
}

?>