<?PHP
session_start();
date_default_timezone_set ( "UTC" ); 
// print "<pre>"; print_r($_COOKIE); print "</pre>";

require_once $_SERVER['DOCUMENT_ROOT']."/libs/functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inc/common.php";
logincheck();

//################################# SIDE MENU ########################################
include $_SERVER['DOCUMENT_ROOT']."/inc/pageSide.php";
include $_SERVER['DOCUMENT_ROOT']."/inc/query_functions.php";

$this_page = $_GET['fr'].'.php';
if (in_array($_GET['fr'],['compareByLabel', 'promotionAnalysis','dataGlunt','trendAnalysis','advancedAnalysis','brandOverview', 'weatherAnalysis'])) {
	$this_page = 'footfall.php';
}
$msg = q_language($this_page);

$unused= ['allsquare', 'allstore', 'by10min', 'byhour', 'byday', 'bymonth'];
foreach($unused as $unused  ){
	if (!isset($msg[$unused ])) {
		$msg[$unused ] = $unused ;
	}
}
// Square store viewDate
$navTopLeft[0] = <<<EOBLOCK
<div class="form-inline d-none d-sm-inline-block">
	<input type = "hidden" id="view_by" value="hour">
	<input type = "hidden" id="fr" value="$_GET[fr]">
	<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">$msg[allsquare]</option></select>
	<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">$msg[allstore]</option></select>
	<span class="ml-3"></span>
	<span class="ml-2" id="date_additional" style="display:none">
		<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
		<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
		<span class="ml-2 mr-2">~</span>
	</span>			
	<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
		<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
</div>
EOBLOCK;

// Square store viewDate From~ To
$navTopLeft[1] = <<<EOBLOCK
<div class="form-inline d-none d-sm-inline-block">
	<input type = "hidden" id="view_by" value="hour">
	<input type = "hidden" id="fr" value="$_GET[fr]">
	<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">$msg[allsquare]</option></select>
	<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">$msg[allstore]</option></select>
	<span class="ml-3"></span>
	<span class="ml-2" id="date_additional">
		<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
		<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
		<span class="ml-2 mr-2">~</span>
	</span>			
	<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
		<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
</div>
EOBLOCK;

// Square store viewby10min, viewbyhour viewbyday viewbymonth viewDate From~ To
$navTopLeft[2]= <<<EOBLOCK
<div class="form-inline d-none d-sm-inline-block">
	<input type = "hidden" id="view_by" value="hour">
	<input type = "hidden" id="fr" value="$_GET[fr]">
	<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">$msg[allsquare]</option></select>
	<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">$msg[allstore]</option></select>
	<span class="ml-3"></span>
	<button class="form-control mr-sm-1" id="tenmin" OnClick="changeViewBy('tenmin');">$msg[by10min]</button>
	<button class="form-control mr-sm-1" id="hour" OnClick="changeViewBy('hour');" style="background-color:#fcc100;">$msg[byhour]</button>
	<button class="form-control mr-sm-1" id="day" OnClick="changeViewBy('day');">$msg[byday]</button>
	<button class="form-control mr-sm-1" id="month" OnClick="changeViewBy('month');">$msg[bymonth]</button>
	<span class="ml-3"></span>
	<span class="ml-2" id="date_additional" style="display:none">
		<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
		<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
		<span class="ml-2 mr-2">~</span>
	</span>
	<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
	<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
	<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
</div>
EOBLOCK;


if($_GET['fr'] == 'dashboard') {
	$nav_top_left =  $navTopLeft[0];
	$JS_FOOT = '<script src="../js/genderGraph.js">';

	$CARD_BLOCK = <<<EOBLOCK
	<div class="col-lg-6 col-xl-5">
		<div class="row w-100">
			<div class="col-sm-6">
				<div class="card flex-fill">
					<div class="card-header">
						<span id="card[0][badge]" class="badge float-right" style="background-color:#47bac1">$msg[visitors]</span>
						<h5 id="card[0][title]" class="card-title mb-0">$msg[today]</h5></div>
					<div class="card-body my-2">
						<div class="row d-flex align-items-center mb-4">
							<div class="col-8"><h2 id="card[0][value]" class="d-flex align-items-center mb-0 font-weight-light">0</h2></div>
							<div class="col-4 text-right"><span id="card[0][percent]" class="text-muted">100%</span></div>
						</div>
						<div class="progress progress-sm shadow-sm mb-1">
							<div id="card[0][progress]" class="progress-bar" role="progressbar" style="width:100%; background-color:#47bac1"></div>
						</div>
					</div>
				</div>
				<div class="card flex-fill">
					<div class="card-header">
						<span id="card[2][badge]" class="badge float-right" style="background-color:#5b7dff">$msg[visitors]</span>
						<h5 id="card[2][title]" class="card-title mb-0">$msg[average12weeks]</h5></div>
					<div class="card-body my-2">
						<div class="row d-flex align-items-center mb-4">
							<div class="col-8"><h2 id="card[2][value]" class="d-flex align-items-center mb-0 font-weight-light">0</h2></div>
							<div class="col-4 text-right"><span id="card[2][percent]" class="text-muted">100%</span></div>
						</div>
						<div class="progress progress-sm shadow-sm mb-1">
							<div id="card[2][progress]"class="progress-bar" role="progressbar" style="width:100%; background-color:#5b7dff"></div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="card flex-fill">
					<div class="card-header">
						<span id="card[1][badge]" class="badge float-right" style="background-color:#fcc100">$msg[visitors]</span>
						<h5 id="card[1][title]" class="card-title mb-0">$msg[yesterday]</h5>
					</div>
					<div class="card-body my-2">
						<div class="row d-flex align-items-center mb-4">
							<div class="col-8"><h2 id="card[1][value]" class="d-flex align-items-center mb-0 font-weight-light">0</h2></div>
							<div class="col-4 text-right"><span id="card[1][percent]" class="text-muted">100%</span></div>
						</div>
						<div class="progress progress-sm shadow-sm mb-1">
							<div id="card[1][progress]" class="progress-bar" role="progressbar" style="width:100%; background-color:#fcc100"></div>
						</div>
					</div>
				</div>
				<div class="card flex-fill">
					<div class="card-header">
						<span id="card[3][badge]" class="badge float-right" style="background-color:#5fc27e">$msg[visitors]</span>
						<h5 id="card[3][title]" class="card-title mb-0">$msg[total12weeks]</h5>
					</div>
					<div class="card-body my-2">
						<div class="row d-flex align-items-center mb-4">
							<div class="col-8"><h2 id="card[3][value]" class="d-flex align-items-center mb-0 font-weight-light">0</h2></div>
							<div class="col-4 text-right"><span id="card[3][percent]"class="text-muted">100%</span></div>
						</div>
						<div class="progress progress-sm shadow-sm mb-1">
							<div id="card[3][progress]" class="progress-bar" role="progressbar" style="width:100%; background-color:#5fc27e"></div>
						</div>
					</div>
				</div>
			</div>					
		</div>
	</div>
EOBLOCK;
	
	$FOOTFALL_BLOCK = <<<EOBLOCK
	<div class="col-lg-6 col-xl-7">
		<div class="card flex-fill w-100 d-flex">
			<div class="card-header"><h5 class="card-title mb-0" id="footfall_title">$msg[footfall]</h5></div>
			<div class="card-body  p-2">
				<div id="footfall_bar_chart"></div>
				<div id="footfall_curve_chart"></div>
			</div>
		</div>
	</div>
EOBLOCK;

// 	$sq = "select body from ".$DB_CUSTOM['web_config']." where page='dashboard' and frame='third_block' and name='third_block'";
// 	$th_json = json_decode(mysqli_fetch_row(mysqli_query($connect0, $sq))[0], true);
// 	$th_flag = $th_json['title'];

// 	$AGE_GENDER_BLOCK = <<<EOBLOCK
// 	<div class="col-12 col-lg-6 ">
// 		<div class="card flex-fill">
// 			<div class="card-header"><h5 class="card-title mb-0">$msg[agegroup]</h5></div>
// 			<div class="card-body w-100 d-flex">
// 				<div class="col-sm-6"><div id="age_ave_chart"></div></div>
// 				<div class="col-sm-6"><div id="age_today_chart"></div></div>
// 			</div>
// 			<div class="card-body">
// 				<div id="age_curve_chart"></div>
// 			</div>
// 		</div>
// 	</div>	
// 	<div class="col-12 col-lg-6 d-flex">
// 		<div class="card flex-fill w-100">
// 			<div class="card-header"><h5 class="card-title mb-0">$msg[gender]</h5></div>
// 			<div class="card-body d-flex w-100 text-center">
// 				<div class="col-sm-6" align="center" id="gender_ave"></div>
// 				<div class="col-sm-6" align="center" id="gender_today"></div>
// 			</div>
// 			<div class="card-body w-100">
// 				<div id="gender_curve_chart"></div>
// 			</div>
// 		</div>
// 	</div>	
// EOBLOCK;

// 	$CURVE_BY_LABEL_BLOCK = <<<EOBLOCK
// 	<div class="col-lg-12 col-xl-12">
// 		<div class="card flex-fill w-100 d-flex">
// 			<div class="card-header"><h5 class="card-title mb-0">$msg[third_block_display]</h5></div>
// 			<div class="card-body  p-2">
// 				<div id="third_block_curve_chart"></div>
// 			</div>
// 		</div>
// 	</div>
// EOBLOCK;


// 	if (!$th_flag) {
// 		$th_flag = 'none';
// 	}
// 	if ($th_flag == 'none') {
// 		$THIRD_BLOCKS = '<input type="hidden" id="third_block" value="none">';
// 	}
// 	else if ($th_flag == 'age_gender') {
// 		$THIRD_BLOCKS = '<input type="hidden" id="third_block" value="age_gender">'.$AGE_GENDER_BLOCK;
// 	}
// 	else if ($th_flag == 'curve_by_label') {
// 		$THIRD_BLOCKS ='<input type="hidden" id="third_block" value="curve_by_label">'.$CURVE_BY_LABEL_BLOCK;
// 	} 
// 	else if ($th_flag == 'tablefor') {
// 		$THIRD_BLOCKS ='<input type="hidden" id="third_block" value="table">'.$TABLE_BLOCK;
// 	} 

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				$CARD_BLOCK
				$FOOTFALL_BLOCK
			</div>
			<div class="row" id="third_block"></div>
		</div>
	$pageFoot
	</main>
EOPAGE;
	
}

else if($_GET['fr'] == 'dataGlunt') {
	$nav_top_left = $navTopLeft[2];
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-body w-100"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'latestFlow') {
	$msg=q_language('footfall.php');
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_on" value="7day">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-3"></span>
			<button class="form-control mr-sm-2" id="7day"   OnClick="changeViewOn(\'7day\');" style="background-color:#fcc100;">'.$msg['recent7days'].'</button>
			<button class="form-control mr-sm-2" id="4week"  OnClick="changeViewOn(\'4week\');">'.$msg['recent4weeks'].'</button>
			<button class="form-control mr-sm-2" id="12week" OnClick="changeViewOn(\'12week\');">'.$msg['recent12weeks'].'</button>
			<span class="ml-3"></span>
		</div>
	';	

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-body w-100"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
					<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'trendAnalysis') {
	$nav_top_left = $navTopLeft[2];
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-3 ">
					<div class="card flex-fill">
						<div class="card-body"><div id="chart_bar"></div></div>
					</div>
				</div>				
				<div class="col-12 col-lg-9 d-flex">
					<div class="card flex-fill w-100">
						<div class="card-body"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>				
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'advancedAnalysis') {
	$msg=q_language('footfall.php');
	$nav_top_left = $navTopLeft[2];
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-body w-100"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
					<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'compareByLabel') {
	$msg=q_language('footfall.php');
	$nav_top_left = $navTopLeft[2];
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-body w-100"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
					<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}


else if($_GET['fr'] == 'promotionAnalysis') {
	$msg = q_language('footfall.php');
	$nav_top_left = $navTopLeft[2];

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-3 ">
					<div class="card flex-fill">
						<div class="card-body"><div id="chart_bar"></div></div>
					</div>
				</div>				
				<div class="col-12 col-lg-9 d-flex">
					<div class="card flex-fill w-100">
						<div class="card-body"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>				
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'brandOverview') {
	$msg = q_language('footfall.php');
	$nav_top_left = $navTopLeft[2];

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-3 ">
					<div class="card flex-fill">
						<div class="card-body"><div id="chart_bar"></div></div>
					</div>
				</div>				
				<div class="col-12 col-lg-9 d-flex">
					<div class="card flex-fill w-100">
						<div class="card-body"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>				
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'weatherAnalysis') {
	$msg = q_language('footfall.php');
	$nav_top_left = $navTopLeft[2];

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-3 ">
					<div class="card flex-fill">
						<div class="card-body"><div id="chart_bar"></div></div>
					</div>
				</div>				
				<div class="col-12 col-lg-9 d-flex">
					<div class="card flex-fill w-100">
						<div class="card-body"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>				
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'heatmap') {
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_by" value="day">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-3"></span>
			<button class="form-control mr-sm-2" id="hour" OnClick="changeViewBy(\'hour\');">'.$msg['byhour'].'</button>
			<button class="form-control mr-sm-2" id="day" OnClick="changeViewBy(\'day\');" style="background-color:#fcc100;">'.$msg['byday'].'</button>			
			<span class="ml-3"></span>
			<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
			<span class="ml-3" id="time_plane" style="display:none">
				<span type="button" class="form-control" OnClick="changeTime(-1);"><i class="fa fa-chevron-left"></i></span>
				<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="reftime" id="reftime" size="4" value="00:00">
				<span type="button" class="form-control" OnClick="changeTime(1);"><i class="fa fa-chevron-right"></i></span>
			</span>
		</div>
		';	

//	$JS_FOOT ='<script src="js/heatmap.js"></script>';
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<input type="hidden" id="s_device" value="" OnChange="doHeatmap();">
			<div class="row" id="heatmapPad" style="display:none"> 
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header" id="heatmapHeader"></div>
						<div class="card-body w-100" align="center">
							<div id="heatmapContainer">
								<div class="tooltips" id="tooltipInstance"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row"  id="deviceSlider"></div>
		</div>
	$pageFoot
	</main>
	<script src="js/heatmap.js"></script>
EOPAGE;

}

else if($_GET['fr'] == 'agegender') {
	$msg = q_language("agegender.php");
	$nav_top_left = $navTopLeft[2];

	$JS_FOOT ='<script src="js/genderGraph.js"></script>';	
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-md-8">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[gender]</h5></div>
						<div class="card-body">
							<div class="chart"><div id="genderBarChart"></div></div>
						</div>				
					</div>
				</div>
				<div class="col-md-4">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[gender]</h5></div>
						<div class="card-body">
							<div class="chart" id="genderGraph"></div>
						</div>				
					</div>
				</div>		
			</div>
			<div class="row">
				<div class="col-md-8">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[age]</h5></div>
						<div class="card-body">
							<div class="chart"><div id="ageBarChart"></div></div>
						</div>				
					</div>
				</div>				
				<div class="col-md-4">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[age]</h5></div>
						<div class="card-body w-100 d-flex" align="center">
							<div class="chart"><div id="ageBGraph"></div></div>
						</div>				
					</div>
				</div>				
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[agegender]</h5></div>
						<div class="card-body w-100">
						</div>				
					</div>
				</div>
			</div>			
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'macsniff') {
	$nav_top_left = $navTopLeft[2];
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[agegender]</h5></div>
						<div class="card-body w-100"><div id="chart"></div></div>				
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
					<div id="macsniff_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>			
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'summary') {
//	$msg = q_language("footfall.php");
	$nav_top_left =  $navTopLeft[0];
	// $nav_top_left = '
	// 	<div class="form-inline d-none d-sm-inline-block">
	// 		<input type = "hidden" id="view_by" value="day">
	// 		<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
	// 		<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
	// 		<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
	// 		<span class="ml-3"></span>
	// 		<span class="ml-2" id="date_additional" style="display:none">
	// 			<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
	// 			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
	// 			<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
	// 			<span class="ml-2 mr-2">~</span>
	// 		</span>
	// 		<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
	// 		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
	// 		<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
	// 	</div>
	// ';

	$JS_FOOT ='<script src="js/genderGraph.js"></script>';	
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row" id="dashBoard">
				<div class="col-12 col-md-6 col-xl d-flex">
					<div class="card flex-fill">
						<div id="card[0]" class="card-body py-4"></div>
					</div>
				</div>
				<div class="col-12 col-md-6 col-xl d-flex">
					<div class="card flex-fill">
						<div id="card[1]" class="card-body py-4"></div>
					</div>
				</div>
				<div class="col-12 col-md-6 col-xl d-flex">
					<div class="card flex-fill">
						<div id="card[2]" class="card-body py-4"></div>
					</div>
				</div>
				<div class="col-12 col-md-6 col-xl d-flex">
					<div class="card flex-fill">
						<div id="card[3]" class="card-body py-4"></div>
					</div>
				</div>
				<div class="col-12 col-md-6 col-xl d-flex">
					<div class="card flex-fill">
						<div id="card[4]" class="card-body py-4"></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-xl-6">
					<div class="card flex-fill w-100">
						<div class="card-header"><h5 id="curve_chart0_display" class="card-title mb-0">$msg[footfall]</h5></div>
						<div class="card-body p-2"><div id="curve_chart0"></div></div>
					</div>
				</div>
				<div class="col-12 col-xl-6">
					<div class="card flex-fill w-100">
						<div class="card-header"><h5 id="curve_chart1_display" class="card-title mb-0">$msg[footfall]</h5></div>
						<div class="card-body p-2"><div id="curve_chart1"></div></div>
					</div>
				</div>
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
	
}

else if($_GET['fr'] == 'standard') {
	$nav_top_left =  $navTopLeft[0];
	// $nav_top_left = '
	// 	<div class="form-inline d-none d-sm-inline-block">
	// 		<input type = "hidden" id="view_by" value="day">
	// 		<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
	// 		<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
	// 		<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
	// 		<span class="ml-3"></span>
	// 		<span class="ml-2" id="date_additional" style="display:none">
	// 			<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
	// 			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
	// 			<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
	// 			<span class="ml-2 mr-2">~</span>
	// 		</span>
	// 		<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
	// 		<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
	// 		<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
	// 	</div>
	// ';
	
	$JS_FOOT = '<script src="js/genderGraph.js">';
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-6">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfallrisingranks]</h5></div>
						<div class="card-body w-100"><div id="footfall_rising_rank"></div></div>
						</br>
					</div>
				</div>
				<div class="col-12 col-lg-6">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfallhourly]</h5></div>
						<div class="card-body w-100"><div id="footfall_hourly"></div></div>
						</br>
					</div>
				</div>			
			</div>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[devicevscounting]</h5></div>
						<div class="card-body w-100"><div id="footfall_device"></div></div>
					</div>
				</div>			
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
	
	
}

else if($_GET['fr'] == 'premium') {
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_by" value="day">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-3"></span>
			<span class="ml-2" id="date_additional" style="display:none">
				<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
				<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
				<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
				<span class="ml-2 mr-2">~</span>
			</span>
			<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
		</div>
	';

	$JS_FOOT = '<script src="js/genderGraph.js">';
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfalltotal]</h5></div>
						<div class="card-body w-100"><div id="footfall_chart"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfallsquare]</h5></div>
						<div class="card-body w-100"><div id="footfall_square"></div></div>
					</div>
				</div>
			</div>				
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfallstore]</h5></div>
						<div class="card-body w-100"><div id="footfall_store"></div></div>
					</div>
				</div>
			</div>			
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfallcamera]</h5></div>
						<div class="card-body w-100"><div id="footfall_device"></div></div>
					</div>
				</div>
			</div>			
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfallcamera]</h5></div>
						<div class="card-body w-100" id = "table_footfall"></div>
					</div>
				</div>
			</div>					
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'export') {
	$msg = q_language("export.php");
	$api_key = strtoupper(md5($_SESSION['logID'].'@'.$_SESSION['db_name']));
	$nav_top_left = "";
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<input type="hidden" id="act" value="modify"> 
			<input type="hidden" id="server_address" value="$_SERVER[HTTP_HOST]">
			<div class="row">
				<div class="col-12 col-lg-4 col-md-4">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[square]<span class="ml-2 mr-2">,</span>$msg[store]</h5></div>
						<div class="card-body" id="place_pad"></div>
					</div>
					<!--div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[camera]</h5></div>
						<div class="card-body" id="camera_pad"></div>
					</div-->
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[label]</h5></div>
						<div class="card-body" id="counter_label_pad"></div>
					</div>

				</div>
				<div class="col-12 col-lg-4 col-md-4">
					<div class="card mb-2">
						<div class="card-header"><h5 class="card-title mb-0">$msg[dateandtime]</h5></div>
						<div class="card-body form-group form-inline mb-0">
							<input type="text" class="form-control text-center" id="refdate_from" OnChange="setConfig();">
							<span class="ml-2 mr-2">~</span>
							<input type="text" class="form-control text-center" id="refdate" OnChange="setConfig();">
						</div>
					</div>
					<div class="card mb-2">
						<div class="card-header"><h5 class="card-title mb-0">$msg[timetable]</h5></div>
						<div class="card-body form-group mb-0">
							<label class="custom-control custom-radio ml-2">
								<input name="interval" value="tenmin" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[tenmin]</span>
							</label>
							<label class="custom-control custom-radio ml-2">
								<input name="interval" value="hourly" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[hourly]</span>
							</label>
							<label class="custom-control custom-radio ml-2">
								<input name="interval" value="daily" type="radio" class="custom-control-input" OnChange="setConfig();" checked>
								<span class="custom-control-label">$msg[daily]</span>
							</label>
							<label class="custom-control custom-radio ml-2">
								<input name="interval" value="weekly" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[weekly]</span>
							</label>
							<label class="custom-control custom-radio ml-2">
								<input name="interval" value="monthly" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[monthly]</span>
							</label>
						</div>
					</div>
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[groupby]</h5></div>
						<div class="card-body form-group mb-0">
							<label class="custom-control custom-radio">
								<input name="groupby" value="none" type="radio" class="custom-control-input" OnChange="setConfig();" checked>
								<span class="custom-control-label">$msg[nogroup]</span>
							</label>
							<label class="custom-control custom-radio">
								<input name="groupby" value="square" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[square]</span>
							</label>
							<label class="custom-control custom-radio">
								<input name="groupby" value="store"type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[store]</span>
							</label>
							<label class="custom-control custom-radio">
								<input name="groupby" value="camera" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[camera]</span>
							</label>						
						</div>
					</div>
				</div>
				<div class="col-12 col-lg-4 col-md-4">
					<div class="card mb-2">
						<div class="card-header"><h5 class="card-title mb-0">$msg[format]</h5></div>
						<div class="card-body">
							<label class="custom-control custom-radio ml-3">
								<input name="output_format" value="table" type="radio" class="custom-control-input" OnChange="setConfig();" checked>
								<span class="custom-control-label">$msg[table]</span>
							</label>
							<label class="custom-control custom-radio ml-3">
								<input name="output_format" value="csv" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[csv]</span>
							</label>
							<label class="custom-control custom-radio ml-3">
								<input name="output_format" value="json" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[json]</span>
							</label>
							<label class="custom-control custom-radio ml-3">
								<input name="output_format" value="curve" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[curve]</span>
							</label>
						</div>
					</div>
					<div class="card mb-2">
						<div class="card-header"><h5 class="card-title mb-0">$msg[order]</h5></div>
						<div class="card-body">
							<label class="custom-control custom-radio ml-3">
								<input name="order" value="asc" type="radio" class="custom-control-input" OnChange="setConfig();" checked>
								<span class="custom-control-label">$msg[asc]</span>
							</label>
							<label class="custom-control custom-radio ml-3">
								<input name="order" value="desc" type="radio" class="custom-control-input" OnChange="setConfig();">
								<span class="custom-control-label">$msg[desc]</span>
							</label>
						</div>
					</div>
					<div class="card mb-2">
						<div class="card-header"><h5 class="card-title mb-0">$msg[apikey]</h5></div>
						<div class="card-body"><input type="text" id="api_key" class="form-control" value="$api_key" readonly></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header"><h5 class="card-title mb-0">$msg[api]</h5></div>
						<div class="card-body">
							<textarea id="query_api" class="form-control" ></textarea>
							<div id="error_board" class="text-danger"></div>
						</div>
						<div class="text-center">
							<button type="button" class="btn btn-primary" name='btn' value='query' id="query" onClick= "QueryAPI();">$msg[query]</button>
						</div>
					</div>
				</div>
			</div>			
		</div>		
	$pageFoot
	</main>
EOPAGE;
	
}

else if($_GET['fr'] == 'sensors') {
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_by" value="hour">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
		</div>
		';	

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row" id="device_info">
				<div class="card col-md-12">
					<div class="card-header"><h5 class="card-title mb-0">$msg[deviceinfo]</h5></div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-7"><canvas id="zone_id" width="800" height="450"></canvas></div>
							<div class="col-md-5">
								<table class="table table-striped table-sm table-boarded">
									<tr><th>$msg[cameracode]</th><td id="code" colspan="4"></td></tr>
									<tr><th>$msg[cameraname]</th><td id="name" colspan="4"></td></tr>
									<tr><th>$msg[mac]</th><td id="mac" colspan="4"></td></tr>
									<tr><th>$msg[brand]</th><td id="brand" colspan="4"></td></tr>
									<tr><th>$msg[model]</th><td id="model" colspan="4"></td></tr>
									<tr><th>$msg[usn]</th><td id="usn" colspan="4"></td></tr>
									<tr><th>$msg[productid]</th><td id="product_id" colspan="4"></td></tr>
									<tr><th>$msg[storename]</th><td id="store_name" colspan="4"></td></tr>
									<tr><th>$msg[installdate]</th><td id="initial_access" colspan="4"></td></tr>
									<tr><th>$msg[lastaccess]</th><td id="last_access" colspan="4"></td></tr>
									<tr><th>$msg[license]</th><td id="license" colspan="4"></td></tr>
									<tr><th>$msg[function]</th>
										<td><span class="ml-2"><span id="functions[0]"></span>$msg[countdb]</span></td>
										<td><span class="ml-4"><span id="functions[1]"></span>$msg[heatmap]</span></td>
										<td><span class="ml-4"><span id="functions[2]"></span>$msg[face]</span></td>
										<td><span class="ml-4"><span id="functions[3]"></span>$msg[macsniff]</span></td>
									</tr>
									<tr><th>$msg[feature]</th>
										<td><span class="ml-2"><span id="features[0]"></span>$msg[countline]</span></td>
										<td><span class="ml-4"><span id="features[1]"></span>$msg[heatmap]</span></td>
										<td><span class="ml-4"><span id="features[2]"></span>$msg[ageandgender]</span></td>
										<td><span class="ml-4"><span id="features[3]"></span>$msg[macsniff]</span></td>
									</tr>
								</table>
							</div>
						</div>
						<div class="row">
							<label>$msg[comment]</label>
							<span name="comment" class="form-control" style="border:0px" id ="comment"></span>
						</div>
					</div>
				</div>
			</div>
			<div class="row"  id="device_list"></div>
		</div>
		$pageFoot
	</main>	
EOPAGE;
}

else if($_GET['fr'] == 'sitemap') {
	$nav_top_left = '';
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header"><h5 class="card-title">$msg[devicetree]</h5></div>
						<div class="card-body">
							<table class="table table-bordered ">
								<thead>
									<tr>
										<th>$msg[square]</th>
										<th>$msg[store]</th>
										<th colspan="2">$msg[camera]</th>
										<th width="120px">$msg[counting]</th>
										<th width="120px">$msg[heatmap]</th>
										<th width="120px">$msg[agegender]</th>
										<th width="120px">$msg[macsniff]</th>
									</tr>
								</thead>
								<tbody id="table_body"></tbody>
							</table>				
						</div>
					</div>
				</div>
			</div>
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

else if($_GET['fr'] == 'trafficDistribution') {
	$msg = q_language('footfall.php');
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_by" value="day">
			<input type = "hidden" id="view_on" value="visit">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-3"></span>
			<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
			<span class="ml-2 mr-2">~</span>
			<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
			<span class="ml-3"></span>
			<button class="form-control mr-sm-2" id="visit" OnClick="changeViewOn(\'visit\');" style="background-color:#fcc100;">'.$msg['footfall'].'</button>
			<button class="form-control mr-sm-2" id="occupy" OnClick="changeViewOn(\'occupy\');">'.$msg['occupy'].'</button>

		</div>
	';	

	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div class="card-body w-100"><div id="apexcharts-heatmap"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
					<div class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'compareByTime') {
	$msg = q_language('footfall.php');
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_by" value="hour">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore(0);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-3"></span>
			<input type="text" class="form-control text-center " name="refdate1" id="refdate1" size="20" OnChange="changeDate()" readonly style="background-color:#FFF">
			<input type="text" class="form-control text-center " name="refdate2" id="refdate2" size="20" OnChange="changeDate()" readonly style="background-color:#FFF">
			<input type="text" class="form-control text-center " name="refdate3" id="refdate3" size="20" OnChange="changeDate()" readonly style="background-color:#FFF">
		</div>
	';	
	
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row" id="result">
				<div class="col-12 col-lg-3 ">
					<div class="card flex-fill">
						<div class="card-body"><div id="chart_bar"></div></div>
					</div>
				</div>				
				<div class="col-12 col-lg-9 d-flex">
					<div class="card flex-fill w-100">
						<div class="card-body"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>

		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'compareByPlace') {
	$msg = q_language('footfall.php');
	$nav_top_left = '
		<div class="form-group">
			<div class="form-inline d-none d-sm-inline-block">
			<input type="hidden" id="view_by" value="hour">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">		
			<select class="form-control" id="square1" OnChange="changeSpot(1)"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control" id="store1" OnChange="changeStore(1);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-2 mr-2">|</span>
			<select class="form-control" id="square2" OnChange="changeSpot(2)"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control" id="store2" OnChange="changeStore(2);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-2 mr-2">|</span>
			<select class="form-control" id="square3" OnChange="changeSpot(3)"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control" id="store3" OnChange="changeStore(3);"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-2 mr-2 mb-2"> </span>
		</div></div>
		
		<div class="form-group">
			<div class="form-inline d-none d-sm-inline-block">
			<span class="ml-2" id="date_additional" style="display:none">
				<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
				<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
				<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
				<span class="ml-2 mr-2">~</span>
			</span>
			<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
			<span class="ml-3"></span>
			<button class="form-control mr-sm-1" id="hour" OnClick="changeViewBy(\'hour\');" style="background-color:#fcc100;">'.$msg['byhour'].'</button>
			<button class="form-control mr-sm-1" id="day" OnClick="changeViewBy(\'day\');">'.$msg['byday'].'</button>
		</div></div>
	';	
	
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row" id="result">
				<div class="col-12 col-lg-3 ">
					<div class="card flex-fill">
						<div class="card-body"><div id="chart_bar"></div></div>
					</div>
				</div>				
				<div class="col-12 col-lg-9 d-flex">
					<div class="card flex-fill w-100">
						<div class="card-body"><div id="chart_curve"></div></div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="card flex-fill">
						<div id="footfall_table" class="card-body w-100"></div>
					</div>
				</div>		
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'kpi') {
	$msg = q_language('kpi.php');
	$nav_top_left = '
		<div class="form-inline d-none d-sm-inline-block">
			<input type = "hidden" id="view_by" value="hour">
			<input type = "hidden" id="fr" value="'.$_GET['fr'].'">
			<select class="form-control mr-sm-2" id="square" OnChange="changeSpot(0);"><option value="0">'.$msg['allsquare'].'</option></select>
			<select class="form-control mr-sm-2" id="store" OnChange="changeStore();"><option value="0">'.$msg['allstore'].'</option></select>
			<span class="ml-3"></span>
			<span type="button" class="form-control" OnClick="changeDate(-2);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate_from" id="refdate_from" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(2);"><i class="fa fa-chevron-right"></i></span>
			<span class="ml-2 mr-2">~</span>
			<span type="button" class="form-control" OnClick="changeDate(-1);"><i class="fa fa-chevron-left"></i></span>
			<input type="text" class="form-control form-control-no-border mr-sm-6 text-center" name="refdate" id="refdate" size="10" OnChange="changeDate(0)">
			<span type="button" class="form-control" OnClick="changeDate(1);"><i class="fa fa-chevron-right"></i></span>
		</div>
	';	

	$pageContents = <<<EOPAGE
	<svg aria-hidden="true" style="position: absolute; width: 0px; height: 0px; overflow: hidden;">
		<symbol id="icon-shopping-bag" viewBox="0 0 1024 1024">
			<path d="M920.935065 887.494949 862.03146 223.410469C860.972019 210.268692 849.954111 200.306369 836.605473 200.306369L712.44175 200.306369C712.229835 89.872889 622.391256 0 512 0 401.608744 0 311.770165 89.872889 311.55825 200.306369L187.394527 200.306369C174.257804 200.306369 163.23983 210.268692 161.96854 223.410469L103.064935 887.494949 103.064935 889.826543C103.064935 963.802099 171.079547 1024 254.561612 1024L769.438388 1024C852.920453 1024 920.935065 963.802099 920.935065 889.826543L920.935065 887.494949ZM512 50.871456C594.422691 50.871456 661.377862 117.852226 661.58971 200.306369L362.41029 200.306369C362.622138 117.852226 429.577309 50.871456 512 50.871456ZM678.328519 463.990092C668.369986 454.027769 652.266855 454.027769 642.308322 463.990092L346.095244 760.528258C336.136711 770.490581 336.136711 786.599896 346.095244 796.562219 350.968553 801.43739 357.536981 803.980966 364.105342 803.980966 370.673704 803.980966 377.030217 801.43739 382.115441 796.562219L678.328519 500.235969C688.287052 490.061664 688.287052 473.952416 678.328519 463.990092ZM623.450697 640.768399C600.143526 640.768399 578.107645 649.882863 561.368835 666.416075 527.255605 700.542338 527.255605 756.288964 561.368835 790.415227 577.89573 806.948438 599.931611 816.062903 623.450697 816.062903 646.969783 816.062903 668.79375 806.948438 685.532559 790.415227 702.271368 773.882016 711.17046 751.837689 711.17046 728.309694 711.17046 704.781631 702.059454 682.949286 685.532559 666.204094 669.005664 649.458901 646.757868 640.768399 623.450697 640.768399ZM649.512362 754.593247C642.520237 761.588098 633.40923 765.403429 623.450697 765.403429 613.704079 765.403429 604.381157 761.588098 597.389033 754.593247 582.980954 740.179649 582.980954 716.863568 597.389033 702.450036 604.381157 695.455185 613.492164 691.639855 623.450697 691.639855 633.197315 691.639855 642.520237 695.455185 649.512362 702.450036 656.504486 709.444821 660.318421 718.559285 660.318421 728.521608 660.318421 738.483931 656.292638 747.598396 649.512362 754.593247ZM462.631165 594.772114C496.744395 560.645851 496.744395 504.899225 462.631165 470.772896 446.10427 454.239684 424.068389 445.12522 400.549303 445.12522 377.242132 445.12522 355.20625 454.239684 338.467441 470.772896 304.354211 504.899225 304.354211 560.645851 338.467441 594.772114 354.994336 611.305326 377.030217 620.41979 400.549303 620.41979 424.068389 620.41979 446.10427 611.305326 462.631165 594.772114ZM374.487638 506.806857C381.479763 499.812073 390.59077 495.996675 400.549303 495.996675 410.507836 495.996675 419.618843 499.812073 426.610967 506.806857 441.019046 521.220455 441.019046 544.536536 426.610967 558.950134 419.618843 565.944918 410.507836 569.760316 400.549303 569.760316 390.59077 569.760316 381.479763 565.944918 374.487638 558.950134 360.291474 544.536536 360.291474 521.220455 374.487638 506.806857ZM769.438388 972.916563 254.561612 972.916563C199.471875 972.916563 154.552586 936.246724 153.916975 890.674402L210.701698 251.177824 311.55825 251.177824 311.55825 340.414836C311.55825 354.404472 322.999988 365.850531 336.984237 365.850531 350.968553 365.850531 362.41029 354.404472 362.41029 340.414836L362.41029 251.177824 661.58971 251.177824 661.58971 340.414836C661.58971 354.404472 673.031447 365.850531 687.015763 365.850531 701.000012 365.850531 712.44175 354.404472 712.44175 340.414836L712.44175 251.177824 813.298302 251.177824 870.083025 890.886383C869.447414 936.246724 824.528125 972.916563 769.438388 972.916563Z"></path>
		</symbol>
		<symbol id="icon-square-meter-effect" viewBox="0 0 1119 1024">
			<path d="M1112.285845 460.001827a23.797301 23.797301 0 0 0-33.554195-1.427838L1023.283939 509.024267a511.403997 511.403997 0 1 0-118.986504 331.496401A23.797301 23.797301 0 0 0 867.173645 809.108231 464.285341 464.285341 0 1 1 975.689337 508.786293l-55.209738-50.450277a23.797301 23.797301 0 0 0-32.126356 35.220005l95.189204 87.098121a23.797301 23.797301 0 0 0 32.126356 0l95.189204-87.098121a23.797301 23.797301 0 0 0 1.427838-33.554194z"></path>
			<path d="M683.220509 302.463695a23.797301 23.797301 0 0 0-33.554194-33.554195L523.54062 394.797222l-125.887722-125.887722a23.797301 23.797301 0 0 0-33.554194 33.554195l135.644615 135.644615V475.946018h-118.986504a23.797301 23.797301 0 0 0 0 47.594602h118.986504v71.391903h-118.986504a23.797301 23.797301 0 0 0 0 47.594602h118.986504v118.986504a23.797301 23.797301 0 0 0 47.594602 0v-118.986504h118.986505a23.797301 23.797301 0 0 0 0-47.594602h-118.986505v-71.391903h118.986505a23.797301 23.797301 0 0 0 0-47.594602h-118.986505v-37.837708z"></path>
		</symbol>
		<symbol id="icon-jiangpai-1" viewBox="0 0 1024 1024">
			<path d="M512.236308 229.12C292.745846 229.12 114.806154 407.04 114.806154 626.550154 114.806154 846.060308 292.745846 1024 512.236308 1024c219.510154 0 397.449846-177.939692 397.449846-397.449846 0-219.490462-177.939692-397.430154-397.449846-397.430154z m243.830154 358.990769l-93.499077 91.136 22.055384 128.689231c7.424 43.185231-17.250462 62.089846-56.812307 41.275077l-115.574154-60.750769-115.574154 60.750769c-38.793846 20.401231-64.393846 2.756923-56.832-41.275077l22.075077-128.689231-93.499077-91.136c-31.369846-30.601846-22.528-60.396308 21.720615-66.816l129.201231-18.766769 57.777231-117.090462c19.416615-39.305846 50.471385-40.093538 70.262154 0l57.77723 117.090462 129.220923 18.766769c43.362462 6.301538 53.700923 35.603692 21.700924 66.816zM883.771077 0H638.030769c-13.252923 0-23.886769 9.275077-27.017846 21.582769L535.276308 170.929231s162.185846 9.747692 258.008615 102.006154l113.132308-228.312616c3.347692-4.647385 5.750154-10.062769 5.750154-16.246154C912.147692 12.701538 899.446154 0 883.771077 0zM485.218462 170.929231l-75.756308-149.346462C406.331077 9.255385 395.697231 0 382.444308 0H136.664615C121.048615 0 108.307692 12.721231 108.307692 28.396308c0 6.183385 2.382769 11.598769 5.750154 16.246154l113.132308 228.332307C323.012923 180.696615 485.218462 170.929231 485.218462 170.929231z" fill="#FFB103"></path>
			<path d="M512.787692 486.793846h34.658462V768h-46.08V542.326154c-16.935385 15.36-38.203077 26.781538-64.196923 34.264615v-45.686154c12.603077-3.150769 25.993846-8.664615 40.172307-16.541538 14.178462-8.664615 25.993846-17.723077 35.446154-27.569231z" fill="#FFB103"></path>
		</symbol>
		<symbol id="icon-jiangpai-3" viewBox="0 0 1024 1024">
			<path d="M512.236308 229.12C292.745846 229.12 114.806154 407.04 114.806154 626.550154 114.806154 846.060308 292.745846 1024 512.236308 1024c219.510154 0 397.449846-177.939692 397.449846-397.449846 0-219.490462-177.939692-397.430154-397.449846-397.430154z m243.830154 358.990769l-93.499077 91.136 22.055384 128.689231c7.424 43.185231-17.250462 62.089846-56.812307 41.275077l-115.574154-60.750769-115.574154 60.750769c-38.793846 20.401231-64.393846 2.756923-56.832-41.275077l22.075077-128.689231-93.499077-91.136c-31.369846-30.601846-22.528-60.396308 21.720615-66.816l129.201231-18.766769 57.777231-117.090462c19.416615-39.305846 50.471385-40.093538 70.262154 0l57.77723 117.090462 129.220923 18.766769c43.362462 6.301538 53.700923 35.603692 21.700924 66.816zM883.771077 0H638.030769c-13.252923 0-23.886769 9.275077-27.017846 21.582769L535.276308 170.929231s162.185846 9.747692 258.008615 102.006154l113.132308-228.312616c3.347692-4.647385 5.750154-10.062769 5.750154-16.246154C912.147692 12.701538 899.446154 0 883.771077 0zM485.218462 170.929231l-75.756308-149.346462C406.331077 9.255385 395.697231 0 382.444308 0H136.664615C121.048615 0 108.307692 12.721231 108.307692 28.396308c0 6.183385 2.382769 11.598769 5.750154 16.246154l113.132308 228.332307C323.012923 180.696615 485.218462 170.929231 485.218462 170.929231z" fill="#E49C98"></path>
			<path d="M504.910769 481.28c28.356923 0 51.593846 6.695385 68.923077 20.48 16.935385 13.784615 25.6 32.689231 25.6 57.107692 0 30.72-15.753846 51.2-46.867692 61.44 16.541538 5.12 29.538462 12.603077 38.203077 22.843077 9.452308 10.633846 14.178462 24.418462 14.178461 40.96 0 25.993846-9.058462 47.261538-27.175384 63.803077-18.904615 16.935385-43.716923 25.6-74.436923 25.6-29.144615 0-52.775385-7.483077-70.498462-22.449231-19.692308-16.541538-30.72-40.96-33.083077-72.467692h46.867692c0.787692 18.116923 6.301538 32.295385 17.329231 42.141539 9.846154 9.058462 22.843077 13.784615 38.990769 13.784615 17.723077 0 31.901538-5.12 42.141539-14.966154 9.058462-9.058462 13.784615-20.086154 13.784615-33.476923 0-16.147692-5.12-27.963077-14.572307-35.446154-9.452308-7.876923-23.236923-11.421538-41.353847-11.421538h-19.692307V604.553846h19.692307c16.541538 0 29.144615-3.544615 37.809231-10.633846 8.270769-7.089231 12.603077-17.723077 12.603077-31.507692 0-13.784615-3.938462-24.024615-11.421538-31.113846-8.270769-7.089231-20.48-10.633846-36.627693-10.633847-16.541538 0-29.144615 3.938462-38.203077 12.209231-9.452308 8.270769-14.966154 20.873846-16.541538 37.809231h-45.292308c2.363077-28.356923 12.603077-50.412308 31.507693-66.166154 17.723077-15.753846 40.566154-23.236923 68.135384-23.236923z" fill="#E49C98"></path>
		</symbol>
			<symbol id="icon-jiangpai-2" viewBox="0 0 1024 1024"><path d="M512.236308 229.12C292.745846 229.12 114.806154 407.04 114.806154 626.550154 114.806154 846.060308 292.745846 1024 512.236308 1024c219.510154 0 397.449846-177.939692 397.449846-397.449846 0-219.490462-177.939692-397.430154-397.449846-397.430154z m243.830154 358.990769l-93.499077 91.136 22.055384 128.689231c7.424 43.185231-17.250462 62.089846-56.812307 41.275077l-115.574154-60.750769-115.574154 60.750769c-38.793846 20.401231-64.393846 2.756923-56.832-41.275077l22.075077-128.689231-93.499077-91.136c-31.369846-30.601846-22.528-60.396308 21.720615-66.816l129.201231-18.766769 57.777231-117.090462c19.416615-39.305846 50.471385-40.093538 70.262154 0l57.77723 117.090462 129.220923 18.766769c43.362462 6.301538 53.700923 35.603692 21.700924 66.816zM883.771077 0H638.030769c-13.252923 0-23.886769 9.275077-27.017846 21.582769L535.276308 170.929231s162.185846 9.747692 258.008615 102.006154l113.132308-228.312616c3.347692-4.647385 5.750154-10.062769 5.750154-16.246154C912.147692 12.701538 899.446154 0 883.771077 0zM485.218462 170.929231l-75.756308-149.346462C406.331077 9.255385 395.697231 0 382.444308 0H136.664615C121.048615 0 108.307692 12.721231 108.307692 28.396308c0 6.183385 2.382769 11.598769 5.750154 16.246154l113.132308 228.332307C323.012923 180.696615 485.218462 170.929231 485.218462 170.929231z" fill="#E88D59"></path>
			<path d="M506.486154 481.28c27.175385 0 49.624615 7.876923 67.347692 23.630769 17.329231 15.753846 25.993846 35.84 25.993846 61.046154 0 24.418462-9.452308 46.473846-27.56923 66.56-11.027692 11.815385-30.72 26.781538-58.289231 45.292308-28.750769 18.904615-46.08 35.446154-52.381539 49.624615h138.633846V768h-196.135384c0-28.750769 9.058462-53.563077 27.963077-74.830769 10.24-11.815385 31.901538-29.144615 64.590769-51.593846 18.116923-12.603077 30.72-23.236923 38.596923-31.507693 12.209231-13.784615 18.510769-28.750769 18.510769-44.504615 0-15.36-4.332308-26.781538-12.20923-34.264615-8.270769-7.483077-20.48-11.027692-36.627693-11.027693-17.329231 0-30.326154 5.907692-38.990769 17.723077-8.664615 11.027692-13.390769 27.963077-14.178462 50.018462H405.661538c0.393846-31.507692 9.452308-56.713846 27.569231-76.012308 18.510769-20.48 42.929231-30.72 73.255385-30.72z" fill="#E88D59"></path>
		</symbol>
		<symbol id="icon-customer-unit-price" viewBox="0 0 1024 1024">
			<path d="M924.93747946 576.73563092c-35.56362557-35.56362557-82.92716249-55.23014573-133.24067785-55.23014573-50.31351537 0-97.67705356 19.66652143-133.24067914 55.23014573-35.56362557 35.56362557-55.23014573 82.92716249-55.23014572 133.24067912 0 50.31351537 19.66652143 97.67705356 55.23014572 133.24067786 35.56362557 35.56362557 82.92716249 55.23014573 133.24067914 55.23014573 50.31351537 0 97.67705356-19.66652143 133.24067785-55.23014573 35.56362557-35.56362557 55.23014573-82.92716249 55.23014572-133.24067786 0-50.31351537-19.66652143-97.67705356-55.23014572-133.24067912zM890.19329269 808.63668891c-26.38591591 26.38591591-61.45787693 40.80803019-98.66037884 40.80803018-37.20250194 0-72.27446421-14.58600331-98.66038013-40.80803018-26.38591591-26.38591591-40.80803019-61.45787693-40.8080302-98.66037887 0-37.20250194 14.58600331-72.27446421 40.8080302-98.66038013 26.38591591-26.38591591 61.45787693-40.80803019 98.66038013-40.80803018 37.20250194 0 72.27446421 14.58600331 98.66037884 40.80803018 26.38591591 26.38591591 40.80803019 61.45787693 40.80803147 98.66038013 0.16388775 37.20250194-14.42211554 72.27446421-40.80803147 98.66037887z"></path>
			<path d="M862.98793924 728.6595049c6.55550671 0 11.96380036-10.4888105 11.96380037-17.04431849s-5.40829364-11.96380036-11.96380037-11.96380036h-46.38021159l50.96906643-66.3745073c3.93330378-5.24440588 2.94997846-12.61935141-2.13053967-16.71654295-5.24440588-3.93330378-17.86375603-8.19438308-21.96094883-2.94997848L792.35235138 681.62374224 741.21939846 613.61035732c-3.93330378-5.24440588-16.71654295-0.98332657-21.96094885 2.94997848-5.24440588 3.93330378-6.22773119 11.47213708-2.13053965 16.71654295l50.96906643 66.3745073H721.88065255c-6.55550671 0-11.96380036 5.40829364-11.96380035 11.96380036s5.40829364 17.04431849 11.96380035 17.04431849h53.42738162v20.8137345h-50.64129091c-6.55550671 0-11.96380036 5.40829364-11.96380036 11.9637991s5.40829364 17.04431849 11.96380036 17.04431848h50.64129091v39.33304158c0 6.55550671 10.4888105 11.96380036 17.04431721 11.96380037s17.04431849-5.40829364 17.04431849-11.96380037v-39.33304158h50.64129091c6.55550671 0 11.96380036-10.4888105 11.96380036-17.04431848s-5.40829364-11.96380036-11.96380036-11.9637991h-50.64129091v-20.8137345h53.59126937z"></path>
			<path d="M924.7735917 843.38087566c-13.76656449 13.76656449-29.49978087 25.23870157-46.38021159 34.08863571 2.94997846 13.93045226 5.08051812 28.02479228 6.55550799 42.44690782H133.19612959c15.56932862-194.20689276 178.47367637-344.4918891 375.79443536-344.49188911 45.88854831 0 89.81044474 8.19438308 130.45458715 23.10816191 5.57218141-7.70272107 11.7999126-14.91377883 18.68319487-21.79706107 9.17770965-9.17770965 19.17485814-17.37209402 29.82755638-24.41926275-12.45546366-5.40829364-25.07481381-10.32492401-38.02193948-14.58600332 84.56604012-50.14962762 140.94339892-140.94339892 140.94339891-244.35652163 0-156.6766153-125.3740703-281.88679782-281.88679783-281.88679784-156.6766153 0-281.88679782 125.3740703-281.88679909 281.88679784 0 103.41312145 56.37736007 194.20689276 140.94340017 244.35652163C195.80122084 597.2215899 73.54101679 760.12593765 73.54101679 951.21896544c0 18.8470826 12.45546366 31.30254499 31.30254499 31.30254501h814.35784977c18.8470826 0 31.30254499-12.45546366 31.302545-31.30254501-0.65555105-40.31636817-6.71939449-79.48552198-17.53598052-116.85191168-2.62220294 3.11386624-5.40829364 6.22773119-8.19438433 9.0138219zM289.87274487 293.53773099c0-122.26020406 97.18539026-219.28170657 219.28170784-219.28170656 122.26020406 0 219.28170657 97.18539026 219.28170658 219.28170656s-97.18539026 219.28170657-219.28170658 219.28170783c-122.42409183 0-219.28170657-97.18539026-219.28170784-219.28170783z"></path>
		</symbol>
		<symbol id="icon-jikeli" viewBox="0 0 1024 1024">
			<path d="M624.52925656 593.13096031c-0.40851094-20.93780625-14.64748969-34.53737812-35.61147093-34.79538375-51.25827-0.63847312-102.51747375-0.63847312-153.77480907 0.20472282-20.24885344 0.30755156-34.0783875 15.544905-34.18121625 35.94519656-0.15330844 44.40706969 0.895545 88.86555375 0.10376344 133.27075312-0.25707188 15.90293625 4.44687656 21.34725188 20.78262844 21.39866625 17.38460531 0 18.38204437-9.25458844 18.28015031-22.13996812-0.30661688-34.76734031-0.10189406-69.48607031-0.10189406-105.12278063h21.040635c0 35.79001875 0.58705875 71.24911594-0.2552025 106.70821313-0.35896594 15.33831281 5.34242156 20.70597375 20.27315812 20.14509 19.78799344-0.84319594 39.67694625-1.04791875 59.46493969 0.10189406 18.07355719 1.04791875 25.66792969-4.14119437 24.72097031-23.92825406-1.5854325-33.97649344-0.43375031-68.05675031-0.43375031-102.31275094 16.07868-3.96171187 19.12147594 3.68127 19.01958187 16.029135-0.25426781 31.41979687 0.20472281 62.89100719 0.15330844 94.30986844 0 16.69471781 11.83652531 15.13359 22.88033531 15.64586437 11.58225844 0.51133969 18.05018719-3.24751969 17.9997075-16.029135-0.2056575-46.50290625 0.58518938-92.97870375-0.36083531-139.43113125M177.26713063 215.50727844c3.3998925 3.42513281 6.00706969 7.59250219 11.98983375 15.23641875H81.39706906c19.94130188 19.96654125 35.38244344 35.9190225 51.56488594 51.05354719 2.88855375 2.73618 8.99845219 2.73618 13.60050656 2.73618 39.70312125 0.20378813 79.3800675-0.33279094 119.08318782 0.2552025 14.4437025 0.20472281 19.30095937-5.98182938 19.19906531-19.63375125-0.35709656-31.47121125-2.60717625-63.24903844 0.79271625-94.33604344 4.80677719-43.35821625-27.96848906-58.74887812-56.01363188-80.63270813v99.88318782C175.78359219 137.71264438 124.42342906 87.75749563 71.91158281 36.70394844 65.9559275 42.12395937 59.28327594 48.92374531 51.81790719 54.52323875 36.30011188 66.02697312 35.58405031 75.30773656 50.66715969 89.649545c43.07777438 40.98100219 84.54487594 83.72598469 126.59997093 125.85773344M284.74282063 768.27826062c-0.10189406-27.76376625-0.20378813-27.866595-26.91963563-27.96848906-32.08444406-0.15330844-64.24554281-0.15330844-96.32905219-0.15330844-27.32908125 0.05141437-63.09479531 25.92313125-74.13953906 55.06760813h102.6698475C136.20854 849.82988469 85.845815 900.93484625 35.50739563 951.9883925c13.57526625 13.98471188 24.67049062 25.51462125 38.75616187 40.00973812 52.10240063-52.40808188 103.1045325-103.76824594 156.76619063-157.76269593v103.02881344c2.40338906 1.42932 4.85819156 2.785725 7.26158062 4.19260968 9.27982875-10.84095656 17.38460531-22.98316406 28.01990344-32.18727281 14.13708563-12.24597094 19.68423-25.97361094 18.89244843-44.71462125-1.30405594-32.05639969-0.30755156-64.19319375-0.46086-96.27670312" fill=""></path>
			<path d="M762.09638281 688.00264906c11.09615813 0.92078531 16.33668656-3.60368062 16.28527125-15.21024469-0.20472281-48.54826406 0.69082219-97.07128875-0.30661593-145.61861812-0.58892813-28.37793469-13.98377719-42.36264656-39.21702188-42.66926344-46.50384187-0.510405-93.00581344-0.30661688-139.48348031 0.20472281-5.34429094 0.05141437-13.14058125 2.45386781-15.44207531 6.39220969-8.4871125 14.52035625-15.13359 30.11574094-24.51718219 49.49428969 11.68321687 0 20.98922062-0.17854781 30.26998406 0.05141437 31.87972125 0.7160625 51.20498531 19.25141437 52.15194469 50.66934094 0.56368875 17.74263562-0.10189406 35.53668656 0.15237375 53.30362688 0.66558281 49.82708063-6.64554281 42.3102975 43.61528812 43.66483312 14.80173375 0.35896594 20.73308344-3.75792375 20.09461032-19.53092156-1.10026781-30.04002188-0.81889125-60.15669656 0.10095843-90.22008844 0.22996219-6.29031562 5.87993531-12.34879969 9.02462625-18.53441812 3.246585 6.39034031 8.94703688 12.67972125 9.15269532 19.17289125 0.99556969 27.32908125 0.30661688 54.65909719 0.35803125 82.03959281 0.05141437 26.7915675 0.10189406 26.7915675 27.50762906 26.7915675 3.4522425-0.05234906 6.852135-0.25613719 10.25296312-0.00093469M761.12511781 284.78862688c32.16109875-0.56275406 64.62787875-3.09327656 96.35616188 0.63940781 43.86862031 5.06291438 57.75050437-29.57822719 86.74167187-55.09284844H834.26721875C888.46452125 176.23884312 939.36662938 125.46667344 989.24231937 75.66576781 976.38311469 60.76120531 965.44213437 48.029135 951.55931656 32c-52.84276781 53.68689844-103.35880031 104.91899344-158.0178975 160.39604625V86.93953906c-2.35290937-1.30405594-4.65346875-2.58287156-7.00450875-3.83551219-12.11790281 13.77998906-24.77238469 27.12529312-35.97043687 41.64658407-4.98532594 6.46792969-9.58738031 15.28783313-9.7911675 23.1102975-0.94695937 38.96181938 0.05141437 77.9741175-0.53751469 116.93593687-0.25613719 15.44114156 6.34079531 20.24791875 20.88732656 19.99178156M938.08594344 794.2518725c-13.49674313-15.15976406-24.72097031-29.9886075-38.32054125-42.02892094-7.38964875-6.49410375-18.84103406-11.24946656-28.71072563-11.60656312-35.4843375-1.32929531-71.1481575 0.35709656-106.63249406-0.74223657-19.22337-0.58705875-25.02665156 6.95309438-24.41435344 25.28465813 1.09933313 30.06526125 3.29799938 60.58951219-0.45899062 90.22008844-5.49759938 43.6638975 28.88833969 58.51984969 49.67190281 83.54837156 2.09677219-0.51414375 4.19354438-0.99837375 6.34173-1.45829906V839.96299719c2.24914594-0.97126406 4.55064-1.96870313 6.82409062-2.96614219 49.57187812 50.5188375 99.19330031 101.08534969 149.09703563 151.93510875 12.14314219-12.29645062 23.51974313-23.72446594 36.50701594-36.81550219-51.20685563-51.82008844-101.90237156-103.12883813-155.86971188-157.86458906h105.96504188zM465.05399469 540.79766281c-8.74324969-18.61107188-15.54303562-34.63927125-23.82635907-49.82521031-1.99487813-3.60461531-8.94890719-6.23890125-13.60144125-6.23890125-48.49684969-0.45992531-97.02080906-0.89367563-145.516725-0.05141437-21.09111469 0.38420531-34.92251813 14.77742813-35.07489093 35.15154562-0.46086 50.57025188-0.25613719 101.08628438 0.05141437 151.65373125 0.05047969 4.98439031 2.40245344 14.162325 4.29450282 14.36704875 14.13802031 1.30405594 28.76120531 2.24914594 42.51502031-0.2813775 3.04186219-0.510405 4.70394844-15.44114156 4.88249625-23.74970531 0.51133969-33.31184531 0.20472281-66.67323656 0.20472281-99.60181125 20.47788094-2.30056031 20.8349775-2.30056031 20.98828594 13.14058125 0.30661688 23.92918875 0 47.80789687 0.05141437 71.73521625 0.05141437 41.97844125 0.10189406 41.41662187 43.10301375 41.21096437 14.34180844-0.10189406 18.84196875-4.44687656 18.63724594-18.86720812-0.48516469-28.6322025-0.15330844-57.3681675 2.25008063-85.8470625 2.14818656-25.08087094 21.44914594-41.36427281 46.8609375-42.67019813 10.78767188-0.58518938 21.62862844-0.12619875 34.18028156-0.12619875M515.36624 385.59259906c28.27417125-0.38514 53.84020687-26.66443312 52.94466094-54.50578875-0.94415531-27.96848906-27.86472562-53.96734031-55.0928475-53.15031843-28.73409656 0.895545-53.02131562 26.43447094-52.562325 55.14519656 0.53751375 29.04258281 25.3360725 52.86894187 54.71051156 52.51091062M511.25121969 533.05278781c28.73409656 0.53564438 55.01619375-23.64874594 55.80704062-51.30968437 0.76747687-27.73852688-24.18345563-53.61117844-52.79228812-54.70957688-28.88740406-1.04885344-53.91686063 22.6756125-54.452505 51.71819438-0.56275406 30.16715531 21.78100219 53.7887925 51.4377525 54.30106687M360.51827281 458.0448125c28.83879469 0.63847312 52.97083594-23.44308844 53.12414438-53.12507906 0.28044188-31.95637594-20.04319594-52.79228812-51.61630125-52.99607625-30.72897375-0.25613719-54.14682281 21.88476563-54.35154563 51.56488687-0.20378813 28.45271906 24.4667025 53.9421 52.8437025 54.55626844M659.50318906 458.68328563c29.98767188 0.35709656 53.61024375-22.24186219 54.12158344-51.84532875 0.53657906-29.78481938-22.29234094-53.17555875-52.07716031-53.38121625-31.47027563-0.20472281-53.40645562 20.73401812-53.84020688 51.25827-0.40851094 30.24380906 22.03807406 53.61117844 51.79578375 53.968275" fill=""></path>
		</symbol>
		<symbol id="icon-time" viewBox="0 0 1024 1024">
			<path d="M341.333333 554.666667h76.8v76.8H341.333333V554.666667z m128 0h76.8v76.8h-76.8V554.666667z m128 0h76.8v76.8h-76.8V554.666667z m128 0h76.8v76.8h-76.8V554.666667z m-384 128h76.8v76.8H341.333333V682.666667z m128 0h76.8v76.8h-76.8V682.666667z m128 0h76.8v76.8h-76.8V682.666667z m128 0h76.8v76.8h-76.8V682.666667z m-384 128h76.8v76.8H341.333333V810.666667z m128 0h76.8v76.8h-76.8V810.666667z m128 0h76.8v76.8h-76.8V810.666667z m128 0h76.8v76.8h-76.8V810.666667z" fill="#4285F4"></path>
			<path d="M234.666667 213.333333h682.666666c46.933333 0 85.333333 38.4 85.333334 85.333334v640c0 46.933333-38.4 85.333333-85.333334 85.333333H234.666667c-46.933333 0-85.333333-38.4-85.333334-85.333333V298.666667c0-46.933333 38.4-85.333333 85.333334-85.333334z m42.666666 256c-25.6 0-42.666667 17.066667-42.666666 42.666667v384c0 25.6 17.066667 42.666667 42.666666 42.666667h597.333334c25.6 0 42.666667-17.066667 42.666666-42.666667v-384c0-25.6-17.066667-42.666667-42.666666-42.666667H277.333333z" fill="#4285F4"></path>
			<path d="M780.8 68.266667c29.866667 0 51.2 21.333333 51.2 51.2v145.066666c0 29.866667-21.333333 51.2-51.2 51.2s-51.2-21.333333-51.2-51.2V119.466667c0-29.866667 21.333333-51.2 51.2-51.2z" fill="#4285F4"></path>
			<path d="M780.8 332.8c-38.4 0-68.266667-29.866667-68.266667-68.266667V119.466667c0-38.4 29.866667-68.266667 68.266667-68.266667s68.266667 29.866667 68.266667 68.266667v145.066666c0 38.4-29.866667 68.266667-68.266667 68.266667z m0-247.466667c-17.066667 0-34.133333 17.066667-34.133333 34.133334v145.066666c0 17.066667 17.066667 34.133333 34.133333 34.133334s34.133333-17.066667 34.133333-34.133334V119.466667c0-17.066667-17.066667-34.133333-34.133333-34.133334z" fill="#FFFFFF"></path>
			<path d="M320 298.666667m-256 0a256 256 0 1 0 512 0 256 256 0 1 0-512 0Z" fill="#FFFFFF"></path>
			<path d="M320 588.8c-157.866667 0-290.133333-128-290.133333-290.133333S157.866667 8.533333 320 8.533333s290.133333 128 290.133333 290.133334-132.266667 290.133333-290.133333 290.133333z m0-512C196.266667 76.8 98.133333 174.933333 98.133333 298.666667s102.4 221.866667 221.866667 221.866666 221.866667-102.4 221.866667-221.866666S443.733333 76.8 320 76.8z" fill="#4285F4"></path>
			<path d="M324.266667 256l72.533333-72.533333c8.533333-8.533333 21.333333-8.533333 29.866667 0 8.533333 8.533333 8.533333 21.333333 0 29.866666L358.4 281.6c4.266667 4.266667 4.266667 12.8 4.266667 17.066667 0 17.066667-8.533333 29.866667-21.333334 38.4v110.933333c0 12.8-8.533333 21.333333-21.333333 21.333333s-21.333333-8.533333-21.333333-21.333333V337.066667C285.866667 328.533333 277.333333 315.733333 277.333333 298.666667c0-25.6 17.066667-42.666667 42.666667-42.666667h4.266667z" fill="#4285F4"></path>
		</symbol>
		<symbol id="icon-building" viewBox="0 0 1024 1024">
			<path d="M1004.308 927.508H19.692c-9.846 0-19.692-7.876-19.692-19.692 0-11.816 7.876-19.692 19.692-19.692h984.616c9.846 0 19.692 7.876 19.692 19.692 0 11.814-7.876 19.692-19.692 19.692z" fill="#ACB2BA"></path>
			<path d="M779.816 320.984L512 96.492 244.184 320.984z" fill="#F26F5A"></path>
			<path d="M271.754 320.984h480.492v606.524H271.754z" fill="#E8EAE8"></path>
			<path d="M752.246 492.308H972.8v435.2H752.246zM51.2 492.308h220.554v435.2H51.2z" fill="#FFD15C"></path>
			<path d="M415.508 734.524h192.984v192.984h-192.984z" fill="#425A6B"></path>
			<path d="M356.43 478.524c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692v55.138c0.002 11.814-9.846 19.692-19.692 19.692zM454.892 478.524c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692s19.692 7.876 19.692 19.692v55.138c-1.968 11.814-9.846 19.692-19.692 19.692zM551.384 478.524c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692v55.138c0 11.814-7.876 19.692-19.692 19.692zM649.846 478.524c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692s19.692 7.876 19.692 19.692v55.138c0 11.814-7.876 19.692-19.692 19.692zM356.43 634.092c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692V614.4c0.002 11.816-9.846 19.692-19.692 19.692zM454.892 634.092c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692s19.692 7.876 19.692 19.692V614.4c-1.968 11.816-9.846 19.692-19.692 19.692zM551.384 634.092c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692V614.4c0 11.816-7.876 19.692-19.692 19.692zM649.846 634.092c-9.846 0-19.692-7.876-19.692-19.692v-55.138c0-9.846 7.876-19.692 19.692-19.692s19.692 7.876 19.692 19.692V614.4c0 11.816-7.876 19.692-19.692 19.692z" fill="#56BFEB"></path>
			<path d="M862.524 628.184c-9.846 0-19.692-7.876-19.692-19.692v-49.23c0-9.846 7.876-19.692 19.692-19.692 11.816 0 19.692 7.876 19.692 19.692v49.23c0 11.816-9.846 19.692-19.692 19.692zM862.524 754.216c-9.846 0-19.692-7.876-19.692-19.692v-49.23c0-9.846 7.876-19.692 19.692-19.692 11.816 0 19.692 7.876 19.692 19.692v49.23c0 11.814-9.846 19.692-19.692 19.692zM862.524 880.246c-9.846 0-19.692-7.876-19.692-19.692v-49.23c0-9.846 7.876-19.692 19.692-19.692 11.816 0 19.692 7.876 19.692 19.692v49.23c0 9.846-9.846 19.692-19.692 19.692zM161.476 628.184c-9.846 0-19.692-7.876-19.692-19.692v-49.23c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692v49.23c0.002 11.816-9.844 19.692-19.692 19.692zM161.476 754.216c-9.846 0-19.692-7.876-19.692-19.692v-49.23c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692v49.23c0.002 11.814-9.844 19.692-19.692 19.692zM161.476 880.246c-9.846 0-19.692-7.876-19.692-19.692v-49.23c0-9.846 7.876-19.692 19.692-19.692 9.846 0 19.692 7.876 19.692 19.692v49.23c0.002 9.846-9.844 19.692-19.692 19.692z" fill="#F26F5A"></path>
		</symbol>
		<symbol id="icon-enter-mall-rate" viewBox="0 0 1146 1024">
			<path d="M1027.95499801 592.063192c-30.88725801 16.955459-67.617707 25.860583-108.77520201 25.860584-68.765932 0-131.73970601-29.228711-174.134732-79.66131401-42.356752 49.41195799-104.131268 79.623039-171.748975 79.62303901-67.617707-1.135467-130.540448-30.249355-172.93547499-79.661313-42.356752 50.470877-105.343284 79.661313-172.922716 79.661313-40.13685001 0-75.629767-9.058221-106.51702501-24.686842C46.464847 553.878325 0.637903 478.69509001 0.637903 395.652891a206.157457 206.157457 0 0 1 11.482252-69.582448c0-2.19438601 1.27580601-4.42704599 1.275806-5.56251299l104.169542-242.4031c16.075153-47.20481401 60.715597-77.492444 118.012036-77.49244301h679.12417899c56.135455 0 104.284365 30.249355 121.44395301 79.69958701l97.305708 236.82782899c1.046161 2.207144 2.23266 4.516352 2.23266 6.749013v2.194385c8.063093 21.318715 11.482252 44.87008999 10.295752 69.582448 0 81.881216-44.742509 157.077209-118.02479301 196.397543z m26.68985699-245.528824c0-1.148225-1.27580601-2.321967-1.275806-4.516352L957.211567 106.28738c-8.063093-22.492456-31.040355-24.725116-43.568768-25.873341H234.416556c-32.060999 0-37.865916 19.137087-40.124092 24.686842L92.381097 344.21240199c0 1.173741-1.173741 2.19438601-1.173741 3.36812701a133.742721 133.742721 0 0 0-8.063093 47.038959c0 52.780085 29.713517 101.01830201 76.714202 125.743419 18.358845 10.07886599 41.183011 14.59521799 67.617707 14.595218 50.30502201 0 96.093692-24.71235799 122.477356-67.388062a92.878661 92.878661 0 0 1 11.482252-17.96334499c17.159588-19.137087 60.728356-20.336344 80.107845 2.19438599 5.69009401 5.536997 8.075851 12.273252 10.334027 15.654137 27.41706599 42.535365 72.070269 68.40870599 122.477356 68.408706a141.93339399 141.93339399 0 0 0 121.443953-67.38806099 39.065173 39.065173 0 0 1 10.29575299-16.80236301c9.249592-10.11714 24.151004-16.82787799 40.13685001-16.827878 20.706328 0 33.260257 8.930641 40.124092 16.827878 6.863835 5.536997 9.249592 12.28600999 11.482252 16.80236301 26.268841 42.68846201 72.070269 67.38806201 122.477356 67.38806099 26.40918 0 50.432603-5.549755 68.80420599-15.666895 46.873105-25.733003 75.502187-72.950575 73.52468701-127.644369a136.064687 136.064687 0 0 0-7.999302-46.018315z m-157.23030499 18.894684h-660.86739901c-22.964504 0-41.195769-18.00162-41.19576899-40.366495s18.346087-40.353737 41.19576899-40.353737h660.86739901c22.964504 0 41.183011 17.976104 41.18301099 40.353737s-18.244023 40.366495-41.18301099 40.366495z m-782.18377101 289.60791399c22.84968199 0 41.183011 17.861281 41.18301101 40.37925301v217.57591901a22.84968199 22.84968199 0 0 0 22.964504 22.53073099h408.84472299c22.964504 0 41.591269 17.976104 41.986768 40.468559 0.255161 22.887956-18.48642601 41.552995-41.859188 41.552995H179.391052c-58.227776 0-105.343284-46.196928-105.343284-103.250963V695.428977c0-22.40315001 18.205749-40.379253 41.183011-40.379253z m730.94741 14.77383101a40.124092 40.124092 0 0 1 55.72719701 6.468335 38.27417401 38.27417401 0 0 1-6.621432 54.578972l-90.13567901 69.735544H1087.879596c23.78102 0 42.79052599 18.626765 42.79052599 41.922979s-19.00950599 41.884704-42.79052599 41.884704H805.492743l89.79121101 69.467625a38.516577 38.516577 0 0 1 6.621432 54.604488 40.08581801 40.08581801 0 0 1-55.72719701 6.468335L662.679043 872.89356199a38.45278599 38.45278599 0 0 1 0-61.03454899z"></path>
		</symbol>
		<symbol id="icon-jiangpai-star" viewBox="0 0 1024 1024">
			<path d="M512 229.376c-219.648 0-397.312 178.176-397.312 397.312S292.864 1024 512 1024c219.648 0 397.312-178.176 397.312-397.312 0.512-219.648-177.664-397.312-397.312-397.312z m244.224 358.912l-93.696 91.136 22.016 128.512c7.168 43.008-17.408 61.952-56.832 41.472L512 788.48l-115.712 60.928c-38.912 20.48-64.512 2.56-56.832-41.472l22.016-128.512-93.696-91.136c-31.232-30.72-22.528-60.416 21.504-66.56l129.024-18.944L476.16 385.536c19.456-39.424 50.688-39.936 70.144 0L604.16 502.784l129.024 18.944c44.544 5.632 54.784 35.328 23.04 66.56zM883.712 0h-245.76c-13.312 0-24.064 9.216-27.136 21.504l-75.776 149.504s162.304 9.728 258.048 101.888L906.24 44.544c3.584-4.608 5.632-10.24 5.632-16.384 0.512-15.36-12.288-28.16-28.16-28.16zM485.376 171.008L409.6 21.504C406.528 9.216 395.776 0 382.464 0h-245.76c-15.36 0-28.16 12.8-28.16 28.16 0 6.144 2.56 11.776 5.632 16.384l113.152 228.352c95.744-92.16 258.048-101.888 258.048-101.888z" fill="#cdcdcd"></path>
		</symbol>
	</svg>
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-sm-6 col-xl d-flex">
					<div class="card flex-fill">
						<div class="card-body py-4">
							<div class="media">
								<div class="d-inline-block mt-0 mr-3"><i class="text-primary" style="padding-top:0px;font-size:35px;"><svg width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><use xlink:href="#icon-enter-mall-rate"></use></svg></i></div>
								<div class="media-body"><h3 class="mb-2" id="card_val[0]"></h3><div class="mb-0">$msg[comeinrate]</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-sm-6 col-xl d-flex">
					<div class="card flex-fill">
						<div class="card-body py-4">
							<div class="media">
								<div class="d-inline-block mt-0 mr-3"><i class="text-warning" style="padding-top:0px;font-size:40px;"><svg width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" class=""><use xlink:href="#icon-jikeli"></use></svg></i></div>
								<div class="media-body"><h3 class="mb-2" id="card_val[1]"></h3><div class="mb-0">$msg[getcustomerability]</div></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-sm-6 col-xl d-flex">
					<div class="card flex-fill">
						<div class="card-body py-4">
							<div class="media">
								<div class="d-inline-block mt-0 mr-3"><i class="text-success" style="padding-top:0px;font-size:35px;"><svg width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" class=""><use xlink:href="#icon-customer-unit-price"></use></svg></i></div>
								<div class="media-body"><h3 class="mb-2" id="card_val[2]"></h3><div class="mb-0">$msg[customerunitprice]</div></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-sm-6 col-xl d-flex">
					<div class="card flex-fill">
						<div class="card-body py-4">
							<div class="media">
								<div class="d-inline-block mt-0 mr-3"><i class="text-danger" style="padding-top:0px;font-size:35px;"><svg width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" class=""><use xlink:href="#icon-shopping-bag"></use></svg></i></div>
								<div class="media-body"><h3 class="mb-2" id="card_val[3]"></h3><div class="mb-0">$msg[shoppingrate]</div></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-sm-6 col-xl d-none d-xxl-flex">
					<div class="card flex-fill">
						<div class="card-body py-4">
							<div class="media">
								<div class="d-inline-block mt-0 mr-3"><i class="text-info" style="padding-top:0px;font-size:35px;"><svg width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><use xlink:href="#icon-square-meter-effect"></use></svg></i></div>
								<div class="media-body"><h3 class="mb-2" id="card_val[4]"></h3><div class="mb-0">$msg[squaremetereffect]</div></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12 col-xl-7">
					<div class="card flex-fill w-100">
						<div class="card-header"><h5 class="card-title mb-0">$msg[footfall]</h5></div>
						<div class="card-body p-2"><div id="footfall_curve_chart"></div></div>
					</div>
				</div>
				<div class="col-12 col-lg-5 d-flex">
					<div class="card flex-fill">
						<div class="card-header"><h5 class="card-title mb-0">$msg[genderandagegroup] - $msg[recent7days]</h5></div>
						<div class="card-body d-flex w-100 mt-3">
							<div class="col-sm-7"><div id="ageGraph"></div></div>
							<div class="col-sm-5"><div id="genderGraph"></div></div>
						</div>
					</div>
				</div>				
			</div>
		</div>
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'version') {
	$nav_top_left ="";
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<h1 class="h3 mb-3">Changelog</h1>
			<div class="row">
				<div class="col-12">
					<div class="card p-3 p-lg-4">
						<div class="card-body">
							<div id="changelog"></div>
						</div>
					</div>
				</div>
			</div>
		</div>	
	$pageFoot
	</main>
EOPAGE;
}

else if($_GET['fr'] == 'feedback') {
	$nav_top_left = "";
	$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<h1 class="h3 mb-3">Feedback :<font color="#F00"> Not implemented yet, wait for version 0.9.0</font></h1>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title">$msg[feedback]</h5>
							<input type="text" id="title" class="form-control" value="">
						</div>
						<div class="card-body">
							<div class="clearfix">
								<div id="quill-toolbar">
									<span class="ql-formats">
										<select class="ql-font"></select>
										<select class="ql-size"></select>
									</span>
									<span class="ql-formats">
										<button class="ql-bold"></button>
										<button class="ql-italic"></button>
										<button class="ql-underline"></button>
										<button class="ql-strike"></button>
									</span>
									<span class="ql-formats">
										<select class="ql-color"></select>
										<select class="ql-background"></select>
									</span>
									<span class="ql-formats">
										<button class="ql-script" value="sub"></button>
										<button class="ql-script" value="super"></button>
									</span>
									<span class="ql-formats">
										<button class="ql-header" value="1"></button>
										<button class="ql-header" value="2"></button>
										<button class="ql-blockquote"></button>
										<button class="ql-code-block"></button>
									</span>
									<span class="ql-formats">
										<button class="ql-list" value="ordered"></button>
										<button class="ql-list" value="bullet"></button>
										<button class="ql-indent" value="-1"></button>
										<button class="ql-indent" value="+1"></button>
									</span>
									<span class="ql-formats">
										<button class="ql-direction" value="rtl"></button>
										<select class="ql-align"></select>
									</span>
									<span class="ql-formats">
										<button class="ql-link"></button>
										<button class="ql-image"></button>
										<button class="ql-video"></button>
									</span>
									<span class="ql-formats">
										<button class="ql-clean"></button>
									</span>
								</div>
								<div id="quill-editor"></div>
								<button type="button" class="btn btn-lg btn-primary" OnClick="writeContents()">$msg[write]</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-body" id="listContents"></div>
					</div>
				</div>
			</div>
		</div>	
	$pageFoot
	</main>
	<link rel="stylesheet" href="/css/all.css">	

EOPAGE;
}

######################################  WRITE  PAGE ###################################### 
$pageBody= <<<EOPAGE
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
if (!isset($JS_FOOT)){
	$JS_FOOT = "";
}
echo '<!DOCTYPE html>'."\n\r".'<html lang="en">'."\r\n";
print $pageHead;
print $pageBody;
echo "\n".'<script src="/js/app.js"></script>';
echo "\n".'<script src="/js/custom.js"></script>';
echo "\n".'<script src="/js/main.js"></script>';
echo "\n".$JS_FOOT;
echo "\n".'</html>';
?>
