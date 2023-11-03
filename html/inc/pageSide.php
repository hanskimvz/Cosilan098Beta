<?PHP
$ex_page = explode("/", $_SERVER['DOCUMENT_URI']);
$page = array_pop($ex_page);
$msg= q_language($page); // index.php, admin.php
// $unused= ['account', 'devicetree', 'camera', 'counterlabel', 'database', 'language', 'messagesetup', 'system', 'basic', 'sidemenu', 'dashboardconfig', 'analysis', 'webconfig'];
// foreach($unused as $unused  ){
// 	if (!isset($msg[$unused ])) {
// 		$msg[$unused ] = $unused ;
// 	}
// }


// $pageSide = <<<EOPAGE
// <nav class="sidebar sidebar-sticky">
// 	<div class="sidebar-content ">
// 		<a class="sidebar-brand" href="/"><img src = "$_TITLE_LOGO" height="26px"><span class="align-middle ml-2">$_DOCUMENT_TITLE</span></a>
// 		<ul class="sidebar-nav">
// 			<li class="sidebar-header">Main</li>
// 			<li id="dashboard" class="sidebar-item">
// 				<a class="sidebar-link" href="/?fr=dashboard" ><i class="align-middle" data-feather="monitor"></i><span class="align-middle">Dashboard</span></a>
// 			</li>
// 			<li class="sidebar-item">
// 				<a href="#footfall" data-toggle="collapse" class="sidebar-link collapsed"><i class="align-middle" data-feather="users"></i><span class="align-middle">Footfall</span></a>
// 				<ul id="footfall" class="sidebar-dropdown list-unstyled collapse">
// 					<li id="dataGlunt" class="sidebar-item"><a class="sidebar-link" href="/?fr=dataGlunt" ><span class="align-middle">Data Glunt</span></a></li>
// 					<li id="latestFlow" class="sidebar-item"><a class="sidebar-link" href="/?fr=latestFlow" ><span class="align-middle">Recent Data</span></a></li>
// 					<li id="trendAnalysis" class="sidebar-item"><a class="sidebar-link" href="/?fr=trendAnalysis" ><span class="align-middle">Trend Analysis</span></a></li>
// 					<li id="advancedAnalysis" class="sidebar-item"><a class="sidebar-link" href="/?fr=advancedAnalysis" ><span class="align-middle">Advanced Analysis</span></a></li>
// 					<li id="promotionAnalysis" class="sidebar-item"><a class="sidebar-link" href="/?fr=promotionAnalysis" ><span class="align-middle">Promotion Analysis</span></a></li>
// 					<li id="brandOverview" class="sidebar-item"><a class="sidebar-link" href="/?fr=brandOverview" ><span class="align-middle">Brand Overview</span></a></li>
// 					<li id="weatherAnalysis" class="sidebar-item"><a class="sidebar-link" href="/?fr=weatherAnalysis" ><span class="align-middle">Weather</span></a></li>
// 				</ul>
// 			</li>
// 			<li id="kpi" class="sidebar-item">
// 				<a class="sidebar-link" href="/?fr=kpi" ><i class="align-middle" data-feather="aperture"></i><span class="align-middle">KPI Overview</span></a>
// 			</li>
// 			<li class="sidebar-item">
// 				<a href="#dataCompare" data-toggle="collapse" class="sidebar-link collapsed"><i class="align-middle" data-feather="sliders"></i><span class="align-middle">Data Compare</span></a>
// 				<ul id="dataCompare" class="sidebar-dropdown list-unstyled collapse">
// 					<li id="compareByTime" class="sidebar-item"><a class="sidebar-link" href="/?fr=compareByTime" ><span class="align-middle">Compare By time</span></a></li>
// 					<li id="compareByPlace" class="sidebar-item"><a class="sidebar-link" href="/?fr=compareByPlace" ><span class="align-middle">Compare By Place</span></a></li>
// 					<li id="trafficDistribution" class="sidebar-item"><a class="sidebar-link" href="/?fr=trafficDistribution" ><span class="align-middle">Traffic Distribution</span></a></li>
// 					<li id="compareByLabel" class="sidebar-item"><a class="sidebar-link" href="/?fr=compareByLabel" ><span class="align-middle">Compare By Label</span></a></li>
// 				</ul>
// 			</li>
// 			<li id="heatmap" class="sidebar-item"><a class="sidebar-link" href="/?fr=heatmap" ><i class="align-middle" data-feather="map-pin"></i><span class="align-middle">Heatmap</span></a></li>
// 			<li id="agegender" class="sidebar-item"><a class="sidebar-link" href="/?fr=agegender" ><i class="align-middle" data-feather="slack"></i><span class="align-middle">Gender / Age</span></a></li>
// 			<li id="macsniff" class="sidebar-item"><a class="sidebar-link" href="/?fr=macsniff" ><i class="align-middle" data-feather="wifi"></i><span class="align-middle">Mac Sniff</span></a></li>
// 			<li class="sidebar-item">
// 				<a href="#report" data-toggle="collapse" class="sidebar-link collapsed"><i class="align-middle" data-feather="book-open"></i><span class="align-middle">Report</span></a>
// 				<ul id="report" class="sidebar-dropdown list-unstyled collapse">
// 					<li id="summary" class="sidebar-item"><a class="sidebar-link" href="/?fr=summary" ><span class="align-middle">Summary</span></a></li>
// 					<li id="standard" class="sidebar-item"><a class="sidebar-link" href="/?fr=standard" ><span class="align-middle">Standard</span></a></li>
// 					<li id="premium" class="sidebar-item"><a class="sidebar-link" href="/?fr=premium" ><span class="align-middle">Premium</span></a></li>
// 					<li id="export" class="sidebar-item"><a class="sidebar-link" href="/?fr=export" ><span class="align-middle">Query Database</span></a></li>
// 				</ul>
// 			</li>
// 			<li class="sidebar-header">setting</li>
// 			<li id="sensors" class="sidebar-item"><a class="sidebar-link" href="/?fr=sensors" ><i class="align-middle" data-feather="camera"></i><span class="align-middle">Sensors</span></a></li>
// 			<li id="sitemap" class="sidebar-item"><a class="sidebar-link" href="/?fr=sitemap" ><i class="align-middle" data-feather="map"></i><span class="align-middle">Sitemap</span></a></li>
// 			<li class="sidebar-header">about</li>
// 			<li id="version" class="sidebar-item"><a class="sidebar-link" href="/?fr=version" ><i class="align-middle" data-feather="pen-tool"></i><span class="align-middle">Version</span></a></li>
// 			<li id="feedback" class="sidebar-item"><a class="sidebar-link" href="/?fr=feedback" ><i class="align-middle" data-feather="phone-call"></i><span class="align-middle">Feedback</span></a></li>
// 		</ul>
// 	</div>
// </nav>
// EOPAGE;

$TITLE_BAR = <<<EOBLOCK
<a class="sidebar-brand" href="/"><img src = "$_TITLE_LOGO" height="26px"><span class="align-middle ml-2">$_DOCUMENT_TITLE</span></a>
EOBLOCK;


// $sq = "select flag, name from ".$_SESSION['db_name'].".webpage_config where page='sidemenu'";
// $rs = mysqli_query($connect0, $sq);
// while($rows = mysqli_fetch_row($rs)){
// 	$flag[$rows[1]] = $rows[0];
// }

function sidebarMenu($id, $href='', $icon='', $label='', $w_name='')
{
	if ($icon){
		$icon = strncmp($icon, 'fa-',3) == 0 ? '<i class="align-middle mr-2 fas fa-fw '.$icon.'"></i>' : '<i class="align-middle" data-feather="'.$icon.'"></i>' ;
	}
	if ($w_name){
		$w_name = 'target="'.$w_name.'"';
	}
	if ($id == -1) {
		return '</ul></li>';
	}
	if ($id == 'split_line') {
		return '<li class="sidebar-header">'.$label.'</li>';
	}
	if (strpos(" ".$href, "#") == 1) {
		return '<li class="sidebar-item"><a href="'.$href.'" data-toggle="collapse" class="sidebar-link collapsed">'.$icon.'<span class="align-middle">'.$label.'</span></a><ul id="'.$id.'" class="sidebar-dropdown list-unstyled collapse">';
	}
	return '<li id="'.$id.'" class="sidebar-item"><a class="sidebar-link" href="'.$href.'" '.$w_name.'>'.$icon.'<span class="align-middle">'.$label.'</span></a></li>';
}

// if (file_exists($ROOT_DIR."/NGINX/html/release.json")){
// 	// print "file exist";
// 	$json_str = file_get_contents($ROOT_DIR."/NGINX/html/release.json");
// 	$arr = json_decode($json_str, true);
// 	foreach($arr['disabled_menu'] as $P => $ids){
// 		foreach($ids as $id){
// 			$sq = "update ".$DB_CUSTOM['web_config']." set flag = 'n' where page='".$P."' and body like '%\"id\":\"".$id."\"%' " ;
// 			$rs = mysqli_query($connect0, $sq);
// 		}
// 	}
// 	foreach($arr['enabled_menu'] as $P => $ids){
// 		foreach($ids as $id){
// 			$sq = "update ".$DB_CUSTOM['web_config']." set flag = 'y' where page='".$P."' and body like '%\"id\":\"".$id."\"%' " ;
// 			$rs = mysqli_query($connect0, $sq);
// 		}
// 	}

// }

// function getMenuFromJson(){
// 	global $ROOT_DIR;
// 	global $DB_CUSTOM;
// 	global $connect0;

// 	if (!file_exists($ROOT_DIR."/bin/menu.json")){
// 		return false;
// 	}
// 	$json_str = file_get_contents($ROOT_DIR."/bin/menu.json");
// 	$arr = json_decode($json_str, true);

// 	for($i=0; $i<sizeof($arr); $i++){
// 		$sq = "select pk from ".$DB_CUSTOM['web_config']." where page='".$arr[$i]['page']."' and frame='".$arr[$i]['frame']."' and depth=".$arr[$i]['depth']." and pos_x=".$arr[$i]['pos_x']." and pos_y=".$arr[$i]['pos_y']."";
// 		// print $sq."<br>\n";
// 		$rs = mysqli_query($connect0, $sq);
// 		if(!$rs->num_rows){
// 			$body_str = (json_encode($arr[$i]['body']));
// 			$sq = "insert into ".$DB_CUSTOM['web_config']."(page, frame, depth, pos_x, pos_y, body, flag) ".
// 				"values('".$arr[$i]['page']."', '".$arr[$i]['frame']."', ".$arr[$i]['depth'].", ".$arr[$i]['pos_x'].", ".$arr[$i]['pos_y'].", '".$body_str."', '".$arr[$i]['flag']."')";
// 			// print $sq."";
// 			$rs = mysqli_query($connect0, $sq);
// 			// if($rs) {
// 			// 	print "OK<br>";
// 			// }
// 			// else {
// 			// 	print "Fail<br>";
// 			// }
// 		}
// 	}
// }
// if (isset($_GET['update_menu'])) {
// 	getMenuFromJson();
// }

$menu_str = "";
$y=0;
if($page == 'index.php'){
	$sq = "select * from ".$DB_CUSTOM['web_config']." where page='main_menu' order by pos_x, pos_y";
}
else if($page == "admin.php") {
	$sq = "select * from ".$DB_CUSTOM['web_config']." where page='admin_menu' order by pos_x, pos_y";
}

$rs = mysqli_query($connect0, $sq);
while($assoc = mysqli_fetch_assoc($rs)){
	if ( $assoc['flag'] == 'n'){
		continue;
	}
	$arr = json_decode($assoc['body'], true);
	if  ($assoc['pos_y'] == 0 && $y) {
		$menu_str  .= sidebarMenu(-1);	
	}
	if (!isset($msg[$arr['lang_key']])) {
		$msg[$arr['lang_key']] = $arr['lang_key'];
	}
	$menu_str  .= "\n".sidebarMenu($arr['id'], $arr['href'], $arr['icon'], $msg[$arr['lang_key']]);
	$y = $assoc['pos_y'];

}
$pageSide = <<<EOPAGE
<nav class="sidebar sidebar-sticky">
	<div class="sidebar-content ">
		$TITLE_BAR 
		<ul class="sidebar-nav">
			$menu_str
		</ul>
	</div>
</nav>
EOPAGE;

?>