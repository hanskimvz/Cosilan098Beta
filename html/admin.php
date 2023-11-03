<?PHP
session_start();
// date_default_timezone_set ( "Asia/Seoul" ); 
require_once $_SERVER['DOCUMENT_ROOT']."/libs/functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inc/common.php";

require_once $_SERVER['DOCUMENT_ROOT']."/inc/query_functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inc/page_functions.php";

if(!$_COOKIE['selected_language']) {
	$_COOKIE['selected_language']= 'chi';
}

if(isset($_GET['fr']) && ($_GET['fr'] == 'login' || $_GET['fr'] == 'register' || $_GET['fr'] == 'logout') ){
	include "inc/auth.php";
	exit;
}
else if (isset($_GET['fr']) && $_GET['fr'] == 'system') {
	include "inc/system.php";
	exit;
}

logincheck();
define('TZ_OFFSET', 3600*9);

/* ######################################################################################################### */
if(($_COOKIE['role'] != 'admin') and  ($_COOKIE['role'] != 'root') and ($_GET['fr'] != 'profile')) {
	echo '<script>alert("'.msg('You don\'t have right to acess Admin Page').'");</script>';
	echo '<script>location.href=("/");</script>';
}

if(isset($_GET['lang'])) {
	setcookie('selected_language', $_GET['lang']);
	print_r( $_COOKIE);
	echo '<script>opener.location.reload()</script>';
	echo '<script>self.close()</script>';
	exit;
}

if (!isset($_GET['search'])) {
	$_GET['search'] = "";
}

if (!isset($_GET['mode'])) {
	$_GET['mode'] = "list";
}

if(!isset($_GET['fr'])) {
	$_GET['fr'] = "account";
}
if(!isset($_GET['act'])) {
	$_GET['act'] = "";
}

$uri = $_SERVER['PHP_SELF']."?fr=".$_GET['fr']."&mode=".$_GET['mode']."&search=".$_GET['search'];

$arr_result = array();
$arr_list = array();

include $_SERVER['DOCUMENT_ROOT']."/inc/pageSide.php";

$this_page = $_GET['fr'].'.php';
$msg = q_language($this_page);

$info_pad = '';
$nav_top_left = '';

if(($_GET['fr'] == 'account') or ($_GET['fr'] == 'language')){
	$nav_top_left='
	<form method="GET" class="form-inline d-none d-sm-inline-block" ENCTYPE="multipart/form-data">
		<input type="hidden" name="fr" value="'.$_GET['fr'].'">
		<input type="text" class="form-control form-control-no-border"  placeholder="Search" name="search" value="'.$_GET['search'].'" size="100">
		<button type="submit" class="btn btn-warning" >'.$msg['search'].'</button>
	</form>';
}
else if($_GET['fr'] == 'database') {
	$nav_top_left='
	<form method="GET" class="form-inline d-none d-sm-inline-block" ENCTYPE="multipart/form-data">
		<input type="hidden" name="fr" value="'.$_GET['fr'].'">
		<input type="hidden" name="db" value="'.$_GET['db'].'">
		<input type="text" class="form-control form-control-no-border"  placeholder="Search" name="search" value="'.$_GET['search'].'" size="100">
		<button type="submit" class="btn btn-warning" >'.$msg['search'].'</button>
	</form>';
}

function search_stringXX($search, $keyword){
	if(!trim($search)) {
		return "";
	}
	if(strpos(trim($search),"&")){
		$s_str = explode("&", trim($search));
		$sp = " and ";
	}
	else {
		$s_str = explode(" ", trim($search));
		$sp = " or ";
	}
				
	for($i=0, $sqa=''; $i<sizeof($s_str); $i++) {
		$s_str[$i] = trim($s_str[$i]);
		if(!$s_str[$i]) {
			continue;
		}
		if(!strncmp( $s_str[$i],"!",1)) {
			$s_str[$i] = substr($s_str[$i],1,strlen($s_str[$i])-1);
			$s_str[$i] = " not like '%".$s_str[$i]."%'";
		}
		else {
			$s_str[$i] = " like '%".$s_str[$i]."%'";
		}
		if($sqa) {
			$sqa .=  $sp;
		}
		for($j=0, $sqb=''; $j<sizeof($keyword); $j++) {
			$keyword[$j] = trim($keyword[$j]);
			if($sqb) {
				$sqb .= "or ";
			}
			$sqb .= $keyword[$j]." ".$s_str[$i]." ";
		}
		$sqa .= "(".$sqb.")";
	}
	print $sqa;
	return $sqa;
}
function search_string($search, $arr_keyword){
	if(!trim($search)) {
		return "";
	}
	if(strpos(trim($search),"&")){
		$s_str = explode("&", trim($search));
		$sp = " and ";
	}
	else {
		$s_str = explode(" ", trim($search));
		$sp = " or ";
	}
	$arr = array();				
	for($i=0; $i<sizeof($s_str); $i++) {
		$strn = trim($s_str[$i]);
		if(!$strn) {
			continue;
		}
		$strn = !strncmp($strn, "!", 1) ? "not like '%".trim(substr($strn, 1, strlen($strn)-1))."%'" : "like '%".trim($strn)."%'";
		$arr_s = array();
		for($j=0, $sqb=''; $j<sizeof($arr_keyword); $j++) {
			$keyword = trim($arr_keyword[$j]);
			array_push($arr_s, $keyword." ".$strn);
		}
		array_push($arr, "(".implode(" or ", $arr_s).")");
	}
	$sq = implode($sp, $arr);
	// print $sq;
	return $sq;
}

if($_GET['fr'] == 'profile') {
	include $_SERVER['DOCUMENT_ROOT']."/inc/profile.php";
	$pageContents = <<<EOPAGE
    <main class="content">
        <div class="container-fluid p-0">
			<div class="row" id="profile_info_pad" style="display:''">$pageInfoPad </div>
		</div>
    $pageFoot
    </main>
EOPAGE;
}

else if($_GET['fr'] == 'account') {
	include $_SERVER['DOCUMENT_ROOT']."/inc/profile.php";
	// LIST
	$sq = "select A.pk, A.code, A.ID, A.email, A.db_name, A.flag, A.role, B.code as Bcode, B.name, B.telephone from ".$DB_COMMON['account']." as A left outer join ".$DB_CUSTOM['account']." as B on A.code=B.code where (A.db_name='".$_SESSION['db_name']."' or A.db_name='none' or A.db_name is null) ";
	// print $sq;
	if(trim($_GET['search'])) {
		$sq .= "and ".search_string($_GET['search'], array('A.code', 'A.ID', 'A.db_name', 'A.role', 'B.name'));
	}
	$sq .= " order by A.pk desc ";

	$sqc = "select A.pk ". substr($sq, strpos($sq,"from"), strlen($sq));
	$rs = mysqli_query($connect0, $sqc);

	$TOTAL_RECORD = $rs->num_rows;
	if(!isset($_GET['page_max']) || !$_GET['page_max']) {
		$_GET['page_max'] = 20;
	}
	if(!isset($_GET['page_no']) || !$_GET['page_no']) {
		$_GET['page_no'] = 1;
	}
	$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
	$sq .= " limit ".$offset.", ".$_GET['page_max'];
	$uri_href = $uri.'&page_no='.$_GET['page_no'].'&page_max='.$_GET['page_max'];
	// print $sq;
	$Pagination = Pagination($uri, $TOTAL_RECORD, $_GET['page_no'],$_GET['page_max'],'appstack');

	$rs = mysqli_query($connect0, $sq);
	$TABLE_BODY = "";
	$TABLE_BODY_FLOAT = "";
	while($assoc = mysqli_fetch_assoc($rs))	{
		if($assoc['db_name'] == $_SESSION['db_name']) {
			$TABLE_BODY .= '<tr>
				<td>'.$assoc['pk'].'</td>
				<td><span onMouseOver="this.style.cursor=\'pointer\'" onClick="viewUserInfo(\''.$assoc['code'].'\')">'.$assoc['code'].'</span></td>
				<td><span onMouseOver="this.style.cursor=\'pointer\'" onClick="viewUserInfo(\''.$assoc['code'].'\')">'.$assoc['ID'].'</span></td>
				<td>'.$assoc['name'].'</td>
				<td>'.$assoc['email'].'</td>
				<td>'.$assoc['telephone'].'</td>
				<td>'.$assoc['role'].'</td>
				<td>'.$assoc['db_name'].'</td>
			</tr>';
		}
		else {
			$TABLE_BODY_FLOAT .= '<tr>
				<td>'.$assoc['pk'].'</td>
				<td><span onMouseOver="this.style.cursor=\'pointer\'" onClick="viewUserInfo(\''.$assoc['code'].'\')">'.$assoc['code'].'</span></td>
				<td><span onMouseOver="this.style.cursor=\'pointer\'" onClick="viewUserInfo(\''.$assoc['code'].'\')">'.$assoc['ID'].'</span></td>
				<td>'.$assoc['email'].'</td>
				<td>'.$assoc['role'].'</td>
				<td>'.$assoc['db_name'].'</td>
			</tr>';
		}
	}

	$TABLE_HEAD = '<tr><th>pk</th><th>'.$msg['code'].'</th><th>'.$msg['id'].'</th><th>'.$msg['name'].'</th><th>'.$msg['email'].'</th><th>'.$msg['telephone'].'</th><th>'.$msg['role'].'</th><th>'.$msg['db_name'].'</th></tr>';
	$TABLE_HEAD_FLOAT = '<tr><th>pk</th><th>'.$msg['code'].'</th><th>'.$msg['id'].'</th><th>'.$msg['email'].'</th><th>'.$msg['db_name'].'</th><th>'.$msg['flag'].'</th></tr>';
	$TABLE_USERS = '<table class="table table-striped table-sm table-bordered table-hover"><thead>'.$TABLE_HEAD.'</thead><tbody>'.$TABLE_BODY.'</tbody></table>';
	$TABLE_USERS_FLOAT = '<table class="table table-striped table-sm table-bordered table-hover"><thead>'.$TABLE_HEAD_FLOAT.'</thead><tbody>'.$TABLE_BODY_FLOAT.'</tbody></table>';

	$pageContents = <<<EOPAGE
		<main class="content">
			<div class="container-fluid p-0">
				<div class="row" id="profile_info_pad" style="display:none">
					$pageInfoPad 
				</div>
				<div class="row">
					<div class="col-12 col-lg-12">
						<div class="float-right mr-3" >$msg[total]: $TOTAL_RECORD</div>
						$Pagination
						<div class="card">
							<div class="card-header">
								<h3 class="card-title mb-0"><b>$msg[accountlist]</b></h3>
							</div>
							<div class="card-body">
								<div class="table-responsive">$TABLE_USERS</div>
							</div>	
						</div>
						<div class="card mt-0">
							<div class="card-header">
								<h3 class="card-title mb-0"><b>$msg[floatingaccount]</b></h3>
							</div>
							<div class="card-body w-100">
								<div class="table-responsive">$TABLE_USERS_FLOAT.</div>
							</div>	
						</div>
					</div>
				</div>						
			</div>
		$pageFoot
		</main>
EOPAGE;
}

else if($_GET['fr'] =='view_param') {
	if (!isset($_GET['view'])) {
		$_GET['view'] = 'basic';
	}

	include $_SERVER['DOCUMENT_ROOT']."/inc/view_device_param.php";
	
	if ($_GET['view'] == 'basic') {
		print $pageHead;
		print $pageContents;
		echo "\n\r";
		echo '<script type="text/javascript" src="/js/app.js"></script>'."\r\n";
		exit();
	}
}

else if($_GET['fr'] == 'snapshot') {
	if($_GET['f'])  {
		$sq = "select date, thumbnail, device_info from ".$DB_COMMON['face']." where pk=".$_GET['pk'];
		$width = "50%";
	}
	else {
		$sq = "select regdate, body, device_info from ".$DB_COMMON['snapshot']." where pk=".$_GET['pk'];
		$width = "100%";
	}
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	$row = mysqli_fetch_row($rs);
	
	$pageSide ='';
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card">
						<div class="card-header">
							<div class="float-right ml-3" >$row[0]</div>
							<h3 class="card-title mb-0"><b>$row[2]</b></h3>
						</div>
						<div class="card-body img-fluid"">
							<img  src="$row[1]" width="$width">
						</div>
					</div>
				</div>
			</div>
		</div>
		$pageFoot
	</main>
EOPAGE;
}
	
else if($_GET['fr'] =='list_device') {
	$msg = q_language('camera.php');
	$sq = "select pk as fpk, device_info, product_id, usn, initial_access, last_access, db_name from ".$DB_COMMON['param']." where db_name='".$_SESSION['db_name']."' or db_name='none' or db_name is null ";

	$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
	$rs = mysqli_query($connect0, $sqc);
	$TOTAL_RECORD = $rs->num_rows;
	if (ord(strtolower($_SESSION['db_name'][0])) > ord('n')) {
		$sq .= " order by db_name desc, last_access desc ";	
	}
	else {
		$sq .= " order by db_name asc, last_access desc ";
	}
	if(!isset($_GET['page_max']) || !$_GET['page_max']) {
		$_GET['page_max'] = 10;	
	}
	if(!isset($_GET['page_no']) || !$_GET['page_no']) {
		$_GET['page_no'] = 1;
	}
	$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
	$sq .= " limit ".$offset.", ".$_GET['page_max'];

	$Pagination = Pagination($uri, $TOTAL_RECORD, $_GET['page_no'],$_GET['page_max'],'appstack');
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	$ff = 0;
	$table_body = "";
	while($assoc = mysqli_fetch_assoc($rs)) {
		// print_r($assoc);
		// list($assoc['mac'], $assoc['brand'], $assoc['model']) = explode("&", $assoc['device_info']);
		// $assoc['mac'] = trim(array_pop(explode("=", $assoc['mac'])));
		// $assoc['brand'] = trim(array_pop(explode("=", $assoc['brand'])));
		// $assoc['model'] = trim(array_pop(explode("=", $assoc['model'])));
		$arr = exDevInfo($assoc['device_info']);
		$assoc['mac'] = $arr['mac'];
		$assoc['brand'] = $arr['brand'];
		$assoc['model'] = $arr['model'];

		$sq = "select body from ".$DB_COMMON['snapshot']." where device_info= '".$assoc['device_info']."'";
		$assoc['snapshot'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
		$assoc['snapshot'] = '<img src="'.$assoc['snapshot'].'" width="100" height="50" data-toggle="modal" data-target="#modalSnapshot" OnClick="viewSnapshot(this,\''.$assoc['device_info'].'\')" onMouseOver="this.style.cursor=\'pointer\'">';
		if($assoc['db_name'] == $_SESSION['db_name']){
			$sq = "select A.name as name, B.name as store_name from ".$DB_CUSTOM['camera']." as A inner join ".$DB_CUSTOM['store']." as B on A.store_code = B.code where A.device_info='".$assoc['device_info']."' ";
			list($assoc['name'], $assoc['store_name'])  = mysqli_fetch_row(mysqli_query($connect0, $sq));
		}
		else {
			$assoc['name'] = "";
			$assoc['store_name'] = "";
			if (!$ff) {
				$table_body .= '<tr><td colspan="11" class="text-center">================= <span class="ml-3 mr-3 badge badge-success">FLOATING CAMERAS</span> ================</td></tr>';
				$ff = 1;
			}
		}
		// onMouseOver="this.style.cursor=\'pointer\'"
		$table_body .= '<tr>
			<td>'.$assoc['fpk'].'</td>
			<td>'.$assoc['snapshot'].'</td>
			<td><button class="btn mb-0" onClick="showDeviceInfo(this, '.$assoc['fpk'].')" >'.$assoc['mac'].'</button></td>
			<td>'.$assoc['brand'].'</td>
			<td>'.$assoc['model'].'</td>
			<td>'.$assoc['usn'].'</td>
			<td>'.$assoc['product_id'].'</td>
			<td>'.$assoc['name'].'</td>
			<td>'.$assoc['store_name'].'</td>
			<td>'.$assoc['last_access'].'</td>
			<td>'.$assoc['db_name'].'</td>
			</tr>';

	}
	$TABLE_HEAD ='<th>pk</th><th>'.$msg['snapshot'].'</th><th>'.$msg['mac'].'</th><th>'.$msg['brand'].'</th><th>'.$msg['model'].'</th><th>'.$msg['usn'].'</th><th>'.$msg['productid'].'</th><th>'.$msg['name'].'</th><th>'.$msg['storegroup'].'</th><th>'.$msg['lastaccess'].'</th><th>'.$msg['db_name'].'</th></tr>';
	$TABLE_HEAD = '<thead>'.$TABLE_HEAD.'<thead>';
	$TABLE = '<table class="table table-striped table-sm table-bordered table-hover">'.$TABLE_HEAD.'<tbody>'.$table_body.'</tbody></table>';

	
	$ADD_TAG =<<<EOBLOCK
	<div class="form-group form-inline mb-0">
		<input type="hidden" id="mode" value="add_camera">
		<input type="hidden" id="fpk" value="0">
		<label class="ml-3">IP:</label>
		<input type="text" class="form-control ml-2" id="IP" value="">
		<label class="ml-3">ID:</label>
		<input type="text" class="form-control ml-2" id="userid" value="root">
		<label class="ml-3">PASSWORD:</label>
		<input type="text" class="form-control ml-2" id="passwd" value="pass">
		<button class="btn btn-sm btn-primary ml-3" onClick="checkDevice(this)">Check</button>
		<span id="result" class="ml-3"></span>
	</div>
	
EOBLOCK;
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row" >
				<div class="col-12 col-lg-5 d-flex">$Pagination</div>
				<div class="col-12 col-lg-5"><button class="btn btn-secondary btn-sm mb-0" onClick="showDeviceInfo(this, 0)">$msg[addmanual]</button></div>
				<div class="col-12 col-lg-2 text-right"><span class="text-right">$msg[total]: $TOTAL_RECORD</span></div>
			</div>	
			<div class="row" id="deviceinfoTag" style="display:none">
				<div class="col-12 col-lg-12" >
					<div class="card">
						<div class="card-header">
							<h3 class="card-title mb-0"><b id="deviceinfotab_head"></b></h3>
						</div>
						<div class="card-body">
						$ADD_TAG
						</div>
					</div>
				</div>
			</div>
			<div class="row"><div class="col-12 col-lg-12"><div class="card">$TABLE</div></div></div>
		</div>
		<div class="modal fade" id="modalSnapshot" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 id="modal_device_info"></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body m-1">
					<div class="text-center" id="modal_snapshot"></div>
					</div>
				</div>
			</div>
		</div>
		$pageFoot
		
	</main>
EOPAGE;
	
}


else if($_GET['fr'] == 'device_tree') {
	include $_SERVER['DOCUMENT_ROOT']."/inc/device_tree.php";
	$device_tree_str = <<<EOBLOCK
    <style>
    .easy-tree{ min-height:20px; margin-bottom:20px; color:#000;border:0; border-top:0; padding-bottom:15px }
    .easy-tree>ul{ padding-left:10px;  }
    .easy-tree li{ list-style-type:none; margin:0; padding:10px 20px 0;	position:relative}
    .easy-tree li::after,.easy-tree li::before{	content:'';	left:-30px;	position:absolute;	right:auto }
    .easy-tree li::before{ border-left:1px solid #888; bottom:10px; height:100%; top:0;	width:1px }
    .easy-tree li::after{ border-top:1px solid #888; height:3px; top:25px; width:50px; }
    .easy-tree li>span, .easy-tree li>div { -moz-border-radius:5px; -webkit-border-radius:5px; border:1px solid #add; border-radius:5px; display:inline-block; padding:5px 8px;min-width:200px; min-height:10px; text-decoration:none; background-color:#add;}
    .easy-tree li.parent_li>div{ cursor:pointer	}
    .easy-tree>ul>li::after,.easy-tree>ul>li::before{ border:0 }
    .easy-tree li:last-child::before{ height:26px }
    .easy-tree li>span>a{ color:#111; text-decoration:none}
    </style>
    <div class="easy-tree" id="device_tree_main_panel">
        <ul>
            <li>
                <div class="glyphicon card border col-md-12 mb-2" style="background-color:#fefefe;">
                    <div class="card-actions float-right dropdown show mr-2">
                        <a href="#" data-toggle="dropdown" data-display="static"><span class="mt-0">&#9661;</span></a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <span type="button" class="dropdown-item" OnClick="viewSquareInfo(0)">$msg[addsquare]</a>
                        </div>
                    </div>
                    <h5 class="card-title ml-2 mb-0"><span class="mr-2">&#10133;</span><b>$msg[devicetree]</b></h5>
                </div>
                $device_tree_str
            </li>
        </ul>
    </div>
EOBLOCK;
// <i class="align-middle" data-feather="more-horizontal"></i>
    $pageContents = <<<EOPAGE
    <main class="content">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-12 col-lg-12 col-xl-5">
                    <div class="card">
                        <div class="card-body">$device_tree_str</div>
                    </div>
                </div>
                <div id="info_frame" class="col-12 col-lg-7 col-xl-7" style="position:relative;">
                    <div id="info_page"></div>
                    <div id="rs"></div>
                    <div class="card col-md-12" id="delete_pad" style="display:none">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="col-form-label">$msg[areyousuretodeletethisrecord]</label><label class="col-form-label ml-2">$msg[typeadminpassword]</label>
                                <input type="password" class="form-control"  id="admin_password">
                            </div>
                            <div class="text-center">
                                <button type="button" class="btn btn-warning ml-3" OnClick="deleteInfo()">$msg[confirmdelete]</button>
                                <button type="button" class="btn btn-secondary mr-3" OnClick="document.getElementById('delete_pad').style.display='none';">$msg[close]</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        $pageFoot
    </main>
EOPAGE;
}

else if($_GET['fr'] == 'counter_label_set') {
	$msg= q_language('camera.php');
	$arr_label = ['entrance', 'exit', 'outside', 'none'];
	$sq = "select counter_label from ".$DB_CUSTOM['counter_label']." group by counter_label";
	$rs = mysqli_query($connect0, $sq);
	while ($assoc = mysqli_fetch_assoc($rs)){
		 array_push($arr_label, $assoc['counter_label']);
		 $arr_label = array_unique($arr_label);
	}
	// print_r($arr_label);
	$table_body = "";
	for($i=0; $i<10; $i++)	{
		if (!isset($arr_label[$i])){
			$arr_label[$i] = "";
		}
		$readonly = in_array($arr_label[$i],  ['entrance', 'exit', 'outside', 'none']) ? 'readonly':'';

		// $sq = "select ".$_COOKIE['selected_language']." from ".$DB_CUSTOM['language']." where varstr='".$arr_label[$i]."' ";
		// $disp = mysqli_fetch_row(mysqli_query($connect0, $sq))[0]; 
		$disp = queryLang('footfall', $arr_label[$i]);
		if ($disp == "{}") {
			$disp = "";
		}
		$table_body .= '<input type="hidden" class="form-control" id="ct_old['.$i.']" value="'.$arr_label[$i].'">
			<tr>
				<td><input type="text" class="form-control" id="ct['.$i.']" value="'.$arr_label[$i].'" '.$readonly.'></td>
				<td><input type="text" class="form-control" id="lbl['.$i.']" value="'.$disp.'"></td>
			</tr>';
	}
	$table_body = '<table class="table table-striped table-sm table-bordered"><th>'.$msg['counterlabel'].'</th><th>'.$msg['display'].'</th>'.$table_body.'</table>';

	// $arr_sq = array();
	// $sq = "select * from ".$DB_CUSTOM['counter_label']." order by camera_code asc ";
	$sq = "select  B.device_info, A.* from ".$DB_CUSTOM['counter_label']." as A left outer join ".$DB_CUSTOM['camera']." as B on A.camera_code=B.code order by A.camera_code asc";
	$rs = mysqli_query($connect0, $sq);
	$table_body_label= "";
	while($assoc = mysqli_fetch_assoc($rs)){
		// print_r($assoc);
		if(!$assoc['camera_code']) {
			continue;
		}
		// $sq = "select device_info from ".$DB_CUSTOM['camera']." where code='".$assoc['camera_code']."' ";
		// $assoc['device_info'] =  mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
		$table_body_label .= '<tr>
		<td>'.$assoc['device_info'].'</td>
		<td>'.$assoc['counter_name'].'</td>
		<td>'.$assoc['counter_label'].'</td>
		<td>'.$assoc['flag'].'</td>
		</tr>';
		// array_push($arr_sq, "update ".$DB_CUSTOM['count']." set counter_label='".$assoc['counter_label']."' where device_info='".$assoc['device_info']."' and counter_name='".$assoc['counter_name']."' " );
	}

	// print_r($arr_sq);
	$table_body_label = '<table class="table table-striped table-sm table-bordered"><th>'.$msg['deviceinfo'].'</th><th>'.$msg['countername'].'</th><th>'.$msg['counterlabel'].'</th>'.$table_body_label.'</table>';

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12 col-xl-12">
					<div class="card">
						<div class="card-body">$table_body</div>
						<div class="text-center">
							<button type="button" class="btn btn-primary mb-2" name="btn" value="modify" onClick="set_counter_label()">$msg[save_changes]</button>
						</div>
						<div id="result_tag"><div>
					</div>
					
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12 col-xl-12">
					<div class="card">
						<div class="card-body">$table_body_label</div>
					</div>
					
				</div>
			</div>

		</div>
		$pageFoot
	</main>
EOPAGE;

}

else if($_GET['fr'] == 'faceset') {
	
	
	
}

else if($_GET['fr'] == 'face_setting') {
	
	
	
	
}
##############################################  DATABASE  ##################################################################
else if($_GET['fr'] == 'database') {
	include $_SERVER['DOCUMENT_ROOT']."/inc/database.php";
}

else if($_GET['fr'] == 'language') {
	// print_r($_SERVER);
	if (isset($_POST['act'])){
		if($_POST['act'] == 'add') {
			$sq = "insert into ".$DB_CUSTOM['language']."(varstr, chi, kor, eng, page) values('".trim($_POST['var'])."', '".trim($_POST['chi'])."', '".trim($_POST['kor'])."', '".trim($_POST['eng'])."', '".trim($_POST['page'])."')";
			mysqli_query($connect0, $sq);
			$order_by = "pk desc ";
		}
		else if($_POST['act'] == 'modify') {
			if($_POST['btn'] == 'modify') {
				$sq = "update ".$DB_CUSTOM['language']." set varstr = '".trim($_POST['var'])."', chi = '".trim($_POST['chi'])."', kor = '".trim($_POST['kor'])."', eng = '".trim($_POST['eng'])."', page='".$_POST['page']."' where pk=".$_POST['pk'];
				mysqli_query($connect0, $sq);
			}
			else if($_POST['btn'] == 'copy') {
				$sq = "insert into ".$DB_CUSTOM['language']."(varstr, chi, kor, eng) values('".trim($_POST['var'])."', '".trim($_POST['chi'])."', '".trim($_POST['kor'])."', '".trim($_POST['eng'])."')";
				mysqli_query($connect0, $sq);
			}
			// print $sq;
		}
	}
	$sq = "select pk, varstr, eng, chi, kor, page from ".$DB_CUSTOM['language']." ";
	if(trim($_GET['search'])) {
		$sq .= "where varstr like '%".trim($_GET['search'])."%' or eng like '%".trim($_GET['search'])."%' or chi like '%".trim($_GET['search'])."%' or kor like '%".trim($_GET['search'])."%' or page like '%".trim($_GET['search'])."%'  ";
	}
	
	$TOTAL_RECORD = mysqli_query($connect0, $sq)->num_rows;
	
	if(!isset($order_by)) {
		$order_by = "varstr asc";
	}
	$sq .= " order by ".$order_by;
	if(!isset($_GET['page_max'])) {
		$_GET['page_max'] = 20;
	}
	if(!isset($_GET['page_no'])) {
		$_GET['page_no'] = 1;
	}
	$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
	$sq .= " limit ".$offset.", ".$_GET['page_max'];
	
	$uri =" ./admin.php?fr=".$_GET['fr']."&search=".$_GET['search'];
	$Pagination = Pagination($uri, $TOTAL_RECORD, $_GET['page_no'],$_GET['page_max'],'appstack');
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	$row_count = $rs->num_rows; 

	$arr_list = Query2Array($connect0,$sq);
	$table_body = "";
	for($i=0; $i<sizeof($arr_list); $i++) {
		$table_body.= '
			<form name="form'.$arr_list[$i]['pk'].'" method="post"  ENCTYPE="multipart/form-data" >
				<input type="hidden" name="act" value="modify">
				<input type="hidden" name="pk" value="'.$arr_list[$i]['pk'].'">
				<tr>
				<td>'.$arr_list[$i]['pk'].'</td>
				<td><input type="text" class="form-control" name="var" value="'.$arr_list[$i]['varstr'].'"></td>
				<td><input type="text" class="form-control" name="chi" value="'.$arr_list[$i]['chi'].'"></td>
				<td><input type="text" class="form-control" name="kor" value="'.$arr_list[$i]['kor'].'"></td>
				<td><input type="text" class="form-control" name="eng" value="'.$arr_list[$i]['eng'].'"></td>
				<td><input type="text" class="form-control" name="page" value="'.$arr_list[$i]['page'].'"></td>
				<td>
					<button type="submit" class="btn btn-warning btn-sm" name="btn" value="modify">'.$msg['modify'].'</button>
					<button type="submit" class="btn btn-primary btn-sm ml-4" name="btn" value="copy">'.$msg['copy'].'</button>
				</td>
				</tr>
			</form>';
	}

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row" >
				<div class="col-12 col-lg-5 d-flex" >$Pagination</div>
				<div class="col-12 col-lg-5 d-flex"></div>
				<div class="col-12 col-lg-2 text-right"><span class="text-right">$msg[total]: $TOTAL_RECORD</span></div>
			</div>	
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card">
						<div class="card-body w-100">
							<div class="table-responsive">
								<table class="table table-striped table-sm table-bordered table-hover" >
									<thead>
										<tr>
											<th>PK</th>
											<th>VAR</th>
											<th>CHI</th>
											<th>KOR</th>
											<th>ENG</th>
											<th>PAGE</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										<form name="form0" method="post" action="" ENCTYPE="multipart/form-data" >
											<input type="hidden" name="act" value="add"> 
											<tr>
												<td>$msg[add]</td>
												<td><input type="text" class="form-control" name="var" ></td>
												<td><input name="chi" class="form-control"></td>
												<td><input name="kor"  class="form-control"></td>
												<td><input name="eng"  class="form-control"></td>
												<td><input name="page" class="form-control"></td>
												<td><input type="button" class="btn btn-success btn-sm" value="$msg[add]" onclick="submit()"></td>
											</tr>
										</form>
										$table_body
									</tbody>
								</table>
							</div>
						</div>	
					</div>
				</div>
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'local_device_setup') {
	if($_POST) {
		print_arr($POST);
		print $rs;
	}
	$timeout =2;
	$discover_message  = 'M-SEARCH * HTTP/1.1' . "\r\n";
	$discover_message .= 'HOST: 239.255.255.250:1900' . "\r\n";
	$discover_message .= 'MAN: "ssdp:discover"' . "\r\n";
	$discover_message .= "MX: 3\r\n";
	$discover_message .= 'ST:urn:schemas-upnp-org:device:nvcdevice' . "\r\n";
	$discover_message .= '' . "\r\n";
	
	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
	socket_sendto($socket, $discover_message, strlen($discover_message), 0, '239.255.255.250', 1900);
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));

	$response = array();
	do {
		$data = socket_read($socket, 4096, PHP_BINARY_READ);
		if($data) {
			array_push($response, $data);
		}
    } while ($data);
	print_r($response);
	
	$arr_rs = array();

	for($i=0; $i<sizeof($response); $i++) {
		$lines = explode("\n", $response[$i]);
		// print_r($lines);

		for ($j=0; $j<sizeof($lines); $j++){
			if(strpos(" ".$lines[$j], "LOCATION:")) {
				$url = str_replace("LOCATION:", "", $lines[$j]);
				$url = trim($url);
				// print $url;
				$xmlstr= file_get_contents($url);
				// print $xmlstr;
				$xmlstr = substr($xmlstr, strpos($xmlstr,'<root '), strlen($xmlstr));
				$arr_xml = new SimpleXMLElement($xmlstr);
				print_r($arr_xml);
				$x =  ($arr_xml->device->modelName);
				print $x;
				$arr_rs[$i]['modelName'] = $x;
				$arr_rs[$i]['modelURL'] = $arr_xml->device->modelURL;
				$arr_rs[$i]['serialNumber'] = $arr_xml->device->serialNumber;
			}
		}
	}

	print_r($arr_rs);

	$arr_result['tlss_port'] = 5100;
	$arr_result['tlss_interval']=1;
	if(!$arr_result['tlss_ip_address']) {
		$arr_result['tlss_ip_address'] = $_SERVER['HTTP_HOST'];
	}
	
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="card col-md-6">
			<div class="card-header"><h5 class="card-title mb-0">$msg[deviceipaddress]</h5></div>
			<div class="card-body">
				<form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
					<div class="col-md-12">
						<div class="form-row">
							<div class="form-group col-md-6">								
								<label class="col-form-label">$msg[deviceipaddress]</label>
								<input type="text" name="dev_ip_address" class="form-control" value="$arr_result[dev_ip_address]">
							</div>
						</div>
						<div class="form-row">
							<div class="form-group col-md-6">								
								<label class="col-form-label">$msg[tlssipaddress]</label>
								<input type="text" name="tlss_ip_address" class="form-control" value="$arr_result[tlss_ip_address]">
							</div>
							<div class="form-group col-md-3">								
								<label class="col-form-label">$msg[tlssport]</label>
								<input type="text" name="tlss_port" class="form-control" value="$arr_result[tlss_port]" readonly>
							</div>
							<div class="form-group col-md-3">								
								<label class="col-form-label">$msg[tlssinterval]</label>
								<input type="text" name="tlss_interval" class="form-control" value="$arr_result[tlss_interval]">
							</div>
						</div>
						<div class="form-row">							
							<div class="form-group">
								<label>$msg[function]</label></br>
								<label class="form-check form-check-inline">
									<input class="form-check-input" type="radio" name="fnct" value="counting" $arr_result[counting]>
									<span class="form-check-label">$msg[countingcamera]</span>
								</label>
								<label class="form-check form-check-inline">
									<input class="form-check-input" type="radio" name="fnct" value="face" $arr_result[face]>
									<span class="form-check-label">$msg[facedet]</span>
								</label>
								<label class="form-check form-check-inline">
									<input class="form-check-input" type="checkbox" name="macsniff" value="kor" $arr_result[macsniff]>
									<span class="form-check-label">$msg[macsniff]</span>
								</label>
							</div>
						</div>
						<div class="text-center"><button type="submit" class="btn btn-primary" name='btn' value='modify'>$msg[setup]</button></div>
					</div>
				</form>
			</div>
		</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'message_setup') {
	$msg = q_language('message.php');
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<h1 class="h3 mb-3">Message</h1>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header form-row">
							<input type="hidden" id="pk" value="">
							<div class="form-group col-md-2">
								<label>category</label>
								<select id="category" class="form-control" >
									<option value= "info">INFO</option>
									<option value= "version">Version</option>
									<option value= "feedback">Feedback</option>
									<option value= "message">Message</option>
								</select>
							</div>
							<div class="form-group col-md-6"><label>title</label><input type="text" id="title" class="form-control"></div>
							<div class="form-group col-md-2"><label>from</label><input type="text" id="from_p" class="form-control"></div>							
							<div class="form-group col-md-2"><label>to</label><input type="text" id="to_p" class="form-control"></div>							
						</div>
						<div class="card-body">
							<div class="clearfix">
								<div id="quill-toolbar">
									<span class="ql-formats"><select class="ql-font"></select><select class="ql-size"></select></span>
									<span class="ql-formats"><button class="ql-bold"></button><button class="ql-italic"></button><button class="ql-underline"></button><button class="ql-strike"></button></span>
									<span class="ql-formats"><select class="ql-color"></select><select class="ql-background"></select></span>
									<span class="ql-formats"><button class="ql-script" value="sub"></button><button class="ql-script" value="super"></button></span>
									<span class="ql-formats"><button class="ql-header" value="1"></button><button class="ql-header" value="2"></button><button class="ql-blockquote"></button><button class="ql-code-block"></button></span>
									<span class="ql-formats"><button class="ql-list" value="ordered"></button><button class="ql-list" value="bullet"></button><button class="ql-indent" value="-1"></button><button class="ql-indent" value="+1"></button></span>
									<span class="ql-formats"><button class="ql-direction" value="rtl"></button><select class="ql-align"></select></span>
									<span class="ql-formats"><button class="ql-link"></button><button class="ql-image"></button><button class="ql-video"></button></span>
									<span class="ql-formats"><button class="ql-clean"></button></span>
								</div>
								<div id="quill-editor" contenteditable="true"></div>
							</div>
							<div class="mt-3"></div>
							<button type="button" class="btn btn-lg btn-primary" OnClick="writeContents()">$msg[save_changes]</button>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-body">
						<table class="table table-striped table-sm">
							<thead>
								<tr><th>category</th><th>title</th><th>from</th><th>to</th><th>body</th><th>date</th></tr>
							</thead>
							<tbody id="table_body">
							</tbody>
						</table>
						</div>
					</div>
				</div>
			</div>
		</div>	
	$pageFoot
	</main>
	<link rel="stylesheet" href="/css/all.css">	
EOPAGE;
}

else if($_GET['fr'] == 'web_update') {
	// print $_SERVER['HTTP_HOST'];
	if($CLOUD_SERVER != $_SERVER['HTTP_HOST']) {
		$rs = exec("ping ".$CLOUD_SERVER." -n 1",$retval);
		// print ($rs);
		if(strpos($rs, "(100% loss)")) {
			$stat_str = "No Connection";
		}
		else { 
			$body = file_get_contents("http://".$CLOUD_SERVER."/release.php?info=true");
			// print $body;
			$lines = explode("\r\n", $body);
			for($i=0; $i<sizeof($lines); $i++) {
				if(!$lines[$i]) {
					continue;
				}
				$ex_line = explode("=", $lines[$i]);
				$ex_line[0] =  trim($ex_line[0]);
				$ex_line[1] =  trim($ex_line[1]);
				${$ex_line[0]} = $ex_line[1];
			}
			$fname = "version.ini"; 
			if(file_exists($fname)) {
				$fpp = fopen($fname,'r');
				$body = fread($fpp, filesize($fname));
				fclose($fpp);
				$lines = explode("\r\n", $body);
				for($i=0; $i<sizeof($lines); $i++) {
					if(!$lines[$i]) {
						continue;
					}
					$ex_line = explode("=", $lines[$i]);
					$ex_line[0] =  trim($ex_line[0]);
					$ex_line[1] =  trim($ex_line[1]);
					${$ex_line[0]} = $ex_line[1];
				}
			}

			if($version == $current_version) {
				$stat_str =  "Already Current version";
			}
			else {
				$stat_str =  "New version available";
				$btn_update = '<button type="button" class="btn btn-lg btn-primary" OnClick="update_web_software(\''.$server.'\')">'.$msg['update'].'</button>';
			}
		}
	}
	$pageContents = <<<EOPAGE
		<main class="content">
		<div class="container-fluid p-0">
			<h1 class="h3 mb-3">Web page Update</h1>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-body">
							<div class="form-row mt-2">
								<div class="form-group col-md-6">
									<label>Current Version: </label>
									<label>$current_version</label>
								</div>
								<div class="form-group col-md-6">
									<label>Available Version: </label>
									<label>$version</label>
								</div>
							</div>									
							<div class="form-group">
								<label>*$stat_str</label>
							</div>
							<div class="form-group" id="status_bar"></div>							
							$btn_update
						</div>
					</div>
				</div>
			</div>
		</div>	
	$pageFoot
	</main>
EOPAGE;
	
}

else if($_GET['fr'] == 'webpageConfig') {
	require_once "inc/webpage_config.php";
}
else if($_GET['fr'] == 'update_language') {
	$sq = "select * from cnt_demo.language";
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	while ($assoc = mysqli_fetch_assoc($rs)){
		// print_r($assoc);
		$sqa = "select * from common.language where varstr='".$assoc['varstr']."' and page='".$assoc['page']."' ";
		$rsa = mysqli_query($connect0, $sqa);
		if (!($rsa -> num_rows)) {
			$sqa = "insert into common.language(varstr, chi, kor, eng, page) values('".$assoc['varstr']."', '".addslashes($assoc['chi'])."', '".addslashes($assoc['kor'])."', '".addslashes($assoc['eng'])."', '".$assoc['page']."') ";
			print $sqa."</br>\n";
			mysqli_query($connect0, $sqa);
		}
		
	}
}

$pageBody = <<<EOPAGE
<body>
	<div class="wrapper">$pageSide
		<div class="main" id="main_page">
			<nav class="navbar sidebar-sticky navbar-expand navbar-light bg-white">
				<a class="sidebar-toggle d-flex mr-2"><i class="hamburger align-self-center"></i></a>
				$nav_top_left
				<div class="navbar-collapse collapse">$nav_right_up</div>
			</nav>
			$pageContents
		</div>
	</div>
</body>
EOPAGE;
echo '<!DOCTYPE html>'."\r\n".'<html lang="en">';
print $pageHead;
print $pageBody;
echo "\n\r";
echo '<script type="text/javascript" src="/js/app.js"></script>'."\r\n";
echo '<script type="text/javascript" src="/js/custom.js"></script>'."\r\n";
echo '<script type="text/javascript" src="/js/admin.js"></script>'."\r\n";
echo '</html>';
?>
