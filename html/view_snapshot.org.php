<?php
// print (floor(microtime(true)));
// print "<br>";
print (time());
$connect = @mysqli_connect("localhost", "work_user", "13579", "WORK");
if(!$connect) {
    echo "DB  Select Error Custom";
    exit;
}
$table = "event_post";

if (isset($_GET['pk'])) {

}

if(isset($_GET['dt'])){
    $_GET['ts'] = strtotime($_GET['dt']);
}

if (!isset($_GET['ts'])) {
    $_GET['ts']= time() + 3600*9;
}

$sq = "select pk, timestamp from ".$table." where timestamp <= ".$_GET['ts']." order by timestamp desc limit 2";
$rs = mysqli_query($connect, $sq);
for($i=0; $i<$rs->num_rows; $i++){
    $row = mysqli_fetch_row($rs);
    if ($i==0) {
        $current_pk = $row[0];
        // $current_ts = $row[1];
    }
    else {
        $prev_pk = $row[0];
        $btn_left = '<a href="view_snapshot.php?ts='.$row[1].'"><<<a>';
    }
}
$sq = "select pk, timestamp from ".$table." where timestamp > ".$_GET['ts']." order by timestamp asc limit 1";
$rs = mysqli_query($connect, $sq);
if ($rs->num_rows){
    $row = mysqli_fetch_row($rs);
    $next_pk = $row[0];
    $btn_right = '<a href="view_snapshot.php?ts='.$row[1].'">>><a>';
}

// $sq = "select * from ".$table." where timestamp < ".$_GET['ts']." order by timestamp desc limit ".$limit.", ".$_GET['offset']."";
// $sq = "select * from ".$table." where timestamp <= ".$_GET['ts']." order by timestamp desc limit 2";
// $sq = "select regdate, snapshot from ".$table." where timestamp=".$current_ts; // 속도 늦음
$sq = "select regdate, snapshot from ".$table." where pk=".$current_pk;
// print $sq;

$rs = mysqli_query($connect, $sq);
$assoc = mysqli_fetch_assoc($rs);
$table_body = '
    <tr><td align="center"><h1>'.$btn_left.'&nbsp;&nbsp;'.$assoc['regdate'].'&nbsp;&nbsp;'.$btn_right.'</h1></td><td><a href="view_snapshot.php">NOW</td></tr>
    <tr><td colspan="2"><img src="data:image/jpg;base64,'.$assoc['snapshot'].'"  /></td></tr>';


$table_body = '<table>'.$table_body.'</table>';

print $table_body;
?>