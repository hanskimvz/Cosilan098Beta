<?PHP
// wget http://49.235.119.5/download.php?file=../html/inc/param.php -O /var/www/html/inc/param.php
// print "cookie: "; print_r($_COOKIE);
if ($_COOKIE['role'] !='admin') {
    print "not permitted \n";
    exit();
}

if(!isset($_GET['table'])  or !$_GET['table']) {
    $_GET['table'] = 'param_tbl';
}


for ($i=0; $i<3; $i++) {
	chdir("../");
    if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
}

$fname = $ROOT_DIR."/bin/param.db";
$db = new SQLite3($fname);
$grps = array();
$eNames = array();
$eValues = array();

$eGroups = array();
$arr_sq = array();

$arr_querys = explode("&", $_SERVER['QUERY_STRING']);
//action=update&group=software.mysql&table.

for ($i=0, $e=0; $i<sizeof($arr_querys); $i++){
    list($A, $B) = explode("=", $arr_querys[$i]);
    $A = trim(strtolower($A));
    $B = trim($B);
    if ($A == 'action'){
        $action = strtolower($B);
    }
    else if($A =='group' ){
        $exp_grp = explode(".", strtolower($B)) ;
        for($j=0; $j<sizeof($exp_grp); $j++){
            $grps[$j] = $exp_grp[$j];
        }
    }
    else {
        $exp_name = explode(".", $A) ;
        for($j=0; $j<sizeof($exp_name); $j++){
            $eNames[$e][$j] = $exp_name[$j];
        }
        $eValues[$e] = str_replace("'", "&#039;", $B);
        $e++;
    }
}
if (!$eNames){
    $eNames= array(array(''));
}
for ($i=0; $i<sizeof($eNames); $i++){
    $eGroups[$i] = array('','','','','','');
    $eGroups[$i] = array_merge($grps, $eNames[$i]);
}



if(!$action) {
    $action="list";
}

// print "list: ".$action."\n";
// print "grps: "; print_r($grps);
// print "eGroups: "; print_r($eGroups);
// print "eValues: "; print_r($eValues);
// print ("=============================================================================================================================\n");

if ($action == 'list'){
    for ($i=0; $i<sizeof($eGroups); $i++){
        $upd_sq = "";
        $sel_sq = "";
        $sq = "";
        for($j=0; $j<6; $j++){
            if (!$eGroups[$i][$j]) {
                continue;
            }
            if ($sq) {
                $sq .= " and ";
            }
            $sq .= "group".($j+1)."='".$eGroups[$i][$j]."'";
        }
        if ($sq) {
            $sq = "where ".$sq;
        }
        $sel_sq = "select groupPath, entryName, entryValue from ".$_GET['table']." ".$sq." order by groupPath asc";
        // print $sel_sq."\n";
        array_push($arr_sq, $sel_sq);
    }
    $arr_rs = array();
    for ($i=0; $i<sizeof($arr_sq); $i++){
        $rs = $db->query($arr_sq[$i]);
        while ($row = $rs->fetchArray()) {
            array_push($arr_rs, ($row['groupPath'].".".$row['entryName']."=".$row['entryValue']));
        }
    }
    $db->close();
    header("Content-Type: text/plain");
    foreach($arr_rs as $A => $B) {
        print $B;
        print "\n";
    }     
}
else if ($action == 'update'){
    for ($i=0; $i<sizeof($eGroups); $i++){
        $upd_sq = "";
        $sel_sq = "";
        $sq = "";
        for($j=0; $j<6; $j++){
            if ($sq) {
                $sq .= " and ";
            }
            $sq .= "group".($j+1)."='".$eGroups[$i][$j]."'";
        }
        $sq = "where ".$sq;
        if (!$eValues[$i]) {
            print "value Error\n";
            continue;
        }
        $upd_sq = "update ".$_GET['table']." set entryValue='".$eValues[$i]."' ".$sq;
        $sel_sq = "select datatype, option, readonly from ".$_GET['table']." ".$sq;
        // print $sel_sq;
        $rs = $db->query($sel_sq);
        while ($rows = $rs->fetchArray()) {
            // print_r($rows);
            if($rows['readonly']) {
                print "this parameter is read only\n";
                continue;
            }
            if($rows['datatype']=='int' || $rows['datatype']=='port') {
                if (!is_numeric($eValues[$i])) {
                    print "Values Error, Integer only \n";
                    continue;
                }
            }
            else if ($rows['datatype']=='yesno' || $rows['datatype']=='select') {
                if (!strpos(" ".$rows['option'], $eValues[$i])){
                    print "Values Error: value should be in '".$rows['option']."' \n";
                    continue;
                }
            }
            array_push($arr_sq, $upd_sq);
        }
    }
    // print_r($arr_sq);
    header("Content-Type: text/plain");
    for ($i=0; $i<sizeof($arr_sq); $i++){
        $rs = $db->query($arr_sq[$i]);
        if($rs){
            print "update OK\n";
        }
        else {
            print "update FAIL\n";
        }
    }
    $db->close();
}
else if ($action == 'add'){
    for ($i=0; $i<sizeof($eGroups); $i++) {
        if($eGroups[$i][0] == 'grouppath'){
            $exp_grp = explode(".",$eValues[$i]);
            $entryName = array_pop($exp_grp);
            $groupPath ="";
            for($j=0; $j<sizeof($exp_grp); $j++){
                if ($groupPath) {
                    $groupPath .= ".";
                }
                $groupPath .= $exp_grp[$j];
                $grp[$j] = $exp_grp[$j];
            }
        }
        else {
            ${$eGroups[$i][0]} = $eValues[$i];
        }
    }
    if($datatype=='int' || $datatype=='port') {
        if (!is_numeric($value)) {
            print "Values Error, Integer only \n";
            exit();
        }
    }
    else if ($datatype=='yesno' || $datatype=='select') {
        if (!strpos(" ".$option, $value)){
            print "Values Error: value should be in '".$option."' \n";
            exit();
        }
    }

    $sq = "insert into ".$_GET['table']."
        (groupPath, entryName, entryValue, group1, group2, group3, group4, group5, group6, made, regdate, description, datatype, option, create_permission, delete_permission, update_permission, read_permission,  readonly, writeonly) 
        values('".$groupPath."','".$entryName."', '".$value."', '".$grp[0]."', '".$grp[1]."', '".$grp[2]."', '".$grp[3]."', '".$grp[4]."', '".$grp[5]."', '".$made."', '".time()."', '".$description."', '".$datatype."', '".$option."', '".$create_permission."', '".$delete_permission."', '".$update_permission."', '".$read_permission."', '".$readonly."', '".$writeonly."') ";

    // print $sq;
    $rs = $db->query($sq);
    if($rs){
        print "add OK\n";
    }
    else {
        print "add FAIL\n";
    }    
}


else if ($action == 'modify'){
}
else if ($action == 'delete'){
}
else if ($action == 'query'){
    
}

$db->close();

exit();



?>
