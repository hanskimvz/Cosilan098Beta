<?php
require_once($_SERVER['DOCUMENT_ROOT']."/libs/functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/inc/page_functions.php");
$msg = q_language('account.php');
if(!isset($_GET['act'])){
    $_GET['act'] == 'view';
}
if (isset($_GET['fr']) && $_GET['fr'] == 'profile') {
    $sq = "select code, ID, email, passwd, db_name, flag, role from ".$DB_COMMON['account']." where ID='".$_SESSION['logID']."' ";
    // print $sq;
    $rs = mysqli_query($connect0, $sq);
    $assoc = mysqli_fetch_assoc($rs);
    $sq = "select name, name_eng, language, telephone, address, address_b, theme, date_in, date_out, img, comment from ".$DB_CUSTOM['account']." where code='".$assoc['code']."' ";
    // print $sq;
    $rs = mysqli_query($connect0, $sq);
    if($rs->num_rows) {
        $assoc = array_merge($assoc, mysqli_fetch_assoc($rs));
    }
    if (!isset($assoc['img']) || !$assoc['img']) {
        $assoc['img'] = "http://49.235.119.5/inc/1.png";
    }    
    // print_r($assoc);
}

else if ($_GET['act'] == 'view') {
    $sq = "select code, ID, email,  db_name, flag, role from ".$DB_COMMON['account']." where code='".$_GET['code']."' ";
    // print $sq;
    $rs = mysqli_query($connect0, $sq);
    $assoc = mysqli_fetch_assoc($rs);
    $assoc['sq'][0] = $sq;
    $sq = "select name, name_eng, language, telephone, address, address_b, theme, date_in, date_out, img, comment from ".$DB_CUSTOM['account']." where code='".$assoc['code']."' ";
    $assoc['sq'][1] = $sq;
    $rs = mysqli_query($connect0, $sq);
    if($rs->num_rows) {
        $assoc = array_merge($assoc, mysqli_fetch_assoc($rs));
    }
    // print_r($assoc);
    if (!isset($assoc['img']) || !$assoc['img']) {
        $assoc['img'] = "http://49.235.119.5/inc/1.png";
    }
    $json_str = json_encode($assoc, JSON_PRETTY_PRINT);
    print $json_str;
    exit();

}
else if ($_GET['act'] == 'modify'){
    // print_r($_POST);
    // print_r($_FILES);

    $arr_sq = array();
    $sq = "select pk from ".$DB_CUSTOM['account']." where code= '".$_POST['code']."' ";
    $rs = mysqli_query($connect0, $sq);
    if (!$rs->num_rows) {
        $sq = "insert into ".$DB_CUSTOM['account']."(code) values('".$_POST['code']."') ";
        array_push($arr_sq, $sq);
    }

    if ($_GET['tab']=='public') {
        $sq = "update ".$DB_COMMON['account']." set ID='".$_POST['ID']."', email='".$_POST['email']."', db_name='".$_POST['db_name']."', role = '".$_POST['role']."', flag='".$_POST['flag']."' where code='".$_POST['code']."' ";
        array_push($arr_sq, $sq);

    }
    else if ($_GET['tab']=='private') {
        $sq = "update ".$DB_CUSTOM['account']." set name='".addslashes($_POST['name'])."', name_eng='".addslashes($_POST['name_eng'])."', telephone='".addslashes($_POST['telephone'])."', address = '".addslashes($_POST['address'])."', address_b='".addslashes($_POST['address_b'])."' where code='".$_POST['code']."' ";
        // print $sq;
        array_push($arr_sq, $sq);        

    }
    else if ($_GET['tab']=='passwd') {
        $sq = "select passwd ".$DB_COMMON['account']."' where code='".$_POST['code']."' ";
        print $sq;
        $rs = mysqli_query($connect0, $sq);
        $passwd = mysqli_fetch_row($rs)[0];
        if ($passwd == $_POST['current_passwd']) {
            $sq = "update ".$DB_COMMON['account']." set passwd='".$_POST['new_pw1']."' where code='".$_POST['code']."' ";
            array_push($arr_sq, $sq);
        }
    }
    else if($_GET['tab'] == "mydata") {
        if (!$_POST['date_in']) {
            $_POST['date_in'] = "1970-01-01";
        }
        if (!$_POST['date_out']) {
            $_POST['date_out'] = "1970-01-01";
        }

        $sq = "update ".$DB_CUSTOM['account']." set language='".$_POST['language']."', date_in='".$_POST['date_in']."', date_out='".$_POST['date_out']."' where code='".$_POST['code']."' ";
        array_push($arr_sq, $sq);

        if (isset($_FILES['file']) && $_FILES['file']){
            // print_r($_FILES);
            $bin_img = file_get_contents($_FILES['file']['tmp_name']);
            $b64_img = "data:image/jpeg;base64,".base64_encode($bin_img);
            $sq = "update ".$DB_CUSTOM['account']." set img = '".addslashes($b64_img)."' where code='".$_POST['code']."' where code='".$_POST['code']."' ";
            array_push($arr_sq, $sq);
        }
    }
    else if($_GET['tab'] == "delete") {
        $sq = "select passwd from ".$DB_COMMON['account']." where (role = 'root' or role = 'admin') and passwd='".$_POST['passwd']."' ";
        $rs = mysqli_query($connect0, $sq);
        if ($rs->num_rows) {
            print "delete user";
            $sq = "delete from ".$DB_CUSTOM['account']." where code='".$_POST['code']."' ";
            array_push($arr_sq, $sq);
            $sq = "delete from ".$DB_COMMON['account']." where code='".$_POST['code']."' ";
            array_push($arr_sq, $sq);
        }
        else {
            print "Error: Type correct admin Password";
        }
    }

    print_r($arr_sq);
    for ($i=0; $i<sizeof($arr_sq); $i++){
        $rs = mysqli_query($connect0, $arr_sq[$i]) ? "...update OK\n" : $arr_sq[$i]."...update Fail\n" ;
        print $rs;
    }
    exit();
}
else {
    $assoc = array(
        'code'=>'',
        'ID'=>'',
        'email'=>'',
        'passwd'=>'',
        'db_name'=>'none',
        'flag'=>'',
        'role'=>'',
        'name'=> '',
        'name_eng'=>'',
        'language'=>'',
        'telephone'=>'',
        'address'=>'',
        'address_b'=>'',
        'theme'=>'none',
        'date_in'=>'',
        'date_out'=>'',
        'img'=>'',
        'comment' =>''
    );
}

$sq = "show databases";
$rs = mysqli_query($connect0, $sq);
$arr_db = array(['value'=>'none', 'text'=>'none']);
while ($row=mysqli_fetch_row($rs)){
    if (!in_array($row[0], ['information_schema', 'mysql', 'test', 'common'])) {
        array_push($arr_db, ['value'=>$row[0], 'text'=>$row[0]]);
    }
}

$arr_role = array(
    ['value' => 'admin',    'text' => $msg['admin']],
    ['value' => 'poweruser','text' => $msg['poweruser']],
    ['value' => 'user',     'text' => $msg['user']],
    ['value' => 'guest',    'text' => $msg['guest']],
);

$arr_flag = array(
    ['value'=>'y', 'text'=>$msg['activate']], 
    ['value'=>'n', 'text'=>$msg['disable']]
);

$arr_lang = array(
    ['value'=>'chi', 'text'=>$msg['chinese']], 
    ['value'=>'eng', 'text'=>$msg['english']], 
    ['value'=>'kor', 'text'=>$msg['korean']], 
    ['value'=>'fre', 'text'=>$msg['french']], 
    
);

$arr_tag = array(
    'act'       => strInput(0,                      'act',          'modify',                   0, 0),
    'code'      => strInput($msg['code'],           'code',         $assoc['code'],             0, 1),
    'id'        => strInput($msg['id'],             'id',           $assoc['ID'],               0, 0),
    'email'     => strInput($msg['email'],          'email',        $assoc['email'],            0, 0),
    'name'      => strInput($msg['name'],           'name',         $assoc['name'],             6, 0),
    'name_eng'  => strInput($msg['englishname'],    'name_eng',     $assoc['name_eng'],         6, 0),
    'telephone' => strInput($msg['telephone'],      'telephone',    $assoc['telephone'],        3, 0),
    'address'   => strInput($msg['address'],        'address',      $assoc['address'],          9, 0),
    'address_b' => strInput($msg['addressdetail'],  'address_b',    $assoc['address_b'],        0, 0),
    'current_pw'=> strInput($msg['currentpassword'],'current_pw',   '',                         0, 0),
    'new_pw1'   => strInput($msg['newpassword'],    'new_pw1',      '',                         0, 0),
    'new_pw2'   => strInput($msg['verifypassword'], 'new_pw2',      '',                         0, 0),
    'date_in'   => strInput($msg['datein'],         'date_in',      $assoc['date_in'],          6, 0),
    'date_out'  => strInput($msg['dateout'],        'date_out',     $assoc['date_out'],         6, 0),
    'comment'   => strTextarea($msg['comment'],     'comment',  $assoc['comment'],              0),
    'role'      => strRadio($msg['role'],           'role',     $arr_role,  $assoc['role'],     5),
    'flag'      => strRadio($msg['flag'],           'flag',     $arr_flag,  $assoc['flag'],     3),
    'language'  => strRadio($msg['language'],       'language', $arr_lang,  $assoc['language'], 5),
    'db_names'  => strSelect($msg['db_name'],       'db_name',  $arr_db,    $assoc['db_name'],  3),
);
if(!isset($badge)){
    $badge= '';
}
$pageInfoPad = <<<EOBLOCK
    <div class="col-12 col-lg-12">
        <h1 class="h3 mb-3">$msg[accountinfo]</h1>
        <div class="row">
            <div class="col-md-3 col-xl-2" >
            <div class="card">
                    <div class="card-header">$badge<h5 class="card-title mb-0">$msg[profilesettings]</h5></div>
                    <div class="list-group list-group-flush" role="tablist">
                        <a class="list-group-item list-group-item-action active" data-toggle="list" href="#account_info" role="tab">$msg[account]</a>
                        <a class="list-group-item list-group-item-action" data-toggle="list" href="#password" role="tab">$msg[password]</a>
                        <a class="list-group-item list-group-item-action" data-toggle="list" href="#mydata" role="tab">$msg[mydata]</a>
                        <a class="list-group-item list-group-item-action" data-toggle="list" href="#delete_record" role="tab">$msg[deleteaccount]</a>
                    </div>
                </div>
            </div>
            <div class="col-md-9 col-xl-10">
                <div class="tab-content">$arr_tag[act]
                    <div class="tab-pane fade show active" id="account_info" role="tabpanel">
                        <div class="card">
                            <div class="card-header">$badge<h5 class="card-title mb-0">$msg[publicinfo]</h5></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        $arr_tag[code]$arr_tag[id]$arr_tag[email]
                                        <div class="form-row">$arr_tag[db_names]$arr_tag[role]$arr_tag[flag]</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <img id="img_view" src="$assoc[img]" class="rounded-circle img-responsive mt-2" width="300" height="300" />
                                        </div> 
                                    </div>
                                </div>
                                <button type="submit" name="btn" class="btn btn-primary" value="public" onClick="saveUserInfo(this)">$msg[save_changes]</button>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header"><h5 class="card-title mb-0">$msg[privateinfo]</h5></div>
                            <div class="card-body">
                                <div class="form-row">$arr_tag[name]$arr_tag[name_eng]</div>
                                <div class="form-row">$arr_tag[telephone]$arr_tag[address]</div>
                                $arr_tag[address_b]
                                <button type="submit" name="btn" class="btn btn-primary" value="private" onClick="saveUserInfo(this)">$msg[save_changes]</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <div class="card">
                            <div class="card-header"><h5 class="card-title">$msg[password]</h5></div>
                            <div class="card-body">
                                $arr_tag[current_pw]$arr_tag[new_pw1]$arr_tag[new_pw2]
                                <button type="submit" name="btn" class="btn btn-primary" value="passwd" onClick="saveUserInfo(this)">$msg[save_changes]</button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="mydata" role="tabpanel">
                        <div class="card">
                            <div class="card-header"><h5 class="card-title">$msg[mydata]</h5></div>
                            <div class="card-body">
                                <div class="form-row">$arr_tag[language]</div>
                                <div class="form-row">$arr_tag[date_in]$arr_tag[date_out]</div>
                                $arr_tag[comment]
                                <div class="form-group">
                                    <label class="form-label w-100">$msg[imagefile]</label>
                                    <input type="file" name="imgfile" id="img_file" accept="image/*" />
                                    <small class="form-text text-muted">img file such as .jpg, .png under 2M Byte</small>
                                </div>
                                <button type="submit" name="btn" class="btn btn-primary" value="mydata" onClick="saveUserInfo(this)">$msg[save_changes]</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="delete_record" role="tabpanel">
                        <div class="card">
                            <div class="card-header"><h5 class="card-title">$msg[deleteaccount]</h5></div>
                            <div class="card-body">
                                <div class="form-group"><p class="mb-0">$msg[areyousuretodeletethisrecord]</p></div>
                                <div class="form-group">
                                    <label>$msg[password]</label>
                                    <input type="password" id="passwd" class="form-control">
                                    <small class="form-text text-muted">$msg[typeadminpassword]</small> 
                                </div>
                                <button type="submit" name="btn" class="btn btn-warning" value="delete" onClick="saveUserInfo(this)">$msg[deleteaccount]</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
EOBLOCK;
?>


