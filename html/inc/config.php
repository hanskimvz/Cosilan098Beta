<?PHP
/*
#######################################
config.php
setting parameter for param.db
V0.95, variables  unnecessary deleted.

*/

//Set variables


for ($i=0; $i<3; $i++) {
	chdir("../");
    if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
	// chdir("../");    
}
$dirname = $ROOT_DIR.'//bin/';
$fname = $ROOT_DIR."/bin/param.db";
// print $fname;

$db = new SQLite3($fname);

function updateParam($groupPath, $entryValue) {
    global $db;
    $ex_grp = explode(".", $groupPath);
    $entryName = array_pop($ex_grp);
    $grppath = "";
    for ($i=0; $i<sizeof($ex_grp); $i++){
        if($grppath) {
            $grppath .= ".";
        }
        $grppath .= $ex_grp[$i];
    }
    $sq = "select prino from param_tbl where groupPath='".$grppath."' and entryName='".$entryName."'";
    // print "<pre>".$sq."</pre></br>";
    $rs = $db->query($sq);
    $assoc = $rs->fetchArray();
    if($assoc[0]) {
        $sq = "update param_tbl set entryValue='".trim(str_replace("'", "&#039;",$entryValue))."' where groupPath='".$grppath."' and entryName='".$entryName."'" ;
        // print $sq."\n";
        $rs = $db->exec($sq) or die(print_r($db->lastErrorMsg(), true));
    }

}

// function query2Line($groupPath, $entryName, $mode = 'tableline'){
function query2Line($groupPath, $mode = 'tableline'){
    global $db;
    $line = "";
    // $sq = "select * from param_tbl where groupPath='".$groupPath."' ";
    $ex_grp = explode(".", $groupPath);
    $entryName = array_pop($ex_grp);
    $sq = "select * from param_tbl where groupPath='".join('.', $ex_grp)."' and entryName='".$entryName."'" ;
    // print $sq;

    $rs = $db->query($sq);
    while ($assoc = $rs->fetchArray()) {
        // print_r($assoc);
        $key =  $assoc['groupPath'].'.'.$assoc['entryName'];
        $line .= '<tr><td>'.$key.'</td>';
        if ($assoc['readonly']== 1) {
            $line .= '<td>'.$assoc['entryValue'].'</td>';
        }
        else if ($assoc['datatype']=='sz' || $assoc['datatype']=='int') {
            $line .= '<td><input type="text" id="'.$key.'" value="'.$assoc['entryValue'].'" size="60" '.($assoc['readonly']== 1 ? "readonly" : "").'></td>';
        }
        else if ($assoc['datatype']=='yesno') {
            $line .= '<td><input type="checkbox" id="'.$key.'" value="yes" '.($assoc['entryValue']=='yes' ? "checked" : "").'></td>';
        }
        else if ($assoc['datatype']=='select') {
            $line .= '<td>';
            $ex_options = explode(",",$assoc['option']);
            for ($i=0; $i<sizeof($ex_options); $i++) {
                $line .= '<input type="radio" id="'.$key.'" value="'.$ex_options[$i].'" '.($assoc['entryValue']== $ex_options[$i] ? "checked" : "").'>'.$ex_options[$i];
            }
            $line .= '</td>';
        }
        else {
            $line .= '<td><input type="text" id="'.$key.'" value="'.$assoc['entryValue'].'" size="60"></td>';
        }

        $line .= '<td>'.$assoc['description'].'</td><td>'.$assoc['datatype'].'</td><td>'.$assoc['readonly'].'</td></tr>'.PHP_EOL;
        if ($assoc['readonly']==0) {
            echo '<script>arr_post.push("'.$key.'");</script>'.PHP_EOL;
        }
    }
    return $line;
}


if($_POST) {
    // print_r($_POST);
    print "";
    $FLAG = false;
    if ($_POST['change_passwd']=='true') {
        if(!(trim($_POST['password']) && trim($_POST['new_password']))) {
            echo '<span style="color:#FF0000">Check Password, needs both old and new</span>';
        }
        else {
            $sq = "select entryValue from param_tbl where groupPath='software.param' and entryName='password'";
            $rs = $db->query($sq);
            $row = $rs->fetchArray();
            if ($row[0] != md5($_POST['password'])) {
                echo '<span style="color:#FF0000">Check Password, password is wrong</span>';
            }
            else {
                $sq = "update param_tbl set entryValue='".md5($_POST['new_password'])."' where groupPath='software.param' and entryName='password'";
                // print $sq;
                $rs = $db->exec($sq) or die(print_r($db->lastErrorMsg(), true));
                print "<span>new password is updated</span>";
            }
        }
        exit();
    }
    else {
        if(!trim($_POST['password'])) {
            print '<span style="color:#FF0000">Check Password, password cannot be empty</span>';
        }
        else {
            $sq = "select entryValue from param_tbl where groupPath='software.param' and entryName='password'";
            $rs = $db->query($sq);
            $row = $rs->fetchArray();
            if ($row[0] != md5($_POST['password'])) {
                echo '<span style="color:#FF0000">Check Password, password is wrong</span>';
            }
            else {
                $FLAG = true;
            }
        }
    }    
    
    if ($FLAG) {
        foreach($_POST['data'] as $i=>$data){
            if ($data['type']=='checkbox'){
                $data['value'] = $data['value'] == 'true' ? 'yes' : 'no';
            }
                
            // print $data['key']." = ". $data['value']."\n";
            if($data['key'] == 'system.datetime.timezone'){
                $tz = $data['value'];
            }
            else {
                updateParam($data['key'], $data['value']);
            }
        }
        try {
            $dtz = new DateTimeZone($tz);
        }
        catch(Exception $e) {
            print '<span style="color:#FF0000">'.($e->getMessage()).'</span>'; //."  ".$e->getCode();
            print '<span style="color:#FF0000">'.$tz.' is not available</span>';
            $dtz = 0;
        }        
        if($dtz) {
            $time_place = new DateTime('now', $dtz);
            $tz_offset =  $dtz->getOffset( $time_place );
            updateParam('system.datetime.timezone', $tz);
            updateParam('system.datetime.timezone.offset', $tz_offset);
        }
        print '<span>All Changes are updated</span>';
    }
    exit();
 }
echo '<script>arr_post = new Array()</script>';
if (!isset($_GET['sector']) || $_GET['sector']) {
    $_GET['sector'] = 'param';
}

if ($_GET['sector'] == 'param') { 
    $table_body  = query2Line('software.bin.version');
    $table_body .= query2Line('software.param.version');
    $table_body .= query2Line('software.webpage.version');
    $table_body .= query2Line('software.build.code');
    $table_body .= query2Line('software.root.webpage.developer');
    $table_body .= query2Line('software.root.update_server.address');
    $table_body .= query2Line('software.root.update_server.mac');
    $table_body .= query2Line('software.root.update.autoupdate');
    $table_body .= query2Line('software.root.update.needed');
    
    $table_body .= query2Line('software.service.root_dir');
    $table_body .= query2Line('software.service.start_on_boot');

    $table_body .= '<tr><td colspan=5 bgColor="#DFDFDF"> </tdtr>';
    $table_body .= query2Line('software.mysql.autobackup.enable');
    $table_body .= query2Line('software.mysql.autobackup.interval');
    $table_body .= query2Line('software.mysql.autobackup.prefix');
    $table_body .= query2Line('software.mysql.autobackup.schedule');
    $table_body .= '<tr><td colspan=5 bgColor="#DFDFDF"> </tdtr>';

    $table_body .= query2Line('software.service.counting.enable');
    $table_body .= query2Line('software.service.tlss.port');
    $table_body .= query2Line('software.service.event.enable');
    $table_body .= query2Line('software.service.event.port');
    $table_body .= query2Line('software.service.face.enable');
    $table_body .= query2Line('software.service.snapshot.enable');
    $table_body .= query2Line('software.service.snapshot.port');
    $table_body .= query2Line('software.service.macsniff.enable');
    $table_body .= query2Line('software.service.macsniff.port');
    $table_body .= query2Line('software.service.query_db.enable');
    $table_body .= query2Line('software.service.query_db.port');
    $table_body .= '<tr><td colspan=5 bgColor="#DFDFDF"> </tdtr>';

    $table_body .= query2Line('software.service.probe_interval');
    $table_body .= query2Line('software.mysql.embedded');
    $table_body .= query2Line('software.mysql.path');
    $table_body .= query2Line('software.mysql.host');
    $table_body .= query2Line('software.mysql.user');
    $table_body .= query2Line('software.mysql.password');
    $table_body .= query2Line('software.mysql.charset');
    $table_body .= query2Line('software.mysql.port');
    $table_body .= query2Line('software.mysql.recycling_time');
    $table_body .= query2Line('software.mysql.db');
    $table_body .= query2Line('software.mysql.db_common.table.user');
    $table_body .= query2Line('software.mysql.db_common.table.account');
    $table_body .= query2Line('software.mysql.db_common.table.param');
    $table_body .= query2Line('software.mysql.db_common.table.snapshot');
    $table_body .= query2Line('software.mysql.db_common.table.counting');
    $table_body .= query2Line('software.mysql.db_common.table.count_event');
    $table_body .= query2Line('software.mysql.db_common.table.face');
    $table_body .= query2Line('software.mysql.db_common.table.heatmap');
    $table_body .= query2Line('software.mysql.db_common.table.macsniff');
    $table_body .= query2Line('software.mysql.db_common.table.access_log');
    $table_body .= query2Line('software.mysql.db_common.table.language');
    $table_body .= query2Line('software.mysql.db_common.table.message');
    $table_body .= query2Line('software.mysql.db_custom.db');
    $table_body .= query2Line('software.mysql.db_custom.table.user');
    $table_body .= query2Line('software.mysql.db_custom.table.account');
    $table_body .= query2Line('software.mysql.db_custom.table.count');
    $table_body .= query2Line('software.mysql.db_custom.table.heatmap');
    $table_body .= query2Line('software.mysql.db_custom.table.age_gender');
    $table_body .= query2Line('software.mysql.db_custom.table.macsniff');
    $table_body .= query2Line('software.mysql.db_custom.table.square');
    $table_body .= query2Line('software.mysql.db_custom.table.store');
    $table_body .= query2Line('software.mysql.db_custom.table.camera');
    $table_body .= query2Line('software.mysql.db_custom.table.counter_label');
    $table_body .= query2Line('software.mysql.db_custom.table.language');
    $table_body .= '<tr><td colspan=5 bgColor="#DFDFDF"> </tdtr>';

    $table_body .= query2Line('software.fpp.host');
    $table_body .= query2Line('software.fpp.port');
    $table_body .= query2Line('software.fpp.api_key');
    $table_body .= query2Line('software.fpp.api_srct');
    $table_body .= query2Line('software.weather.host');
    $table_body .= query2Line('software.weather.port');
    $table_body .= query2Line('software.weather.api_key');
    $table_body .= query2Line('software.weather.api_srct');
    $table_body .= '<tr><td colspan=5 bgColor="#DFDFDF"> </tdtr>';

    $table_body .= query2Line('software.service.license.code');
    $table_body .= query2Line('software.service.license.exp_date');
    $table_body .= query2Line('software.service.license.timestamp');
    $table_body .= '<tr><td colspan=5 bgColor="#DFDFDF"> </tdtr>';

    // $table_body .= query2Line('system.datatime.datetime');
    $table_body .= query2Line('system.datetime.timezone');
    $table_body .= query2Line('system.datetime.timezone.offset');
    $db->close();


    $table_body = '<table class="table table-striped table-sm table-bordered table-hover"><tbody>'.$table_body.'</tbody></table>';
}

else if ($_GET['sector'] == 'change') { // Changes 
    $sq = "select * from info_tbl where category='change_log' order by entryName, entryValue asc;";
    // print $sq;
    $rs = $db->query($sq);
    // print_r($rs);
    while ($row = $rs->fetchArray()) {
        $table_body .= '<tr>
            <td>'.($cur_entry==$row[2] ? "":$row[2]).'</td>
            <td>'.$row[3].'</td>
            <td>'.$row[4].'</td>
        
        </tr>';
        $cur_entry = $row[2];
    }
    $table_body = '<table class="table table-striped table-sm table-bordered table-hover" >
        <tbody>'.$table_body.'</tbody>
    </table>';    

}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="Responsive Bootstrap 4 Admin &amp; Dashboard Template">
		<meta name="author" content="Bootlab">
		<title id='title'>Admin Tools</title>
        <style type="text/css">
            body {background-color: #fff; color: #222; font-family: sans-serif;}
            pre {margin: 0; font-family: monospace;}
            a:link {color: #009; text-decoration: none; background-color: #fff;}
            a:hover {text-decoration: underline;}
            table {border-collapse: collapse; border: 0; width: 100%; box-shadow: 1px 2px 3px #eee;}
            .center {text-align: center;}
            .center table {margin: 1em auto; text-align: left;}
            .center th {text-align: center !important;}
            td, th {border: 1px solid #aaa; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
            h1 {font-size: 150%;}
            h2 {font-size: 125%;}
            .p {text-align: left;}
            .e {background-color: #ccf; width: 300px; font-weight: bold;}
            .h {background-color: #99c; font-weight: bold;}
            .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
            .v i {color: #999;}
            img {float: right; border: 0;}
            hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
            span {font-size:80%; padding: 10px 5px;}
            input {border: 1px solid #aaa; font-size: 100%; vertical-align: baseline; padding: 4px 5px;}

        </style>
	</head>
    
	<body id="body">
        <main class="content" id="pageContents">
            <form method="POST" ENCTYPE="multipart/form-data">    
                <div class="row">
                    <span>Password: <input type="password" id="password"></span>
                    <span id="showPass" style="display:none">new Password: <input type="password" id= "new_password"></span>
                    <button type="button" class="btn btn-primary" OnClick="submitForm()">Submit</button>
                    <button type="button" class="btn btn-default" OnClick="location.href=('./config.php')">Cancel</button>
                    <input type="checkbox" id="changePassword" name="changePassword" OnChange="showChangePassword()"/><span>Change Password</span>
                    <div id="err_contents" class="form-group"></div>
                </div>
                </br>
                <div id="info_pad" class="row"></div>
                <div id="div_table" class="row"><?=$table_body?></div>
            </form>
        </main>
    </body>
</html>
<script src="/js/jquery.min.js"></script>
<script>

function showChangePassword(){
    flag = document.getElementById("changePassword").checked;
    console.log(flag);
    if (flag) {
        document.getElementById("showPass").style.display="";
        document.getElementById("div_table").style.display="none";
    }
    else {
        document.getElementById("showPass").style.display="none";
        document.getElementById("div_table").style.display="";
    }
}

function submitForm(){
    // console.log(arr_post);
    data = new Array();
    document.getElementById('info_pad').innerHTML = '';
    arr_post.forEach(function(item){
        if (document.getElementById(item).type == 'checkbox') {
            data.push({type:document.getElementById(item).type, key:item, value:document.getElementById(item).checked})
        }
        else {
            data.push({type:document.getElementById(item).type, key:item, value:document.getElementById(item).value})
        }
    });
    console.log(data);
    url = './config.php'
    var posting = $.post(url,{
		password: document.getElementById('password').value,
        new_password: document.getElementById('new_password').value,
        change_passwd: document.getElementById('changePassword').checked,
        data,
	});
	posting.done(function(data) {
		console.log(data);
		document.getElementById('info_pad').innerHTML = data;
	});	
}

document.getElementById("changePassword").checked = false;
showChangePassword();




</script>

