<?PHP
if(strpos($_SERVER['DOCUMENT_URI'],'auth.php') >0){
	session_start();
}
date_default_timezone_set ( "UTC" ); 
// if(isset($_POST['userseq']) and $_POST['userseq']) {
// 	setcookie("userseq", $_POST['userseq'],0,'/');
// 	setcookie("role", $_POST['role'],0,'/');
// 	setcookie("selected_language", $_POST['selected_language'],0,'/');
// 	setcookie("theme", $_POST['theme'],0,'/');
// 	// print_r($_COOKIE);print_r($_SESSION); exit;
// 	echo "<script> location.href=('/')</script>";
// 	exit;
// }

if(isset($_GET['lang']) && $_GET['lang']) {
	setcookie("selected_language", $_GET['lang'],0,'/');
	print_r($_COOKIE['selected_language']);
	// echo "<script> location.href=('".$_GET['p']."?fr=".$_GET['fr']."')</script>";
}

else if(isset($_GET['fr']) && ($_GET['fr'] == 'logout')) {
	setcookie('selected_language', '',0,'/');
	setcookie('role', '',0,'/');
	setcookie('userseq', '',0,'/');
	setcookie('theme', '',0,'/');
	session_destroy();
}
else if(isset($_GET['fr']) && ($_GET['fr'] == 'proc_login')) {
	if(!trim($_POST['ID'])) {
		print 'checkid';
		exit();
	}
	if (strpos($_POST['ID'], '#')) { // injection hacking
		print 'checkid';
		exit();
	}
	if((!$_POST['password'])) {
		print 'checkpassword';
		exit();
	}	

	require_once $_SERVER['DOCUMENT_ROOT']."/libs/dbconnect.php";
	$assoc = array('name'=>'');
	$sq = "select code, ID, passwd, db_name, role, flag from ".$DB_COMMON['account']." where (ID='".$_POST['ID']."' or  email='".$_POST['ID']."') and passwd='".$_POST['password']."'";
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	if(!($rs->num_rows)) {
		print 'passworderror';
		exit();
	}
	$arr_user =  mysqli_fetch_assoc($rs);
	if ($arr_user['flag'] == 'n'){
		print "notreadytouse";
		exit();
	}
	if($arr_user['db_name'] != 'none') {
		$sq = "select name from ".$DB_CUSTOM['account']." where code = '".$arr_user['code']."' ";
		$rs = mysqli_query($connect, $sq);
		if($rs->num_rows) {
			$assoc =  mysqli_fetch_assoc($rs);
		}
	}
	$arr_user = array_merge($arr_user, $assoc);
	// print_r($arr_user);
	if(!isset($_POST['theme'])) {
		$_POST['theme'] = 'basic';
	}
	$_SESSION['userip'] = $_SERVER['REMOTE_ADDR'];
	$_SESSION['logID']  = $arr_user['ID'];
	$_SESSION['db_name'] = $arr_user['db_name']; 
	$_SESSION['user_name'] = $arr_user['name'];
				
	
	$sq = "insert ".$DB_COMMON['access_log']."(IP_addr, regdate, ID, PHPSESSID, act) values('".$_SERVER['REMOTE_ADDR']."', '".date('Y-m-d H:i:s', time()+3600*8)."', '".$arr_user['ID']."', '".$_COOKIE['PHPSESSID']."', '".addslashes($_SERVER['HTTP_USER_AGENT']."\\".$_POST['theme'])."')";
	// print $sq;
	if(mysqli_query($connect0, $sq))  {
		$userseq = md5($arr_user['ID']."test");
		setcookie("userseq", $userseq, 0,'/');
		setcookie("role", $arr_user['role'],0,'/');
		setcookie("selected_language", $_POST['language'],0,'/');
		setcookie("theme", $_POST['theme'],0,'/');
		print "loginOK";
		exit;
	}		
	exit();
}
else if(isset($_GET['fr']) && ($_GET['fr'] == 'proc_register')) {
	// print_r($_POST);
	if(!trim($_POST['ID'])) {
		print 'checkid';
		exit();
	}
	if(!trim($_POST['email'])) {
		print 'checkemail';
		exit();
	}

	if (strpos($_POST['ID'], '#')) { // injection hacking
		print 'checkid';
		exit();
	}
	if(!$_POST['password'] || strlen($_POST['password'])<5) {
		print 'checkpassword';
		exit();
	}

	require_once $_SERVER['DOCUMENT_ROOT']."/libs/dbconnect.php";
	$sq = "select * from ".$DB_COMMON['account']." where ID='".$_POST['ID']."' or email='".$_POST['email']."'";
	$rs = mysqli_query($connect0, $sq);
	if($rs->num_rows) {
		print 'idemailalreadyused';
		exit();
	}

	$ex = explode("@",$_POST['email']);
	$url = array_pop($ex);	
	if(!$url || !(checkdnsrr($url,"MX") || checkdnsrr($url, "A"))) { 
		print 'wrongemailaddress';
		exit();
	}
	$code = "U".time().rand(0,9).rand(0,9).rand(0,9);
	$sq = "insert into ".$DB_COMMON['account']."(code, email, ID, passwd, regdate) values('".$code."', '".trim($_POST['email'])."', '".trim($_POST['ID'])."', '".trim($_POST['password'])."', now())";
 	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	if($rs)  {
		print 'registersucceed';
	}
	exit();
}

$sel_lang = array("eng"=>"", "kor"=>"", "chi"=>"");
$sel_lang[$_COOKIE['selected_language']] = 'selected';

if($_GET['fr'] == 'login') {
	$msg= q_language('login.php');
	$msg['checkid'] = "check ID";
	echo '<script>msg = new Array()</script>';
	foreach($msg as $key=>$val){
		echo '<script>msg["'.$key.'"]= "'.$val.'"</script>';
	}

	$pageSide='';
	if (!isset($_POST['ID'])){
		$_POST['ID'] ='';
	}
	$pageContents = <<<EOPAGE
	<main class="main h-100 w-100">
		<div class="container h-100">
			<div class="row h-100">
				<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">
						<div class="text-center mt-4">
							<h1 class="h2">$msg[welcome]</h1>
							<p class="lead">$msg[welcome_message]</p>
						</div>
						<div class="card">
							<div class="card-body">
								<div class="m-sm-4">
									<div class="text-center">
									<span><i class="align-middle" style="width:60px; height:60px; color:#f000f0" data-feather="activity"></i></span>
										<!--img src="img/avatars/avatar.jpg" alt="Chris Wood" class="img-fluid rounded-circle" width="132" height="132" /-->
									</div>
									<form name="loginForm" method="POST" ENCTYPE="multipart/form-data">
										<div class="form-group">
											<label>$msg[emailorid]</label>
											<input class="form-control form-control-lg" type="text" name="ID" id="ID" placeholder="Enter Email or ID" value="$_POST[ID]"/>
										</div>
										<div class="form-group">
											<label>$msg[password]</label>
											<input class="form-control form-control-lg" type="password" name="password" id="password" autocomplete="on" placeholder="Enter your password" />
											<small><a href="pages-reset-password.html">$msg[forgotpassword]</a></small>
										</div>
										<div class="form-row">
											<div class="form-group col-md-12">
												<label>$msg[language]</label>
												<select class="form-control pull-right" name="language" OnChange="changeLanguage(this)" id="language">
													<option value="chi" $sel_lang[chi]>$msg[chinese]</option>
													<option value="eng" $sel_lang[eng]>$msg[english]</option>
													<option value="kor" $sel_lang[kor]>$msg[korean]</option></select> 
											</div>
											<!--div class="form-group col-md-6">
												<label>$msg[theme]</label>
												<select class="form-control pull-right" name="theme" OnMouseOver="changeTheme(this)">
													<option value="classic">Classic</option>
													<option value="modern">Modern</option>
												</select>
											</div-->
										</div>
										
										<div class="text-center mt-3">
											<!--a href="index.html" class="btn btn-lg btn-primary" Onclick="check_form()">$msg[signin]</a-->
											<button type="button" class="btn btn-lg btn-primary col-md-3 mt-2" name="login" OnClick="submitForm(this)">$msg[signin]</button>
										</div>
									</form>
								</div>
								<div class="m-sm-4">$msg[notregistred]  <a href="admin.php?fr=register">$msg[registerhere]</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
EOPAGE;
	print $pageHead;
	print $pageContents;
	echo '<script src="../js/app.js"></script>';
	// echo '<script src="../js/custom.js"></script>';
	echo '</html>';
}

else if($_GET['fr'] == 'register') {
	$msg = q_language('register.php');
	echo '<script>msg = new Array()</script>';
	foreach($msg as $key=>$val){
		echo '<script>msg["'.$key.'"]= "'.$val.'"</script>';
	}	
	
	if(!isset($_POST['ID'])) {
		$_POST['ID']='';
	}
	if(!isset($_POST['email'])) {
		$_POST['email']='';
	}
	
	$pageSide = '';
	$pageContents = <<<EOPAGE
	<main class="main h-100 w-100">
		<div class="container h-100">
			<div class="row h-100">
				<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">
						<div class="text-center mt-4">
							<h1 class="h2">$msg[welcome]</h1>
							<p class="lead">$msg[registernewid]</p>
						</div>
						<div class="card">
							<div class="card-body">
								<div class="m-sm-4">
									<div class="text-center">
									<span><i class="align-middle" style="width:60px; height:60px; color:#f000f0" data-feather="activity"></i></span>
										<!--img src="img/avatars/avatar.jpg" alt="Chris Wood" class="img-fluid rounded-circle" width="132" height="132" /-->
									</div>
									<form name="registerForm" method="POST" ENCTYPE="multipart/form-data">
										<div class="form-group">
											<label>$msg[id]</label>
											<input class="form-control form-control-lg" type="text" name="ID" id="ID" placeholder="Enter ID"  value="$_POST[ID]" />
										</div>
										<div class="form-group">
											<label>$msg[email]</label>
											<input class="form-control form-control-lg" type="text" name="email" id="email" placeholder="Enter Email" value="$_POST[email]" />
										</div>
										<div class="form-group">
											<label>$msg[password]</label>
											<input class="form-control form-control-lg" type="password" name="password" id="password" autocomplete="off" placeholder="Enter your password" />
										</div>
										<div class="form-group">
											<label>$msg[verifypassword]</label>
											<input class="form-control form-control-lg" type="password" name="password2" id="password2"  autocomplete="off" placeholder="Retype your password" />
										</div>
										<div class="form-row">
											<div class="form-group col-md-6">
												<label>$msg[favoritelanguage]</label>
												<select class="form-control pull-right" name="language" OnChange="changeLanguage(this)" id="language">
													<option value="chi" $sel_lang[chi]>$msg[chinese]</option>
													<option value="eng" $sel_lang[eng]>$msg[english]</option>
													<option value="kor" $sel_lang[kor]>$msg[korean]</option></select> 
											</div>
										</div>
										<div class="text-center mt-3">
											<button type="button" class="btn btn-lg btn-primary col-md-3 mt-2" name="register" OnClick="submitForm(this)"">$msg[signup]</button>
										</div>
									</form>
								</div>
								<div class="m-sm-4">$msg[alreadyregistred] <a href="admin.php?fr=login">$msg[signhere]</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
EOPAGE;
	print $pageHead;
	print $pageContents;
	echo '<script src="../js/app.js"></script>';
	// echo '<script src="../js/custom.js"></script>';
	echo '</html>';
}

else if($_GET['fr'] == 'logout') {
	$msg = q_language('logout.php');
	$pageSide='';
	$pageContents = <<<EOPAGE
	<main class="main h-100 w-100">
		<div class="container h-100">
			<div class="row h-100">
				<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">
						<div class="text-center">
							<h1>$msg[thankyouforusing]</h1>
							<p>$msg[ifyouhaveanyinquirypleaseletusknow]</p>
							<p>$msg[itcanbeveryhelpfultodevelopthesite]</p>
							<br />
							<div class="horizontal-links">
								<a href="/admin.php?fr=login" class="m-2">$msg[relogin]</a> | 
								<a href="/about.php"  class="m-2">$msg[aboutus]</a> | 
								<a href="/mailto.php"  class="m-2">$msg[contactus]</a> | 
								<a href="/faq.php"  class="m-2">$msg[faq]</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
EOPAGE;
	print $pageHead;
	print $pageContents;
	echo '<script src="../js/app.js"></script>';
	echo '<script src="../js/custom.js"></script>';
	echo '</html>';
}

else if($_GET['fr'] == 'about') {


}

else if($_GET['fr'] == 'faq') {


}
else if($_GET['fr'] == 'mailto') {
	include "../libs/functions.php";
	$msg= q_language('mailto.php');
	$pageContents = <<<EOPAGE
	<main class="main d-flex justify-content-center w-100">
		<div class="container d-flex flex-column">
			<div class="row h-100">
				<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">
					<div class="form-group form-inline col-md-12">
						<label class="mr-4">Title</label>
						<input type="text" class="form-control col-md-12" name="title" />
					</div>
					<div class="form-group form-inline col-md-12">
						<label class="mr-4">Body</label>
						<textarea class="form-control col-md-12" name="content" rows="10"></textarea>
					</div>					
					
					<button type="button" class="btn btn-lg btn-primary" OnClick="writeContents()">$msg[write]</button>
					</div>
				</div>
			</div>
		</div>
	</main>
EOPAGE;

print $pageContents;
}


?>

<script language="javascript">
	
	function checkForm(page){
		let arr = new Array();
		if (page == 'login') {
			document.getElementById("ID").style.borderColor = "";
			document.getElementById("password").style.borderColor = "";
			arr['ID'] = document.getElementById("ID").value;
			arr['password'] = document.getElementById("password").value;
			arr['lanaguage'] = document.getElementById("language").value;

			arr['ID'] = arr['ID'].trim();
			arr['password'] = arr['password'].trim();
			if (!arr['ID'] ) {
				document.getElementById("ID").style.borderColor = "#FF0000";
				return false;
			}
			if (!arr['password'] ) {
				document.getElementById("password").style.borderColor = "#FF0000";
				return false;
			}
		}
		else if (page == 'register'){
			document.getElementById("ID").style.borderColor = "";
			document.getElementById("email").style.borderColor = "";
			document.getElementById("password").style.borderColor = "";
			document.getElementById("password2").style.borderColor = "";

			arr['ID'] = document.getElementById("ID").value;
			arr['password'] = document.getElementById("password").value;
			arr['password2'] = document.getElementById("password2").value;
			arr['email'] = document.getElementById("email").value;
			arr['lanaguage'] = document.getElementById("language").value;
			arr['ID'] = arr['ID'].trim();
			arr['email'] = arr['email'].trim();
			arr['password'] =  arr['password'].trim();
			arr['password2'] = arr['password2'].trim();

			if (!arr['ID']) {
				document.getElementById("ID").style.borderColor = "#FF0000";
				return false;
			}			
			if (!arr['email']) {
				document.getElementById("email").style.borderColor = "#FF0000";
				return false;
			}
			if (!arr['password']) {
				document.getElementById("password").style.borderColor = "#FF0000";
				return false;
			}			
			if (!arr['password2']) {
				document.getElementById("password2").style.borderColor = "#FF0000";
				return false;
			}
			if (arr['password'] != arr['password2']) {
				document.getElementById("password").style.borderColor = "#FF0000";
				document.getElementById("password2").style.borderColor = "#FF0000";
				return false;
			}
		}
		return arr;
	}
	function submitForm(t){
		const page = t.name;
		let url = '';
		console.log(page);
		arr = checkForm(page);
		if (!arr){
			return false;		
		}
		console.log(arr);
		if (page == 'login') {
			url = "inc/auth.php?fr=proc_login";
		}
		else if (page == 'register') {
			url = "inc/auth.php?fr=proc_register";
		}
		else {
			return false;
		}
		console.log(url);
		const posting = $.post(url,{
			ID: arr['ID'],
			email: arr['email'],
			password: arr['password'],
			language: arr['lanaguage']
		});
		posting.done(function(data) {
			console.log("data:",data);
			if (data.indexOf('loginOK') >=0) {
				location.href=('/');
			}
			else if (data.indexOf('registersucceed') >=0) {
				alert(msg['registersucceed']);
				location.href=('/admin.php?fr=login');
			}			
			else{
				// console.log(msg[data]);
				alert(msg[data]);
			}
		});

	}
	function changeLanguage(e){
		// console.log(e.value);
		var url = 'inc/auth.php?lang=' + e.value;
		var posting = $.post(url,{});
		posting.done(function(data) {
			// console.log(data);
			location.reload();

		});
	}

	function changeTheme(e){
		// console.log(e);
	}

	function writeContents(){
	}
	// console.log(msg);

</script>