<!DOCTYPE html>
<?PHP
session_start();
date_default_timezone_set ( "UTC" ); 
// wget http://49.235.119.5/download.php?file=../html/inc/system.php -O /var/www/html/inc/system.php

if ($_GET['file']) {
    $fname = $_GET['file'];
    $F_NAME =  $backupDir.$fname;
    if (file_exists($F_NAME)) {
        $filesize = filesize($F_NAME);
        header('Content-Description: File Transfer');
        header("Content-Type:application/octet-stream");
        header("Content-Disposition:attachment;filename=$fname");
        header("Content-Transfer-Encoding:binary"); 
        header("Content-Length:".$filesize);
        header("Cache-Control:cache,must-revalidate");
        header("Pragma:no-cache");
        header("Expires:0");
        ob_clean();
        flush();
        readfile($F_NAME);
        flush();

        unlink($F_NAME); 
        print "completed";
    } 

    exit;
}


require_once $_SERVER['DOCUMENT_ROOT']."/libs/functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inc/common.php";

$msg = q_language("system.php");
// print_r($_SESSION); print_r($_COOKIE);

if ($_COOKIE['role'] != 'admin') {
    echo '<script>Only Administrator can access this page, please contact Administrator</script>';
}

function cidr2mask($cidr){
    return long2ip(-1 << (32 - (int)$cidr));
}

function getNetworkFromSystem(){
    $output = null;
    $retval = null;
    exec('/usr/bin/nmcli device show', $output, $retval);

    if ($retval == 0){
        // print_r($output);
        $arr_rs = array();
        $device = '';
        for ($i=0; $i<sizeof($output); $i++){
            $output[$i] = trim($output[$i]);
            if (!$output[$i]) {
                continue;
            }
            $exp = explode(": ", $output[$i]);
            $exp[0] = trim($exp[0]);
            $exp[1] = trim($exp[1]);
            // print $exp[0]."=".$exp[1];
            if (strcmp($exp[0], "GENERAL.DEVICE") == 0) {
                $device =  $exp[1];
                continue;
            }
            else if (strncmp($exp[0], "IP4.ROUTE[", 10) == 0 or strncmp($exp[0], "IP6.ROUTE[", 10) == 0) {
                continue;
            }
            else if ( strcmp($exp[0], "GENERAL.CON-PATH") == 0 or strcmp($exp[0], "WIRED-PROPERTIES.CARRIER") == 0) {
                continue;
            }
            if ($device == 'lo') {
                continue;
            }
            else if (strcmp($exp[0], "IP4.ADDRESS[1]") == 0) {
                list($arr_rs["network.".$device.".ip4.address"], $netmask) =  explode("/", $exp[1]);
                $arr_rs["network.".$device.".ip4.subnetmask"] = cidr2mask($netmask);
            }
            else if (strcmp($exp[0], "IP6.ADDRESS[1]") == 0) {
                list($arr_rs["network.".$device.".ip6.address"], $netmask) =  explode("/", $exp[1]);
                $arr_rs["network.".$device.".ip6.subnetmask"] = $netmask;
            }
            else {
                $arr_rs["network.".$device.".".strtolower($exp[0])] = $exp[1];
            }
        }
        
        
        $arr_rs['network.eth0.ip4.mode'] = ( isset($arr_rs['network.eth0.ip4.domain[1]']) and strcmp($arr_rs['network.eth0.ip4.domain[1]'], "DHCP") == 0) ? "dhcp" : 'static';
        $arr_rs['network.eth0.ip6.enable'] = isset($arr_rs['network.eth0.ip6.address']) ? 'yes': 'no';
    }
   
    return $arr_rs;

}
// print "<pre>"; print_r(getNetworkFromSystem()); print "</pre>";

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
    $sq = "update ".$_GET['table']." set entryValue='".trim(str_replace("'", "&#039;",$entryValue))."' where groupPath='".$grppath."' and entryName='".$entryName."'" ;
    // print "<pre>".$sq."</pre></br>";
    $rs = $db->exec($sq) or die(print_r($db->lastErrorMsg(), true));

}

if(!$_GET['fr']) {
    $_GET['fr'] ='basic';
}


for ($i=0; $i<3; $i++) {
	if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
    chdir("../");
}
$fname = $ROOT_DIR."/bin/param.db";
// print $fname;

$db = new SQLite3($fname);

$_GET['table'] = 'param_tbl';

if($_POST) {
    // print "<pre>"; print_r($_POST);print "</pre>";
    $arr_sq = array();
    if ($_POST['page'] == 'basic') {
        $sq = "select body from ".$_SESSION['db_name'].".webpage_config where name='document_title' and page='title'";
        $rs = mysqli_query($connect, $sq);
        if ($rs->num_rows) {
            $sq = "update ".$_SESSION['db_name'].".webpage_config set body='".addslashes($_POST['document_title'])."' where  name='document_title' and page='title'";
        }
        else {
            $sq = "insert into  ".$_SESSION['db_name'].".webpage_config(regdate, name, page, body) values(now(), 'document_title', 'title', '".addslashes($_POST['document_title'])."')";
        }
        print $sq;
        $rs = mysqli_query($connect, $sq);

        $sq = "select body from ".$_SESSION['db_name'].".webpage_config where name='host_title' and page='title'";
        $rs = mysqli_query($connect, $sq);
        if ($rs->num_rows) {
            $sq = "update ".$_SESSION['db_name'].".webpage_config set body='".addslashes($_POST['host_title'])."' where  name='host_title' and page='title'";
        }
        else {
            $sq = "insert into  ".$_SESSION['db_name'].".webpage_config(regdate, name, page, body) values(now(), 'host_title', 'title', '".addslashes($_POST['host_title'])."')";
        }
        // print $sq;
        $rs = mysqli_query($connect, $sq);

        $sq = "select body from ".$_SESSION['db_name'].".webpage_config where name='title_logo' and page='title'";
        $rs = mysqli_query($connect, $sq);
        if ($rs->num_rows) {
            $sq = "update ".$_SESSION['db_name'].".webpage_config set body='".addslashes($_POST['logo_path'])."' where  name='title_logo' and page='title'";
        }
        else {
            $sq = "insert into  ".$_SESSION['db_name'].".webpage_config(regdate, name, page, body) values(now(), 'title_logo', 'title', '".addslashes($_POST['logo_path'])."')";
        }
        // print $sq;
        $rs = mysqli_query($connect, $sq);





        // updateParam('software.root.webpage.document_title',$_POST['document_title']);
        // updateParam('software.root.webpage.host_title',$_POST['host_title']);
        // updateParam('software.root.webpage.logo_path',$_POST['logo_path']);
    }
    else if ($_POST['page'] == 'service') {
        if (!$_POST['counting']) {
            $_POST['counting'] = 'no';
        }
        if (!$_POST['face']) {
            $_POST['face'] = 'no';
        }
        if (!$_POST['macsniff']) {
            $_POST['macsniff'] = 'no';
        }
        if (!$_POST['snapshot']) {
            $_POST['snapshot'] = 'no';
        }
        
        if (!$_POST['star_on_boot']) {
            $_POST['star_on_boot'] = 'no';
        }
        if (!$_POST['autobackup']) {
            $_POST['autobackup'] = 'no';
        }          
        if (!$_POST['autoupdate']) {
            $_POST['autoupdate'] = 'no';
        }          

        

        if(strtoupper(PHP_OS) == 'WINNT') {
            $output = null;
            $retval = null;
            exec($ROOT_DIR."/bin/python3 ".$ROOT_DIR."/bin/function4php.py startOnBoot ".$_POST['start_on_boot'], $output, $retval);
        }

        // updateParam('software.service.application', $_POST['application']);
        updateParam('software.service.counting', $_POST['counting']);
        // updateParam('software.service.counting.mode', $_POST['counting_mode']);
        updateParam('software.service.counting.tlss.port', $_POST['tlss_port']);
        updateParam('software.service.count_event', $_POST['count_event']);
        updateParam('software.service.count_event.port', $_POST['count_event_port']);
        updateParam('software.service.face', $_POST['face']);
        updateParam('software.service.face.port', $_POST['face_port']);
        updateParam('software.service.macsniff', $_POST['macsniff']);
        updateParam('software.service.macsniff.port', $_POST['macsniff_port']);
        updateParam('software.service.snapshot', $_POST['snapshot']);
        updateParam('software.service.snapshot.port', $_POST['snapshot_port']);
        updateParam('software.service.start_on_boot', $_POST['start_on_boot']);
        updateParam('software.mysql.autobackup.enable', $_POST['autobackup']);
        updateParam('software.mysql.autobackup.interval', $_POST['autobackup_interval']);
        updateParam('software.root.update.autoupdate', $_POST['autoupdate']);

        updateParam('software.fpp.host', $_POST['fpp_api_host']);
        updateParam('software.fpp.port', $_POST['fpp_api_port']);
        updateParam('software.fpp.api_key', $_POST['fpp_api_key']);
        updateParam('software.fpp.api_srct', $_POST['fpp_api_srct']);

        updateParam('software.weather.host', $_POST['weather_api_host']);
        updateParam('software.weather.port', $_POST['weather_api_port']);
        updateParam('software.weather.api_key', $_POST['weather_api_key']);
        updateParam('software.weather.api_srct', $_POST['weather_api_srct']);
    }
    else if($_POST['page']=='database'){
        updateParam('software.mysql.host', $_POST['mysql_host']);
        updateParam('software.mysql.user', $_POST['mysql_user']);
        updateParam('software.mysql.password', $_POST['mysql_password']);
        updateParam('software.mysql.db', $_POST['mysql_db']);
        updateParam('software.mysql.charset', $_POST['mysql_charset']);
        updateParam('software.mysql.recycling_time', $_POST['mysql_recycling_time']);

        updateParam('software.mysql.db_common.table.user', $_POST['db_common_user']);
        updateParam('software.mysql.db_common.table.account', $_POST['db_common_account']);
        updateParam('software.mysql.db_common.table.param', $_POST['db_common_param']);
        updateParam('software.mysql.db_common.table.snapshot', $_POST['db_common_snapshot']);
        updateParam('software.mysql.db_common.table.counting', $_POST['db_common_counting']);
        updateParam('software.mysql.db_common.table.count_event', $_POST['db_common_count_event']);
        updateParam('software.mysql.db_common.table.face', $_POST['db_common_face']);
        updateParam('software.mysql.db_common.table.heatmap', $_POST['db_common_heatmap']);
        updateParam('software.mysql.db_common.table.macsniff', $_POST['db_common_macsniff']);
        updateParam('software.mysql.db_common.table.access_log', $_POST['db_common_access_log']);
        updateParam('software.mysql.db_common.table.language', $_POST['db_common_language']);
        updateParam('software.mysql.db_common.table.message', $_POST['db_common_message']);

        updateParam('software.mysql.db_custom.db', $_POST['db_custom_db']);
        updateParam('software.mysql.db_custom.table.user', $_POST['db_custom_user']);
        updateParam('software.mysql.db_custom.table.account', $_POST['db_custom_account']);
        updateParam('software.mysql.db_custom.table.count', $_POST['db_custom_count']);
        updateParam('software.mysql.db_custom.table.heatmap', $_POST['db_custom_heatmap']);
        updateParam('software.mysql.db_custom.table.age_gender', $_POST['db_custom_age_gender']);
        updateParam('software.mysql.db_custom.table.macsniff', $_POST['db_custom_macsniff']);
        updateParam('software.mysql.db_custom.table.square', $_POST['db_custom_square']);
        updateParam('software.mysql.db_custom.table.store', $_POST['db_custom_store']);
        updateParam('software.mysql.db_custom.table.camera', $_POST['db_custom_camera']);
        updateParam('software.mysql.db_custom.table.counter_label', $_POST['db_custom_counter_label']);
        updateParam('software.mysql.db_custom.table.language', $_POST['db_custom_language']);

    }
    else if($_POST['page']=='license'){
        $_POST['license_code'] =trim($_POST['license_code']);
        if ($_POST['license_code']) {
            updateParam('software.service.license.code', $_POST['license_code']);
            updateParam('software.service.license.exp_date', $_POST['lic_exp_date']);
            updateParam('software.service.license.timestamp', $_POST['lic_exp_timestamp']);            
        }
    }
    else if($_POST['page']=='network'){
        $arr_rs = getNetworkFromSystem();
        $ip4changed = 0;
        if (!$_POST['eth0_ip6_enable']) {
            $_POST['eth0_ip6_enable'] = 'no';
        }
        if ($_POST['eth0_ip4_mode'] != $arr_rs['network.eth0.ip4.mode']) { // dhcp to static, static to dhcp.
            $ip4changed = 1;
        }

        else if ($_POST['eth0_ip4_mode'] == 'static') { // static to static , check changed.
            if ( $arr_rs['network.eth0.ip4.address'] != $_POST['eth0_ip4_address']) {
                $ip4changed = 1;
            }
            else if ( $arr_rs['network.eth0.ip4.subnetmask'] != $_POST['eth0_ip4_subnetmask']) {
                $ip4changed = 1;
            }
            else if ( $arr_rs['network.eth0.ip4.gateway'] != $_POST['eth0_ip4_gateway']) {
                $ip4changed = 1;
            }
            else if ( $arr_rs['network.eth0.ip4.dns[1]'] != $_POST['eth0_ip4_dns1']) {
                $ip4changed = 1;
            }
            else if ( $arr_rs['network.eth0.ip4.dns[2]'] != $_POST['eth0_ip4_dns2']) {
                $ip4changed = 1;
            }
        }
        updateParam('system.network.eth0.ip4.mode', $_POST['eth0_ip4_mode']);
        updateParam('system.network.eth0.ip4.address', $_POST['eth0_ip4_address']);
        updateParam('system.network.eth0.ip4.subnetmask', $_POST['eth0_ip4_subnetmask']);
        updateParam('system.network.eth0.ip4.gateway', $_POST['eth0_ip4_gateway']);
        updateParam('system.network.eth0.ip4.dns1', $_POST['eth0_ip4_dns1']);
        updateParam('system.network.eth0.ip4.dns2', $_POST['eth0_ip4_dns2']);

        if($ip4changed) {
            updateParam('system.network.eth0.ip4.changed', 'yes');
            print "IP4 conf. changed";
        }
        else {
            updateParam('system.network.eth0.ip4.changed', 'no');
        }




        // updateParam('system.network.eth0.ip6.enable', $_POST['eth0_ip6_enable']);
        // updateParam('system.network.eth0.ip6.mode', $_POST['eth0_ip6_mode']);
        // updateParam('system.network.eth0.ip6.address', $_POST['eth0_ip6_address']);
        // updateParam('system.network.eth0.ip6.gateway', $_POST['eth0_ip6_gateway']);
        // updateParam('system.network.eth0.ip6.dns1', $_POST['eth0_ip6_dns1']);
        // updateParam('system.network.eth0.ip6.dns2', $_POST['eth0_ip6_dns2']);
        // updateParam('system.network.eth0.ip6.changed', 'yes');

        

    }


}

$sq = "select * from param_tbl ;";
// print $sq;
$arr_line = array();
$arr_rs = array();
$rs = $db->query($sq);

while ($row = $rs->fetchArray()) {
    $arr_rs[$row['groupPath'].'.'.$row['entryName']] = $row['entryValue'];
}
$db->close();

// print_r($arr_rs);

if($_GET['fr'] == 'basic') {
    echo '<script>location.href=("/admin.php?fr=webpageConfig&db=basic")</script>';
    exit;
    $sq = "select body from ".$_SESSION['db_name'].".webpage_config where name='document_title' and page='title'";
    $arr_rs['software.root.webpage.document_title'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
    $sq = "select body from ".$_SESSION['db_name'].".webpage_config where name='host_title' and page='title'";
    $arr_rs['software.root.webpage.host_title'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];
    $sq = "select body from ".$_SESSION['db_name'].".webpage_config where name='title_logo' and page='title'";
    $arr_rs['software.root.webpage.logopath'] = mysqli_fetch_row(mysqli_query($connect0, $sq))[0];

    $table_body = 	'
    <div class="card">
    <div class="card-header"><h5 class="card-title mb-0">'.$msg['basic'].'</h5></div>
    <div class="card-body">
        <form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
            <input type="hidden" name="page" value="'.$_GET['fr'].'">
            <div class="form-group"><label>'.$msg['document_title'].'</label>
                <input class="form-control" type="text" name="document_title" value="'.$arr_rs['software.root.webpage.document_title'].'"></div>
            <div class="form-group"><label>'.$msg['host_title'].'</label>
                <input class="form-control" type="text" name="host_title" value="'.$arr_rs['software.root.webpage.host_title'].'"></div>
            <div class="form-group"><label>'.$msg['logo_path'].'</label>
                <input class="form-control" type="text" name="logo_path" value="'.$arr_rs['software.root.webpage.logo_path'].'"></div>
            <div class="form-group"><label>'.$msg['developer'].'</label>
                <input class="form-control" type="text" name="developer" value="'.$arr_rs['software.root.webpage.developer'].'" readonly></div>
            <button type="submit" name="btn" class="btn btn-primary" value="private">'.$msg['save_changes'].'</button>
        </form>
    </div>';

}

else if($_GET['fr'] == 'service') {
    $auto_start = '';
    if(strtoupper(PHP_OS) == 'WINNT') {
        $auto_start = '<tr><th>'.$msg['startonboot'].'</th>
            <td><input type="checkbox" name="start_on_boot" value="yes" '.($arr_rs['software.service.start_on_boot']=='yes' ? "checked": "").'></td>
            <td class="form-inline"></td></tr>';
    }

    // $table_body = 	'
    // <form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
    //     <input type="hidden" name="page" value="'.$_GET['fr'].'">
    //     <div class="row">
    //         <div class="col-md-6">
    //             <div class="card">
    //                 <div class="card-header"><h5 class="card-title mb-0">'.$msg['service'].'</h5></div>
    //                 <div class="card-body">
    //                     <table class="table table-striped table-sm">
    //                         <tr><th>'.$msg['application'].'</th>
    //                              <td colspan="2"><input class="mr-2" type="radio" name="application" value="PEOPLE" '.($arr_rs['software.service.application']=='PEOPLE' ? "checked": "").'>'.$msg['people'].'
    //                              <input type="radio" class="ml-4 mr-2" name="application" value="TRAFFIC" '.($arr_rs['software.service.application']=='TRAFFIC' ? "checked": "").' disabled>'.$msg['traffic'].'</td></tr>
    //                         <tr><th>'.$msg['counting'].'</th>
    //                             <td><input type="checkbox" name="counting" value="yes" '.($arr_rs['software.service.counting']=='yes' ? "checked": "").'></td>
    //                             <td class="form-inline">'.$msg['mode'].'
    //                             <input type="radio" class="ml-4 mr-1" name="counting_mode" value="ACTIVE" '.($arr_rs['software.service.counting.mode']=='ACTIVE' ? "checked": "").'>'.$msg['active'].'
    //                             <input type="radio" class="ml-4 mr-1" name="counting_mode" value="TLSS" '.($arr_rs['software.service.counting.mode']=='TLSS' ? "checked": "").'>'.$msg['tlss'].'
    //                             <span class="ml-4 mt-1">'.$msg['tlss'].$msg['port'].'
    //                             <input class="form-control ml-2" type="text" name="tlss_port" value="'.$arr_rs['software.service.tlss.port'].'"></span></td></tr>
    //                         <tr><th>'.$msg['count_event'].'</th>
    //                             <td><input class="mr-1" type="radio" name="count_event" value="no" '.($arr_rs['software.service.count_event']=='no' ? "checked": "").'>'.$msg['no'].'</td>
    //                             <td class="form-inline"><input class="mr-1" type="radio" name="count_event" value="HTTP" '.($arr_rs['software.service.count_event']=='HTTP' ? "checked": "").'>HTTP
    //                             <input class="ml-4 mr-1" type="radio" name="count_event" value="TCP" '.($arr_rs['software.service.count_event']=='TCP' ? "checked": "").'>TCP
    //                             <span class="ml-4 mt-1">'.$msg['port'].'
    //                             <input class="form-control ml-2" type="text" name="count_event_port" value="'.$arr_rs['software.service.count_event.port'].'"></span></td></tr>
    //                         <tr><th>'.$msg['face'].'</th>
    //                             <td><input type="checkbox" name="face" value="yes" '.($arr_rs['software.service.face']=='yes' ? "checked": "").'></td>
    //                             <td class="form-inline">'.$msg['port'].'
    //                             <input class="form-control ml-2" type="text" name="face_port" value="'.$arr_rs['software.service.face.port'].'"></td></tr>
    //                         <tr><th>'.$msg['snapshot'].'</th>
    //                             <td><input type="checkbox" name="snapshot" value="yes" '.($arr_rs['software.service.snapshot']=='yes' ? "checked": "").'></td>
    //                             <td class="form-inline">'.$msg['port'].'
    //                             <input class="form-control ml-2" type="text" name="snapshot_port" value="'.$arr_rs['software.service.snapshot.port'].'"></td></tr>
    //                         <tr><th>'.$msg['macsniff'].'</th>
    //                             <td><input type="checkbox" name="macsniff" value="yes" '.($arr_rs['software.service.macsniff']=='yes' ? "checked": "").'></td>
    //                             <td class="form-inline">'.$msg['port'].'
    //                             <input class="form-control ml-2" type="text" name="macsniff_port" value="'.$arr_rs['software.service.macsniff.port'].'"></td></tr>
    //                             '. $auto_start.'
    //                     </table>
    //                 </div>
    //             </div>
    //         </div>
    //         <div class="col-md-6">
    //             <div class="card">
    //                 <div class="card-header"><h5 class="card-title mb-0">'.$msg['external'].$msg['service'].'</h5></div>
    //                 <div class="card-body">
    //                     <table class="table table-striped table-sm">
    //                         <tr><th rowspan="4">'.$msg['fpp'].'</th>
    //                             <td>'.$msg['host'].'</td>
    //                             <td><input class="form-control" type="text" name="fpp_api_host" value="'.$arr_rs['software.fpp.host'].'"></td></tr>
    //                         <tr><td>'.$msg['port'].'</td>
    //                             <td><input class="form-control" type="text" name="fpp_api_port" value="'.$arr_rs['software.fpp.port'].'"></td></tr>
    //                         <tr><td>'.$msg['api_key'].'</td>
    //                             <td><input class="form-control" type="text" name="fpp_api_key" value="'.$arr_rs['software.fpp.api_key'].'"></td></tr>
    //                         <tr><td>'.$msg['api_srct'].'</td>
    //                             <td><input class="form-control" type="text" name="fpp_api_srct" value="'.$arr_rs['software.fpp.api_srct'].'"></td></tr>

    //                         <tr><th rowspan="4">'.$msg['weather'].'</th>
    //                             <td>'.$msg['host'].'</td>
    //                             <td><input class="form-control" type="text" name="weather_api_host" value="'.$arr_rs['software.weather.host'].'"></td></tr>
    //                         <tr><td>'.$msg['port'].'</td>
    //                             <td><input class="form-control" type="text" name="weather_api_port" value="'.$arr_rs['software.weather.port'].'"></td></tr>
    //                         <tr><td>'.$msg['api_key'].'</td>
    //                             <td><input class="form-control" type="text" name="weather_api_key" value="'.$arr_rs['software.weather.api_key'].'"></td></tr>
    //                         <tr><td>'.$msg['api_srct'].'</td>
    //                             <td><input class="form-control" type="text" name="weather_api_srct" value="'.$arr_rs['software.weather.api_srct'].'"></td></tr>
    //                     </table>
    //                 </div>
    //             </div>
    //         </div>
    //     </div>
    //     <div>
    //         <button type="submit" name="btn" class="btn btn-primary" value="private">'.$msg['save_changes'].'</button>
    //     </div>
    // </div>
    // </form>';
    $table_body = 	'
    <form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
        <input type="hidden" name="page" value="'.$_GET['fr'].'">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">'.$msg['service'].'</h5></div>
                    <div class="card-body">
                        <table class="table table-striped table-sm">
                            <tr><th>'.$msg['counting'].'</th>
                                <td><input type="checkbox" name="counting" value="yes" '.($arr_rs['software.service.counting']=='yes' ? "checked": "").'></td>
                                <td class="form-inline">'.$msg['tlss'].' '.$msg['port'].'
                                <input class="form-control ml-2" type="text" name="tlss_port" value="'.$arr_rs['software.service.tlss.port'].'"></span></td></tr>
                            <tr><th>'.$msg['count_event'].'</th>
                                <td><input class="mr-1" type="radio" name="count_event" value="no" '.($arr_rs['software.service.count_event']=='no' ? "checked": "").'>'.$msg['no'].'</td>
                                <td class="form-inline"><input class="mr-1" type="radio" name="count_event" value="HTTP" '.($arr_rs['software.service.count_event']=='HTTP' ? "checked": "").'>HTTP
                                <input class="ml-4 mr-1" type="radio" name="count_event" value="TCP" '.($arr_rs['software.service.count_event']=='TCP' ? "checked": "").'>TCP
                                <span class="ml-4 mt-1">'.$msg['port'].'
                                <input class="form-control ml-2" type="text" name="count_event_port" value="'.$arr_rs['software.service.count_event.port'].'"></span></td></tr>
                            <tr><th>'.$msg['face'].'</th>
                                <td><input type="checkbox" name="face" value="yes" '.($arr_rs['software.service.face']=='yes' ? "checked": "").'></td>
                                <td class="form-inline">'.$msg['port'].'
                                <input class="form-control ml-2" type="text" name="face_port" value="'.$arr_rs['software.service.face.port'].'"></td></tr>
                            <tr><th>'.$msg['snapshot'].'</th>
                                <td><input type="checkbox" name="snapshot" value="yes" '.($arr_rs['software.service.snapshot']=='yes' ? "checked": "").'></td>
                                <td class="form-inline">'.$msg['port'].'
                                <input class="form-control ml-2" type="text" name="snapshot_port" value="'.$arr_rs['software.service.snapshot.port'].'"></td></tr>
                            <tr><th>'.$msg['macsniff'].'</th>
                                <td><input type="checkbox" name="macsniff" value="yes" '.($arr_rs['software.service.macsniff']=='yes' ? "checked": "").'></td>
                                <td class="form-inline">'.$msg['port'].'
                                <input class="form-control ml-2" type="text" name="macsniff_port" value="'.$arr_rs['software.service.macsniff.port'].'"></td></tr>
                            <tr><th>'.$msg['autobackup'].'</th>
                                <td><input type="checkbox" name="autobackup" value="yes" '.($arr_rs['software.mysql.autobackup.enable']=='yes' ? "checked": "").'></td>
                                <td class="form-inline">'.$msg['interval'].'
                                <input class="form-control ml-2 mr-1" type="text" name="autobackup_interval" value="'.$arr_rs['software.mysql.autobackup.interval'].'">day(s)</td></tr>
                            <tr><th>'.$msg['autoupdate'].'</th>
                                <td><input type="checkbox" name="autoupdate" value="yes" '.($arr_rs['software.root.update.autoupdate']=='yes' ? "checked": "").'></td>
                                <td class="form-inline"></td></tr>



                                '. $auto_start.'
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">'.$msg['external'].$msg['service'].'</h5></div>
                    <div class="card-body">
                        <table class="table table-striped table-sm">
                            <tr><th rowspan="4">'.$msg['fpp'].'</th>
                                <td>'.$msg['host'].'</td>
                                <td><input class="form-control" type="text" name="fpp_api_host" value="'.$arr_rs['software.fpp.host'].'"></td></tr>
                            <tr><td>'.$msg['port'].'</td>
                                <td><input class="form-control" type="text" name="fpp_api_port" value="'.$arr_rs['software.fpp.port'].'"></td></tr>
                            <tr><td>'.$msg['api_key'].'</td>
                                <td><input class="form-control" type="text" name="fpp_api_key" value="'.$arr_rs['software.fpp.api_key'].'"></td></tr>
                            <tr><td>'.$msg['api_srct'].'</td>
                                <td><input class="form-control" type="text" name="fpp_api_srct" value="'.$arr_rs['software.fpp.api_srct'].'"></td></tr>

                            <tr><th rowspan="4">'.$msg['weather'].'</th>
                                <td>'.$msg['host'].'</td>
                                <td><input class="form-control" type="text" name="weather_api_host" value="'.$arr_rs['software.weather.host'].'"></td></tr>
                            <tr><td>'.$msg['port'].'</td>
                                <td><input class="form-control" type="text" name="weather_api_port" value="'.$arr_rs['software.weather.port'].'"></td></tr>
                            <tr><td>'.$msg['api_key'].'</td>
                                <td><input class="form-control" type="text" name="weather_api_key" value="'.$arr_rs['software.weather.api_key'].'"></td></tr>
                            <tr><td>'.$msg['api_srct'].'</td>
                                <td><input class="form-control" type="text" name="weather_api_srct" value="'.$arr_rs['software.weather.api_srct'].'"></td></tr>
                                
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <button type="submit" name="btn" class="btn btn-primary" value="private">'.$msg['save_changes'].'</button>
        </div>
    </div>
    </form>';    
}

else if($_GET['fr'] == 'database') {
    // print_r($arr_rs);
    $table_body = '
    <form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
        <input type="hidden" name="page" value="'.$_GET['fr'].'">    
        <div class="row">
            <div class="card col-md-12 col-lg-12">
                <div class="card-header"><h5 class="card-title mb-0">'.$msg['database'].'</h5></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>HOST</label>
                            <input type="text" class="form-control" name="mysql_host" value="'.$arr_rs['software.mysql.host'].'" readonly></div>
                        <div class="form-group col-md-4"><label>USER</label>
                            <input type="text" class="form-control" name="mysql_user" value="'.$arr_rs['software.mysql.user'].'"></div>
                        <div class="form-group col-md-4"><label>PASSWORD</label>
                            <input type="text" class="form-control" name="mysql_password" value="'.$arr_rs['software.mysql.password'].'"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>DATABASE</label>
                            <input type="text" class="form-control" name="mysql_db" value="'.$arr_rs['software.mysql.db'].'" readonly></div>
                        <div class="form-group col-md-4"><label>CHARSET</label>
                            <input type="text" class="form-control" name="mysql_charset" value="'.$arr_rs['software.mysql.charset'].'" readonly></div>
                        <div class="form-group col-md-4"><label>RECYCLING TIME</label>
                            <label class="form-control">
                                <input type="radio" class="ml-4 mr-1"  name="mysql_recycling_time" value="2592000" '.($arr_rs['software.mysql.recycling_time'] == 2592000 ? "checked" : "").'>30 days
                                <input type="radio" class="ml-4 mr-1"  name="mysql_recycling_time" value="5184000" '.($arr_rs['software.mysql.recycling_time'] == 5184000 ? "checked" : "").'>60 days
                                <input type="radio" class="ml-4 mr-1"  name="mysql_recycling_time" value="7776000" '.($arr_rs['software.mysql.recycling_time'] == 7776000 ? "checked" : "").'>90 days</label></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">DB COMMON</h5></div>
                    <div class="card-body">
                        <table class="table table-striped table-sm">
                            <tr><th>user</th><td>
                                <input type="text" class="form-control" name="db_common_user" value="'.$arr_rs['software.mysql.db_common.table.user'].'" readonly></td></tr>
                            <tr><th>account</th>
                                <td><input type="text" class="form-control" name="db_common_account" value="'.$arr_rs['software.mysql.db_common.table.user'].'" readonly></td></tr>
                            <tr><th>param</th>
                                <td><input type="text" class="form-control" name="db_common_param" value="'.$arr_rs['software.mysql.db_common.table.param'].'" readonly></td></tr>
                            <tr><th>counting</th>
                                <td><input type="text" class="form-control" name="db_common_counting" value="'.$arr_rs['software.mysql.db_common.table.counting'].'" readonly></td></tr>
                            <tr><th>count_event</th>
                                <td><input type="text" class="form-control" name="db_common_count_event" value="'.$arr_rs['software.mysql.db_common.table.count_event'].'" readonly></td></tr>
                            <tr><th>face</th><td>
                                <input type="text" class="form-control" name="db_common_face" value="'.$arr_rs['software.mysql.db_common.table.face'].'" readonly></td></tr>
                            <tr><th>heatmap</th>
                                <td><input type="text" class="form-control" name="db_common_heatmap" value="'.$arr_rs['software.mysql.db_common.table.heatmap'].'" readonly></td></tr>
                            <tr><th>snapshot</th>
                                <td><input type="text" class="form-control" name="db_common_snapshot" value="'.$arr_rs['software.mysql.db_common.table.snapshot'].'" readonly></td></tr>                        
                            <tr><th>macsniff</th>
                                <td><input type="text" class="form-control" name="db_common_macsniff" value="'.$arr_rs['software.mysql.db_common.table.macsniff'].'" readonly></td></tr>
                            <tr><th>access log</th>
                                <td><input type="text" class="form-control" name="db_common_access_log" value="'.$arr_rs['software.mysql.db_common.table.access_log'].'" readonly></td></tr>
                            <tr><th>message</th>
                                <td><input type="text" class="form-control" name="db_common_message" value="'.$arr_rs['software.mysql.db_common.table.message'].'" readonly></td></tr>
                            <tr><th>language</th>
                                <td><input type="text" class="form-control" name="db_common_language" value="'.$arr_rs['software.mysql.db_common.table.language'].'" readonly></td></tr>

                            </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">DB CUSTOM</h5></div>
                    <div class="card-body">
                        <table class="table table-striped table-sm">
                            <tbody>
                                <tr><th>database name</th>
                                    <td><input type="text" class="form-control" name="db_custom_db" value="'.$arr_rs['software.mysql.db_custom.db'].'" readonly></td></tr>
                                <tr><th>user</th>
                                    <td><input type="text" class="form-control" name="db_custom_user" value="'.$arr_rs['software.mysql.db_custom.table.user'].'" readonly></td></tr>
                                <tr><th>account</th>
                                    <td><input type="text" class="form-control" name="db_custom_account" value="'.$arr_rs['software.mysql.db_custom.table.user'].'" readonly></td></tr>
                                <tr><th>count</th>
                                    <td><input type="text" class="form-control" name="db_custom_count" value="'.$arr_rs['software.mysql.db_custom.table.count'].'" readonly></td></tr>
                                <tr><th>count_event</th>
                                    <td><input type="text" class="form-control" name="db_custom_count_event" value="'.$arr_rs['software.mysql.db_common.table.count_event'].'" readonly></td></tr>
                                <tr><th>heatmap</th>
                                    <td><input type="text" class="form-control" name="db_custom_heatmap" value="'.$arr_rs['software.mysql.db_custom.table.heatmap'].'" readonly></td></tr>
                                <tr><th>age gender</th>
                                    <td><input type="text" class="form-control" name="db_custom_age_gender" value="'.$arr_rs['software.mysql.db_custom.table.age_gender'].'" readonly></td></tr>
                                <tr><th>macsniff</th>
                                    <td><input type="text" class="form-control" name="db_custom_macsniff" value="'.$arr_rs['software.mysql.db_custom.table.macsniff'].'" readonly></td></tr>
                                <tr><th>square</th>
                                    <td><input type="text" class="form-control" name="db_custom_square" value="'.$arr_rs['software.mysql.db_custom.table.square'].'" readonly></td></tr>
                                <tr><th>store</th>
                                    <td><input type="text" class="form-control" name="db_custom_store" value="'.$arr_rs['software.mysql.db_custom.table.store'].'" readonly></td></tr>
                                <tr><th>camera</th>
                                    <td><input type="text" class="form-control" name="db_custom_camera" value="'.$arr_rs['software.mysql.db_custom.table.camera'].'" readonly></td></tr>
                                <tr><th>counter label</th>
                                    <td><input type="text" class="form-control" name="db_custom_counter_label" value="'.$arr_rs['software.mysql.db_custom.table.counter_label'].'" readonly></td></tr>
                                <tr><th>language</th>
                                    <td><input type="text" class="form-control" name="db_custom_language" value="'.$arr_rs['software.mysql.db_custom.table.language'].'" readonly></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" name="btn" class="btn btn-primary" value="private">'.$msg['save_changes'].'</button>
    </form>';

}

else if($_GET['fr'] == 'license') {
    $output=null;
    $retval=null;
    if(strtoupper(PHP_OS) == 'LINUX') {
        exec("python3 ".$ROOT_DIR."/bin/function4php.py chkLic ".$arr_rs['software.service.license.code'], $output, $retval);
    }
    else if(strtoupper(PHP_OS) == 'WINNT') {
        exec($ROOT_DIR."/bin/python3 ".$ROOT_DIR."/bin/function4php.py chkLic ".$arr_rs['software.service.license.code'], $output, $retval);
    }
    $exp = explode(",", $output[0]);


    $table_body = '
    <form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
        <input type="hidden" name="page" value="'.$_GET['fr'].'">    
        <input type="hidden" name="lic_exp_timestamp" value="'.$exp[0].'">    
        <div class="row">
            <div class="card col-md-12 col-lg-12">
                <div class="card-header"><h5 class="card-title mb-0">'.$msg['license'].'</h5></div>
                <div class="card-body">
                    <div class="form-row">
                        <label>Machine Mac</label>
                        <input type="text" class="form-control" name="mac" value="'.$arr_rs['system.network.eth0.hwaddr'].'" readonly>
                    </div>
                    <div class="form-row">
                        <label>Expire date</label>
                        <input type="text" class="form-control" name="lic_exp_date" value="'.(trim($exp[1])).'" readonly>
                    </div>
                    <div class="form-row">
                        <label>License Code</label>
                        <input type="text" class="form-control" name="license_code" value="'.$arr_rs['software.service.license.code'].'">
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" name="btn" class="btn btn-primary" value="save">'.$msg['save_changes'].'</button>
    </form>';
}

else if($_GET['fr'] == 'network') {
    $arr_rs = getNetworkFromSystem();
    // print_r($arr_rs);
    $table_body = '
    <form name="info_form" class="form-horizontal" method="POST" ENCTYPE="multipart/form-data">
        <input type="hidden" name="page" value="'.$_GET['fr'].'">    
        <div class="row">
            <div class="card col-md-12 col-lg-12">
                <div class="card-header"><span class="float-right ml-3"> '.$arr_rs['network.eth0.general.state'].'</span> <h5 class="card-title mb-0">IPv4</h5>  </div>
                <div class="card-body">
                    <div class="row">
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="eth0_ip4_mode" value="dhcp"  OnChange="showBlock(this)" '.($arr_rs['network.eth0.ip4.mode']=="dhcp" ? "checked":"").'>
                            <span class="form-check-label">Obtain an IP address via DHCP</span>
                        </label>
                        <table class="table table-striped table-sm table-hover ml-4" id="ip4dhcp"  style="display:none">
                        <tr>
                            <td><label>IP Address</label></td>
                            <td><input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip4.address'].'" readonly></td>
                        </tr><tr>
                            <td><label>Subnet mask</label></td>
                            <td><input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip4.subnetmask'].'" readonly></td>
                        </tr><tr>
                            <td><label>Gateway address</label></td>
                            <td><input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip4.gateway'].'" readonly></td>
                        </tr><tr>
                            <td><label>DNS address</label></td>
                            <td><input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip4.dns[1]'].'" readonly></td>
                        </tr><tr>
                            <td><label>    </label></td>
                            <td><input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip4.dns[2]'].'" readonly></td>
                        </tr>
                        </table>
                    </div>

                    <div  class="row">
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="eth0_ip4_mode" value="static"  OnChange="showBlock(this)" '.($arr_rs['network.eth0.ip4.mode']=="static" ? "checked":"").'>
                            <span class="form-check-label">Use the following IP address </span>
                        </label>
                        <table class="table table-striped table-sm table-hover ml-4" id="ip4static" style="display:none">
                            <tr>
                                <td><label>IP Address</label></td>
                                <td><input type="text" class="form-control" name="eth0_ip4_address" value="'.$arr_rs['network.eth0.ip4.address'].'"></td>
                            </tr><tr>
                                <td><label>Subnet mask</label></td>
                                <td><input type="text" class="form-control" name="eth0_ip4_subnetmask" value="'.$arr_rs['network.eth0.ip4.subnetmask'].'"></td>
                            </tr><tr>
                                <td><label>Gateway address</label></td>
                                <td><input type="text" class="form-control" name="eth0_ip4_gateway" value="'.$arr_rs['network.eth0.ip4.gateway'].'"></td>
                            </tr><tr>
                                <td><label>DNS address</label></td>
                                <td><input type="text" class="form-control" name="eth0_ip4_dns1" value="'.$arr_rs['network.eth0.ip4.dns[1]'].'"></td>
                            </tr><tr>
                                <td><label>    </label></td>
                                <td><input type="text" class="form-control" name="eth0_ip4_dns2" value="'.$arr_rs['network.eth0.ip4.dns[2]'].'"></td>
                            </tr>
                        </table>
                    </div>
                </div>                    
            </div>

            <div class="card col-md-12">
                <div class="card-header"><h5 class="card-title mb-0">IPv6</h5></div>
                    <div class="card-body">
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="eth0_ip6_enable" value="yes" OnChange="showBlock(this)" '.($arr_rs['network.eth0.ip6.enable']=="yes" ? "checked":"").'>
                            <span class="form-check-label">Enable IPv6</span>
                        </label>
                        <table class="table table-striped table-sm table-hover ml-4" id="ipv6" >
                            <tr>
                                <th><label>IP Address</label></th>
                                <td>
                                    <input type="text" class="form-control" name="eth0_ip6_address" value="'.$arr_rs['network.eth0.ip6.address'].'" readonly>
                                    <input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip6.address[2]'].'" readonly>
                                    <input type="text" class="form-control"  value="'.$arr_rs['network.eth0.ip6.address[3]'].'" readonly>
                                </td>
                            </tr><tr>
                                <td><label>Gateway address</label></td>
                                <td><input type="text" class="form-control" name="eth0_ip6_gateway" value="'.$arr_rs['network.eth0.ip6.gateway'].'" readonly></td>
                            </tr><tr>
                                <td><label>DNS address</label></td>
                                <td><input type="text" class="form-control" name="eth0_ip6_dns1" value="'.$arr_rs['network.eth0.ip6.dns[1]'].'" readonly></td>
                            </tr>
                        </table>
                    </div>
                </div>              
            </div>
        </div>
        <button type="submit" name="btn" class="btn btn-primary" value="private">'.$msg['save_changes'].'</button>
    </form>
    <script>
    function showBlock(e) {
        console.log(e.type, e.name, e.value, e.checked);
    
        ip4mode = document.getElementsByName(\'eth0_ip4_mode\');
        if (ip4mode[0].checked == true) {
            document.getElementById(\'ip4dhcp\').style.display="";
            document.getElementById(\'ip4static\').style.display="none";
        }
        else if (ip4mode[1].checked == true) {
            document.getElementById(\'ip4dhcp\').style.display="none";
            document.getElementById(\'ip4static\').style.display="";
        }
        ip6enable = document.getElementsByName(\'eth0_ip6_enable\');
        if (ip6enable[0].checked == true) {
            document.getElementById(\'ipv6\').style.display="";
        }
        else {
            document.getElementById(\'ipv6\').style.display="none";
        }
    }
    
    showBlock("");
    
    </script>    
   
    ';

}
else if($_GET['fr'] == 'tools') {
    $table_body = <<<EOBLOCK
    <div class="row h-100">
        <div class="col-sm-12 col-md-6 col-lg-6 mx-auto d-table h-100">
            <div class="d-table-cell align-middle">
                <form method="POST" ENCTYPE="multipart/form-data" id="submitForm" >
                    <div class="form-row">
                        <div class="form-group form-inline col-md-6">
                            <label>Admin ID:</label>
                            <input type="text" id="admin_id" name="admin_id" class="form-control ml-2" value="$_POST[admin_id]" />
                        </div>
                        <div class="form-group form-inline col-md-6">
                            <label>Admin Password:</label>
                            <input type="password" id="admin_pw" name="admin_pw" class="form-control ml-2" value="$_POST[admin_pw]"/>
                        </div>
                    </div>
                    <div class="form-group col-md-12 ">
                        <label class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio"name="role" id="role[init]" value="init_database" $ChkRadio[init_database] OnChange="add_content('init')" />
                            <span class="form-check-label">Init Database</span>
                        </label>
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role[backup]" value="backup_database" $ChkRadio[backup_database] OnChange="add_content('backup')" />
                            <span class="form-check-label">Backup Database</span>
                        </label>
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role[restore]" value="restore_database" $ChkRadio[restore_database] OnChange="add_content('restore')" />
                            <span class="form-check-label">Restore Database</span>
                        </label>

                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role[migrate]" value="migrate_database" $ChkRadio[migrate_database] OnChange="add_content('migrate')" />
                            <span class="form-check-label">Migrate Database</span>
                        </label>
                    </div>
                                    
                    <div id="main_body" class="form-group"></div>
                </form>
                <div class="progress mb-3"><div id="progress_bar" class="progress-bar bg-warning" role="progressbar" style="width:0%"></div></div>
                <button type="button" class="btn btn-primary mr-3" data-toggle="modal" data-target="#centeredModalDanger" OnClick="checkData()">Submit</button>
                <button type="button" class="btn btn-secondary ml-3" onClick ="location.href=('./tools.php')">Cancel</button>
            </div>
        </div>
    </div>
    
EOBLOCK;

}

$pageContents = <<<EOPAGE
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
                $table_body
                </div>
			</div>	
		</div>
	</main>
EOPAGE;
// print (php_uname('n'));
$network_tag = "";
if (strtoupper(php_uname('n')) == "COSILAN") {
    $network_tag = '
    <li id="network" class="sidebar-item '.$active['network'].'">
    <a class="sidebar-link" href="/inc/system.php?fr=network"><i class="align-middle mr-2 fas fa-fw fa-sitemap"></i><span class="align-middle">'.$msg['network'].'</span></a>
    </li>';
}


$active[$_GET['fr']] = "active";
$pageSide = <<<EOPAGE
	<nav class="sidebar sidebar-sticky">
		<div class="sidebar-content js-simplebar">
            <a class="sidebar-brand" href="/">$_TITLE_LOGO<span class="align-middle ml-2">$_DOCUMENT_TITLE</span></a>
			<ul class="sidebar-nav">
				<li id="account" class="sidebar-item $active[basic]">
					<a class="sidebar-link" href="/inc/system.php?fr=basic"><i class="align-middle mr-2 fas fa-fw fa-id-card"></i><span class="align-middle">$msg[basic]</span></a>
				</li>
				<li id="service" class="sidebar-item $active[service]">
					<a class="sidebar-link" href="/inc/system.php?fr=service"><i class="align-middle mr-2 fas fa-fw fa-sitemap"></i><span class="align-middle">$msg[service]</span></a>
				</li>				
				<li id="database" class="sidebar-item $active[database]">
					<a class="sidebar-link" href="/inc/system.php?fr=database"><i class="align-middle mr-2 fas fa-fw fa-database"></i><span class="align-middle">$msg[database]</span></a>
				</li>				
				<li id="license" class="sidebar-item $active[license]">
					<a class="sidebar-link" href="/inc/system.php?fr=license"><i class="align-middle mr-2 fas fa-fw fa-sitemap"></i><span class="align-middle">$msg[license]</span></a>
				</li>
                $network_tag	
				<li id="tools" class="sidebar-item $active[tools]">
					<a class="sidebar-link" href="/inc/system.php?fr=tools"><i class="align-middle mr-2 fas fa-fw fa-sitemap"></i><span class="align-middle">$msg[tools]</span></a>
				</li>	
			
            </ul>
		</div>
	</nav>
EOPAGE;


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
echo '<!DOCTYPE html>'."\r\n".'<html lang="en">';
print $pageHead;
print $pageBody;
echo "\n\r";
echo '<script type="text/javascript" src="/js/app.js"></script>'."\r\n";
echo '<script type="text/javascript" src="/js/custom.js"></script>'."\r\n";
// echo '<script type="text/javascript" src="/js/admin.js"></script>'."\r\n";
echo '</html>';
?>

