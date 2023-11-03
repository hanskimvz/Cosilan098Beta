<?PHP
// print_r($_GET);
// print_r($_SERVER);
// print ("55".getcwd());
// $frame = explode("?",$_GET['fr'])[0];
// print $frame;

// exit;

for ($i=0; $i<3; $i++) {
	chdir("../");
	if (is_dir("bin")) {
		$ROOT_DIR = getcwd();
		break;
	}
}

function PageNotFound(){
    // print $_SERVER['REQUEST_URI'];
    $str = '
        <link rel="stylesheet" href="/css/app.css">
        <link rel="stylesheet" href="/css/all.css">	
        <main class="main d-flex justify-content-center w-100">
            <div class="container d-flex flex-column">
                <div class="row h-100">
                    <div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
                        <div class="d-table-cell align-middle">
                            <div class="text-center">
                                <h1 class="display-1 font-weight-bold">404</h1>
                                <p class="h1">Page not found.</p>
                                <p class="h2 font-weight-normal mt-3 mb-4">The page you are looking for might have been removed.</p>
                                <button OnClick="parent.location.href=(\'/\')" class="btn btn-primary btn-lg">Return to website</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <script src="/js/app.js"></script> 
    ';
    print $str;
    echo '<script>parent.document.getElementById("title").innerHTML = "404 Error";</script>';

}


if ($_GET['fr'] == 'about.php'){
	$htmlbody = '
		<main class="main d-flex justify-content-center w-100">
			<div class="container d-flex flex-column">
				<div class="row h-100">
					<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
						<div class="d-table-cell align-middle">
							<div class="text-center">
								<h1 class="display-1 font-weight-bold">Cosilan</h1>
								<p class="h1">Cosilan Webpage V0.92</p>
								<p class="h2 font-weight-normal mt-3 mb-4">The page you are looking for might have been removed.</p>
								<a href="/" class="btn btn-primary btn-lg">Return to website</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>';
}

else if ($_GET['fr'] == 'faq.php'){
    print "FAQ.PHP";



}

else if ($_GET['fr'] == 'mailto.php'){
    print "mailto.PHP";



}

else if ($_GET['fr'] == 'support.php'){
    print "support.PHP";



}

else if ($_GET['fr'] == 'config.php'){
    if (file_exists($ROOT_DIR."/html/inc/config.php")) {
        echo '<script>location.href=("config.php")</script>';
    }
    else {
        PageNotFound();
    }
}

else if ($_GET['fr'] == 'network.php'){
    if (file_exists($ROOT_DIR."/html/inc/network.php")) {
        echo '<script>location.href=("network.php")</script>';
    }
    else {
        PageNotFound();
    }
}

else if ($_GET['fr'] == 'help.php'){
    $configVars['MYSQL']['HOST'] = 'localhost';
    $configVars['MYSQL']['USER'] = 'doc_user';
    $configVars['MYSQL']['PASSWORD'] = '13579';

    $connect = @mysqli_connect($configVars['MYSQL']['HOST'], $configVars['MYSQL']['USER'], $configVars['MYSQL']['PASSWORD'], 'document');
    
    if(!$connect) {
        echo "DB  Select Error";
        exit;
    }
    if (!$_GET['table']){
        $_GET['table'] = 'paragraph';
    }

    if (!$_GET['mode']) {
        $_GET['mode'] = 'list';
    }
    
    $tableBody = "";
    function simpleBodyText($str,$search){
        $str = strip_tags($str);
        $st = strpos($str, $search);
        if ($st) {
            $st =-10;
        }
        if ($st<0){
            $st = 0;
        }
        $ed = $st+500;
        return mb_substr($str, $st, $ed);
    }

    if ($_GET['mode'] == 'view'){
        if ($_GET['code']) {
            // $sq = "select * from ".$_GET['table']." where pk=".$_GET['pk'];
            $sq = "select * from ".$_GET['table']." where code='".$_GET['code']."'";
            // print $sq;
            $rs = mysqli_query($connect, $sq);
            $assoc =  mysqli_fetch_assoc($rs);
            if($_SESSION['logID']) {
                $assoc['title'] = '<a href="modify.php?pk='.$assoc['pk'].'">'.$assoc['title'].'</a>';
            }
        }
        $htmlbody = '
            <head>
                <meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                <link href="/css/app.css" rel="stylesheet">
                <link rel="stylesheet" href="/css/all.css">	
                <style>
                    .ql-syntax {
                    background-color: #23241f;
                    color: #f8f8f2;
                    /* overflow: visible; */
                    }
                </style>
            </head>
            <body>
                <main class="content">
                    <div class="container-fluid p-0">
                        <h1 class="h3 mb-3">'.$assoc['title'].'</h1>
                        <div class="row">
                            <div class="col-12 col-lg-12">
                                <div class="card">
                                    <div class="card-body">
                                        '.$assoc['body'].'
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </body>
            <script src="/js/app.js"></script>';

    }
    else {
        if($_GET['search']) {
            $sq = "select pk, code, title, body, regdate, last_modified, flag from ".$_GET['table']." where title like '%".trim($_GET['search'])."%' or body like '%".trim($_GET['search'])."%' ";
            print $sq;
            $rs = mysqli_query($connect, $sq);
            while ($assoc = mysqli_fetch_assoc($rs)){
                // print_r($assoc);
                $view_href = '/help.php?mode=view&code='.$assoc['code'].'';
                $assoc['title'] = '<a href="'.$view_href.'" target="view_doc">'.$assoc['title'].'</a>';
                $tableBody .= '<tr>
                    <td><p>'.$assoc['title'].' ---- '.date("Y-m-d", strtotime($assoc['last_modified'])).'</br>
                    '.simpleBodyText($assoc['body'], trim($_GET['search'])).'</p></td>
                    </tr>';
            }
            if (!$tableBody){
                $tableBody = "No Record, Please Try Other Keywords!";
            }
            $tableBody = '<table class="table table-sm table-striped">
                <tbody>'.$tableBody.'</tbody></table>';
        }
        else {
            $tableBody = '<table class="table table-striped"><tbody>Try Keyword for Search</tbody></table>';
        
        }

    
        $htmlbody = '
            <link rel="stylesheet" href="/css/app.css">
            <link rel="stylesheet" href="/css/all.css">	
            <div class="wrapper">	
                <div class="main" id="main_page">
                    <nav class="navbar sidebar-sticky navbar-expand navbar-light bg-white">
                        <i class="hamburger align-self-center"></i>
                        <form method="GET" class="form-inline d-none d-sm-inline-block" ENCTYPE="multipart/form-data">
                        <input type="hidden" name="fr" value="help.php">
                        <input type="text" class="form-control form-control-no-border"  placeholder="Search" name="search" value="'.$_GET['search'].'" size="100">
                        <button type="submit" class="btn btn-warning" >查找</button>
                        </form>			
                    </nav>
                    <main class="content">
                        <div class="container-fluid p-0" >
                            <div class="row">
                            '.$tableBody.'
                            </div>
                        </div>
                    </main>
                </div>
            </div>
            <script src="/js/app.js"></script>';
    }


    print $htmlbody;
}

else if ($_GET['fr'] == 'log.php'){
    if (file_exists($ROOT_DIR."/html/inc/log.php")) {
        echo '<script>location.href=("log.php?level='.$_GET['level'].'&start='.$_GET['start'].'&end='.$_GET['end'].'")</script>';
    }
    else {
        PageNotFound();
    }
}

else {
    PageNotFound();
    // print $_SERVER['REQUEST_URI'];
    // $str = '
    //     <link rel="stylesheet" href="/css/app.css">
    //     <link rel="stylesheet" href="/css/all.css">	
    //     <main class="main d-flex justify-content-center w-100">
    //         <div class="container d-flex flex-column">
    //             <div class="row h-100">
    //                 <div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
    //                     <div class="d-table-cell align-middle">
    //                         <div class="text-center">
    //                             <h1 class="display-1 font-weight-bold">404</h1>
    //                             <p class="h1">Page not found.</p>
    //                             <p class="h2 font-weight-normal mt-3 mb-4">The page you are looking for might have been removed.</p>
    //                             <button OnClick="parent.location.href=(\'/\')" class="btn btn-primary btn-lg">Return to website</button>
    //                         </div>
    //                     </div>
    //                 </div>
    //             </div>
    //         </div>
    //     </main>
    //     <script src="/js/app.js"></script> 
    // ';
    // print $str;
    // echo '<script>parent.document.getElementById("title").innerHTML = "404 Error";</script>';
}



?>

