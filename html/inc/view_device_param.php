<?PHP    
    $counter = array();
	$zone = array();
	$rule = array();
	$device_info = "mac=".$_GET['mac']."&brand=".$_GET['brand']."&model=".$_GET['model'];
	
	$sq  = "select A.device_info, A.param as param , A.last_access, B.body as snapshot from ".$DB_COMMON['param']." as A inner join ".$DB_COMMON['snapshot']." as B on A.device_info=B.device_info  where A.device_info='".$device_info."' ";
	// print $sq;
	$rs = mysqli_query($connect0, $sq);
	$assoc = mysqli_fetch_assoc($rs);
	// print_r($_GET);
	if($_GET['view'] == 'all') {
		// print_arr($assoc);
		header("Content-Type: text/plain");
		print $assoc['param'];
		print PHP_EOL;
		print "last_access=".$assoc['last_access'];
		exit;
	}
	$arr_void_param= ["<xs", "</xs", "MD.","ENCODERPROFILE.Default.", "EVENTPROFILE.Default.", "EVENT.Default.", "SCHEDULE.Default.", "SYSTEM.Info.Rack", "ONVIF.","PRIVACYZONE.", "VCA.Default.", "ADREC.","AVIREC." ];
	$lines = explode("\n",$assoc['param']);
	for($i=0; $i<count($lines)-1; $i++) {
		$line = trim($lines[$i]);
		if(!$line) {
			continue;
		}
		if (str_in_array($arr_void_param, $line)) {
			continue;
		}
		// print($line."</br>");
		$ex = explode("=", $line);
		if (!isset($ex[1])) {
			continue;
		}
		$key = strtoupper(trim($ex[0]));
		$val = trim($ex[1]);
		if (startsWith($line, "VCA.")) {
			if (startsWith($key, "VCA.CH0.CT")) {
				$ex_key = explode(".",$key);
				$p = substr($ex_key[2], 2, strlen($ex_key[2]));
				if(isset($ex_key[4])) {
					$counter[$p][$ex_key[3]][$ex_key[4]] = trim($val);
				}
				else {
					$counter[$p][$ex_key[3]] = trim($val);
				}
			}
			else if(startsWith($key, "VCA.CH0.RL")) {
					$ex_key = explode(".",$key);
					$p = substr($ex_key[2], 2, strlen($ex_key[2]));
					$rule[$p][$ex_key[3]] = trim($val);			
			}
			else if(startsWith($key, "VCA.CH0.ZN")) {
				$ex_key = explode(".",$key);
				$p = substr($ex_key[2],2,strlen($ex_key[2]));
				$zone[$p][strtolower($ex_key[3])] = trim($val);
			}
			else {
				$vca[$key] = trim($val);
			}
		}
		else if (str_in_array(["NETWORK.", "HOST.", "IOT.", "DDNS.","IPFILTER.", "NETLOSS." ], $key)) {
			$network[$key] = $val;
		}	 
		else if (startsWith($key, "FD.")) {
			$fd[$key] = $val;
		}
		else if (str_in_array(["EVENT", "HEARTBEAT."], $key)) {
			$event[$key] = $val;
		}
		else if (str_in_array(["ENCODER.", "VIDEOIN.", "VIDEOOUT.", "VIDEOCODEC.", "SYSTEM.STATUS.VIDEOIN.","SYSTEM.PROPERTIES.STREAM."], $key)) {
            if ($key == "VIDEOCODEC.CH0.ST0.ENABLE") {
                $key = "ENCODER.CH0.VIDEOCODEC.ST0.ENABLE";
            }
            else if ($key == "VIDEOCODEC.CH0.ST1.ENABLE") {
                $key = "ENCODER.CH0.VIDEOCODEC.ST1.ENABLE";
            }
            else if ($key == "VIDEOCODEC.CH0.ST0.STANDARD") {
                $key = "ENCODER.CH0.VIDEOCODEC.ST0.STANDARD";
            }
            else if ($key == "VIDEOCODEC.CH0.ST1.STANDARD") {
                $key = "ENCODER.CH0.VIDEOCODEC.ST1.STANDARD";
            }

            $audiovideo[$key] = $val;
		}
		else if (str_in_array(["DIDO.", "PTZ.", "UART."], $line)) {
			$peripherial[$key] = $val;
		}
		else {
			$param[$key] = $val;
		}
	}
    if(isset($param['BRAND.PRODFULLNAME'])) {
        $param["BRAND.PRODUCT.FULLNAME"] = $param['BRAND.PRODFULLNAME'];
    }

    $table_basic = '
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th>USN</th>				<td>'.(isset($param["VERSION.SERIALNO"])?$param["VERSION.SERIALNO"]:"").'</td></tr>
		<tr><th>Full name</th>			<td>'.(isset($param["BRAND.PRODUCT.FULLNAME"])?$param["BRAND.PRODUCT.FULLNAME"]:"").'</td></tr>
		<tr><th>Short name</th>			<td>'.(isset($param["BRAND.PRODUCT.SHORTNAME"])?$param["BRAND.PRODUCT.SHORTNAME"]:"").'</td></tr>
		<tr><th>Firmware version</th> 	<td>'.(isset($param["VERSION.FIRMWARE"])?$param["VERSION.FIRMWARE"]:"").''.(isset($param["VERSION.DESCRIPTION"])?('('.$param["VERSION.DESCRIPTION"].')'):"").'</td></tr>
		<tr><th>Userfs version</th>		<td>'.(isset($param["VERSION.USERFS"])?$param["VERSION.USERFS"]:"").'</td></tr>
		<tr><th>Micro-P version</th>	<td>'.(isset($param['VERSION.MICROP'])?$param['VERSION.MICROP']:"").'</td></tr>
		<tr><th>Manufacturer</th>		<td>'.(isset($param["BRAND.BRAND"])?$param["BRAND.BRAND"]:"").'</td></tr>
		<tr><th>MAC address</th>		<td>'.(isset($network["NETWORK.ETH0.MAC"])?$network["NETWORK.ETH0.MAC"]:"").'</td></tr>
		<tr><th>Pan/Tilt</th>			<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.PANTILT"])?$param["SYSTEM.PROPERTIES.HARDWARE.PANTILT"]:"").'</td></tr>
		<tr><th>Focus/Zoom</th>			<td>'.(isset($param["FOCUSZOOM"])?$param["FOCUSZOOM"]:"").'</td></tr>
		<tr><th>DC Auto-Iris</th>		<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.IRISTYPE"])?$param["SYSTEM.PROPERTIES.HARDWARE.IRISTYPE"]:"").'</td></tr>
		<tr><th>Day/Night</th>			<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.DNTYPE"])?$param["SYSTEM.PROPERTIES.HARDWARE.DNTYPE"]:"").'</td></tr>
		<tr><th>IR Illumination</th>	<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.IRTYPE"])?$param["SYSTEM.PROPERTIES.HARDWARE.IRTYPE"]:"").'</td></tr>
		<tr><th>TV-Out</th>				<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.TVOUT"])?$param["SYSTEM.PROPERTIES.HARDWARE.TVOUT"]:"").'</td></tr>
		<tr><th>AudioIn/AudioOut</th>	<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.AUDIOIN"])?$param["SYSTEM.PROPERTIES.HARDWARE.AUDIOIN"]:"").''.(isset($param["SYSTEM.PROPERTIES.HARDWARE.AUDIOOUT"])? ('/'.$param["SYSTEM.PROPERTIES.HARDWARE.AUDIOOUT"]):"").'</td></tr>
		<tr><th>DI/DO</th>				<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.DI"])?$param["SYSTEM.PROPERTIES.HARDWARE.DI"]:"").''.(isset($param["SYSTEM.PROPERTIES.HARDWARE.DO"])? ('/'.$param["SYSTEM.PROPERTIES.HARDWARE.DO"]):"").'</td></tr>
		<tr><th>RS-485</th>				<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.RS485"])?$param["SYSTEM.PROPERTIES.HARDWARE.RS485"]:"").'</td></tr>
		<tr><th>USB</th>				<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.USB"])?$param["SYSTEM.PROPERTIES.HARDWARE.USB"]:"").'</td></tr>
		<tr><th>SD</th>					<td>'.(isset($param["SYSTEM.PROPERTIES.HARDWARE.SD"])?$param["SYSTEM.PROPERTIES.HARDWARE.SD"]:"").'</td></tr>
		<tr><th>Date Sync Source</th>	<td>'.(isset($param["SYSTEM.DATETIME.SYNCSOURCE"])?$param["SYSTEM.DATETIME.SYNCSOURCE"]:"").'</td></tr>
		<tr><th>Date sync Interval</th>	<td>'.(isset($param["SYSTEM.DATETIME.SYNCINTERVAL"])?$param["SYSTEM.DATETIME.SYNCINTERVAL"]:"").'</td></tr>
		<tr><th>Timezone</th>			<td>'.(isset($param["SYSTEM.DATETIME.TZ.NAME"])?$param["SYSTEM.DATETIME.TZ.NAME"]:"").''.(isset($param["SYSTEM.DATETIME.TZ.POSIXRULE"])?('('.$param["SYSTEM.DATETIME.TZ.POSIXRULE"].')'):"").'</td></tr>
		<tr><th>VCA</th>				<td>'.(isset($vca["VCA.LC0.LICENSEINFO"])?$vca["VCA.LC0.LICENSEINFO"]:"").' '.(isset($vca["VCA.LC1.LICENSEINFO"])? " / ".$vca["VCA.LC1.LICENSEINFO"] :"").'</td></tr>
	</table>';

	// $table_param = print_arr($param);
	$tds_vca_crpt = '';
	$tds_vca_hm = '';
	for($i=0; $i<4; $i++) {
		$tds_vca_crpt .= '
			<tr><td>'.$i.'</td>
				<td>'.(isset($vca["VCA.CH0.CRPT.DB.TB".$i.".ENABLE"])?$vca["VCA.CH0.CRPT.DB.TB".$i.".ENABLE"]:"-").'</td>
				<td>'.(isset($vca["VCA.CH0.CRPT.DB.TB".$i.".SAMPLING"])?$vca["VCA.CH0.CRPT.DB.TB".$i.".SAMPLING"]:"-").'</td>
				<td>'.(isset($vca["VCA.CH0.CRPT.DB.TB".$i.".ROLLCOUNT"])?$vca["VCA.CH0.CRPT.DB.TB".$i.".ROLLCOUNT"]:"-").'</td></tr>';
		$tds_vca_hm .= '
			<tr><td>'.$i.'</td>
				<td>'.(isset($vca["VCA.CH0.HM.TB".$i.".ENABLE"])?$vca["VCA.CH0.HM.TB".$i.".ENABLE"]:"-").'</td>
				<td>'.(isset($vca["VCA.CH0.HM.TB".$i.".SAMPLING"])?$vca["VCA.CH0.HM.TB".$i.".SAMPLING"]:"-").'</td>
				<td>'.(isset($vca["VCA.CH0.HM.TB".$i.".ROLLOVERTIME"])?$vca["VCA.CH0.HM.TB".$i.".ROLLOVERTIME"]:"-").'</td></tr>';
	}
	$table_vca = '
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th>License</th>		<td colspan="4">'.(isset($vca["VCA.LC0.LICENSEINFO"])?$vca["VCA.LC0.LICENSEINFO"]:"").' '.(isset($vca["VCA.LC1.LICENSEINFO"])? " / ".$vca["VCA.LC1.LICENSEINFO"] :"").'</td></tr>
		<tr><th>VCA Enable</th>		<td colspan="4">'.(isset($vca["VCA.CH0.ENABLE"])?$vca["VCA.CH0.ENABLE"]:"").'</td></tr>
		<tr><th>Counting Line</th>	<td colspan="4">'.(isset($vca["VCA.CH0.ENABLECNTLINE"])?$vca["VCA.CH0.ENABLECNTLINE"]:"").'</td></tr>
		<tr><th>Object Tracking</th><td colspan="4">'.(isset($vca["VCA.CH0.ENABLEMOVOBJ"])?$vca["VCA.CH0.ENABLEMOVOBJ"]:"").'</td></tr>
		<tr><th>Track Mode</th>		<td colspan="4">'.(isset($vca["VCA.CH0.TRACKMODE"])?$vca["VCA.CH0.TRACKMODE"]:"").'</td></tr>
		<tr><th>Counting Report</th><td colspan="4">'.(isset($vca["VCA.CH0.CRPT.DB.ENABLE"])?$vca["VCA.CH0.CRPT.DB.ENABLE"]:"").'</td></tr>
		<tr><th rowspan="5">Counting DB</th><th>#</th><th>enable</th><th>sampling</th><th>rollcount</th></tr>
			'.$tds_vca_crpt.'
		</tr>
		<tr><th>Heatmap</th>		<td colspan="4">'.(isset($vca["VCA.CH0.HM.ENABLE"])? $vca["VCA.CH0.HM.ENABLE"]:"").'</td></tr>
		<tr><th rowspan="5">Heatmap DB</th><th>#</th><th>enable</th><th>sampling</th><th>rollcount</th></tr>
			'.$tds_vca_hm.'
		</tr>
	</table>';
    unset($tds_vca_crpt);
    unset($tds_vca_hm);

	$table_zone = '';
	for ($i=0; $i<sizeof($counter); $i++) { 
		if (!isset($counter[$i]['SC0']['SOURCE'])) {
			$counter[$i]['SC0']['SOURCE'] = 0;
		}
		if (!isset($rule[trim($counter[$i]['SC0']['SOURCE'])]['NAME']) || !$rule[$counter[$i]['SC0']['SOURCE']]['NAME']){
			$rule[trim($counter[$i]['SC0']['SOURCE'])]['NAME'] ="";
		}
		if(!isset($counter[$i]['SC0']['TYPE'])){
			$counter[$i]['SC0']['TYPE']= "";
		}
		$table_zone .='
		<tr>
			<td>'.($i+1).'</td>
			<td>'.$counter[$i]['NAME'].'</td>
			<td>'.$counter[$i]['ENABLE'].'</td>
			<td>'.$counter[$i]['COUNT'].'</td>
			<td>'.$counter[$i]['UID'].'</td>
			<td>'.$rule[trim($counter[$i]['SC0']['SOURCE'])]['NAME'].'</td>
			<td>'.$counter[$i]['SC0']['TYPE'].'</td>
		</tr>';
		
	}
	$table_zone = '<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th colspan="7" class="table-primary">Counters</th></tr>
		<tr><th>#</th><th>Name</th><th>Enable</th><th>Value</th><th>Uid</th><th>Source</th><th>type</th></tr>'.$table_zone.'</table>';

	$table_zone ='
	<div class="row">
		<div class="col-md-12">'.draw_zone($zone, $assoc['snapshot'],"width=600; height=320").'</div>
	</div>
	<div class="row mt-4">	
		<div class="col-md-12">'.$table_zone.'</div>
	</div>';


	$table_face = '
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th>FD.ENABLE</th>			<td>'.(isset($fd["FD.ENABLE"])?$fd["FD.ENABLE"]:"-").' / '.(isset($fd["FD.CH0.ENABLE"])?$fd["FD.CH0.ENABLE"]:"-").'</td></tr>
		<tr><th>Clip rate H</th>		<td>'.(isset($fd["FD.CH0.CLIPRATEH"])?$fd["FD.CH0.CLIPRATEH"]:"-").'</td></tr>
		<tr><th>Clip rate W</th>		<td>'.(isset($fd["FD.CH0.CLIPRATEW"])?$fd["FD.CH0.CLIPRATEW"]:"-").'</td></tr>
		<tr><th>Confidence</th>			<td>'.(isset($fd["FD.CH0.CONFIDENCE"])?$fd["FD.CH0.CONFIDENCE"]:"-").'</td></tr>
		<tr><th>Ignore Cnt</th>			<td>'.(isset($fd["FD.CH0.IGNORECNT"])?$fd["FD.CH0.IGNORECNT"]:"-").'</td></tr>
		<tr><th>Max Size rate</th>		<td>'.(isset($fd["FD.CH0.MAXSIZERATE"])?$fd["FD.CH0.MAXSIZERATE"]:"-").'</td></tr>
		<tr><th>Track Factor H</th>		<td>'.(isset($fd["FD.CH0.TRKFACTORH"])?$fd["FD.CH0.TRKFACTORH"]:"-").'</td></tr>
		<tr><th>Track Factor W</th>		<td>'.(isset($fd["FD.CH0.TRKFACTORW"])?$fd["FD.CH0.TRKFACTORW"]:"-").'</td></tr>
		<tr><th>Track Hold on</th>		<td>'.(isset($fd["FD.CH0.TRKHOLDON"])?$fd["FD.CH0.TRKHOLDON"]:"-").'</td></tr>
		<tr><th>Zone Enable</th>		<td>'.(isset($fd["FD.CH0.DA0.ENABLE"])?$fd["FD.CH0.DA0.ENABLE"]:"-").'</td></tr>
		<tr><th>Zone Position</th>		<td>'.(isset($fd["FD.CH0.DA0.POSITION"])?$fd["FD.CH0.DA0.POSITION"]:"-").'</td></tr>
		<tr><th>Zone track duration</th><td>'.(isset($fd["FD.CH0.DA0.TRKDURATION"])?$fd["FD.CH0.DA0.TRKDURATION"]:"-").'</td></tr>
		<tr><th>OSD Enable</th>			<td>'.(isset($fd["FD.CH0.OSD.ENABLE"])?$fd["FD.CH0.OSD.ENABLE"]:"-").'</td></tr>
		<tr><th>OSD 1st Stream </th>	<td>'.(isset($fd["FD.CH0.OSD.ST0"])?$fd["FD.CH0.OSD.ST0"]:"-").'</td></tr>
		<tr><th>OSD 2nd Stream</th>		<td>'.(isset($fd["FD.CH0.OSD.ST1"])?$fd["FD.CH0.OSD.ST1"]:"-").'</td></tr>
		<tr><th>OSD Snapshot</th>		<td>'.(isset($fd["FD.CH0.OSD.SNAPSHOT"])?$fd["FD.CH0.OSD.SNAPSHOT"]:"-").'</td></tr>
		<tr><th>OSD Zone</th>			<td>'.(isset($fd["FD.CH0.OSD.ZONE"])?$fd["FD.CH0.OSD.ZONE"]:"-").'</td></tr>
	</table>';
	
	$table_network = '
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th colspan="2" class="table-primary">IP Address Configuration</th></tr>
		<tr><th>Local IP Address</th>		<td>'.$network["NETWORK.ETH0.IPADDRESS"].'</td></tr>
		<tr><th>Subnet Mask</th>			<td>'.$network["NETWORK.ETH0.SUBNET"].'</td></tr>
		<tr><th>Gateway Address</th>		<td>'.$network["NETWORK.ETH0.GATEWAY"].'</td></tr>
		<tr><th>Primary DNS Server</th>		<td>'.(isset($network["NETWORK.DNS.PREFERRED"])?$network["NETWORK.DNS.PREFERRED"]:$network["NETWORK.DNS0"]).'</td></tr>
		<tr><th>Secondary DNS Server</th>	<td>'.(isset($network["NETWORK.DNS.ALTERNATE0"])?$network["NETWORK.DNS.ALTERNATE0"]:$network["NETWORK.DNS1"]).'</td></tr>
	</table>
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th colspan="2" class="table-primary">TLSS Configuration</th></tr>
		<tr><th>Enable</th>					<td>'.(isset($network["HOST.ENABLE"])?$network["HOST.ENABLE"]:"-").'</td></tr>
		<tr><th>Server Address</th>			<td>'.(isset($network["HOST.ADDRESS"])?$network["HOST.ADDRESS"]:"-").'</td></tr>
		<tr><th>PORT</th>					<td>'.(isset($network["HOST.PORT"])?$network["HOST.PORT"]:"-").'</td></tr>
		<tr><th>Update Interval</th>		<td>'.(isset($network["HOST.UPDATEINTERVAL"])?$network["HOST.UPDATEINTERVAL"]:"-").'</td></tr>
	</table>
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th colspan="2" class="table-primary">RTMP Configuration</th></tr>
		<tr><th>RTMP Enable</th>			<td>'.(isset($network["NETWORK.RTMP.ENABLE"]) ? $network["NETWORK.RTMP.ENABLE"]:"").'</td></tr>
		<tr><th>Port</th>					<td>'.(isset($network["NETWORK.RTMP.PORT"]) ? $network["NETWORK.RTMP.PORT"]:"").'</td></tr>
		<tr><th>Publish Enable S0</th>		<td>'.(isset($network["NETWORK.RTMP.ST0.PUBLISH.ENABLE"]) ? $network["NETWORK.RTMP.ST0.PUBLISH.ENABLE"] :"").'</td></tr>
		<tr><th>URL</th>					<td>'.(isset($network["NETWORK.RTMP.ST0.PUBLISH.URL"]) ? $network["NETWORK.RTMP.ST0.PUBLISH.URL"]:"").'</td></tr>
		<tr><th>Publish Enable S1</th>		<td>'.(isset($network["NETWORK.RTMP.ST1.PUBLISH.ENABLE"]) ? $network["NETWORK.RTMP.ST1.PUBLISH.ENABLE"]:"").'</td></tr>
		<tr><th>URL</th>					<td>'.(isset($network["NETWORK.RTMP.ST1.PUBLISH.URL"]) ? $network["NETWORK.RTMP.ST1.PUBLISH.URL"]:"").'</td></tr>
	</table>';
//	print_arr($network)
	
	$table_event_http ='';
	if(isset($event["EVENT.NOTIFY.HTTP.LIST"])) {
		$ex_c =explode(",",$event["EVENT.NOTIFY.HTTP.LIST"]);
		for($i=0; $i<$event["EVENT.NOTIFY.HTTP.NBROFCOUNT"]; $i++) {
			$table_event_http .='<tr>
				<td>'.$ex_c[$i].'</td>
				<td>'.$event["EVENT.NOTIFY.HTTP.H".$ex_c[$i].".NAME"].'</td>
				<td>'.$event["EVENT.NOTIFY.HTTP.H".$ex_c[$i].".ADDRESS"].':'.$event["EVENT.NOTIFY.HTTP.H".$ex_c[$i].".PORT"].'/'.$event["EVENT.NOTIFY.HTTP.H".$ex_c[$i].".SYNTAX"].'</td>
				<td>'.$event["EVENT.NOTIFY.HTTP.H".$ex_c[$i].".OPTIONENABLE"].'</td>
			</tr>';
		}
	}
	$table_event_http = '<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th colspan="4" class="table-primary">Event Notify HTTP</th></tr>
		<tr><th>#</th><th>EVENT.Notify.Http[].Name</th><th>EVENT.Notify.Http[].Address</th><th>EVENT.Notify.Http[].Option</th></tr>'.$table_event_http.'</table>';

	$table_event_rule = '';
	if (isset($event["EVENTPROFILE.NBROFCOUNT"])) {
		$ex_c =explode(",",$event["EVENTPROFILE.LIST"]);
		for($i=0; $i<$event["EVENTPROFILE.NBROFCOUNT"]; $i++) {
			$table_event_rule .='<tr>
				<td>'.$i.'</td>
				<td>'.$event["EVENTPROFILE.P".$ex_c[$i].".NAME"].'</td>
				<td>'.$event["EVENTPROFILE.P".$ex_c[$i].".ENABLE"].'</td>
				<td>
					Face: '.$event["EVENTPROFILE.P".$ex_c[$i].".SOURCE.FACE.ENABLE"].'</br>
					VCA: '.$event["EVENTPROFILE.P".$ex_c[$i].".SOURCE.VCA.ENABLE"].'</br>
					COUNT: '.$event["EVENTPROFILE.P".$ex_c[$i].".SOURCE.VCA.COUNTENABLE"].'</br>
				</td>
				<td>
					HTTP: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.HTTP.ENABLE"].', list: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.HTTP.ENABLELIST"].', Method: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.HTTP.METHOD"].',Thumbnail: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.HTTP.POST.THUMBNAIL"].', Snapshot: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.HTTP.POST.SNAPSHOT"].'</br>
					TCP: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.TCPPUSH.ENABLE"].'</br>
					FTP: '.$event["EVENTPROFILE.P".$ex_c[$i].".NOTIFICATION.FTP.ENABLE"].'</br>
				</td>
			</tr>';
		}	
	}
	$table_event_rule = '<table class="table table-striped table-sm table-bordered table-hover">
		<tr><th class="table-primary">Event Rule</th><td colspan="4" class="table-primary">'.(isset($event["EVENTPROFILE.ENABLE"])?$event["EVENTPROFILE.ENABLE"]:"").'</td></tr>
		<tr><th>#</th><th>EVENT RULE Name</th><th>EVENT RULE ENABLE</th><th>EVENT RULE SOURCE</th><th>EVENT RULE ACTION</th></tr>'.$table_event_rule.'</table>';

	$table_event =''.$table_event_http.''.$table_event_rule;
	unset($table_event_http);
	unset($table_event_rule);

	$table_audiovideo ='
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr class="table-primary"><th>Streams</th><th>First Stream</th><th>Second Stream</th><th>Snapshot</th></tr>
		<tr><th>Enable</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.ENABLE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.ENABLE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.ENABLE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.ENABLE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.SNAPSHOT.ENABLE"]) ? $audiovideo["ENCODER.CH0.SNAPSHOT.ENABLE"]:"").'</td></tr>
		<tr><th>Video Codec</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.STANDARD"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.STANDARD"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.STANDARD"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.STANDARD"]:"").'</td>
			<td>MJPEG</td></tr>
		<tr><th>Resolution</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.RESOLUTION"])? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.RESOLUTION"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.RESOLUTION"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.RESOLUTION"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.SNAPSHOT.RESOLUTION"]) ? $audiovideo["ENCODER.CH0.SNAPSHOT.RESOLUTION"]:"").'</td></tr>
		<tr><th>Max. FPS</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.MAXFPS"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.MAXFPS"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.MAXFPS"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.MAXFPS"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.SNAPSHOT.MAXFPS"])? $audiovideo["ENCODER.CH0.SNAPSHOT.MAXFPS"]:"").'</td></tr>
		<tr><th>GOP</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.PCOUNT"]) ? ($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.PCOUNT"]+1):"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.PCOUNT"]) ? ($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.PCOUNT"]+1):"").'</td>
			<td></td></tr>
		<tr><th>Profile Identification</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.PROFILE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.PROFILE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.PROFILE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.PROFILE"]:"").'</td>
			<td></td></tr>
		<tr><th>Bitrate</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.BITRATECTRL"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.BITRATECTRL"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.BITRATECTRL"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.BITRATECTRL"]:"").'</td>
			<td></td></tr>
		<tr><th>Max. Bitrate(VBR)</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.MAXBITRATE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.MAXBITRATE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.MAXBITRATE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.MAXBITRATE"]:"").'</td>
			<td></td></tr>
		<tr><th>Image Quality(VBR)</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.QVALUE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.QVALUE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.QVALUE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.QVALUE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.SNAPSHOT.QUALITY"]) ? $audiovideo["ENCODER.CH0.SNAPSHOT.QUALITY"]:"").'</td></tr>
		<tr><th>Target Bitrate(CBR)</th>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.BITRATE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST0.H264.BITRATE"]:"").'</td>
			<td>'.(isset($audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.BITRATE"]) ? $audiovideo["ENCODER.CH0.VIDEOCODEC.ST1.H264.BITRATE"]:"").'</td>
			<td></td></tr>
	</table>';

	$table_peripherial ='
	<table class="table table-striped table-sm table-bordered table-hover">
		<tr class="table-primary"><th>UART Channel</th><th>Ch0</th><th>Ch1</th></tr>
		<tr><th>Type</th>
			<td>'.(isset($peripherial["UART.CH0.TYPE"])? $peripherial["UART.CH0.TYPE"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.TYPE"])? $peripherial["UART.CH1.TYPE"]:"").'</td></tr>
		<tr><th>Baudrate</th>
			<td>'.(isset($peripherial["UART.CH0.BAUDRATE"])? $peripherial["UART.CH0.BAUDRATE"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.BAUDRATE"])? $peripherial["UART.CH1.BAUDRATE"]:"").'</td></tr>
		<tr><th>Data bits</th>
			<td>'.(isset($peripherial["UART.CH0.DATABITS"])? $peripherial["UART.CH0.DATABITS"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.DATABITS"])? $peripherial["UART.CH1.DATABITS"]:"").'</td></tr>
		<tr><th>Flow Control</th>
			<td>'.(isset($peripherial["UART.CH0.FLOWCONTROL"])? $peripherial["UART.CH0.FLOWCONTROL"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.FLOWCONTROL"])? $peripherial["UART.CH1.FLOWCONTROL"]:"").'</td></tr>
		<tr><th>Parity</th>
			<td>'.(isset($peripherial["UART.CH0.PARITY"])? $peripherial["UART.CH0.PARITY"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.PARITY"])? $peripherial["UART.CH1.PARITY"]:"").'</td></tr>
		<tr><th>Stop bits</th>
			<td>'.(isset($peripherial["UART.CH0.STOPBITS"])? $peripherial["UART.CH0.STOPBITS"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.STOPBITS"])? $peripherial["UART.CH1.STOPBITS"]:"").'</td></tr>
		<tr><th>Mode</th>
			<td>'.(isset($peripherial["UART.CH0.MODE"])? $peripherial["UART.CH0.MODE"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.MODE"])? $peripherial["UART.CH1.MODE"]:"").'</td></tr>
		<tr><th>Serialoverip Addr.</th>
			<td>'.(isset($peripherial["UART.CH0.SERIALOVERIP.TCPCLIENTIPADDRESS"])? $peripherial["UART.CH0.SERIALOVERIP.TCPCLIENTIPADDRESS"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.SERIALOVERIP.TCPCLIENTIPADDRESS"])? $peripherial["UART.CH1.SERIALOVERIP.TCPCLIENTIPADDRESS"]:"").'</td></tr>
		<tr><th>Serialoverip Port</th>
			<td>'.(isset($peripherial["UART.CH0.SERIALOVERIP.TCPCLIENTPORT"])? $peripherial["UART.CH0.SERIALOVERIP.TCPCLIENTPORT"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.SERIALOVERIP.TCPCLIENTPORT"])? $peripherial["UART.CH1.SERIALOVERIP.TCPCLIENTPORT"]:"").'</td></tr>
		<tr><th>Serialoverip Timeout</th>
			<td>'.(isset($peripherial["UART.CH0.SERIALOVERIP.TCPCONNECTTIMEOUT"])? $peripherial["UART.CH0.SERIALOVERIP.TCPCONNECTTIMEOUT"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.SERIALOVERIP.TCPCONNECTTIMEOUT"])? $peripherial["UART.CH1.SERIALOVERIP.TCPCONNECTTIMEOUT"]:"").'</td></tr>
		<tr><th>Serialoverip Mode</th>
			<td>'.(isset($peripherial["UART.CH0.SERIALOVERIP.MODE"])? $peripherial["UART.CH0.SERIALOVERIP.MODE"]:"").'</td>
			<td>'.(isset($peripherial["UART.CH1.SERIALOVERIP.MODE"])? $peripherial["UART.CH1.SERIALOVERIP.MODE"]:"").'</td></tr>
	</table>';
	
	// foreach($peripherial as $A=>$B) {
	// 	$table_peripherial .= $A.'='.$B.'<br>';
	// }

	$pageSide='';	
	if (!isset($table_param)){
		$table_param = "";
	}

	$pageContents = <<<EOPAGE
	<html lang="en">
	<head>
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
	
	<main class="content">
		<div class="container-fluid p-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="tab tab-vertical">
						<ul class="nav nav-tabs" role="tablist">
							<li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic</a></li>	
							<li class="nav-item"><a class="nav-link" href="#vca" data-toggle="tab">VCA</a></li>
							<li class="nav-item"><a class="nav-link" href="#zone" data-toggle="tab">ZONE</a></li>
							<li class="nav-item"><a class="nav-link" href="#face" data-toggle="tab">Face Detection</a></li>
							<li class="nav-item"><a class="nav-link" href="#audiovideo" data-toggle="tab">Video & Audio</a></li>
							<li class="nav-item"><a class="nav-link" href="#network" data-toggle="tab">Network</a></li>
							<li class="nav-item"><a class="nav-link" href="#event" data-toggle="tab">Event</a></li>
							<li class="nav-item"><a class="nav-link" href="#peripherial" data-toggle="tab">Peripherial</a></li>
							<li class="nav-item"><a class="nav-link" href="admin.php?fr=view_param&view=all&$device_info" target ="view">Etc.</a></li>
							
						</ul>
						<div class="tab-content">
							<div class="tab-pane active col-md-12" id="basic">$table_basic</div>
							<div class="tab-pane fade col-md-12" id="param">$table_param</div>
							<div class="tab-pane fade col-md-12" id="vca">$table_vca</div>
							<div class="tab-pane fade col-md-12" id="zone">$table_zone</div>
							<div class="tab-pane fade col-md-12" id="face">$table_face</div>
							<div class="tab-pane fade col-md-12" id="network">$table_network</div>
							<div class="tab-pane fade col-md-12" id="event">$table_event</div>
							<div class="tab-pane fade col-md-12" id="audiovideo">$table_audiovideo</div>
							<div class="tab-pane fade col-md-12" id="peripherial">$table_peripherial</div>
						</div>
					</div>
				</div>	
			</div>	
		</div>
	</main>
EOPAGE;

?>