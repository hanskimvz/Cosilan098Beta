<?php
session_start();
date_default_timezone_set ( "UTC" );
$TZ_OFFSET = 3600*8;
define (TZ_OFFSET, 3600*8);
//CONNECT DB
include  $_SERVER['DOCUMENT_ROOT'].'/libs/dbconnect.php';



if (!isset($_POST['mode'])) {
    if (!isset($_POST['date_from'])) {
        $_POST['date_from'] = date("Y-m-d", time()+TZ_OFFSET);
    }
    if (!isset($_POST['time_from'])) {
        $_POST['time_from'] = "00:00";
    }
    if (!isset($_POST['date_to'])) {
        $_POST['date_to'] = date("Y-m-d", time()+TZ_OFFSET);
    }    
    if (!isset($_POST['time_to'])) {
        $_POST['time_to'] = date("24:00");
    }    
    $sq = "select device_info from ".$DB_COMMON['param']." where db_name='".$_SESSION['db_name']."' ";
    $sq = "select A.device_info from ".$DB_CUSTOM['camera']." as A inner join ".$DB_CUSTOM['counter_label']." as B on A.code = B.camera_code group by A.code";
    // print $sq;
    $rs = mysqli_query($connect0, $sq);
    $option_device_info = "";

    while($assoc = mysqli_fetch_assoc($rs)){
        $option_device_info .= '<option value="'.$assoc['device_info'].'">'.$assoc['device_info'].'</option>';
    }

    $control_panel = '<table>
        <tr>
            <th>Device Info</th>
            <td><select id="device_info" onChange="blank_table()">'.$option_device_info.'</select></td>
            <th>Start Datetime</th>
            <td><input type="text" id="date_from" value="'.$_POST['date_from'].'" size="11"/>
                <input type="text" id="time_from" value="'.$_POST['time_from'].'" size="5"/></td>
            <th>End datetime</th>
            <td><input type="text" id="date_to" value="'.$_POST['date_to'].'" size="11"/>
                <input type="text" id="time_to" value="'.$_POST['time_to'].'" size="5"/></td>

            <td><input type="button" value="QUERY" onClick="query()"/></td>
            <td><input type="button" value="SUBMIT" onClick="change_numbers_in_db()" /></td>
        </tr>
    </table>';
}

else if ($_POST['mode'] == 'list') {
    // print_r($_POST);
    $arr_counter_labels = array();
    $arr_rs = array();
    $ts_from = strtotime($_POST['date_from']);
    $ts_to = strtotime($_POST['date_to']);

    // $device_info = "mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model'];
    $sq = "select counter_label from ".$DB_CUSTOM['counter_label']." where camera_code is not null or camera_code !=''";
    $rs = mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        if (!in_array($assoc['counter_label'],  $arr_counter_labels)) {
            array_push($arr_counter_labels, $assoc['counter_label']);
        }
    }
    $sq = "select * from ".$DB_CUSTOM['count']." where timestamp >= ".$ts_from." and timestamp < ".$ts_to." and device_info='".$_POST['device_info']."' order by timestamp asc";
    $rs = mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        $datetime = sprintf("%04d-%02d-%02d %02d:%02d", $assoc['year'], $assoc['month'], $assoc['day'], $assoc['hour'], $assoc['min']);
        $arr_t[$datetime][$assoc['counter_label']] = $assoc['counter_val'];
        $arr_t[$datetime]['timestamp'] = $assoc['timestamp'];
        if (!in_array($assoc['counter_label'],  $arr_counter_labels)) {
            array_push($arr_counter_labels, $assoc['counter_label']);
        }
        $sum[$assoc['counter_label']] += $assoc['counter_val'];
    }

    $table_body = '
        <input type="hidden" id="ts_from" value="'.$ts_from.'" />
        <input type="hidden" id="ts_to" value="'.$ts_to.'" />
        <input type="hidden" id="ct_label_list" value="'.(join(",", $arr_counter_labels )).'" />
    ';
    $table_body .= '<tr><td>datetime</td><td>timestamp</td>';
    foreach($arr_counter_labels as $ct_label) {
        $table_body .= '<td>'.strtoupper($ct_label).'
            <input type="button" id="plus_one['.$ct_label.']" name= "'.$ct_label.'" value="+1" onClick="cal(this)">
            <input type="button" id="plus_ten['.$ct_label.']" name= "'.$ct_label.'" value="+10" onClick="cal(this)">
            <input type="button" id="multiply_two['.$ct_label.']" name= "'.$ct_label.'" value="x2" onClick="cal(this)">
        </td>';
    }
    $table_body .= '</tr>';
    $table_body .= '<tr><td colspan="2">'.(date("Y-m-d H:i:s", $ts_from)).' ~ '.(date("Y-m-d H:i:s", $ts_to)).'</td>';
    foreach($arr_counter_labels as $ct_label) {
        $table_body .='<td><input type="text" id="sum['.$ct_label.']" class="number"  value="'.$sum[$ct_label].'" size="5" readonly></td>';
    }
    $table_body .= '</tr>';

    for ($t = $ts_from; $t < $ts_to; $t+=600) {
        if ($t > time()+ TZ_OFFSET) {
            break;
        }
        $datetime = date("Y-m-d H:i", $t);
        if (isset($arr_t[$datetime])) {
            $arr_rs[$datetime] = $arr_t[$datetime];
            
        }   
        else {
            $arr_rs[$datetime]["timestamp"] =$t;
        }
        $table_body .= '<tr>
            <td>'.$datetime.'</td>
            <td>'.$t.'</td>';
            foreach($arr_counter_labels as $ct_label) {
                $gag = "";
                if (!isset( $arr_rs[$datetime][$ct_label]) || ! $arr_rs[$datetime][$ct_label]) {
                    $arr_rs[$datetime][$ct_label] = 0;
                    $gag = '<font color="#FF0000">[empty]</font>';
                }
                $table_body .= '<td>
                    <input type="text" class="number" size="5" 
                    id="'.$ct_label.'['.$t.']" name= "'.$ct_label.'" value="'. $arr_rs[$datetime][$ct_label].'" onChange="cal(this)">'.$gag.'
                </td>';
            }
        $table_body .= '</tr>';

    }
    $table_body = '<table>'.$table_body.'</table>';
    print $table_body;
    exit();
}
else if ($_POST['mode'] == 'modify'){
    $arr_sq = array();
    $arr_rs = array();

    // print_r($_POST['data']);
    $sq = "select * from ".$DB_CUSTOM['count']." where timestamp >= ".$_POST['ts_from']." and timestamp < ".$_POST['ts_to']." and device_info='".$_POST['device_info']."' order by timestamp asc";
    // print $sq;
    $rs = mysqli_query($connect0, $sq);
    while($assoc = mysqli_fetch_assoc($rs)){
        array_push($arr_rs, $assoc);
    }

    $sq = "select A.code as camera_code, B.code as store_code, C.code as square_code from ".$DB_CUSTOM['camera']." as A inner join ".$DB_CUSTOM['store']." as B inner join ".$DB_CUSTOM['square']." as C on A.store_code = B.code and B.square_code = C.code where A.device_info = '".$_POST['device_info']."' ";
    // print $sq;
    $rs = mysqli_query($connect0, $sq);
    $dev = mysqli_fetch_assoc($rs);
    foreach ($_POST['data'] as $i => $arr){
        $sq = "";
        foreach ($arr_rs as $j => $assoc) {
            if ($assoc['timestamp'] == $arr['timestamp'] && $assoc['counter_label']==$arr['label'] ) {
                $sq = "update ".$DB_CUSTOM['count']." set counter_val = ".$arr['value']." where pk=".$assoc['pk'];
                break;
            }
        }
        if (!$sq) {
            if (!$arr['value']) {
                continue;
            }
            $dt = date("Y/n/j/G/i/w/W", $arr['timestamp']);

            list($year, $month, $day, $hour, $min, $way, $week) =  explode("/", $dt);
            $sq = "insert into ".$DB_CUSTOM['count']."(device_info, timestamp, year, month, day, hour, min, wday, week, counter_label, counter_val, counter_name, camera_code, store_code, square_code) values('".$_POST['device_info']."', ".$arr['timestamp'].", ".$year.", ".$month.", ".$day.", ".$hour.", ".((int)$min).", ".$way.", ".$week.", '".$arr['label']."', ".$arr['value'].", 'dummy', '".$dev['camera_code']."', '".$dev['store_code']."', '".$dev['square_code']."' )";
            // unset($_POST['data'][$i]);
        }
        array_push($arr_sq, $sq);
    }
    $err = false;
    foreach($arr_sq as $sq) {
        $rs = mysqli_query($connect0, $sq);
        if (!$rs) {
            print $sq." Fail</ br>\n";
            $err = true;
        }
    }
    if (!$err) {
        print "DONE";
    }
    // print_r($arr_sq);
    exit();
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
            pre {margin: 0; font-family: monospace;}
            a:link {color: #009; text-decoration: none; background-color: #fff;}
            a:hover {text-decoration: underline;}
            table {border-collapse: collapse; border: 0; width: 100%; box-shadow: 1px 2px 3px #eee;}
            .number {text-align: right;padding-right:2px;}
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
 
	<body id="body">
        <main class="content" id="pageContents">
            <div class = "row">
                <?=$control_panel?>
            </div>
            <div class="row" id = "result">
            </div>
            <div class="row" id = "table_body">
            </div>
        </main>
    </body>
</html>
<script type="text/javascript" src="../js/jquery.min.js"></script>

    
<script>
function blank_table(){
    document.getElementById("result").innerHTML = "";
    document.getElementById("table_body").innerHTML= "";
}

function cal(t){
    // console.log(t,t.id, t.name, t.value);
    ts_from = document.getElementById("ts_from").value *1;
    ts_to = document.getElementById("ts_to").value *1;
    // console.log(ts_from, ts_to);
    total = 0;
    for (ts = ts_from; ts < ts_to; ts += 600) {
        num = document.getElementById(t.name + "[" + ts + "]");
        if (!num){
            continue;
        }
        if (t.id == "plus_one[" + t.name + "]") {
            num.value = (num.value)*1+1;
        }
        else if (t.id == "plus_ten[" + t.name + "]") {
            num.value = (num.value)*1+10;
        }
        else if (t.id == "multiply_two[" + t.name + "]") {
            num.value = (num.value)*2;
        }
        total += num.value*1;
    }
    // console.log(total)
    document.getElementById("sum[" + t.name + "]").value = total;
}


function query() {
    let dateReg = /^\d{4}([./-])\d{2}\1\d{2}$/;
    let timeReg = /^\d{2}([:])\d{2}$/;
    let date_from = document.getElementById("date_from").value.trim();
    let time_from = document.getElementById("time_from").value.trim();
    let date_to = document.getElementById("date_to").value.trim();
    let time_to = document.getElementById("time_to").value.trim();
    
    document.getElementById("result").innerHTML = "";
    document.getElementById("table_body").innerHTML= "";
    let error = "";

    if (!date_from.match(dateReg)) {
        document.getElementById("date_from").focus();
        error = "date_from format invaild";
    } 
    else if (!date_to.match(dateReg)) {
        document.getElementById("date_to").focus();
        error = "date_to format invaild";
    } 
    else if (!time_from.match(timeReg)) {
        document.getElementById("time_from").focus();
        error = "time_from format invaild";
    } 
    else if (!time_to.match(timeReg)) {
        document.getElementById("time_to").focus();
        error = "time_to format invaild";
    }

    if (error) {
        document.getElementById("result").innerHTML = '<font color="#FF0000">' + error + '</font>';
        return false;
    }
    date_from = date_from + " " + time_from;
    date_to = date_to + " " + time_to;
    console.log("no error");
    url ="";
    var posting = $.post(url,{
        mode: "list",
        date_from: date_from,
        date_to: date_to,
        device_info: document.getElementById("device_info").value,
    });
    posting.done(function(data) {
        // console.log(data);
        document.getElementById("table_body").innerHTML= data;
    });	


    // form.submit();
    // cal(0);
}

function change_numbers_in_db() {
    ts_from = document.getElementById("ts_from").value *1;
    ts_to = document.getElementById("ts_to").value *1;
    ct_labels = document.getElementById("ct_label_list").value.split(",");
    console.log(ct_labels);
    arr = new Array;
    for (ts = ts_from; ts<ts_to; ts+=600) {
        ct_labels.forEach(function(item){
            id = document.getElementById(item+"["+ ts +"]");
            if (id) {
                if (isNaN(id.value)) {
                    id.focus();
                    alert ("Value Must be Integer");
                    return false;
                }
                arr.push({
                    "label": item,
                    "timestamp": ts,
                    "value": id.value 
                });
            }
        });
    }
    // console.log(arr);
    if (!confirm("database will be changed. it may have critical problem")) {
        return false;
    }
    url = "";
    var posting = $.post(url,{
        mode: "modify",
        ts_from: ts_from,
        ts_to: ts_to,
        device_info: document.getElementById("device_info").value,
        data: arr,
    });
    posting.done(function(data) {
        console.log(data);
        document.getElementById("result").innerHTML =  data ;
    });	
}



</script>