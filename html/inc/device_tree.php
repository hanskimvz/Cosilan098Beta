<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/libs/functions.php");
require_once $_SERVER['DOCUMENT_ROOT']."/inc/page_functions.php";
if (!isset($_GET['mode'])){
    $_GET['mode'] = 'list';
}
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
            if (trim($b)){
                $ct_name[trim($b)] = '';
            }
			// array_push($ct_name, trim($b));
		}
	}
	return $ct_name;
}	

function strTree($arr, $depth=0){
    $cls = $depth ? " col-md-".$depth : "";
    if ($arr['feather'] == 'camera') {
        $feather = '<span class="mr-2">&#128247;</span>';
    }
    else if ($arr['feather'] == 'store') {
        $feather = '<span class="mr-2">&#127968;</span>';
        // $feather = '<i class="align-middle mr-2" data-feather="home"></i>';
    }
    else if ($arr['feather'] == 'square') {
        $feather = '<span class="mr-2">&#127972;</span>';
    }
    else {
        $feather = '<i class="align-middle mr-2" data-feather="'.$arr['feather'].'"></i>';
    }
    
    $str =  '<div class="card-header glyphicon mb-0 mt-0'.$cls.'" style="background-color:'.$arr['color'].'; padding-bottom:0px;">
        <div class="card-actions float-right dropdown show mr-0 mt-0">
            <a href="#" data-toggle="dropdown" data-display="static">&#8803;</a>
            <div class="dropdown-menu dropdown-menu-right">
                <span type="button" class="dropdown-item" OnClick="'.$arr['pull_down'].'">'.$arr['pull_down_disp'].'</span>
            </div>
        </div>
        <h5 class="card-title ml-1 mb-1 mt-0">
            <span type="button" OnClick="'.$arr['on_click'].'">'.$feather.'<b>'.$arr['name'].'</b>
            </span>
        </h5>
    </div>';
    return $str;
    // <i class="align-middle mr-2" data-feather="'.$arr['feather'].'"></i><b>'.$arr['name'].'</b>
    // <i class="align-middle" data-feather="more-horizontal"></i></a>
}

// if (strpos(" ".$_SERVER['SCRIPT_NAME'], '/inc/device_tree.php') > 0){
//     function strInput($label, $id, $value, $size=0, $readonly=0) {
//         $size = $size ? " col-md-".$size : "";
//         $readonly = $readonly ? " readonly": "";
//         if (!$label) {
//             return '<input type="hidden" id="'.$id.'" value="'.$value.'">';
//         }

//         $str = '<div class="form-group'.$size.'">
//             <label class="col-form-label">'.$label.'</label>
//             <input type="text" id="'.$id.'" value="'.$value.'" class="form-control"'.$readonly.'>
//         </div>';
//         return $str;
//     }

//     function strTextarea($label, $id, $value, $size=0) {
//         $size = $size ? " col-md-".$size : "";
//         $str = '<div class="form-group'.$size.'">
//             <label class="col-form-label">'.$label.'</label>
//             <textarea id="'.$id.'" class="form-control">'.$value.'</textarea>
//         </div>';
//         return $str;
//     }

//     function strSelect($label, $id, $arr_option, $selected, $size=0){
//         $size = $size ? " col-md-".$size : "";
//         $option_str = '';
//         for($i=0; $i<sizeof($arr_option); $i++){
//             $option_str .= $arr_option[$i]['value'] == $selected ? 
//                 '<option value="'.$arr_option[$i]['value'].'" selected>'.$arr_option[$i]['text'].'</option>' :
//                 '<option value="'.$arr_option[$i]['value'].'">'.$arr_option[$i]['text'].'</option>';
//         }
//         $str = '<select id="'.$id.'" class="form-control">'.$option_str.'</select>';
//         if ($label) {
//             $str = '<div class="form-group'.$size.'">
//                 <label>'.$label.'</label>
//                 '.$str.'
//             </div>';
//         }
//         return $str;
//     }
// }
if ($_GET['mode'] == 'list'){
    $msg = q_language('device_tree.php');
    $device_tree_str = "";
    $arr_rs = array();
    $sq = "select pk, code, name from ".$DB_CUSTOM['square']." ";
    $rs = mysqli_query($connect0, $sq);
    while ($assoc = mysqli_fetch_assoc($rs)){
        // array_push($arr_rs, array("code"=>$assoc['code'], "name" => $assoc['name'], "store"=>[]));
        $arr = array(
            "name"=>$assoc['name'],
            "color"=>"#aea",
            "pull_down"=> 'viewStoreInfo(\''.$assoc['code'].'\')',
            "pull_down_disp"=> $msg['addstore'],
            "on_click"=> 'viewSquareInfo(\''.$assoc['code'].'\')',
            "feather"=>"square",
        );         
        array_push($arr_rs, array("code"=>$assoc['code'], "list_tag"=>strTree($arr, 0), "name" => $assoc['name'], "store"=>[]));
    }
    $sz_square = sizeof($arr_rs);
    for ($i=0; $i<$sz_square; $i++){
        $sq =  "select pk, code, name from ".$DB_CUSTOM['store']." where square_code='".$arr_rs[$i]['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        while ($assoc = mysqli_fetch_assoc($rs)){
            // array_push($arr_rs[$i]['store'], array("code" => $assoc['code'], "name"=>$assoc['name'], "camera"=>[]));
            $arr = array(
                "name"=>$assoc['name'],
                "color"=>"#fdd",
                "pull_down"=> 'floatingCamera(\''.$assoc['code'].'\')',
                "pull_down_disp"=> $msg['addcamera'],
                "on_click"=> 'viewStoreInfo(\''.$assoc['code'].'\')',
                "feather"=>"store",
            ); 
            array_push($arr_rs[$i]['store'], array("code"=>$assoc['code'], "name"=>$assoc['name'], "list_tag"=>strTree($arr, 8), "camera"=>[]));           
        }
        $sz_store = sizeof($arr_rs[$i]['store']);
        for($j=0; $j<$sz_store; $j++){
            $sq =  "select pk, code, name, device_info from ".$DB_CUSTOM['camera']." where store_code='".$arr_rs[$i]['store'][$j]['code']."' ";
            // print $sq;
            $rs = mysqli_query($connect0, $sq);
            while ($assoc = mysqli_fetch_assoc($rs)){
                // array_push($arr_rs[$i]['store'][$j]['camera'], array("code" => $assoc['code'], "name"=>$assoc['name'], "label"=>[]));
                $arr = array(
                    "name"=>$assoc['name'],
                    "color"=>"#adf",
                    "pull_down"=> 'viewDeviceParamDetail(\''.$assoc['device_info'].'\')',
                    "pull_down_disp"=> $msg['viewparameter'],
                    "on_click"=> 'viewCameraInfo(\''.$assoc['code'].'\')',
                    "feather"=>"camera",
                );
                array_push($arr_rs[$i]['store'][$j]['camera'], array("code"=>$assoc['code'], "name"=>$assoc['name'],"list_tag" => strTree($arr,12), "label"=>[]));
            }            
        }
    }

    $device_tree_str = "";
    for($i=0; $i<sizeof($arr_rs); $i++){
        $device_tree_str .= '<li><div class="card col-md-12 mb-2" style="background-color:#fff;">'.$arr_rs[$i]['list_tag'];
        $device_tree_str .= '<ul>';
        for($j=0; $j<sizeof($arr_rs[$i]['store']); $j++){
            $device_tree_str .= '<li>'.$arr_rs[$i]['store'][$j]['list_tag'];
            $device_tree_str .= '<ul>';
            for($k=0; $k<sizeof($arr_rs[$i]['store'][$j]['camera']); $k++){
                $device_tree_str .= '<li>'.$arr_rs[$i]['store'][$j]['camera'][$k]['list_tag'].'</li>';
            }
            $device_tree_str .= '</ul></li>';
        }
        $device_tree_str .= '</ul></div></li>';
    }
    $device_tree_str = '<ul>'.$device_tree_str.'</ul>';
    if(isset($_GET['query'])){
        $device_tree_str = '<ul><li>
            <div class="glyphicon card border col-md-12 mb-0 mt-0" style="background-color:#fefefe;">
                <div class="card-actions float-right dropdown show mr-2">
                    <a href="#" data-toggle="dropdown" data-display="static">&#9661;</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <span type="button" class="dropdown-item" OnClick="viewSquareInfo(0)">'.$msg['addsquare'].'</a>
                    </div>
                </div>
                <h5 class="card-title ml-2 mb-0"><span class="mr-2">&#10133;</span><b>'.$msg['devicetree'].'</b></h5>
            </div>
            '.$device_tree_str.'
        </li></ul>';
        print $device_tree_str;
    }
}

else if ($_GET['mode'] == 'floating_camera') {
    $msg = q_language('camera.php');
    $sq = "select A.device_info as device_info, B.body as snapshot, B.regdate as regdate from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B on A.device_info = B.device_info where A.db_name ='none' or A.db_name is null order by last_access desc ";
    $rs = mysqli_query($connect0, $sq);
    $str_body = "";
    for($i=0; $i<($rs->num_rows); $i++) {
        $assoc = mysqli_fetch_assoc($rs);
        if(time() - strtotime($assoc['regdate']) < 3600) {
            $assoc['regdate'] = '<span style="font-color:#00F">'.$assoc['regdate'].'</span>';
        }
        $str_body .='<div class="col-12 col-md-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <span class="float-right">'.$assoc['regdate'].'<br>
                        <span type="button" OnClick="addDeviceToStore(\''.$_GET['st_code'].'\', \''.$assoc['device_info'].'\')" class="btn btn-sm btn-primary float-right mt-2" >'.$msg['addtostore'].'</span>
                    </span>
                    <h3 class="card-title mb-0"><b>'.str_replace("&","<br>",str_replace("=",": ",$assoc['device_info'])).'</b></h3>
                </div>
                <img class="card-img-top" src="'.$assoc['snapshot'].'"></img>		
            </div>
        </div>';		
    }
    print '<div class="col-12 col-md-12 col-lg-12" style="position:relative;">
        <div class="row">'.$str_body.'</div>
    </div>';
}

else if ($_GET['mode'] == 'view') {
################### INFO PAD #####################//
    $msg = q_language($_GET['info'].".php");
    if ($_GET['info']=='square') {
        if ($_GET['code']) {
            $sq = "select * from ".$DB_CUSTOM['square']." where code='".$_GET['code']."' ";
            $rs = mysqli_query($connect0, $sq);
            $assoc = mysqli_fetch_assoc($rs);
            $assoc['mode'] = 'modify';
        }
        else {
            $assoc = array('code'=>'SQ'.time().rand(0,9).rand(0,9).rand(0,9), 'name'=>'', 'addr_state'=>'', 'addr_city'=>'', 'addr_b'=>'', 'comment'=>'', 'mode'=>'add');
        }
        
        $arr_tag = array(
            'mode'      => strInput(0,                   'mode',        $assoc['mode'],       0, 0),
            'frame'     => strInput(0,                   'fr',          'square',             0, 0),
            'code'      => strInput($msg['code'],        'code',        $assoc['code'],       6, 1),
            'name'      => strInput($msg['name'],        'name',        $assoc['name'],       6, 0),
            'addr_state'=> strInput($msg['address'],     'addr_state',  $assoc['addr_state'], 6, 0),
            'addr_city' => strInput($msg['addrcity'],    'addr_city',   $assoc['addr_city'],  6, 0),
            'addr_b'    => strInput($msg['addressdetail'],'addr_b',     $assoc['addr_b'],     0, 0),
            'comment'   => strTextarea($msg['comment'], 'comment', $assoc['comment'], 0) 
        );

        $info_pad = <<<EOBLOCK
        <div class="card col-md-12">
            <div class="card-header"><h5 class="card-title mb-0"><b>$msg[squareinfo]</b></h5></div>
            <div class="card-body">
                $arr_tag[mode]$arr_tag[frame]
                <div class="col-md-12">
                    <div class="form-row">$arr_tag[code]$arr_tag[name]</div>
                    <div class="form-row">$arr_tag[addr_state]$arr_tag[addr_city]</div>
                    $arr_tag[addr_b]$arr_tag[comment]
                </div>
                <div class="float-right"><button class="btn btn-sm btn-warning" OnClick="document.getElementById('delete_pad').style.display='';">$msg[delete]</button></div>	
                <div class="text-center"><button class="btn btn-primary" OnClick="modifySquare('$assoc[code]')" >$msg[save_changes]</button></div>
            </div>
        </div>
EOBLOCK;
    }
    else if ($_GET['info']=='store') {
        if (!strncmp($_GET['code'], "ST", 2)) {
            $sq = "select * from ".$DB_CUSTOM['store']." where code='".$_GET['code']."' ";
            $rs = mysqli_query($connect0, $sq);
            $assoc = mysqli_fetch_assoc($rs);
            $assoc['mode'] = 'modify';
        }
        else {
            $assoc = array('code'=>'ST'.time().rand(0,9).rand(0,9).rand(0,9), 'name'=>'', 'addr_state'=>'', 'addr_city'=>'', 'addr_b'=>'', 'comment'=>'', 'mode'=>'add', 'phone'=>'', 'fax'=>'', 'contact_person'=>'', 'contact_tel'=>'', 'area'=>0, 'sniffing_mac'=>'', 'open_hour'=>'', 'close_hour'=>'');
            $assoc['square_code'] = $_GET['code'];
        }
        // print_r($assoc);
        $arr_hours= array();
        for($i=0; $i<=24; $i++) {
            $hour_tag = sprintf("%02d:00 %s", (($i%12)?($i%12):12) ,($i<12 ? $msg['date_am']: $msg['date_pm']));
            array_push($arr_hours, ['value'=>$i, 'text'=>$hour_tag]);
            // $option_hour .= '<option value="'.$i.'" >'.$hour_tag.'</option>';
        }

        $sq = "select code, name from ".$DB_CUSTOM['square']." ";
        $rs = mysqli_query($connect0, $sq);

        $arr_square = array();
        while ($row= mysqli_fetch_row($rs)) {
            array_push($arr_square, ['value'=>$row[0], 'text'=>$row[0].': '.$row[1]]);
        }

        if ( $assoc['apply_open_hour'] == 'y') {
            $assoc['apply_open_hour'] = "checked";
            $openhour_display = "";
        }
        else {
            $assoc['apply_open_hour'] = "";
            $openhour_display = "none";
        }
        $arr_tag = array(
            'mode'          => strInput(0,                                  'mode',           $assoc['mode'],           0, 0),
            'frame'         => strInput(0,                                  'fr',             'store',                  0, 0),
            'code'          => strInput($msg['code'],                       'code',           $assoc['code'],           6, 1),
            'name'          => strInput($msg['name'],                       'name',           $assoc['name'],           6, 0),
            'phone'         => strInput($msg['phone'],                      'phone',          $assoc['phone'],          6, 0),
            'fax'           => strInput($msg['fax'],                        'fax',            $assoc['fax'],            6, 0),
            'contact_person'=> strInput($msg['contact'],                    'contact_person', $assoc['contact_person'], 6, 0),
            'contact_tel'   => strInput($msg['tel'],                        'contact_tel',    $assoc['contact_tel'],    6, 0),
            'addr_state'    => strInput($msg['address'],                    'addr_state',     $assoc['addr_state'],     6, 0),
            'addr_city'     => strInput($msg['addrcity'],                   'addr_city',      $assoc['addr_city'],      6, 0),
            'addr_b'        => strInput($msg['addressdetail'],              'addr_b',         $assoc['addr_b'],         0, 0),
            'area'          => strInput($msg['area']."(".$msg['sqmt'].")",  'area',           $assoc['area'],           6, 0),
            'sniffing_mac'  => strInput($msg['sniffing_mac'],               'sniffing_mac',   $assoc['sniffing_mac'],   6, 0),
            'flag_open_hour'=> '<input class="form-check-input ml-4" type="checkbox" id="apply_open_hour" onChange="viewOpenhour(this)" '.$assoc['apply_open_hour'].'>',
            'open_hour'     => strSelect(0,                   'open_hour',   $arr_hours,  $assoc['open_hour'],      0),
            'close_hour'    => strSelect(0,                   'close_hour',  $arr_hours, $assoc['close_hour'],     0),
            'square'        => strSelect($msg['squaregroup'], 'square_code', $arr_square,     $assoc['square_code'],    6),
            'comment'       => strTextarea($msg['comment'],                 'comment',        $assoc['comment'],        0) 
        );
        $info_pad = <<<EOPAGE
        <div class="card col-md-12">
            <div class="card-header"><h5 class="card-title mb-0"><b>$msg[storeinfo]</b></h5></div>
            <div class="card-body">
                $arr_tag[mode]$arr_tag[frame]
                <div class="col-md-12">
                    <div class="form-row">$arr_tag[code]$arr_tag[name]</div>
                    <div class="form-row">$arr_tag[phone]$arr_tag[fax]</div>
                    <div class="form-row">$arr_tag[contact_person]$arr_tag[contact_tel]</div>
                    <div class="form-row">$arr_tag[addr_state]$arr_tag[addr_city]</div>
                    $arr_tag[addr_b]
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>$msg[workinghour]$arr_tag[flag_open_hour]</label>
                            <div class="form-group mb-0" id="disp_open_hour" style="display:$openhour_display">
                                <label class="form-check-inline">$arr_tag[open_hour]</label>
                                <label class="form-check-inline mr-3">~</label>
                                <label class="form-check-inline">$arr_tag[close_hour]</label>
                            </div>
                        </div>
                        $arr_tag[square]
                    </div>
                    <div class="form-row">$arr_tag[area]$arr_tag[sniffing_mac]</div>
                    $arr_tag[comment]
                </div>
                <div class="float-right"><button class="btn btn-sm btn-warning" OnClick="document.getElementById('delete_pad').style.display='';">$msg[delete]</button></div>
                <div class="text-center"><button class="btn btn-primary" onClick="modifyStore('$assoc[code]')">$msg[save_changes]</button></div>
            </div>
        </div>
EOPAGE;
    }

    else if ($_GET['info']=='camera') {
        // print_r($_GET);
        $assoc =  array();
        if (!strncmp($_GET['code'], "C", 1)) { // modify camera information
            $sq = "select code, store_code, mac, brand, model, usn, product_id, name, comment, enable_countingline as ck_crpt, enable_heatmap as ck_heatmap, enable_face_det as ck_agender, enable_macsniff as ck_sniff, flag as ck_flag, device_info from ".$DB_CUSTOM['camera']." where code='".$_GET['code']."' ";
            $rs = mysqli_query($connect0, $sq);
            $assoc = mysqli_fetch_assoc($rs);

            $assoc['ck_crpt'] = $assoc['ck_crpt']=='y'? 'checked' :'';
            $assoc['ck_heatmap'] = $assoc['ck_heatmap']=='y'? 'checked' :'';
            $assoc['ck_agender'] = $assoc['ck_agender']=='y'? 'checked' :'';
            $assoc['ck_sniff'] = $assoc['ck_sniff']=='y'? 'checked' :'';
            $assoc['ck_flag'] = $assoc['ck_flag']=='y'? 'checked' :'';
            $assoc['mode'] = "modify";

            $dev_info = $assoc['device_info'];
            if($assoc['mac'] && $assoc['brand'] && $assoc['model']) {
                $dev_info = "mac=".$assoc['mac']."&brand=".$assoc['brand']."&model=".$assoc['model'];
            }
            else {
                $ex = explode("&", $dev_info);
                $assoc['mac'] = trim(explode("=", $ex[0])[1]);
                $assoc['brand'] = trim(explode("=", $ex[1])[1]);
                $assoc['model'] = trim(explode("=", $ex[2])[1]);
            }
        }
        else if($_GET['code'] == 0) { // add new floationg camera to store
            $dev_info = "mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model'];
            $assoc['mac'] = $_GET['mac'];
            $assoc['brand'] = $_GET['brand'];
            $assoc['model'] = $_GET['model'];
            $assoc['store_code'] = $_GET['st_code'];
            $assoc['name'] = '';
            $assoc['code'] = 'C'.time().rand(0,9).rand(0,9).rand(0,9);
            $assoc['ck_crpt'] = '';
            $assoc['ck_heatmap'] ='';
            $assoc['ck_agender'] = '';
            $assoc['ck_sniff'] ='';
            $assoc['ck_flag'] = '';
            $assoc['mode'] =  "add";
            $assoc['comment'] =  "";
        }
        if ($dev_info) {
            $sq = "select A.usn as usn, A.product_id as product_id, A.initial_access as initial_access, A.last_access as last_access, A.lic_pro as lic_pro, A.lic_surv as lic_surv, A.lic_count as lic_ct, A.face_det as face_det, A.heatmap as hm, A.countrpt as crpt, A.macsniff as sniff, A.param as param, B.body as snapshot, B.regdate as regdate from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B on A.device_info=B.device_info where A.device_info='".$dev_info."' ";
            // print $sq;
            $rs = mysqli_query($connect0, $sq);
            $assoc = array_merge($assoc, mysqli_fetch_assoc($rs)); 


            $arr = array();
            if ($assoc['lic_pro']=='y'){
                array_push($arr, 'PRO');
            }
            if ($assoc['lic_surv']=='y'){
                array_push($arr, 'SURV');
            }
            if ($assoc['lic_ct']=='y'){
                array_push($arr, 'COUNT');
            }
            $assoc['license'] = implode(",", $arr);  
            
            $ct_display = $assoc['crpt']=='y' ? '':'none';
            $assoc['ck_crpt'] .= $assoc['crpt']=='y' ?      '' : ' disabled';
            $assoc['ck_heatmap'] .= $assoc['hm']=='y'?       '' : ' disabled';
            $assoc['ck_agender'] .= $assoc['face_det']=='y'? '' : ' disabled';
            $assoc['ck_sniff'] .= $assoc['sniff']=='y'?      '' : ' disabled';

            foreach(['crpt', 'hm', 'face_det', 'sniff'] as $func) {
                $assoc[$func] = $assoc[$func]=='y' ? '<span class="mr-1 btn-success">&nbsp;&#10003;&nbsp;</span>' : '<span class="mr-2 btn-default">&#10005;</span>';    
            }

            // store code option string
            $sq = "select square_code from ".$DB_CUSTOM['store']." where code = '".$assoc['store_code']."' ";
            $assoc['square_code'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
            $sq = "select code, name from ".$DB_CUSTOM['store']." where square_code = '".$assoc['square_code']."' ";
            $rs = mysqli_query($connect0, $sq);
            $arr_option = array();
            while ($row = mysqli_fetch_row($rs)){
                array_push($arr_option, ['value'=>$row[0], 'text'=>$row[0].': '.$row[1]]);
            }
            // $x = strSelect($msg['store'], 'store_code', $arr_option, $assoc['store_code'], $size=3);
            // print($x);
            // zone and image
            // $assoc['zone'] = getZoneFromParam($assoc['param']);
            // print ('<<'.json_encode([$assoc['snapshot'], $assoc['zone']], JSON_PRETTY_PRINT).'>>');

            // counter table
            $arr_ct_names = getCounterFromParam($assoc['param']);
            $label_list = ['none','entrance', 'exit', 'outside'];
            $sq = "select camera_code, counter_name, counter_label, flag from ".$DB_CUSTOM['counter_label']."  ";

            $rs = mysqli_query($connect0, $sq);
            while ($row = mysqli_fetch_row($rs)) { 
                if (!in_array($row[2], $label_list)){
                    array_push($label_list, $row[2]);
                }
                if ($row[0] == $assoc['code']) {
                    $arr_ct_names[$row[1]] = $row[2];
                }
            }
            // print_r($arr_ct_names);
            $counter_table = "";
            $arr = array();
            foreach($arr_ct_names as $ct_name=>$ct_label) {
                array_push($arr, $ct_name);
                $counter_table .= '<tr><td>'.$ct_name.'</td>';
                $sel_ct_label ='';
                foreach($label_list as $label){
                    $sel_ct_label .= '<option value="'.$label.'" '.($ct_label == $label ? "selected": "").'>'.$msg[strtolower($label)].'</option>' ;
                }
                $counter_table .= '<td><select id="'.$ct_name.'" class="form-control">'.$sel_ct_label.'</select></td>';
                $counter_table .= '</tr>';
            }
            // print ($counter_table);
            $ct_list = implode(",",$arr);
            $counter_table = '<input type="hidden" id="ct_list" value="'.$ct_list.'">
            <table class="table table-striped table-sm table-bordered">
            <tr>
            <th>'.$msg['countername'].'</th>
            <th>'.$msg['counterlabel'].'<span class="ml-3 badge badge-success" onMouseOver="this.style.cursor=\'pointer\'" onClick="location.href=(\'/admin.php?fr=counter_label_set\')">'.$msg['manage'].'</span></th>
            </tr>'.$counter_table.'</table>';
        }
     
        // print_r($assoc);
        $arr_tag = array(
            'mode'          => strInput(0,                  'mode',              $assoc['mode'],            0, 0),
            'frame'         => strInput(0,                  'fr',               'camera',                   0, 0),
            'device_info'   => strInput(0,                  'device_info',      $dev_info,                  0, 0),
            'code'          => strInput($msg['code'],       'code',             $assoc['code'],             4, 1),
            'name'          => strInput($msg['name'],       'name',             $assoc['name'],             8, 0),
            'mac'           => strInput($msg['mac'],        'mac',              $assoc['mac'],              3, 1),
            'brand'         => strInput($msg['brand'],      'brand',            $assoc['brand'],            2, 1),
            'model'         => strInput($msg['model'],      'model',            $assoc['model'],            2, 1),
            'usn'           => strInput($msg['usn'],        'usn',              $assoc['usn'],              3, 1),
            'product_id'    => strInput($msg['productid'],  'product_id',       $assoc['product_id'],       2, 1),
            'install_date'  => strInput($msg['installdate'],'initial_access',   $assoc['initial_access'],   3, 1),
            'last_access'   => strInput($msg['lastaccess'], 'last_access',      $assoc['last_access'],      3, 1),
            'license'       => strInput($msg['license'],    'license',          $assoc['license'],          2, 1),
            'store'         => strSelect($msg['store'],     'store_code', $arr_option, $assoc['store_code'],4),
            'comment'       => strTextarea($msg['comment'], 'comment',          $assoc['comment'],          0)
        );

        $info_pad = <<<EOPAGE
        <div class="card col-12 col-md-12 col-lg-12" style="position:relative; ">
            <div class="card-header">
                <span class="float-right" id="regdate">$assoc[regdate]</span>
                <h3 class="card-title mb-0"><b>$msg[deviceinfo]</b></h3>
            </div>
            <div class="card-body">
                <div class="text-center mt-0 mb-0"><canvas id="zone_config" width="800" height="450" class="text-center"></canvas></div>
                <div class="form-row">
                    $arr_tag[mode]$arr_tag[frame]$arr_tag[device_info]$arr_tag[code]$arr_tag[name]
                    $arr_tag[mac]$arr_tag[brand]$arr_tag[model]$arr_tag[usn]$arr_tag[product_id]$arr_tag[install_date]$arr_tag[last_access]$arr_tag[license]
                    $arr_tag[store]
                    <div class="form-group col-md-12"><label>$msg[function]</label>
                        <div class="form-group mb-0">
                            <label class="form-check-inline col-md-3">$assoc[crpt]$msg[countdb]</label>
                            <label class="form-check-inline col-md-2">$assoc[hm]$msg[heatmap]</label>
                            <label class="form-check-inline col-md-2">$assoc[face_det] $msg[face]</label>
                            <label class="form-check-inline col-md-2">$assoc[sniff]$msg[macsniff]</label>
                        </div>
                    </div>
                    <div class="form-group col-md-12 mt-0"><label>$msg[feature]</label>
                        <div class="form-group mb-0">
                            <label class="form-check-inline col-md-2"><input class="form-check-input" type="checkbox" id="enable_countingline" OnChange="showCounterLabel()" $assoc[ck_crpt]>$msg[countingline]</label>
                            <label class="form-check-inline col-md-2"><input class="form-check-input" type="checkbox" id="enable_heatmap" $assoc[ck_heatmap]>$msg[heatmap]</label>
                            <label class="form-check-inline col-md-2"><input class="form-check-input" type="checkbox" id="enable_face_det" $assoc[ck_agender]>$msg[ageandgender]</label>
                            <label class="form-check-inline col-md-2"><input class="form-check-input" type="checkbox" id="enable_macsniff" $assoc[ck_sniff]>$msg[macsniffing]</label>
                            <!--label class="form-check-inline col-md-2"><input class="form-check-input" type="checkbox" id="enable_snapshot">$msg[snapshot]</label-->
                            <label class="form-check-inline col-md-2"><input class="form-check-input" type="checkbox" id="flag" $assoc[ck_flag]>$msg[activate]</label>
                        </div>
                    </div>
                    <div class="form-group col-md-12" id="counter_label" style="display:$ct_display">$counter_table</div>
                    <div class="form-group col-md-12"><label>$msg[comment]</label><textarea id="comment" class="form-control"></textarea></div>
                </div>
                <div class="float-right"><button class="btn btn-sm btn-warning" OnClick="document.getElementById('delete_pad').style.display='block';">$msg[delete]</button></div>
                <div class="text-center"><button class="btn btn-primary" OnClick="modifyDeviceInfo('$dev_info')">$msg[save_changes]</button></div>					
                </div>
            </div>
        </div>
EOPAGE;
    }
    print $info_pad;
    exit;
}
else if($_GET['mode'] == 'modify'){
    print_r($_POST);
    if (!isset($_POST['code'])) {
        print "code is empty";
        exit();
    }
    if ($_GET['info']=='square'){
        $sq = "update ".$DB_CUSTOM['square']." set name='".addslashes($_POST['name'])."', addr_state='".addslashes($_POST['addr_state'])."', addr_city='".addslashes($_POST['addr_city'])."', addr_b='".addslashes($_POST['addr_b'])."', comment='".addslashes($_POST['comment'])."' where code='".$_POST['code']."' ";
        // print $sq;
    }
    else if ($_GET['info']=='store'){
        $_POST['apply_open_hour'] = $_POST['apply_open_hour']=='y' ? 'y' : 'n';
        if (!$_POST['area']) {
            $_POST['area'] = 0;
        }
        $sq = "update ".$DB_CUSTOM['store']." set name='".addslashes(trim($_POST['name']))."', comment='".addslashes(trim($_POST['comment']))."', addr_state='".addslashes(trim($_POST['addr_state']))."', addr_city='".addslashes(trim($_POST['addr_city']))."', addr_b='".addslashes(trim($_POST['addr_b']))."', square_code = '".trim($_POST['square_code'])."', phone = '".addslashes(trim($_POST['phone']))."', fax = '".addslashes(trim($_POST['fax']))."', contact_person = '".addslashes(trim($_POST['contact_person']))."', contact_tel = '".addslashes(trim($_POST['contact_tel']))."', apply_open_hour='".$_POST['apply_open_hour']."', open_hour = ".trim($_POST['open_hour']).", close_hour = ".trim($_POST['close_hour']).", sniffing_mac = '".trim($_POST['sniffing_mac'])."', area = ".$_POST['area']." where code='".$_POST['code']."'";
    }
    else if ($_GET['info']=='camera') {
        // print_r($_POST);
        $device_info = "mac=".$_POST['mac']."&brand=".$_POST['brand']."&model=".$_POST['model'];
        $sq = "select store_code from ".$DB_CUSTOM['camera']." where device_info ='".$device_info."' ";
		$old_store_code = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];

        $sq = "update ".$DB_COMMON['param']." set db_name='".$_SESSION['db_name']."' where device_info='".$device_info."' ";
        $rs = mysqli_query($connect0, $sq) ? "...param update OK" : $sq."...param update FAIL";
        print '</br>'.$rs;

        $sq = "update ".$DB_CUSTOM['camera']." set name='".addslashes(trim($_POST['name']))."', mac='".trim($_POST['mac'])."', usn='".trim($_POST['usn'])."', model='".trim($_POST['model'])."', brand='".trim($_POST['brand'])."', product_id='".trim($_POST['product_id'])."', enable_countingline='".$_POST['enable_countingline']."', enable_heatmap='".$_POST['enable_heatmap']."', enable_face_det='".$_POST['enable_face_det']."', enable_macsniff='".$_POST['enable_macsniff']."', flag='".$_POST['flag']."', comment='".addslashes(trim($_POST['comment']))."' where device_info='".$device_info."' ";
		// print $sq;
        $rs = mysqli_query($connect0, $sq) ? "...camera update OK" : $sq."...camera update FAIL";
        print('</br>'.$rs);

        if($old_store_code != $_POST['store_code']) { // if store code changed
			$sq = "select square_code from  ".$DB_CUSTOM['store']." where code='".trim($_POST['store_code'])."' ";
			$square_code = mysqli_fetch_row( mysqli_query($connect0, $sq))[0];

			$sq = "update ".$DB_CUSTOM['camera']." set square_code='".$square_code."', store_code='".trim($_POST['store_code'])."' where device_info='".$device_info."' ";
			$rs = mysqli_query($connect0, $sq) ? "...camera upate OK" : $sq."...camera update Fail";
            print('</br>'.$rs);

			$sq = "update ".$DB_CUSTOM['count']." set  square_code='".$square_code."', store_code='".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
			$rs = mysqli_query($connect0, $sq) ? '...counting table update OK': $sq."...counting table update Fail";
            print('</br>'.$rs);

            $sq = "update ".$DB_CUSTOM['heatmap']." set  square_code='".$square_code."', store_code='".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
			$rs = mysqli_query($connect0, $sq) ? "...heatmap table update OK" : $sq."...heatmap table update Fail";
			print('</br>'.$rs);

            $sq = "update ".$DB_CUSTOM['age_gender']." set  square_code='".$square_code."', store_code='".$_POST['store_code']."', camera_code='".$_POST['code']."'  where device_info = '".$device_info."' ";
			$rs = mysqli_query($connect0, $sq) ? "...age_gender table update OK": $sq."...age_gender table update Fail";
            print('</br>'.$rs);
		}
        
        if(isset($_POST['counters']) && $_POST['enable_countingline']=='y') { // counter label update
            foreach($_POST['counters'] as $A=>$counters) {
                // print $counters['name']."--".$counters['label']."</br>'n";
				$sq = "select pk from ".$DB_CUSTOM['counter_label']." where camera_code='".$_POST['code']."' and counter_name='".$counters['name']."'";
				$rs = mysqli_query($connect0, $sq);
				if($rs->num_rows) {
					$sq = " update ".$DB_CUSTOM['counter_label']." set counter_label='".trim($counters['label'])."' where camera_code='".$_POST['code']."' and counter_name='".$counters['name']."'";
				}
				else {
					$sq = "insert into ".$DB_CUSTOM['counter_label']."(camera_code, counter_name, counter_label) values('".$_POST['code']."', '".trim($counters['name'])."', '".trim($counters['label'])."')";
				}				
                // print($sq);
				$rs = mysqli_query($connect0, $sq) ? "...Counter Label Update OK" : $sq."...Counter Label Update Fail";
                print('</br>'.$rs);
			}
		}

        $sq ="";
    }
    else {
        $sq ="";
    }
    if($sq) {
        print $sq;
        $rs = mysqli_query($connect0, $sq) ? "...update OK" : $sq."...update FAIL";
        print $rs;
    }
}
else if($_GET['mode'] == 'add'){
    if (!isset($_POST['code'])) {
        print "Error: code is empty";
        exit();
    }
    if (!isset($_POST['name'])) {
        print "Error: name is empty";
        exit();
    }    
    if ($_GET['info']=='square'){
        $sq = "insert into ".$DB_CUSTOM['square']."(regdate, code, name, addr_state, addr_city, addr_b, comment) values(now(), '".$_POST['code']."', '".addslashes($_POST['name'])."', '".addslashes($_POST['addr_state'])."', '".addslashes($_POST['addr_city'])."', '".addslashes($_POST['addr_b'])."', '".addslashes($_POST['comment'])."')";
    }
    else if ($_GET['info']=='store'){
        $_POST['apply_open_hour'] = $_POST['apply_open_hour']=='y' ? 'y' : 'n';
        $sq = "insert into ".$DB_CUSTOM['store']." (regdate, code, square_code, name, addr_state, addr_city, addr_b, phone, fax, contact_person, contact_tel, apply_open_hour, open_hour, close_hour, comment, sniffing_mac, area) values(now(), '".$_POST['code']."', '".$_POST['square_code']."', '".addslashes($_POST['name'])."', '".addslashes($_POST['addr_state'])."', '".addslashes($_POST['addr_city'])."', '".addslashes($_POST['addr_b'])."', '".$_POST['phone']."', '".$_POST['fax']."', '".addslashes($_POST['contact_person'])."', '".$_POST['contact_tel']."', '".$_POST['apply_open_hour']."', '".$_POST['open_hour']."', '".$_POST['close_hour']."', '".addslashes($_POST['comment'])."', '".$_POST['sniffing_mac']."', '".$_POST['area']."')";

    }
    else if ($_GET['info']=='camera'){
        print_r($_POST);
        $device_info = "mac=".$_POST['mac']."&brand=".$_POST['brand']."&model=".$_POST['model'];
        $sq = "update ".$DB_COMMON['param']." set db_name='".$_SESSION['db_name']."' where device_info='".$device_info."' ";
        $rs = mysqli_query($connect0, $sq) ? "...param update OK" : $sq."...param update FAIL";
        print '</br>'.$rs;
        $sq = "select square_code from ".$DB_CUSTOM['store']." where code = '".$_POST['store_code']."' ";
        // print $sq;
        $square_code = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];

        $sq = "insert into ".$DB_CUSTOM['camera']."(regdate, code, device_info, store_code, square_code, mac, brand, model, usn, product_id, name, comment, enable_countingline, enable_heatmap, enable_snapshot, enable_face_det, enable_macsniff, flag) values(now(), '".$_POST['code']."', '".$device_info."', '".$_POST['store_code']."', '".$square_code."', '".$_POST['mac']."', '".$_POST['brand']."', '".$_POST['model']."', '".$_POST['usn']."', '".$_POST['product_id']."', '".addslashes($_POST['name'])."', '".addslashes($_POST['comment'])."', '".$_POST['enable_countingline']."', '".$_POST['enable_heatmap']."', '".$_POST['enable_snapshot']."', '".$_POST['enable_face_det']."', '".$_POST['enable_macsniff']."', '".$_POST['flag']."')"; 
        $rs = mysqli_query($connect0, $sq) ? "...Camera Update OK" : $sq."...Camera Update Fail";
        print('</br>'.$rs);

        if(isset($_POST['counters']) && $_POST['enable_countingline']=='y') { // counter label update
            foreach($_POST['counters'] as $A=>$counters) {
                // print $counters['name']."--".$counters['label']."</br>'n";
				$sq = "select pk from ".$DB_CUSTOM['counter_label']." where camera_code='".$_POST['code']."' and counter_name='".$counters['name']."'";
				$rs = mysqli_query($connect0, $sq);
				if($rs->num_rows) {
					$sq = " update ".$DB_CUSTOM['counter_label']." set counter_label='".trim($counters['label'])."' where camera_code='".$_POST['code']."' and counter_name='".$counters['name']."'";
				}
				else {
					$sq = "insert into ".$DB_CUSTOM['counter_label']."(camera_code, counter_name, counter_label) values('".$_POST['code']."', '".trim($counters['name'])."', '".trim($counters['label'])."')";
				}				
				$rs = mysqli_query($connect0, $sq) ? "...Counter Label Update OK" : $sq."...Counter Label Update Fail";
                print('</br>'.$rs);
			}
		}        

        $sq ="";
    }
    else {
        $sq ="";
    }
    if($sq) {
        print $sq;
        $rs = mysqli_query($connect0, $sq) ? "update OK" : $sq."update FAIL";
        print $rs;
    }
}
else if($_GET['mode'] == 'delete'){
    // print_r($_GET);    print_r($_POST);
    $rt = false;
    $sq = "select passwd from ".$DB_COMMON['account']." where role='root' or role='admin'";
    $rs = mysqli_query($connect0, $sq);
    while ($row = mysqli_fetch_row($rs)){
        if ($row[0] == $_POST['passwd']) {
            $rt = true;
            break;
        }
    }
    if(!$rt) {
        $strn = "Error: admininistrator password wrong";
    }
    else if($_GET['fr'] == 'square') {
        $sq = "select pk from ".$DB_CUSTOM['store']." where square_code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        if ($rs->num_rows) {
            $strn = "Error: This square has stores, cannot be deleted!";
        }
    }
    else if($_GET['fr'] == 'store') {
        $sq = "select pk from ".$DB_CUSTOM['camera']." where store_code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        if ($rs->num_rows) {
            $strn = "Error: This store has cameras, cannot be deleted!";
        }
    }
    else if($_GET['fr'] == 'camera') {
        $sq = "select pk from ".$DB_CUSTOM['count']." where camera_code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        $ct_num = $rs->num_rows;

        $sq = "select pk from ".$DB_CUSTOM['heatmap']." where camera_code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        $hm_num = $rs->num_rows;

        $sq = "select pk from ".$DB_CUSTOM['age_gender']." where camera_code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        $ag_num = $rs->num_rows;

        $strn = "{Info: This camera has records\nCount: ".$ct_num."\nHeatmap: ".$hm_num."\nAgeGender: ".$ag_num."\n\nAll data will be erased!}...confirmation OK";
    }
    if (!isset($strn) || !$strn) {
        $strn =  "{Are you really want to delete?}...confirmation OK";
    }
    print $strn;
    exit();
    
}
else if($_GET['mode'] == 'delete_act'){
    if($_GET['fr'] == 'square') {
        $sq = "delete from ".$DB_CUSTOM['square']." where code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq) ? "Store:".$_POST['code']."...delete OK" : $sq."...delete FAIL";
        print ($rs);
    }
    else if($_GET['fr'] == 'store') {
        $sq = "delete from ".$DB_CUSTOM['store']." where code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq) ? "Store:".$_POST['code']."...delete OK" : $sq."...delete FAIL";
        print ($rs);
    }
    else if ($_GET['fr'] == 'camera'){
        $sq = "select device_info from ".$DB_CUSTOM['camera']." where code='".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq);
        $device_info = mysqli_fetch_row($rs)[0];

        $sq = "update ".$DB_COMMON['param']." set db_name='none' where device_info='".$device_info."' ";
        $rs = mysqli_query($connect0, $sq) ? "Param:".$device_info."...update OK" : $sq."...update FAIL";
        print $rs."\n";

        $sq = "delete from ".$DB_CUSTOM['counter_label']." where camera_code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq) ? "Counter Label:".$_POST['code']."...delete OK" : $sq."...delete FAIL";
        print $rs."\n";

        $sq = "delete from ".$DB_CUSTOM['camera']." where code = '".$_POST['code']."' ";
        $rs = mysqli_query($connect0, $sq) ? "Camera:".$_POST['code']."...delete OK" : $sq."...delete FAIL";
        print $rs."\n";


    }
}

else if($_GET['mode'] = 'imageNzone'){
    // print_r($_GET);
    $dev_info = "mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model'];
    $sq = "select A.param as param, B.body as snapshot from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B on A.device_info=B.device_info where A.device_info = '".$dev_info."' order by B.regdate desc limit 1";
    $rs = mysqli_query($connect0, $sq);
    $assoc = mysqli_fetch_assoc($rs);
    $assoc['sql'] = $sq;
    $assoc['zone'] = getZoneFromParam($assoc['param']);
    unset($assoc['param']);
    // print_r($assoc);
    print (json_encode($assoc, JSON_PRETTY_PRINT));
}


?>