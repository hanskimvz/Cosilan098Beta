<?PHP
function checkListCounterLabels($page, $arr_label, $arr_chk_label){
    global $DB_CUSTOM;
    global $connect0;
    $str = "";
    for ($i=0; $i<sizeof($arr_label); $i++){
        $sq = "select ".$_COOKIE['selected_language']." from ".$DB_CUSTOM['language']." where varstr='".$arr_label[$i]."' and  page='camera.php' limit 1";
        $counter_display = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];

        $str .= '<label class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input" id="'.$page.'['.$arr_label[$i].']"'.(in_array($arr_label[$i], $arr_chk_label) ? " checked":"").'><span class="form-check-label">'.$counter_display.'</span></label> ';
    }
    return $str;
}

function orgcheckListCounterLabels($page, $arr_label, $counter_label){
    global $DB_CUSTOM;
    global $connect0;
    $str = "";
    for ($i=0; $i<sizeof($arr_label); $i++){
        $sq = "select ".$_COOKIE['selected_language']." from ".$DB_CUSTOM['language']." where varstr='".$arr_label[$i]."' and  page='camera.php' limit 1";
        $counter_display = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];

        $str .= '<label class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input" id="'.$page.'['.$arr_label[$i].']"'.(strpos(" ".$counter_label, $arr_label[$i]) ? " checked":"").'><span class="form-check-label">'.$counter_display.'</span></label> ';
    }
    // print $str;
    return $str;
}

$arr_label = ["entrance", "exit", "outside","none"];
$sq ="select * from ".$DB_CUSTOM['counter_label']." group by counter_label order by counter_label asc";
$rs = mysqli_query($connect0, $sq);
while($assoc = mysqli_fetch_assoc($rs)){
    if (!in_array($assoc['counter_label'], $arr_label)){
        array_push($arr_label, $assoc['counter_label']);
    }
}

$label_list='';
for ($i=0; $i<sizeof($arr_label); $i++){
    $label_list .= $arr_label[$i].",";
}

$table_body ='';
############################################

if($_GET['db'] == 'basic') {
    $msg = q_language('admin.php');
    $table_body = <<<EOBLOCK
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">$msg[basic]</h5></div>
        <div class="card-body">
            <div class="form-group"><label>$msg[document_title]</label>
                <input class="form-control" type="text" id="document_title" value="$_DOCUMENT_TITLE"></div>
            <div class="form-group"><label>$msg[host_title]</label>
                <input class="form-control" type="text" id="host_title" value="$_HOST_TITLE"></div>
            <div class="form-group"><label>$msg[logo_path]</label>
                <input class="form-control" type="text" id="title_logo" value="$_TITLE_LOGO"></div>
            <div class="form-group"><label>$msg[developer]</label>
                <input class="form-control" type="text" id="developer" value="$_DEVELOPER" readonly></div>
            <button type="button" name="basic" class="btn btn-primary" value="basic" OnClick="changeOption(this)">$msg[save_changes]</button>
            <span id = "basic_result"></span>
            </div>
    </div>    
EOBLOCK;
}	
// else if ($_GET['db'] == 'main') { // main.php
//     $msg = q_language('index.php');
//     $arr_cfg = queryWebConfig('sidemenu', 0, $arr_cfg);
//     print "<pre>"; print_r($arr_cfg); print "</pre>";
    
//     $sq = "select * from ".$DB_CUSTOM['web_config']."  where page='sidemenu'";
//     $rs =  mysqli_query($connect0, $sq);

//     while($assoc = mysqli_fetch_assoc($rs)){
//         $arr_rs[$assoc['name']] = $assoc['flag']=='y'? "checked": "";
//     }
//     $arr_menu = [
//         "dashboard", 
//         "footfall", "-dataGlunt", "-latestFlow", "-trendAnalysis", "-advancedAnalysis", "-promotionAnalysis", "-brandOverview", "-weatherAnalysis",
//         "kpi",
//         "dataCompare", "-compareByTime", "-compareByPlace",  "-compareByLabel", "-trafficDistribution",
//         "heatmap", 
//         "agegender", 
//         "macsniff",
//         "report", "-summary", "-standard", "-premium", "-export",
//         "sensors",
//         "sitemap",
//         "version",
//         "feedback",
//     ];
//     for($i=0; $i<sizeof($arr_menu); $i++){
//         $sub_tag= "";
//         if ($arr_menu[$i][0] == '-') {
//             $arr_menu[$i] = substr($arr_menu[$i], 1, strlen($arr_menu[$i]));
//             $sub_tag= '<span class="ml-4">-</span>';
//         }
//         if (!$msg[strtolower($arr_menu[$i])]) {
//             $msg[strtolower($arr_menu[$i])]= strtoupper($arr_menu[$i]);
//         }
//         $table_body .= '<tr><td>'.$sub_tag.'<input class="ml-2 mr-2" type="checkbox" name="'.$arr_menu[$i].'" '.$arr_rs[$arr_menu[$i]].' OnChange="changeOption(this)">'.$msg[strtolower($arr_menu[$i])].'</td></tr>';			
//     }
    
//     $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';
//     $card_title = 'Main Menu Show';
//     $table_body = '<div class="card"><div class="card-body">'.$table_body.'</div></div>';
// }
else if ($_GET['db'] == 'sidemenu') {
    $msg = q_language('index.php');
    $sq = "select * from ".$DB_CUSTOM['web_config']." where page='main_menu' order by pos_x, pos_y";
    $rs = mysqli_query($connect0, $sq);
    
    while($assoc = mysqli_fetch_assoc($rs)){
        $hypon = $assoc['pos_y'] ? '<span class="ml-4">-</span>' :'';
        $ckd = $assoc['flag']=='y'?" checked": "";
        $arr = json_decode($assoc['body'], true);
        $label = isset($msg[$arr['lang_key']]) ?  $msg[$arr['lang_key']] : $arr['lang_key'];
        if($arr['id']=='split_line') {
            continue;
        }
        $table_body .= '<tr><td>'.$hypon.'<input class="ml-2 mr-2" type="checkbox" name="'.$arr['id'].'"'.$ckd.' OnChange="changeOption(this)">'.$label.'</td></tr>';			
    }
    $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';
    $table_body = '<div class="card"><div class="card-body">'.$table_body.'</div></div>';
}
// else if ($_GET['db'] == 'sidemenuxx') {
//     $msg = q_language('index.php');
//     // print_r($msg);
//     $sq = "select * from ".$DB_CUSTOM['web_config']."  where page='sidemenu'";
//     $rs =  mysqli_query($connect0, $sq);
//     while($assoc = mysqli_fetch_assoc($rs)){
//         $arr_rs[$assoc['name']] = $assoc['flag']=='y'? "checked": "";
//     }
//     $arr_menu = [
//         "dashboard", 
//         "footfall", "-dataGlunt", "-latestFlow", "-trendAnalysis", "-advancedAnalysis", "-promotionAnalysis", "-brandOverview", "-weatherAnalysis",
//         "kpi",
//         "dataCompare", "-compareByTime", "-compareByPlace",  "-compareByLabel", "-trafficDistribution",
//         "heatmap", 
//         "agegender", 
//         "macsniff",
//         "report", "-summary", "-standard", "-premium", "-export",
//         "sensors",
//         "sitemap",
//         "version",
//         "feedback",
//     ];
//     $table_body = "";
//     for($i=0; $i<sizeof($arr_menu); $i++){
//         $sub_tag= "";
//         if ($arr_menu[$i][0] == '-') {
//             $arr_menu[$i] = substr($arr_menu[$i], 1, strlen($arr_menu[$i]));
//             $sub_tag = '<span class="ml-4">-</span>';
//         }
//         if (!isset($msg[strtolower($arr_menu[$i])])) {
//             $msg[strtolower($arr_menu[$i])]= strtoupper($arr_menu[$i]);
//         }
//         $table_body .= '<tr><td>'.$sub_tag.'<input class="ml-2 mr-2" type="checkbox" name="'.$arr_menu[$i].'" '.$arr_rs[$arr_menu[$i]].' OnChange="changeOption(this)">'.$msg[strtolower($arr_menu[$i])].'</td></tr>';			
//     }
    
//     $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';
//     $card_title = 'Main Menu Show';
//     $table_body = '<div class="card"><div class="card-body">'.$table_body.'</div></div>';
// }

else if ($_GET['db'] == 'dashboard') {
    $msg = q_language('dashboard.php');
    // print_r($msg);
    $arr_colors = ["#47bac1", "#fcc100", "#5b7dff", "#5fc27e", "#50c27e", "#5f027e"];
    $arr_result['card_banner'] = [
        ["title"=>"today", "color"=> "#47bac1", "labels"=>["entrance"]],
        ["title"=>"yesterday", "color"=> "#fcc100", "labels"=>["entrance"]],
        ["title"=>"average", "color"=> "#5b7dff", "labels"=>["entrance"]],
        ["title"=>"total",  "color"=> "#5fc27e", "labels"=>["entrance"]],
    ];
    $arr_result['footfall'] = [
        ["title"=>"footfall", "color"=> "#47bac1", "labels"=>["entrance"]],
        ["title"=>"lastweek", "color"=> "#fcc100", "labels"=>["entrance"]],
        ["title"=>"thisweek",  "color"=> "#5b7dff", "labels"=>["entrance"]],
        ["title"=>"last12weeks", "color"=> "#5fc27e", "labels"=>["entrance"]],

    ];

    $sq = "select * from ".$DB_CUSTOM['web_config']." where page='dashboard' order by depth asc";
    $rs =  mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        $arr_result[$assoc['frame']][$assoc['depth']] = json_decode($assoc['body'], true);
    }
    // print_r($arr_result);
    $table_body_card = '<tr><th>PAGE</th><th>title</th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</th><th>badge</th><th>color</th></tr>';
    $cat = [
        '<table><tr><td style="background-color:#13f1fd"></td><td></td></tr><tr><td></td><td></td></tr></table>',
        '<table><tr><td></td><td style="background-color:#13f1fd"></td></tr><tr><td></td><td></td></tr></table>',
        '<table><tr><td></td><td></td></tr><tr><td  style="background-color:#13f1fd"></td><td></td></tr></table>',
        '<table><tr><td></td><td></td></tr><tr><td></td><td style="background-color:#13f1fd"></td></tr></table>',
    ];
    for ($i=0; $i<4; $i++){
        $table_body_card .= '<tr>
            <td class="text-center">'.$cat[$i].'</td>
            <td><select class="form-control" id="card_banner['.$i.'][title]">
                <option value="today" '.($arr_result['card_banner'][$i]['title']=='today'?'selected':'').'>today</option>
                <option value="yesterday" '.($arr_result['card_banner'][$i]['title']=='yesterday'?'selected':'').'>yesterday</option>
                <option value="average" '.($arr_result['card_banner'][$i]['title']=='average'?'selected':'').'>average</option>
                <option value="total" '.($arr_result['card_banner'][$i]['title']=='total'?'selected':'').'>total</option>
            </select></td>
            <td><input type="text" id="card_banner['.$i.'][display]" class="form-control" value="'.$msg['card_banner'.$i.'_display'].'"></td>
            <td>'.(checkListCounterLabels("card_banner[".$i."]", $arr_label, $arr_result['card_banner'][$i]['labels'])).'</td>
            <td><input type="text" id="card_banner['.$i.'][badge]" class="form-control" value="'.$msg['card_banner'.$i.'_badge'].'"></td>
            <td><input type="color" id="card_banner['.$i.'][color]" class="form-control" value="'.$arr_result['card_banner'][$i]['color'].'"></td>
        </tr>';
    }
    $table_body_card = '
    <div class="card-header"><h5 class="card-title mb-0">Card Banner</h5></div>
    <div class="card-body">
        <table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body_card.'</tbody></table>
        <button type="button" name="card_banner" class="btn btn-primary" value="card_banner" OnClick="changeOption(this)">'.$msg['save_changes'].'</button>
        <span id = "card_banner_result"></span>
    </div>';


    $table_body_footfall = '
        <tr><td>Title</td><td colspan="3"><input type="text" id="footfall_title" class="form-control" value="'.$msg['footfall_title'].'"></td></tr>
        <tr><th>PAGE</th><th>title</th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</th></tr>';

    $footfall_graph_cat = ['Bar Graph A', 'Bar Graph B', 'Curve Chart A', 'Curve Chart B'];
    for ($i=0; $i<2; $i++){
        if (!isset($arr_result['footfall'][$i]['labels']) || !$arr_result['footfall'][$i]['labels']){
            $arr_result['footfall'][$i]['labels'] = [];
        }        
        $table_body_footfall .= '<tr><td>'. $footfall_graph_cat[$i].'</td>
            <td><select class="form-control" id="footfall['.$i.'][title]">
            <option value="lastweek" '.($arr_result['footfall'][$i]['title']=='lastweek'?'selected':'').'>lastweek</option>
            <option value="thisweek" '.($arr_result['footfall'][$i]['title']=='thisweek'?'selected':'').'>thisweek</option>
            </select></td>
            <td><input type="text" id="footfall['.$i.'][display]" class="form-control" value="'.$msg['footfall_'.$i.'_display'].'"></td>
            <td>'.(checkListCounterLabels("footfall[".$i."]", $arr_label, $arr_result['footfall'][$i]['labels'])).'</td>
        </tr>';
    }
    for (; $i<4; $i++){
        if (!isset($arr_result['footfall'][$i]['labels']) || !$arr_result['footfall'][$i]['labels']){
            $arr_result['footfall'][$i]['labels'] = [];
        }         
        $table_body_footfall .= '<tr><td>'. $footfall_graph_cat[$i].'</td>
            <td><input type="text" class="form-control" id="footfall['.$i.'][title]" value="last12weeks" readonly></td>
            <td><input type="text" id="footfall['.$i.'][display]" class="form-control" value="'.$msg['footfall_'.$i.'_display'].'"></td>
            <td>'.(checkListCounterLabels("footfall[".$i."]", $arr_label, $arr_result['footfall'][$i]['labels'])).'</td>
        </tr>';
    }

    $table_body_footfall = '
    <div class="card-header"><h5 class="card-title mb-0">Footfall Section</div>
    <div class="card-body">
        <table class="table table-striped table-sm table-bordered table-hover">'.$table_body_footfall.'</table>
        <button type="button" name="footfall" class="btn btn-primary" value="footfall" OnClick="changeOption(this)">'.$msg['save_changes'].'</button>
        <span id = "footfall_result"></span>
    </div>';
    // $table_body_footfall = '
    // <div class="card-header"><h5 class="card-title mb-0">Footfall Section</div>
    // <div class="card-body">
    //     <table class="table table-striped table-sm table-bordered table-hover">
    //         <tr><td>Title</td><td colspan="3"><input type="text" id="footfall_title" class="form-control" value="'.$msg['footfall_title'].'"></td></tr>
    //         <tr><th>PAGE</th><th>title</th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</th></tr>
    //         <tr><td>Bar Graph A</td>
    //             <td><select class="form-control" id="footfall[0][title]">
    //             <option value="lastweek" '.($arr_result['footfall'][0]['title']=='lastweek'?'selected':'').'>lastweek</option>
    //             <option value="thisweek" '.($arr_result['footfall'][0]['title']=='thisweek'?'selected':'').'>thisweek</option>
    //             </select></td>
    //             <td><input type="text" id="footfall[0][display]" class="form-control" value="'.$msg['footfall_0_display'].'"></td>
    //             <td>'.(checkListCounterLabels("footfall[0]", $arr_label, $arr_result['footfall'][0]['label'])).'</td>
    //         </tr>
    //         <tr><td>Bar Graph B</td>
    //             <td><select class="form-control" id="footfall[1][title]">
    //             <option value="lastweek" '.($arr_result['footfall'][1]['title']=='lastweek'?'selected':'').'>lastweek</option>
    //             <option value="thisweek" '.($arr_result['footfall'][1]['title']=='thisweek'?'selected':'').'>thisweek</option>
    //             </select></td>
    //             <td><input type="text" id="footfall[1][display]" class="form-control" value="'.$msg['footfall_1_display'].'"></td>
    //             <td>'.(checkListCounterLabels("footfall[1]", $arr_label, $arr_result['footfall'][1]['label'])).'</td>
    //         </tr>
    //         <tr><td>Curve Chart A</td>
    //             <td><input type="text" class="form-control" id="footfall[2][title]" value="last12weeks" readonly></td>
    //             <td><input type="text" id="footfall[2][display]" class="form-control" value="'.$msg['footfall_2_display'].'"></td>
    //             <td>'.(checkListCounterLabels("footfall[2]", $arr_label, $arr_result['footfall'][2]['label'])).'</td>
    //         </tr>
    //         <tr><td>Curve Chart B</td>
    //             <td><input type="text" class="form-control" id="footfall[3][title]" value="last12weeks" readonly></td>
    //             <td><input type="text" id="footfall[3][display]" class="form-control" value="'.$msg['footfall_3_display'].'"></td>
    //             <td>'.(checkListCounterLabels("footfall[3]", $arr_label, $arr_result['footfall'][3]['label'])).'</td>
    //         </tr>
    //     </table>
    //     <button type="button" name="footfall" class="btn btn-primary" value="footfall" OnClick="changeOption(this)">'.$msg['save_changes'].'</button>
    //     <span id = "footfall_result"></span>
    // </div>';
    
    $table_curve_block = '<thead><tr><th></th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</tr></thead>';
    for ($i=0; $i<6; $i++) {
        if (!isset($arr_result['curve_by_label'][$i]['color']) || !!$arr_result['curve_by_label'][$i]['color']) {
            $arr_result['curve_by_label'][$i]['color'] = $arr_colors[$i];
        }
        if (!isset($arr_result['curve_by_label'][$i]['labels']) || !$arr_result['curve_by_label'][$i]['labels']){
            $arr_result['curve_by_label'][$i]['labels'] = [];
        }
        $table_curve_block .= '<tr>
        <td>'.$i.'</td>
        <td><input type="text" id="curve_label['.$i.'][display]" class="form-control" value="'.$msg['curve_by_label'.$i.'_display'].'"></td>
        <td>'.(checkListCounterLabels("curve_label[".$i."]", $arr_label, $arr_result['curve_by_label'][$i]['labels'])).'</td>
        <td><input type="color" id="curve_label['.$i.'][color]" class="form-control" value="'.$arr_result['curve_by_label'][$i]['color'].'"></td>
        </tr>';
    }
    $table_curve_block = '<table id="third_block_curve_block" style="display:none" class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_curve_block.'</tbody></table>';

    $table_table_block = '';
    $table_table_block = '<table id="third_block_table_block" style="display:none" class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_table_block.'</tbody></table>';

    // $table_curve_block = '
    // <div class="card-header"><h5 class="card-title mb-0">Curve By Label</div>
    // <div class="card-body">
    //     <div class="form-row form-group">
    //         <div class="col-md-12">
    //             <table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_curve_block.'</tbody></table>
    //         </div>
    //     </div>
    //     <button type="button" name="curve_label" class="btn btn-primary" value="curve_label" OnClick="changeOption(this)">'.$msg['save_changes'].'</button>
    //     <span id = "curve_label_result"></span>
    // </div>';
    // print_r($arr_result);
    $table_thrid_block = '
    <div class="card-header"><h5 class="card-title mb-0">Thrid Block Section</div>
    <div class="card-body">
        <div class="form-row form-group">
            <div class="col-md-4">
                <label>Category</label>
                <select class="form-control" id="third_block" onchange="third_display()">
                    <option value="none" '.($arr_result['third_block'][0]['title']=='none'?'selected':'').'>'.$msg['none'].'</option>
                    <option value="age_gender" '.($arr_result['third_block'][0]['title']=='age_gender'?'selected':'').'>'.$msg['agegender'].'</option>
                    <option value="curve_by_label" '.($arr_result['third_block'][0]['title']=='curve_by_label'?'selected':'').'>'.$msg['curvebylabel'].'</option>
                    <option value="tablefor" '.($arr_result['third_block'][0]['title']=='tablefor'?'selected':'').'>'.$msg['table'].'</option>
                </select>
            </div>
            <div class="col-md-8">
                <label>Dispaly</label>
                <input type="text" id="third_block_display" class="form-control" value="'.$msg['third_block_display'].'">
            </div>
        </div>
        '.$table_curve_block.'
        '.$table_table_block.'
    <button type="button" name="third_block" class="btn btn-primary" value="third_block" OnClick="changeOption(this)">'.$msg['save_changes'].'</button>
    <span id = "third_block_result"></span>
    </div>';

    

    $table_body = <<<EOBLOCK
    <input type="hidden" id="label_list" value="$label_list">
    <div class="card">$table_body_card</div>
    <div class="card">$table_body_footfall</div>
    <div class="card">$table_thrid_block</div>

    <!--div class="card" id="third_block_curve_block" style="display:none">$table_curve_block</div-->
    <script>
    function third_display() {
        third_id = document.getElementById("third_block").value;
        // console.log(third_id);
        document.getElementById("third_block_curve_block").style.display="none";

        if (third_id == 'curve_by_label') {
            document.getElementById("third_block_curve_block").style.display="";
        }

    }
    third_display();
    </script>

EOBLOCK;
}

else if ($_GET['db'] == 'analysis') {
    $msg = array_merge(q_language('index.php'), q_language('footfall.php') );
    $arr_rs =  array();
    $arr_page = array();
    $arr_tabs = ["dataGlunt", "latestFlow", "trendAnalysis", "advancedAnalysis", "compareByTime", "compareByPlace", "compareByLabel", "trafficDistribution", "age_group", "traffic_reset_hour" ];

    $sq = "select * from ".$DB_CUSTOM['web_config']."  where page='analysis'";
    $rs = mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        if ($assoc['frame'] == 'age_group' || $assoc['frame'] == 'traffic_reset_hour') {
            array_push($arr_rs, array('page'=>$assoc['frame'], 'labels'=>$assoc['body']));
        }
        else {
            $arr = json_decode($assoc['body'], true);
            array_push($arr_rs, array('page'=>$assoc['frame'], 'labels'=>$arr['labels']));
        }
        if (!in_array($assoc['frame'], $arr_page)){
            array_push($arr_page, $assoc['frame']);
        }
    }
    // print_r($arr_page);
    for($i=0; $i<sizeof($arr_tabs); $i++){
        if (!in_array($arr_tabs[$i], $arr_page)) {
            if ($arr_tabs[$i] == 'age_group' || $arr_tabs[$i] == 'traffic_reset_hour') {
                array_push($arr_rs, ["page"=>$arr_tabs[$i], "labels"=>""]);
            }
            else {
                array_push($arr_rs, ["page"=>$arr_tabs[$i], "labels"=>[]]);
            }
            array_push($arr_page, $arr_tabs[$i]);
        }
    }
    // print_r($arr_page);
    $page_list = implode(',', $arr_page);
    // print_r($arr_rs);

    $table_body = '<tr><th colspan="2">PAGE</th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</th></tr>';
    for ($i=0; $i<sizeof($arr_rs); $i++){

        if ($arr_rs[$i]['page'] == 'age_group'){
            $age_group = $arr_rs[$i]['labels'] ;
            continue;
        }
        if ($arr_rs[$i]['page'] == 'traffic_reset_hour'){
            $traffic_reset_hour = $arr_rs[$i]['labels'] ;
            continue;
        }        
        if (!isset($msg['analysis_'.strtolower($arr_rs[$i]['page'])])){
            $msg['analysis_'.strtolower($arr_rs[$i]['page'])] = 'analysis_'.strtolower($arr_rs[$i]['page']);
        }
        $table_body .= '<tr>
            <td>'.$msg[strtolower($arr_rs[$i]['page'])].'</td><td>'.$arr_rs[$i]['page'].'</td>
            <td><input type="text" id="'.$arr_rs[$i]['page'].'[display]" class="form-control" value="'.$msg['analysis_'.strtolower($arr_rs[$i]['page'])].'"></td>
            <td>'.(checkListCounterLabels($arr_rs[$i]['page'].'[0]', $arr_label, $arr_rs[$i]['labels'])).'</td>
        </tr>';
    }
    $table_body .= '<tr>
        <td colspan="2">Traffic Dist. Reset Hour</td>
        <td colspan="2"><input type="text" class="form-control" id="traffic_reset_hour" value="'.$traffic_reset_hour.'"></td>
    </tr>';
    $table_body .= '<tr>
        <td colspan="2">Age Group</td>
        <td colspan="2"><input type="text" class="form-control" id="age_group" value="'.$age_group.'"></td>
    </tr>';
    $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';

    $table_body = <<<EOBLOCK
        <input type="hidden" id="label_list" value="$label_list">
        <input type="hidden" id="page_list" value="$page_list">
        <div class="card">
            <div class="card-body">
                $table_body
                <button type="button" name="analysis" class="btn btn-primary" value="analysis" OnClick="changeOption(this)">$msg[save_changes]</button>
                <span id = "analysis_result"></span>
            </div>
        </div>		
EOBLOCK;

}
else if ($_GET['db'] == 'analysisxxx') {
    $msg = q_language('index.php');
    $msg = array_merge($msg, q_language('dashboard.php') );

    $tab_list = ["dataGlunt", "latestFlow", "trendAnalysis", "advancedAnalysis", "byTime", "byPlace", "trafficDistribution"];
    $page_list = "";
    for($i=0; $i<sizeof($tab_list); $i++){
        $page_list .= $tab_list[$i].",";
        $arr_result[$tab_list[$i]][0] = ["display"=>$msg['footfall'], "label"=>"entrance"];
    }

    $sq = "select * from ".$DB_CUSTOM['web_config']."  where page='footfall' or page='dataCompare'";
    $rs =  mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        $arr_result[$assoc['frame']][$assoc['depth']] = json_decode($assoc['body'], true);
    }

    $sq = "select body from ".$DB_CUSTOM['web_config']."  where page='footfall' and name ='traffic_reset'";
    if(!isset($arr_result['traffic_reset']) || !$arr_result['traffic_reset']){
        $arr_result['traffic_reset'] = "04:00";
    }
    
    $sq = "select body from ".$DB_CUSTOM['web_config']."  where page='age_gender' and name ='age_group'";
    $arr_result['age_group'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
    if(!isset($arr_result['age_group']) || !$arr_result['age_group']) {
        $arr_result['age_group'] = '[0,18,30,45,65]';
    }
    // print_r($arr_result);

    $msg['bytime'] = $msg['comparebytime'];
    $msg['byplace'] = $msg['comparebyplace'];
    $table_body = '<tr><th>PAGE</th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</th></tr>';
    for ($i=0; $i<sizeof($tab_list); $i++){
        $table_body .= '<tr>
            <td>'.$msg[strtolower($tab_list[$i])].': '.$tab_list[$i].'</td>
            <td><input type="text" id="'.$tab_list[$i].'[display]" class="form-control" value="'.$arr_result[$tab_list[$i]][0]['display'].'"></td>
            <td>'.(checkListCounterLabels($tab_list[$i], $arr_label, $arr_result[$tab_list[$i]][0]['labels'])).'</td>
        </tr>';
    }
    $table_body .= '<tr><td>Traffic Dist. Reset Hour</td><td colspan="2"><input type="text" class="form-control" id="traffic_reset" value="'.$arr_result['traffic_reset'].'"></td></tr>';
    $table_body .= '<tr><td>Age Group</td><td colspan="2"><input type="text" class="form-control" id="age_group" value="'.$arr_result['age_group'].'"></td></tr>';

    $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';


    $table_body = <<<EOBLOCK
        <input type="hidden" id="label_list" value="$label_list">
        <input type="hidden" id="page_list" value="$page_list">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">FootFall</h5></div>
            <div class="card-body">
                $table_body
                <button type="button" name="analysis" class="btn btn-primary" value="analysis" OnClick="changeOption(this)">$msg[save_changes]</button>
                <span id = "analysis_result"></span>
            </div>
        </div>		
EOBLOCK;

}
else if ($_GET['db'] == 'report') {
    $card_title = 'Report';
    $msg = q_language('report.php');
    if (!isset($msg['save_changes'])){
        $msg['save_changes'] = "Save Changes";
    }
    $arr_cfg = [
        ['category' => 'yesterday',     'labels' => ['entrance']],
        ['category' => 'today',         'labels' => ['entrance']],
        ['category' => 'recent7days',   'labels' => ['entrance']],
        ['category' => 'peakday',       'labels' => ['entrance']],
        ['category' => 'weakday',       'labels' => ['entrance']],
    ];
    
    updateWebConfig('report', 'card', 0, $arr_cfg[0], 'y');
    updateWebConfig('report', 'card', 1, $arr_cfg[1], 'y');
    updateWebConfig('report', 'card', 2, $arr_cfg[2], 'y');
    updateWebConfig('report', 'card', 3, $arr_cfg[3], 'y');
    updateWebConfig('report', 'card', 4, $arr_cfg[4], 'y');

    $arr_rs = queryWebConfig2('report', 0, $arr_cfg);
    // print "<pre>"; print_r($arr_rs); print "</pre>";
    $sq = "select * from ".$DB_CUSTOM['web_config']."  where page='report'";
    $rs =  mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        $arr_result[$assoc['frame']][$assoc['depth']] = json_decode($assoc['body'], true);
    }

    $tab_list = ["card", "summary", "standard", "premium", "export"];
    for($i=0; $i<sizeof($tab_list); $i++){
        $page_list .= $tab_list[$i].",";
        $arr_result[$tab_list[$i]][0] = ["display"=>$msg['footfall'], "label"=>"entrance"];
    }

    $table_body = '<tr><th>PAGE</th><th>'.$msg['display'].'</th><th>'.$msg['counterlabel'].'</th></tr>';
    for ($i=0; $i<sizeof($tab_list); $i++){
        $table_body .= '<tr>
            <td>'.$msg[strtolower($tab_list[$i])].': '.$tab_list[$i].'</td>
            <td><input type="text" id="'.$tab_list[$i].'[display]" class="form-control" value="'.$arr_result[$tab_list[$i]][0]['display'].'"></td>
            <td>'.(checkListCounterLabels($tab_list[$i], $arr_label, $arr_result[$tab_list[$i]][0]['label'])).'</td>
        </tr>';
    }

    $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';


    $table_body = <<<EOBLOCK
    <input type="hidden" id="label_list" value="$label_list">
    <div class="card">$table_body_card</div>
    <div class="card">$table_body_summary</div>
    <div class="card">$table_body_standard</div>
    <div class="card">$table_body_premium</div>	    
    <div class="card">$table_body_export</div>	    
EOBLOCK;
}

else if ($_GET['db'] == 'realtime_screen2') {
    $table_body = <<<EOBLOCK
    <div class="card">
        <div class="card-header">
            <select name="template" class="form-control">
                <option value="1">Template1</option>
                <option value="2">Template2</option>
                <option value="3">Template3</option>
                <option value="4">Template4</option>
            </select>
        </div>
        <div class="card-body">
            <canvas>
            </canvas>
        </div>
    </div>
EOBLOCK;
}

else if ($_GET['db'] == 'realtime_screen') {
    $msg = q_language('realtime.php');
    if (!isset($msg['save_changes'])){
        $msg['save_changes'] = "Save Changes";
    }
    $arr_cfg = [
        "title"=>[
            ["text" => "Title", "font" => ["simhei", 80, "bold"], "color" => ["white", "black"], "size" => [0, 1], "position" => ["center", 40], "padding" => [0, 0]]
        ],
        "label"=> [
            ["text" => "Label0", "font" => ["simhei", 90, "bold"], "color" => ["white", "black"], "size" => [10, 1], "position" => [100, 200], "padding"  => [30, 20]],
            ["text" => "Lable1", "font" => ["simhei", 90, "bold"], "color" => ["white", "black"], "size" => [10, 1], "position" => [980, 200], "padding"  => [30, 20]],
            ["text" => "Lable2", "font" => ["simhei", 90, "bold"], "color" => ["white", "black"], "size" => [10, 1], "position" => [100, 600], "padding"  => [30, 20]],
            ["text" => "Label3", "font" => ["simhei", 90, "bold"], "color" => ["white", "black"], "size" => [10, 1], "position" => [980, 600], "padding"  => [30, 20]]
        ],
        "number" => [
            ["text" => "Number0", "font" => ["ds-digital", 120, "bold"], "color" => ["red", "black"], "size" => [8, 1], "position" => [100, 370], "padding" => [0, 0], 
                "ct_labels" => ["entrance"], "rule" => "today"],
            ["text" => "Number1", "font" => ["ds-digital", 120, "bold"], "color" => ["red", "black"], "size" => [8, 1], "position" => [980, 370], "padding" => [0, 0], 
                "ct_labels" => ["exit"], "rule" => "today"],
            ["text" => "Number2", "font" => ["ds-digital", 120, "bold"], "color" => ["red", "black"], "size" => [8, 1], "position" => [100, 770], "padding" => [0, 0], 
                "ct_labels" => ["entrance", "exit"], "rule" => "entrance-exit"],
            ["text" => "Number3", "font" => ["ds-digital", 120, "bold"], "color" => ["red", "black"], "size" => [8, 1], "position" => [980, 770], "padding" => [0, 0], 
                "ct_labels" => ["entrance"], "rule" => "yesterday"],
            ["text" => "Number4", "font" => ["ds-digital", 60, "bold"],  "color" => ["blue","black"], "size" => [8, 1], "position" => [1300,900], "padding" => [0, 0], 
                "ct_labels" => ["entrance"], "rule" => "today/yesterday"]
        ]
    ];

    // print "<pre>";
    // // print_r($arr_cfg);
    // print "</pre>";
    // foreach($arr_cfg as $cat=>$arr){
    //     for ($i=0; $i<sizeof($arr); $i++){
    //         $arr_body = json_encode($arr[$i]);
    //         updateWebConfig("realtime_screen", $cat, $i, $arr_body, 'n');
    //     }

    // }
    // updateWebConfig($page, $frame, $depth, $arr_body, $flag='n')

    // updateWebConfig('realtime_screen', 'title',  0, $arr_cfg[0], 'y');
    // updateWebConfig('realtime_screen', 'label',  0, $arr_cfg[1], 'y');
    // updateWebConfig('realtime_screen', 'label',  1, $arr_cfg[2], 'y');
    // updateWebConfig('realtime_screen', 'label',  2, $arr_cfg[3], 'y');
    // updateWebConfig('realtime_screen', 'label',  3, $arr_cfg[4], 'y');
    // updateWebConfig('realtime_screen', 'number', 0, $arr_cfg[5], 'y');
    // updateWebConfig('realtime_screen', 'number', 1, $arr_cfg[6], 'y');
    // updateWebConfig('realtime_screen', 'number', 2, $arr_cfg[7], 'y');
    // updateWebConfig('realtime_screen', 'number', 3, $arr_cfg[8], 'y');

    // $arr_rs = queryWebConfig2('realtime_screen', 0, $arr_cfg);
    // print "<pre>"; print_r($arr_rs); print "</pre>";
    $tk_font_family = [

    ];
    $sq = "select * from ".$DB_CUSTOM['web_config']." where page='realtime_screen'";
    $rs =  mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        $arr_result[$assoc['frame']][$assoc['depth']] = json_decode($assoc['body'], true);
        $arr_result[$assoc['frame']][$assoc['depth']]['enable'] = $assoc['flag'];
    }

    $table_body = '<input type="hidden" id="title_size" value="'.sizeof($arr_result['title']).'">
        <tr><th>'.$msg['display'].'</th><th colspan="3">'.$msg['font'].'</th><th>'.$msg['foreground'].'</th><th>'.$msg['background'].'</th><th>'.$msg['width'].'</th><th>'.$msg['height'].'</th><th colspan="2">'.$msg['position'].'</th><th colspan="2">'.$msg['padding'].'</th><th>'.$msg['enable'].'</th></tr>';
    for ($i=0; $i<sizeof($arr_result['title']); $i++){
        $table_body .='<tr>
            <td><input type="text" id="title['.$i.'][text]" value="'.$arr_result['title'][$i]['text'].'" class="form-control"></td>
            <td><select id="title['.$i.'][fontfamily]" class="form-control">
                <option value="simhei" '.($arr_result['title'][$i]['font'][0]=='simhei' ? "selected": "").'>Simhei</option>
                <option value="simsun" '.($arr_result['title'][$i]['font'][0]=='simsun' ? "selected": "").'>SimSun</option>
                <option value="fangsong" '.($arr_result['title'][$i]['font'][0]=='fangsong' ? "selected": "").'>FangSong</option>
                <option value="microsoft yahei" '.($arr_result['title'][$i]['font'][0]=='microsoft yahei' ? "selected": "").'>Microsoft Yahei</option>
                <option value="arial"  '.($arr_result['title'][$i]['font'][0]=='arial' ? "selected": "").'>Arial</option>
                <option value="gulim"  '.($arr_result['title'][$i]['font'][0]=='gulim' ? "selected": "").'>Gulim</option>
                <option value="batang" '.($arr_result['title'][$i]['font'][0]=='batang' ? "selected": "").'>Batang</option>
            </select></td>
            <td><input type="text" id="title['.$i.'][fontsize]" value="'.$arr_result['title'][$i]['font'][1].'" class="form-control" size="3"></td>
            <td><select  id="title['.$i.'][fontstyle]" class="form-control">
                <option value= "" '.($arr_result['title'][$i]['font'][2]=='' ? "selected": "").'>Regular</option>
                <option value= "bold" '.($arr_result['title'][$i]['font'][2]=='bold' ? "selected": "").'>Bold</option>
                <option value= "italic" '.($arr_result['title'][$i]['font'][2]=='italic' ? "selected": "").'>italic</option>
            </select></td>
            <td><input type="color" id="title['.$i.'][fg]" value="'.$arr_result['title'][$i]['color'][0].'" class="form-control"></td>
            <td><input type="color" id="title['.$i.'][bg]" value="'.$arr_result['title'][$i]['color'][1].'" class="form-control"></td>
            <td><input type="text" id="title['.$i.'][width]" value="'.$arr_result['title'][$i]['size'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="title['.$i.'][height]" value="'.$arr_result['title'][$i]['size'][1].'" class="form-control" size="3"></td>
            <td><input type="text" id="title['.$i.'][posx]" value="'.$arr_result['title'][$i]['position'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="title['.$i.'][posy]" value="'.$arr_result['title'][$i]['position'][1].'" class="form-control" size="3"></td>
            <td><input type="text" id="title['.$i.'][padx]" value="'.$arr_result['title'][$i]['padding'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="title['.$i.'][pady]" value="'.$arr_result['title'][$i]['padding'][0].'" class="form-control" size="3"></td>
            <td><input type="checkbox" id="title['.$i.'][enable]" '.($arr_result['title'][$i]['enable'] == 'y' ? "checked":"").'></td>
        </tr>';
    }
    $table_body_title = '<table class="table table-striped table-sm table-bordered table-hover">'.$table_body.'</table>';

    $table_body = '<input type="hidden" id="label_size" value="'.sizeof($arr_result['label']).'">
        <tr><th>No.</th><th>'.$msg['display'].'</th><th colspan="3">'.$msg['font'].'</th><th>'.$msg['foreground'].'</th><th>'.$msg['background'].'</th><th>'.$msg['width'].'</th><th>'.$msg['height'].'</th><th colspan="2">'.$msg['position'].'</th><th colspan="2">'.$msg['padding'].'</th><th>'.$msg['enable'].'</th></tr>';
    for ($i=0; $i<sizeof($arr_result['label']); $i++){
        $table_body .='<tr>
            <td>'.$i.'</td>
            <td><input type="text" id="label['.$i.'][text]" value="'.$arr_result['label'][$i]['text'].'" class="form-control"></td>
            <td><select id="label['.$i.'][fontfamily]" class="form-control">
                <option value="simhei" '.($arr_result['label'][$i]['font'][0]=='simhei' ? "selected": "").'>Simhei</option>
                <option value="simsun" '.($arr_result['label'][$i]['font'][0]=='simsun' ? "selected": "").'>SimSun</option>
                <option value="fangsong" '.($arr_result['label'][$i]['font'][0]=='fangsong' ? "selected": "").'>FangSong</option>
                <option value="microsoft yahei" '.($arr_result['label'][$i]['font'][0]=='microsoft yahei' ? "selected": "").'>Microsoft Yahei</option>
                <option value="arial"  '.($arr_result['label'][$i]['font'][0]=='arial' ? "selected": "").'>Arial</option>
                <option value="gulim"  '.($arr_result['label'][$i]['font'][0]=='gulim' ? "selected": "").'>Gulim</option>
                <option value="batang" '.($arr_result['label'][$i]['font'][0]=='batang' ? "selected": "").'>Batang</option>
            </select></td>
            <td><input type="text" id="label['.$i.'][fontsize]" value="'.$arr_result['label'][$i]['font'][1].'" class="form-control" size="3"></td>
            <td><select  id="label['.$i.'][fontstyle]" class="form-control">
                <option value= "" '.($arr_result['label'][$i]['font'][2]=='' ? "selected": "").'>Regular</option>
                <option value= "bold" '.($arr_result['label'][$i]['font'][2]=='bold' ? "selected": "").'>Bold</option>
                <option value= "italic" '.($arr_result['label'][$i]['font'][2]=='italic' ? "selected": "").'>italic</option>
            </select></td>
            <td><input type="color" id="label['.$i.'][fg]" value="'.$arr_result['label'][$i]['color'][0].'" class="form-control"></td>
            <td><input type="color" id="label['.$i.'][bg]" value="'.$arr_result['label'][$i]['color'][1].'" class="form-control"></td>
            <td><input type="text" id="label['.$i.'][width]" value="'.$arr_result['label'][$i]['size'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="label['.$i.'][height]" value="'.$arr_result['label'][$i]['size'][1].'" class="form-control" size="3"></td>
            <td><input type="text" id="label['.$i.'][posx]" value="'.$arr_result['label'][$i]['position'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="label['.$i.'][posy]" value="'.$arr_result['label'][$i]['position'][1].'" class="form-control" size="3"></td>
            <td><input type="text" id="label['.$i.'][padx]" value="'.$arr_result['label'][$i]['padding'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="label['.$i.'][pady]" value="'.$arr_result['label'][$i]['padding'][1].'" class="form-control" size="3"></td>
            <td><input type="checkbox" id="label['.$i.'][enable]" '.($arr_result['label'][$i]['enable'] == 'y' ? "checked":"").'></td>
        </tr>';
    }
    $table_body_label = '<table class="table table-striped table-sm table-bordered table-hover">'.$table_body.'</table>';

    
    $table_body = '<input type="hidden" id="number_size" value="'.sizeof($arr_result['number']).'">
        <tr><th rowspan="2">No.</th><th colspan="3">'.$msg['font'].'</th><th>'.$msg['foreground'].'</th><th>'.$msg['background'].'</th><th>'.$msg['width'].'</th><th>'.$msg['height'].'</th><th colspan="2">'.$msg['position'].'</th><th>'.$msg['enable'].'</th></tr>
        <tr><th colspan="2">'.$msg['rule'].'</th><th colspan="8">'.$msg['counterlabel'].'</th></tr>';
    $arr_rules = array(
        'today' => 'Today', 
        'yesterday' => 'Yesterday',
        'entrance-exit'=> 'Occupancy (Entrance - Exit)', 
        'today/yesterday' => 'Daily increase rate',
        'thisyear' => 'This year',
        'total' =>'Total'
    );
    for ($i=0; $i<sizeof($arr_result['number']); $i++){
        $rules = '<input type="text" id="number['.$i.'][rule]" value="'.$arr_result['number'][$i]['rule'].'" class="form-control">';
        $rules = '';
        foreach($arr_rules as $label =>$display) {
            $rules .= '<option value="'.$label.'" '.($arr_result['number'][$i]['rule']==$label ? "selected": "").' >'.$display.'</option>';

        }
        $rules = '<select id="number['.$i.'][rule]" class="form-control form-cotrol-sm">'.$rules.'</select>';
        $table_body .='<tr>
            <td rowspan="2">'.$i.'</td>
            <td><select id="number['.$i.'][fontfamily]" class="form-control">
                <option value="ds-digital" '.($arr_result['number'][$i]['font'][0]=='ds-digital' ? "selected": "").'>DS-Digital</option>
                <option value="arial"  '.($arr_result['number'][$i]['font'][0]=='arial' ? "selected": "").'>Arial</option>
                <option value="gulim"  '.($arr_result['number'][$i]['font'][0]=='gulim' ? "selected": "").'>Gulim</option>
                <option value="bauhaus 93"  '.($arr_result['number'][$i]['font'][0]=='bauhaus 93' ? "selected": "").'>Bauhaus 93</option>
                <option value="HP Simplified"  '.($arr_result['number'][$i]['font'][0]=='HP Simplified' ? "selected": "").'>HP Simplified</option>
            </select></td>
            <td><input type="text" id="number['.$i.'][fontsize]" value="'.$arr_result['number'][$i]['font'][1].'" class="form-control" size="3"></td>
            <td><select  id="number['.$i.'][fontstyle]" class="form-control">
                <option value= "" '.($arr_result['number'][$i]['font'][2]=='' ? "selected": "").'>Regular</option>
                <option value= "bold" '.($arr_result['number'][$i]['font'][2]=='bold' ? "selected": "").'>Bold</option>
                <option value= "italic" '.($arr_result['number'][$i]['font'][2]=='italic' ? "selected": "").'>italic</option>
            </select></td>            
            <td><input type="color" id="number['.$i.'][fg]" value="'.$arr_result['number'][$i]['color'][0].'" class="form-control" size="3"></td>
            <td><input type="color" id="number['.$i.'][bg]" value="'.$arr_result['number'][$i]['color'][1].'" class="form-control" size="3"></td>
            <td><input type="text" id="number['.$i.'][width]" value="'.$arr_result['number'][$i]['size'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="number['.$i.'][height]" value="'.$arr_result['number'][$i]['size'][1].'" class="form-control" size="3"></td>
            <td><input type="text" id="number['.$i.'][posx]" value="'.$arr_result['number'][$i]['position'][0].'" class="form-control" size="3"></td>
            <td><input type="text" id="number['.$i.'][posy]" value="'.$arr_result['number'][$i]['position'][1].'" class="form-control" size="3"></td>
            <td><input type="checkbox" id="number['.$i.'][enable]" '.($arr_result['number'][$i]['enable'] == 'y' ? "checked":"").'></td>
            </tr><tr>
            <td colspan="2">'.$rules.'</td>
            <td colspan="7">'.(checkListCounterLabels("number[".$i."][ct_labels]", $arr_label,$arr_result['number'][$i]['ct_labels'])).'</td>
            
            
        </tr>';
    }
    $table_body_number = '<table class="table table-striped table-sm table-bordered table-hover">'.$table_body.'</table>';

    $table_body = <<<EOBLOCK
    <input type="hidden" id="label_list" value="$label_list">
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">TITLE<button class="btn btn-sm ml-3 mb-0" type="button" value="add_title" OnClick="changeRtScreen(this)">+</button></h5></div>
        <div class="card-body">$table_body_title
            <button type="button" class="btn btn-primary" value="title" OnClick="changeRtScreen(this)">$msg[save_changes]</button>
            <span id = "title_result"></span>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Label</h5></div>
        <div class="card-body">$table_body_label
            <button type="button" class="btn btn-primary" value="label" OnClick="changeRtScreen(this)">$msg[save_changes]</button>
            <span id = "label_result"></span>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Number</h5></div>
        <div class="card-body">$table_body_number
            <button type="button" class="btn btn-primary" value="number" OnClick="changeRtScreen(this)">$msg[save_changes]</button>
            <span id = "number_result"></span>
        </div>
    </div>

EOBLOCK;
?>
<script>
function changeRtScreen(t) {
    tab = t.value;
    console.log(tab);
    

    console.log(label_list);
    arr = new Array();
    
    // {"text":"Number4","font":["ds-digital",60,"bold"],"color":["blue","black"],"size":[8,1],"position":[1300,900],"padding":[0,0],"ct_labels":["entrance"],"rule":""}
    sz = document.getElementById(tab + "_size").value;
    for (i=0; i<sz; i++){
        ct_labels = new Array();
        rule = "";
        if (tab =='number') {
            document.getElementById("label_list").value.split(",").forEach(function(item) {
                if (item.trim()) {
                    console.log(item);
                    if (document.getElementById("number["+i+"][ct_labels][" + item + "]").checked)  {
                        ct_labels.push(item);
                    }
                }
            });
            rule = document.getElementById(tab + "["+i+"][rule]").value;
        }
        arr.push({
            depth: i,
            text : document.getElementById(tab + "["+i+"][text]") ? document.getElementById(tab + "["+i+"][text]").value : "",
            font: [
                document.getElementById(tab + "["+i+"][fontfamily]").value,
                document.getElementById(tab + "["+i+"][fontsize]").value,
                document.getElementById(tab + "["+i+"][fontstyle]").value,
            ],
            color:[
                document.getElementById(tab + "["+i+"][fg]").value,
                document.getElementById(tab + "["+i+"][bg]").value,
            ],
            size:[
                document.getElementById(tab + "["+i+"][width]").value,
                document.getElementById(tab + "["+i+"][height]").value,
            ],
            position: [
                document.getElementById(tab + "["+i+"][posx]").value,
                document.getElementById(tab + "["+i+"][posy]").value,
            ],
            padding: [
                document.getElementById(tab + "["+i+"][padx]") ? document.getElementById(tab + "["+i+"][padx]").value : 0,
                document.getElementById(tab + "["+i+"][pady]") ? document.getElementById(tab + "["+i+"][pady]").value : 0,
            ],
            ct_labels: ct_labels,
            rule: rule,
            enable: document.getElementById(tab + "["+i+"][enable]").checked ? "y" : 'n'
        });
    }
    console.log(arr);
    let url = "/inc/query.php?fr=webpageConfig&db=realtime_screen&mode=update&name=" + tab;
	console.log(url);
	document.getElementById('title_result').innerHTML = "";
    document.getElementById('label_result').innerHTML = "";
    document.getElementById('number_result').innerHTML = "";

    var posting = $.post(url,{
        arr
    });
    posting.done(function(data) {
        console.log(data);
        document.getElementById(tab + '_result').innerHTML = data;
    });
}
</script>
<?PHP

}

if(!isset($card_title)) {
    $card_title='';
}
if(!isset($table_body)) {
    $table_body='';
}

if(!isset($js)) {
    $js='';
}
$pageContents = <<<EOPAGE
    <main class="content">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-12">$table_body</div>
            </div>
        </div>
        $js
        $pageFoot
    </main>
EOPAGE;

?>