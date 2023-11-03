<?PHP
for ($i=0; $i<3; $i++) {
	chdir("../");
	if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
}

// $LOG_SOURCE = "SQLITE"; #(SQLITE, FILE)
$LOG_SOURCE = "FILE"; #(SQLITE, FILE)

function dirToArray($dir) { 
    $result = array(); 
    $cdir = scandir($dir); 
    foreach ($cdir as $key => $value) { 
       if (!in_array($value,array(".",".."))) { 
          if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) { 
             $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value); 
          } 
          else { 
             $result[] = $value; 
          } 
       } 
    } 
     return $result; 
 }


function logfile2Array($fname){
    // "%(levelname)-8s  %(asctime)s %(module)s %(funcName)s %(lineno)s %(message)s %(threadName)s"
    $arr = array();
    // $f = fopen($fname,"r");
    // $body =  fread($f, filesize($fname));
    // fclose($f);
    $body = file_get_contents($fname);
    $lines  =  explode("\n", $body);
    $size_lines = sizeof($lines);
    for($i=0; $i<$size_lines; $i++){
        $line = trim($lines[$i]);
        if (!$line){
            continue;
        }
        $arr[$i]['no'] = $i;
        $arr[$i]['level'] = trim(strtolower(substr($line, 0, 8)));
        $arr[$i]['date'] = trim(substr($line, 10, 19));
        $line = trim(substr($line, 34, strlen($line)));
        
        $tabs =  explode(" ", $line);
        $arr[$i]['module'] = trim(array_shift($tabs));
        $arr[$i]['function'] =  trim(array_shift($tabs));
        $arr[$i]['line'] = trim(array_shift($tabs));
        $arr[$i]['thread'] = trim(array_pop($tabs));
        $arr[$i]['message'] = trim(implode(" ", $tabs));
    }
    return $arr;
}


function view_log($fname) {
    // print $level." ". $start;
    $level_class = array("info"=>"", "warning"=>"#C0AD02", "error"=>"#C00000", "critical"=>"#FF0000");
    $arr_rs = logfile2Array($fname);
    
    $start  = 0;
    $offset = sizeof($arr_rs);
    if(isset($_GET['start']) && isset($_GET['end'])) {
        $start  = $_GET['start'];
        $offset = $_GET['end'] - $_GET['start'] + 1;
    }
    else if (isset($_GET['start'])) {
        $offset = $_GET['start'];
    }
    else if (isset($_GET['end'])) {
        $start  = sizeof($arr_rs) - $_GET['end'];
    }
    if  ($start <0) {
        $start = 0;
    }

    $arr_rs = array_slice($arr_rs, $start, $offset);
    $table_body= '';
    for ($i=0; $i<sizeof($arr_rs); $i++){
        if (isset($_GET['level']) && ($arr_rs[$i]['level'] != $_GET['level'])) {
            continue;
        }
        if (isset($_GET['module']) && ($arr_rs[$i]['module'] != $_GET['module'])) {
            continue;
        }
        if (isset($_GET['function']) && ($arr_rs[$i]['function'] != $_GET['function'])) {
            continue;
        }

        $table_body .= '<tr style="color:'.$level_class[trim($arr_rs[$i]['level'])].'">
            <td>'.$arr_rs[$i]['no'].'</td>
            <td>'.trim($arr_rs[$i]['level']).'</td>
            <td>'.trim($arr_rs[$i]['date']).'</td>
            <td>'.trim($arr_rs[$i]['module']).'</td>
            <td>'.trim($arr_rs[$i]['function']).'</td>
            <td align="right">'.trim($arr_rs[$i]['line']).'</td>
            <td>'.trim($arr_rs[$i]['message']).'</td>
            <td>'.trim($arr_rs[$i]['thread']).'</td>
        </tr>';
    }

    $table_body = '<table>
        <thead>
        <tr>
        <th>No</th>
        <th>Level</th>
        <th>Date</th>
        <th>Module</th>
        <th>Function</th>
        <th>Line</th>
        <th>Message</th>
        <th>Thread</th>
        </tr></thead>
        <tbody>'.$table_body.'</tbody></table>';

    return $table_body;
}

// $level_class = array("INFO"=>"", "WARNING"=>"#C0AD02", "ERROR"=>"#C00000", "CRITICAL"=>"#FF0000");



// function view_log2($fname, $level='', $start=0, $end=0) {
//     // print $level." ". $start;
//     global $level_class;
//     $f = fopen($fname,"r");
//     $body =  fread($f, filesize($fname));
//     fclose($f);

//     $lines  =  explode("\n", $body);
//     $size_lines = sizeof($lines);
//     if ($end) {
//         $start = $size_lines - $end;
//     }
//     $table_body = "";
//     for ($i=$start; $i<$size_lines; $i++) {
       
//         $line = trim($lines[$i]);
//         $line = str_replace('<module>', 'module', $line);
//         $j=0;
//         $ex = explode(" ", $line);
//         $log_level = $ex[$j++];

//         if ($level  && (strtoupper($level) != strtoupper($log_level))) {
//                continue;
//         }

//         for ($date_str = '';$j<10; $j++){
//             if ($ex[$j]){
//                 $date_str = $ex[$j].' '.$ex[$j+1];
//                 $j+=2;
//                 break;
//             }
//         }
//         $module = $ex[$j++];
//         $f_name = $ex[$j++];
//         $line_no = $ex[$j++];
//         for ($message = ""; $j<(sizeof($ex) -1); $j++){
//             $message .= $ex[$j]." ";
//         }          
//         $thread = $ex[$j];
//         $table_body .= '<tr style="color:'.$level_class[trim($log_level)].'"><td>'.$i.'</td>
//             <td>'.trim($log_level).'</td>
//             <td>'.trim($date_str).'</td>
//             <td>'.trim($module).'</td>
//             <td>'.trim($f_name).'</td>
//             <td align="right">'.trim($line_no).'</td>
//             <td>'.trim($message).'</td>
//             <td>'.trim($thread).'</td>
//         </tr>';
//     }

//     $table_body = '<table>
//         <thead>
//         <tr>
//         <th>No</th>
//         <th>Level</th>
//         <th>Date</th>
//         <th>Module</th>
//         <th>Function</th>
//         <th>Line</th>
//         <th>Message</th>
//         <th>Thread</th>
//         </tr></thead>
//         <tbody>'.$table_body.'</tbody></table>';

//     return $table_body;
// }


function view_log_from_db($fname, $ref_date = '', $level='', $start=0, $end=0){
    global $level_class;
    if (!file_exists($fname)) {
        print "NO File";
        return False;
    }
    $db = new SQLite3($fname);
    // $version = $db->querySingle('SELECT SQLITE_VERSION()');
    // echo $version . "\n";

    if (!$ref_date) {
        $ref_date = date('Y-m-d');
    }
    $sq = "select * from log where Created like '".$ref_date."%' ";
    if ($level) {
        $sq .= " and logLevelName = '".strtoupper($level)."' ";
    }
    // print $sq;
    $rs = $db->query($sq);
    // print_r($rs);
    $table_body = '';
    $table_body .= "\n";
    $n= 1;
    while ($row = $rs->fetchArray()) {
        $row['FuncName'] = str_replace('<module>','module', $row['FuncName']);
        $table_body .= '<tr style="color:'.$level_class[trim($row['LogLevelName'])].'";>
            <td>'.$n.'</td>
            <td>'.$row['Created'].'</td>
            <td>'.$row['Name'].'</td>
            <td>'.$row['LogLevel'].'</td>
            <td>'.$row['LogLevelName'].'</td>
            <td>'.$row['Module'].'</td>
            <td>'.$row['FuncName'].'</td>
            <td>'.$row['LineNo'].'</td>
            <td>'.$row['Message'].'</td>
            <td>'.$row['Process'].'</td>
            <td>'.$row['Thread'].'</td>
            <td>'.$row['ThreadName'].'</td>
        </tr>';
        $n += 1;

    }
    $table_body = '<table>
        <thead>
        <tr>
        <th>No</th>
        <th>Date</th>
        <th>Name</th>
        <th>logLevel</th>
        <th>LogLevelName</th>
        <th>Module</th>
        <th>FunctionName</th>
        <th>LineNo.</th>
        <th>Message</th>
        <th>Process</th>
        <th>Thread</th>
        <th>ThreadName</th>
        </tr></thead>
        <tbody>'.$table_body.'</tbody></table>';
    return $table_body;
}

function list_log($fname){
    if (!file_exists($fname)) {
        print "NO File";
        return False;
    }
    $db = new SQLite3($fname);
    
    for ($i=0, $table_body=''; $i<3; $i++){
        $ref_date = date('Y-m-d', time()-3600*24*$i);
        $table_body .= '<td><a href="./log.php?ref_date='.$ref_date.'">'.$ref_date.'</a></td>';
    }
    // print ($ref_date);
    $sq = "delete from log where Created < '".$ref_date." 00:00:00' ";
    $rs = $db->exec($sq) or die(print_r($db->lastErrorMsg(), true));
    $sq = "VACUUM";
    $rs = $db->exec($sq) or die(print_r($db->lastErrorMsg(), true));
    $table_body = '<tr>'.$table_body.'</tr>';
    $table_body = '<table><tbody>'.$table_body.'</tbody></table>';
    return $table_body;

}

if($LOG_SOURCE == 'SQLITE'){
    $fname =  $_GET['fname'];
    if (!$fname) {
        $fname = "log.db";
    }

    $fname = $ROOT_DIR."/bin/log/".$fname;
    $HTML_BODY = view_log_from_db($fname, $_GET['ref_date'], $_GET['level'], $_GET['start'] );
}

else if($LOG_SOURCE == 'FILE'){
    $browse_dir = "";
    $fnames = dirToArray($ROOT_DIR."/bin/log");
    // print_r ($fnames);
    for($i=0; $i<sizeof($fnames); $i++) {
        if ($fnames[$i] == 'log.db'){
            continue;
        }
        $browse_dir .= '<td><a href="./log.php?fname='.$fnames[$i].'">'.$fnames[$i].'</a></td>';
    }
    $browse_dir =  '<table class="table table-striped table-sm table-bordered table-hover"><tbody><tr>'.$browse_dir.'</tr></tbody></table>';

    if(!isset($_GET['fname'])) {
        $_GET['fname'] = "bi.log";
    }
    $fname = $ROOT_DIR."/bin/log/".$_GET['fname'];
    $HTML_BODY = view_log($fname);
    $list_log = '';
}

?>
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
            /* pre {margin: 0; font-family: monospace;}
            a:link {color: #009; text-decoration: none; background-color: #fff;}
            a:hover {text-decoration: underline;} */
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
        </style>
	</head>
<script>
function vacuum() {
    console.log("vacuum");
}
</script>    
	<body id="body">
        <main class="content" id="pageContents">
            <div class = "row">
            <?=$browse_dir?>
            </div>
            <div class="row">
                <div><?=$list_log?></div>
                <?=$HTML_BODY?>
            </div>
        </main>
    </body>
</html>




