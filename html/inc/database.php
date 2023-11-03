<?PHP
	$msg = q_language('database.php');
	if(!isset($_GET['page_max']) || !$_GET['page_max']) {
		$_GET['page_max'] = 20;
	}
	if(!isset($_GET['page_no']) || !$_GET['page_no']) {
		$_GET['page_no'] = 1;
	}
	$TABLE_BODY = "";
	switch($_GET['db']) {
		case 'counting':
			$sq = "select pk, device_info, timestamp, cast(concat (year,'-', month,'-', day,' ', hour,':', min) as datetime) as date,  counter_name, counter_val, counter_label, camera_code, store_code, square_code from ".$DB_CUSTOM['count']." ";
			
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			$sq .= " order by timestamp desc";
			
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));

			$rs = mysqli_query($connect, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;
			
			$rs = mysqli_query($connect, $sq);
			while($row = mysqli_fetch_row($rs)){
				$TABLE_BODY .= '<tr>';
				for ($j=0; $j<sizeof($row); $j++) {
					$TABLE_BODY .= '<td>'.$row[$j].'</td>';
				}
				$TABLE_BODY .= '</tr>';
				// $TABLE_BODY .= '<tr>
				// 	<td>'.$row[0].'</td><td>'.$row[1].'</td><td>'.$row[2].'</td><td>'.$row[3].'</td><td>'.$row[4].'</td><td>'.$row[5].'</td><td>'.$row[6].'</td><td>'.$row[7].'</td><td>'.$row[8].'</td><td>'.$row[9].'</td>
				// </tr>';
			}		
			break;
		
		case 'agegender':
			$sq = "select pk, device_info, timestamp, cast(concat (year,'-', month,'-', day,' ', hour,':', min) as datetime) as date, age, gender, camera_code, store_code, square_code  from ".$DB_CUSTOM['age_gender']." ";

			if($_GET['search']) {
				$sq .= "where device_info like '%".trim($_GET['search'])."%' ";
			}
			$sq .= "order by timestamp desc";
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));

			$rs = mysqli_query($connect, $sqc);
			$TOTAL_RECORD = $rs->num_rows;		
		
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;
		
			$rs = mysqli_query($connect, $sq);
			while($row = mysqli_fetch_row($rs)){
				$TABLE_BODY .= '<tr>';
				for ($j=0; $j<9; $j++) {
					$TABLE_BODY .= '<td>'.$row[$j].'</td>';
				}
				$TABLE_BODY .= '</tr>';
			}		
		
			break;
	
		case 'heatmap':
			$sq = "select pk, device_info, timestamp, cast(concat (year,'-', month,'-', day,' ', hour,':00') as datetime) as date, body_csv as heatmap, camera_code, store_code, square_code from ".$DB_CUSTOM['heatmap']." ";	
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			$sq .= " order by timestamp desc";

			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			$rs = mysqli_query($connect, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;

			$rs = mysqli_query($connect, $sq);
			$img= "";
			while($row = mysqli_fetch_row($rs)){
				$scale = 1;
				$MAX = 0;
			 
				$str = "";
				// $line = explode("\r\n",$row[5]);
				$col = explode(",",$row[4]);
				for($y = 0; $y< 45; $y++) {
					// $col = explode(",",$line[$y]);
					for($x =0; $x<80; $x++) {
						$col[$x+$y*80] = isset($col[$x+$y*80]) ? trim($col[$x+$y*80]): 0;
						if($col[$x+$y*80]) {
							$str .= "{x:".($x*$scale).", y:".($y*$scale).", value:".$col[$x+$y*80]." },";
							if($col[$x+$y*80] > $MAX) {
								$MAX = $col[$x+$y*80];
							}
						}
					}
				}
				$row[4] = draw_heatmap($str, $MAX, 1, $img);
			
				unset($str);
				$TABLE_BODY .='<tr>';
				for($i=0; $i<sizeof($row); $i++) {
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}				
			break;				

			case 'weather':
				$sq = "select pk, timestamp, datetime, cityid, city, cityCn, weather, temperature, temp_low, temp_high, wind, humidity, visibility, pressure, air, air_pm25, air_level from ".$DB_CUSTOM['weather']." ";	
				if($_GET['search']) {
					$sq .= " where device_info like '%".trim($_GET['search'])."%'";
				}
				$sq .= " order by timestamp desc";
	
				$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
				$rs = mysqli_query($connect, $sqc);
				$TOTAL_RECORD = $rs->num_rows;
				
				$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
				$sq .= " limit ".$offset.", ".$_GET['page_max'];
				// print $sq;
	
				$rs = mysqli_query($connect, $sq);
				while($row = mysqli_fetch_row($rs)){
					$TABLE_BODY .='<tr>';
					for($i=0; $i<sizeof($row); $i++) {
						$TABLE_BODY .='<td>'.$row[$i].'</td>';
					}
					$TABLE_BODY .='</tr>';
				}				
				break;		
		
		case 'params':
			$table = "params";		
			$sq = "select pk, device_info, usn, product_id as P_id, lic_pro as pro, lic_surv as sur, lic_count as cnt, face_det as face, heatmap as hm, countrpt as crpt, macsniff as mac, initial_access, last_access, concat('') as status, db_name, url as local_ip, method  from ".$DB_COMMON['param']." ";
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			$sq .= " order by last_access desc";
			
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;		
			
			$rs = mysqli_query($connect0, $sq);
			while($row = mysqli_fetch_row($rs)){
				$delay_sec = time() + TZ_OFFSET - strtotime($row[12]);
				if($delay_sec < 3660 ){
					$row[13] = '<span class="badge badge-success">'.date("H:i:s", $delay_sec).'</span>';
				}
				else if($delay_sec>(3600*24)){
					$row[13] = '<span class="badge badge-danger">'.floor($delay_sec/3600/24).'d '.date("H:i:s", $delay_sec).'</span>';
				}
				else {
					$row[13] = '<span  class="badge badge-warning">'.floor($delay_sec/3600/24).'d '.date("H:i:s", $delay_sec).'</span>';
				}
				# $row[1] ==> str_replace("&", "&amp;", $row[1]) because of IE Error
				$row[1] = '<a href = "admin.php?fr=view_param&'.$row[1].'" target="aaa">'.str_replace("&", "&amp;", $row[1]).'</a>';
				for ($j=4; $j<11; $j++) {
					if ($row[$j] == 'n') {
						$row[$j] = "<font color=#AAAAAA>n</font>";
					}
				}

				$TABLE_BODY .= '<tr>';
				for ($j=0; $j<17; $j++){
					$TABLE_BODY .= '<td>'.$row[$j].'</td>';
				}
				$TABLE_BODY .= '</tr>';

			}			

			break;
		
		case 'counting_common':
			$sq = "select pk, regdate, device_info, timestamp, counter_name, counter_val, flag, datetime, tag, status from ".$DB_COMMON['counting']." ";
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			
			$sq .= " order by timestamp desc";
			
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;		
			
			$rs = mysqli_query($connect0, $sq);
			while($row = mysqli_fetch_row($rs)){
				$TABLE_BODY .='<tr>';
				for($i=0; $i<sizeof($row); $i++) {
					// if ($i==3) {
					// 	$row[$i].=date("Y-m-d H:i:s", $row[$i]);
					// }
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}		
			
			break;

			case 'event_counting_common':
				$sq = "select pk, regdate, device_info, device_ip, timestamp, counter_name as ct_name, counter_val as ct_val, message, flag, status from ".$DB_COMMON['count_event']." ";
				if($_GET['search']) {
					$sq .= " where device_info like '%".trim($_GET['search'])."%'";
				}

				$sq .= " order by timestamp desc";
				
				$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
				$rs = mysqli_query($connect0, $sqc);
				$TOTAL_RECORD = $rs->num_rows;
				
				$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
				$sq .= " limit ".$offset.", ".$_GET['page_max'];
				// print $sq;		
				
				$rs = mysqli_query($connect0, $sq);
				while($row = mysqli_fetch_row($rs)){
					// $row[7] = "...".substr($row[7],125,50)."...";
					$TABLE_BODY .='<tr>';
					for($i=0; $i<sizeof($row); $i++) {
						$TABLE_BODY .='<td>'.$row[$i].'</td>';
					}
					$TABLE_BODY .='</tr>';
				}		
				
				break;			


		case 'heatmap_common':
			$sq = "select pk, regdate, device_info, timestamp, datetime, body_csv as heatmap, flag, status from ".$DB_COMMON['heatmap']." ";	
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			$sq .= " order by timestamp desc";

			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;

			$rs = mysqli_query($connect0, $sq);
			$img = "";
			while($row = mysqli_fetch_row($rs)){
				$scale = 1;
				$MAX = 0;
			 
				$str = "";
				// $line = explode("\r\n",$row[5]);
				$col = explode(",",$row[5]);
				for($y = 0; $y< 45; $y++) {
					// $col = explode(",",$line[$y]);
					for($x =0; $x<80; $x++) {
						$col[$x+$y*80] = isset($col[$x+$y*80]) ? trim($col[$x+$y*80]) : 0;
						if($col[$x+$y*80]) {
							$str .= "{x:".($x*$scale).", y:".($y*$scale).", value:".$col[$x+$y*80]." },";
							if($col[$x+$y*80] > $MAX) {
								$MAX = $col[$x+$y*80];
							}
						}
					}
				}
				$row[5] ='<span OnClick="">'.draw_heatmap($str, $MAX, 1, $img).'</span>';	
				unset($str);
				$TABLE_BODY .='<tr>';
				for($i=0; $i<sizeof($row); $i++) {
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}			
			break;

		case 'snapshot';
			$sq = "select pk, regdate, device_info, body from ".$DB_COMMON['snapshot']." ";
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			$sq .= "order by regdate desc";
			$_GET['page_max'] = 10;
			
					$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			// print $sqc;
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;
			
			$rs = mysqli_query($connect0, $sq);
			while($row = mysqli_fetch_row($rs)){
				$row[3] = '<img src="'.$row[3].'" height="100px" data-toggle="modal" data-target="#modalSnapshot" OnClick="viewSnapshot(this,\''.$row[2].'\')" onMouseOver="this.style.cursor=\'pointer\'">';
				// $row[3] = '<a href="./admin.php?fr=snapshot&pk='.$row[0].'" target="aaa"><img src="'.$row[3].'" height="100px"></a>';
				for($i=0; $i<sizeof($row); $i++) {
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}
			// $TABLE_BODY .= '<div class="modal fade"  tabindex="0" role="dialog" aria-hidden="true" id="modalSnapshot" style="display:">
			// <div class="modal-dialog" role="document"><div class="modal-content" id="modal_snapshot"></div></div></div>';

			$TABLE_BODY .= '
			<div class="modal fade" id="modalSnapshot" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-xl" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 id="modal_device_info"></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body m-1">
						<div class="text-center" id="modal_snapshot"></div>
						</div>
					</div>
				</div>
			</div>';

			break;				

		case 'face_thumbnail':
			$sq = "select pk, device_info, timestamp, datetime, thumbnail, age, gender,  face_token, flag, flag_fd, flag_ud, flag_fs,event_info from ".$DB_COMMON['face']." ";
			if($_GET['search']) {
				$sq .= " where device_info like '%".trim($_GET['search'])."%'";
			}
			$sq .= " order by timestamp desc";
			$_GET['page_max'] = 10;
			
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			// print $sqc;
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;
			
			$rs = mysqli_query($connect0, $sq);
			while($row = mysqli_fetch_row($rs)){
				$row[1] = str_replace("&","<br />",$row[1]);
				$row[4] = '<img src="'.$row[4].'" height="100px" data-toggle="modal" data-target="#modalSnapshot" OnClick="viewSnapshot(this)" onMouseOver="this.style.cursor=\'pointer\'">';
				// $row[4]'<a href="./admin.php?fr=snapshot&pk='.$row[0].'" target="aaa"><img src="'.$row[3].'" height="100px"></a>';
				$row[12] = str_replace("datetime=", "<br>datetime=",str_replace("ch=0", "<br>ch=0",str_replace("hassnapshot=", "<br>hassnapshot=",$row[12])));
				
				$TABLE_BODY .='<tr>';
				for($i=0; $i<sizeof($row); $i++) {
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}
			$TABLE_BODY .= '<div class="modal fade"  tabindex="0" role="dialog" aria-hidden="true" id="modalSnapshot" style="display:">
				<div class="modal-dialog" role="document">
					<div class="modal-content"><div class="modal-body m-1"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><div class="text-center" id="modal_snapshot"></div></div></div>
				</div>
			</div>';
			break;	
			
		case 'sniff':
			$sq = "select pk, regdate, timestamp, ip_addr, port, mac_src, mac_dst, frame, subframe, channel, rssi from ".$DB_COMMON['mac']." ";
			if($_GET['search']) {
				$sq .= " where mac_src like '%".trim($_GET['search'])."%' or mac_dst like '%".trim($_GET['search'])."%' ";
			}
			$sq .= " order by timestamp desc";
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;		
			
			$rs = mysqli_query($connect0, $sq);
			while($row = mysqli_fetch_row($rs)){
				$TABLE_BODY .='<tr>';
				for($i=0; $i<sizeof($row); $i++) {
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}			
			break;		

		case 'access_log':
			$table = "access_log";
			$sq = "select pk, regdate, last_session_time, concat('') as duration, ip_addr, PHPSESSID, ID, act  from ".$DB_COMMON['access_log']." ";	
			$sq .= " order by regdate desc";
			$sqc = "select pk ". substr($sq, strpos($sq,"from"), strlen($sq));
			$rs = mysqli_query($connect0, $sqc);
			$TOTAL_RECORD = $rs->num_rows;
			
			$offset = ($_GET['page_no'] - 1) * $_GET['page_max'];
			$sq .= " limit ".$offset.", ".$_GET['page_max'];
			// print $sq;		
			
			$rs = mysqli_query($connect0, $sq);
			while($row = mysqli_fetch_row($rs)){
				$delay_sec = strtotime($row[2]) - strtotime($row[1]);
				if($delay_sec <(3600*24)) {
					$row[3] = date('H:i:s', $delay_sec);
				}
				else {
					$row[3] = date('d H:i:s', $delay_sec);
				}
				$TABLE_BODY .='<tr>';
				for($i=0; $i<sizeof($row); $i++) {
					$TABLE_BODY .='<td>'.$row[$i].'</td>';
				}
				$TABLE_BODY .='</tr>';
			}			
			break;		
	}

	$uri ="./admin.php?fr=".$_GET['fr']."&db=".$_GET['db']."&search=".$_GET['search'];	
	$Pagination = Pagination($uri, $TOTAL_RECORD, $_GET['page_no'],$_GET['page_max'],'appstack');
	$TABLE_HEAD = "";
	while($fields = mysqli_fetch_field($rs)) {
		$TABLE_HEAD .= '<th>'.($fields->name).'</th>';
	}
	$TABLE_HEAD = '<thead><tr>'.$TABLE_HEAD.'</tr></thead>';	
	$TABLE_BODY = '<tbody>'.$TABLE_BODY.'</tbody>';
	$TABLE_BODY = '<table class="table table-striped table-sm table-bordered table-hover" >'.$TABLE_HEAD.$TABLE_BODY.'</table>';

	$pageContents = <<<EOPAGE
	<script src="../js/heatmap.js"></script>
	<main class="content">
		<div class="container-fluid p-0" >
			<div class="row">
				<div class="col-12 col-lg-5 d-flex" >$Pagination</div>
				<div class="col-12 col-lg-5 d-flex"></div>
				<div class="col-12 col-lg-2 text-right"><span class="text-right">TOTAL: $TOTAL_RECORD</span></div>
			</div>
			<div class="row">
				<div class="col-12 col-lg-12 d-flex" >
					<div class="card flex-fill">
						<div class="card-body d-flex w-100">
							<div class="table-responsive">
							$TABLE_BODY
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		$pageFoot
	</main>	
EOPAGE;

?>