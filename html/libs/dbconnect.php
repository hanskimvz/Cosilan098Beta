<?PHP
// echo php_uname();
// echo PHP_OS; // Linux , WINNT
// session_start();
for ($i=0; $i<3; $i++) {
	chdir("../");
	if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
}

$fname = $ROOT_DIR."/bin/param.db";

// print(get_current_user());

// Centos 8.x need to readwrite : selinux
// chcon  -t httpd_sys_rw_content_t /home/www/bin/param.db
// chcon  -t httpd_sys_rw_content_t /home/www/bin/
// chown user..

 $db = new SQLite3($fname);

$sq = "select groupPath, entryName, entryValue from param_tbl ";

 $rs = $db->query($sq);
//  print_r($db->lastErrorMsg());

$configVars = array();
while ($row = $rs->fetchArray()) {
	$configVars[$row['groupPath'].".".$row['entryName']] = $row['entryValue'];
}

 $db->close();

$MYSQL_PORT = $configVars['software.mysql.port'];
if (!$MYSQL_PORT) {
	$MYSQL_PORT = 3306;
}
// print_r($configVars);
// mysqli_connect( host, username, password, databasename, port, socket );
$connect0 = @mysqli_connect($configVars['software.mysql.host'], $configVars['software.mysql.user'], $configVars['software.mysql.password'], $configVars['software.mysql.db'], $MYSQL_PORT);

if(!isset($_SESSION['db_name'])) { 
	$_SESSION['db_name'] ="cnt_demo";
}

if($_SESSION['db_name'] != 'none') {
	$connect  = @mysqli_connect($configVars['software.mysql.host'], $configVars['software.mysql.user'], $configVars['software.mysql.password'], $_SESSION['db_name'], $MYSQL_PORT);
	if(!$connect) {
		echo "DB  Select Error Custom";
		exit;
	}
	$sq = "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
	//print ($sq);
	mysqli_query($connect0, $sq);
	mysqli_query($connect, $sq);
}

$DB_COMMON['account'] 	= 'common.'.$configVars['software.mysql.db_common.table.user'];
$DB_COMMON['param'] 	= 'common.'.$configVars['software.mysql.db_common.table.param'];
$DB_COMMON['counting'] 	= 'common.'.$configVars['software.mysql.db_common.table.counting'];
$DB_COMMON['count_event']='common.'.$configVars['software.mysql.db_common.table.count_event'];
$DB_COMMON['snapshot'] 	= 'common.'.$configVars['software.mysql.db_common.table.snapshot'];
$DB_COMMON['heatmap'] 	= 'common.'.$configVars['software.mysql.db_common.table.heatmap'];
$DB_COMMON['face'] 		= 'common.'.$configVars['software.mysql.db_common.table.face'];
$DB_COMMON['mac'] 		= 'common.'.$configVars['software.mysql.db_common.table.macsniff'];
$DB_COMMON['access_log']= 'common.'.$configVars['software.mysql.db_common.table.access_log'];
$DB_COMMON['message'] 	= 'common.'.$configVars['software.mysql.db_common.table.message'];
$DB_COMMON['language'] 	= 'common.'.$configVars['software.mysql.db_common.table.language'];
$DB_COMMON['weather'] 	= 'common.'.$configVars['software.mysql.db_common.table.weather'];

$DB_CUSTOM['account'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.account'];
$DB_CUSTOM['count'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.count'];
$DB_CUSTOM['heatmap'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.heatmap'];
$DB_CUSTOM['age_gender']= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.age_gender'];
$DB_CUSTOM['square'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.square'];
$DB_CUSTOM['store'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.store'];
$DB_CUSTOM['camera'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.camera'];
$DB_CUSTOM['counter_label'] = $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.counter_label'];
$DB_CUSTOM['weather'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.weather'];
$DB_CUSTOM['language'] 	= $_SESSION['db_name'].".".$configVars['software.mysql.db_custom.table.language'];
$DB_CUSTOM['web_config']= $_SESSION['db_name'].".webpage_config";


$user_img = "<i class=\"fa fa-user-o\"></i> ";

$LOGIN_PAGE = "/admin.php?fr=login";
// $CLOUD_SERVER = "49.235.119.5";
$CLOUD_SERVER = "14.6.84.62";
$DEVELOPER_WEB = "47.56.150.14";

// define(_TZ_OFFSET, 28800); // China
define(_TZ_OFFSET, 32400); // Korea
?>
