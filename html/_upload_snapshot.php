<?PHP
$table = "event_post";

/*
CREATE TABLE IF NOT EXISTS EVENT_POST(
	pk int(11) unsigned not null auto_increment,
	IP_addr varchar(15),
	regdate datetime,
    timestamp double, 
    getstr mediumtext,
	eventinfo mediumtext,
	snapshot mediumblob,
	flag enum('y','n') default 'n',
	primary key(pk)
);
CREATE USER IF NOT EXISTS 'work_user'@'localhost' IDENTIFIED BY '13579';
GRANT ALL PRIVILEGES ON WORK.* TO 'work_user'@'localhost'
FLUSH PRIVILEGES
*/

$connect = @mysqli_connect("localhost", "work_user", "13579", "WORK");
if(!$connect) {
    echo "DB  Select Error Custom";
    exit;
}

$IP_addr = $_SERVER['REMOTE_ADDR'];
$timestamp = microtime(true) + 3600*9;
$regdate= date("Y-m-d H:i:s", floor($timestamp));

$get_str =  urldecode($_SERVER['QUERY_STRING']);
$event_info = $_POST['eventinfo'];

if ($_FILES) {
    $fname = $_FILES['snapshot']['tmp_name'];	
    $fp = fopen($fname,'r');
    $snapshot =  fread($fp, filesize($fname));
    fclose($fp);
    // $snapshot =  addslashes(base64_encode($snapshot));
    $snapshot = addslashes("data:image/jpg;base64,".base64_encode($snapshot));
}
else {
    $snapshot = "";
}

$sq = "select pk from ".$table." where timestamp < ".($timestamp-604800)." order by timestamp asc limit 1";
$rs = mysqli_query($connect, $sq);
if ($rs->num_rows){
    $pk = mysqli_fetch_row($rs)[0];
    $sq = "update ".$table." set IP_addr='".$IP_addr."', regdate='".$regdate."', timestamp=".$timestamp.", getstr='".$get_str."', eventinfo='".$event_info."', snapshot='".$snapshot."' where pk=".$pk;
}
else {
    $sq = "insert into ".$table."(IP_addr, regdate, timestamp, getstr, eventinfo, snapshot)  values('".$IP_addr."','".$regdate."', ".$timestamp.", '".$get_str."', '".$event_info."', '".$snapshot."')";
}
print $sq;
$rs = mysqli_query($connect, $sq);

if($rs) {
	print "OK";
}
else {
	print "FAIL";
}


?>