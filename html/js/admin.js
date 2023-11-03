function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        // vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

Get = getUrlVars();
if (!Get['fr']){
	Get['fr'] = 'account';
}

console.log(Get);

var a = '';
var b = '';	

if(Get['fr'] == "database") {
	b = document.getElementById(Get['fr']);
	a = document.getElementById(Get['db']);
}
else if (Get['fr'] == "webpageConfig") {
	b = document.getElementById(Get['fr']);
	a = document.getElementById(Get['db']);
}
else {
	a = document.getElementById(Get['fr']);
}
if (a) {
	a.classList.add("active");
}

if (b) {
	b.classList.add("show");
}


//#### User or Account //
function tagRadio(name) {
	ixs = document.getElementsByName(name);
	let val = '';
	ixs.forEach(function(item){
		if (item.checked){
			val = item.value;
		}
	});
	return val;
}

function chkRadio(name, chkVal){
	ixs = document.getElementsByName(name);
	ixs.forEach(function(item, i){
		ixs[i].checked = false;
		if (item.value == chkVal) {
			ixs[i].checked = true;
		}
	});
}


function viewUserInfo(code){
	let url ='inc/profile.php?act=view&code=' + code;
	console.log(url);
	document.getElementById("profile_info_pad").style.display='';
	$.getJSON(url, function(response) {
		console.log(response);
		document.getElementById('code').value = response['code'];
		document.getElementById('id').value = response['ID'] ? response['ID'] :"";
		document.getElementById('email').value = response['email'] ? response['email'] : "";
		document.getElementById('db_name').value = response['db_name'];
		chkRadio('role', response['role']);
		chkRadio('flag', response['flag']);
		// document.getElementsByName('role').response['role'].checked=true;
		// document.getElementById('flag').value = response['flag'];
		document.getElementById('name').value = response['name'] ? response['name'] :"";
		document.getElementById('name_eng').value = response['name_eng'] ? response['name_eng'] :"";
		document.getElementById('telephone').value = response['telephone'] ? response['telephone'] :"";
		document.getElementById('address').value = response['address'] ? response['address'] :"";
		document.getElementById('address_b').value = response['address_b'] ? response['address_b'] :"";
		chkRadio('language', response['language']);
		// document.getElementById('language').value = response['language'] ? response['language']: "";
		document.getElementById('date_in').value = response['date_in'] ? response['date_in'] :"";
		document.getElementById('date_out').value = response['date_out'] ?response['date_out'] : "";
		document.getElementById('img_view').src = response['img'];

	});

}

function saveUserInfo(t){
	// console.log(t.name, t.value)
	let tab =t.value; // public, private, passwd, mydata, delete
	let url ='inc/profile.php?act=modify&tab=' + tab;
	let formData = new FormData();
	if (tab == 'public'){
		formData.append('code', document.getElementById('code').value);
		formData.append('ID', document.getElementById('id').value);
		formData.append('email', document.getElementById('email').value);
		formData.append('db_name', document.getElementById('db_name').value);
		formData.append('role', tagRadio('role'));
		formData.append('flag', tagRadio('flag'));
	}
	else if (tab == 'private'){
		formData.append('code', document.getElementById('code').value);
		formData.append('name', document.getElementById('name').value);
		formData.append('name_eng', document.getElementById('name_eng').value);
		formData.append('telephone', document.getElementById('telephone').value);
		formData.append('address', document.getElementById('address').value);
		formData.append('address_b', document.getElementById('address_b').value);
	}
	else if (tab == 'passwd'){
		cur_pw = document.getElementById('current_pw');
		new_pw1 = document.getElementById('new_pw1');
		new_pw2 = document.getElementById('new_pw2');
		cur_pw.style.borderColor = "";
		new_pw1.style.borderColor = "";
		new_pw2.style.borderColor = "";

		if (!cur_pw.value.trim()) {
			cur_pw.style.borderColor = "#FF0000";
			cur_pw.focus();
			return false;
		}
		if (!new_pw1.value.trim()) {
			new_pw1.style.borderColor = "#FF0000";
			new_pw1.focus();
			return false;
		}
		if (!new_pw2.value.trim()) {
			new_pw2.style.borderColor = "#FF0000";
			new_pw2.focus();
			return false;
		}
		if (new_pw1.value.trim() != new_pw2.value.trim()) {
			new_pw1.style.borderColor = "#FF0000";
			new_pw2.style.borderColor = "#FF0000";
			return false;
		}

		formData.append('code', document.getElementById('code').value);
		formData.append('current_pw', cur_pw.value);
		formData.append('new_pw1', new_pw1.value);
		formData.append('new_pw2', new_pw2.value);
	}
	else if (tab == 'mydata'){
		formData.append('code', document.getElementById('code').value);
		formData.append('language', tagRadio('language'));
		formData.append('date_in', document.getElementById('date_in').value);
		formData.append('date_out', document.getElementById('date_out').value);
		formData.append('comment', document.getElementById('comment').value);
		formData.append('file', document.getElementById('img_file').files[0]);
	}
	else if (tab == 'delete'){
		passwd = document.getElementById('passwd');
		if (!passwd.value.trim()) {
			passwd.style.borderColor = "#FF0000";
			passwd.focus();
			return false;
		}	
		formData.append('code', document.getElementById('code').value);
		formData.append('passwd', document.getElementById('passwd').value);
	}
	console.log('url', url);
	$.ajax({
		type: "POST",
		enctype: 'multipart/form-data',
		url: url,
		data: formData,
		processData: false,
		contentType: false,
		cache: false,
		timeout: 600000,
		success: function (response) {
			console.log("SUCCESS : ", response);
		},
		error: function (e) {
			console.log("ERROR : ", e);
		}
	});	

}


function draw_zone(id, zone) {
	// console.log(zone);
	var context = id.getContext("2d");
	var width = 800;
	var height =  450;
	var i = 0;
	var j = 0;
	context.clearRect(0,0,width,height);
	var P = new Array();
	var x = new Array();
	var y = new Array();

	for (i = 0; i<zone.length; i++) {
		P = zone[i]['points'].split(',');
		if(zone[i]['style'] == 'polygon'){
			P.push(P[0]);
		}
		for (j=0; j<P.length; j++) {
			p_xy = P[j].split(":");
			x[j] = Math.round((width*p_xy[0])/65535);
			y[j] = Math.round((height*p_xy[1])/65535);
		}
		context.beginPath(); 	
		context.moveTo(x[0], y[0]);
		for (j=1; j<P.length; j++) {
			context.lineTo(x[j],y[j]);
		}
		if(zone[i]['style'] == 'polygon'){
			context.lineWidth = 0;
			context.fillStyle = 'rgba(' + zone[i]['color'] + ',0.3)';
			context.closePath();
			if(zone[i]['type'] == 'nondetection') {
				context.fillStyle = "rgba(100,100,100,0.6)";
			}
			context.fill();
		}
		else {
			context.lineWidth = 3;
			context.strokeStyle = 'rgba(' + zone[i]['color'] + ',0.5)';
			context.stroke();
		}
		context.font = "12pt Calibri";
		context.fillStyle = 'rgba(' + zone[i]['color'] + ',0.8)';
		context.fillText(zone[i]['name'], x[0], y[0]-10);
	}
	
}

function viewSnapshot(e, ix){
	console.log(e);
	const dev_info = document.getElementById('modal_device_info');
	const id = document.getElementById('modal_snapshot');
	if (dev_info) {
		dev_info.innerHTML = '<a href = "admin.php?fr=view_param&' + ix + '" target="aaa">' + ix +'</a>';
	}
	id.innerHTML = '<img src="' + e.src + '" height="620" />';
}

function scroll_info() {
	var y_p = window.top.scrollY;
	// console.log(y_p);
	y_p -= 100;
	if(y_p<0) {
		y_p = 0;
	}
	document.getElementById('info_frame').style.top = y_p + "px";
}

function ApplyJsonToField(response){
	// console.log(response);
	// arr = Object.keys(response).map((key) => [key, response[key]]); not working on IE
	arr_key = Object.keys(response);
	// console.log(arr_key);
	for (i=0; i< arr_key.length; i++) {
		id = document.getElementById(arr_key[i]);
		if (id) {
			// console.log(id, id.type);
			if ((id.type == "text") || (id.type == "hidden") || (id.type == "select-one")) {
				id.value = response[arr_key[i]];
			}
			else if (id.type == "checkbox") {
				if (response[arr_key[i]] =='y') {
					id.checked = true;
				}
			}
		}
	}
}

function viewSquareInfo(code) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	console.log(code);
	let url = "./inc/device_tree.php?mode=view&info=square&code=" + code;
	console.log(url);
	let posting = $.post(url,{});
	posting.done(function(data) {
		document.getElementById("info_page").innerHTML = data;
	});	
}
function modifySquare(code) {
	let mode = document.getElementById("mode").value;
	if (!document.getElementById("name").value) {
		document.getElementById("name").style.borderColor = "#FF0000";
		return;
	}

	let url = "./inc/device_tree.php?&mode=" + mode + "&info=square&code=" + code;
	console.log(url);
	document.getElementById('rs').innerHTML = "";
	let posting = $.post(url,{
		code: document.getElementById("code").value, 
		name: document.getElementById("name").value, 
		addr_state: document.getElementById("addr_state").value, 
		addr_city: document.getElementById("addr_city").value, 
		addr_b: document.getElementById("addr_b").value, 
		comment:document.getElementById("comment").value
	});
	posting.done(function(data) {
		console.log(data);
		document.getElementById('rs').innerHTML = data;
		if(data.indexOf("OK") >=0) {
			// location.reload();
			url = "./inc/device_tree.php?mode=list&query=1";
			postingx = $.post(url,{});
			postingx.done(function(data) {
				// console.log(data)
				document.getElementById("device_tree_main_panel").innerHTML=data;
				if(mode == 'add'){
					document.getElementById("mode").value = "modify";
				}					
			});
		}
	});	
}
function viewSquareInfoXX(pk) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	var url = "./admin.php?href=admin&fr=device_tree&mode=view&info=square";
	// console.log(url);
	var posting = $.post(url,{});
	posting.done(function(data) {
		document.getElementById("info_page").innerHTML = data;
	
		var url = "./inc/query.php?href=admin&fr=square&mode=view&pk=" + pk;
		console.log(url);

		$.getJSON(url, function(response) {
			ApplyJsonToField(response);
		});
	});	
}
function viewOpenhour(t) {
	console.log(t)
	if (t.checked) {
		document.getElementById('disp_open_hour').style.display='';
	}
	else {
		document.getElementById('disp_open_hour').style.display='none';
	}
}

function viewStoreInfo(code) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	if (code.indexOf('SQ') == 0) {
		console.log("add");
	}
	let url = "./inc/device_tree.php?mode=view&info=store&code=" + code;
	let posting = $.post(url,{});
	posting.done(function(data) {
		document.getElementById("info_page").innerHTML = data;
	});	
}


function modifyStore(code) {
	let mode = document.getElementById("mode").value;
	if (!document.getElementById("name").value) {
		document.getElementById("name").style.borderColor = "#FF0000";
		return;
	}		
	let url = "./inc/device_tree.php?mode="+mode+"&info=store&code=" + code;
	console.log(url);
	document.getElementById('rs').innerHTML = "";
	var posting = $.post(url,{
		code: document.getElementById("code").value, 
		name: document.getElementById("name").value, 
		phone: document.getElementById("phone").value, 
		fax: document.getElementById("fax").value, 
		contact_person: document.getElementById("contact_person").value, 
		contact_tel: document.getElementById("contact_tel").value, 
		addr_state: document.getElementById("addr_state").value, 
		addr_city: document.getElementById("addr_city").value, 
		addr_b: document.getElementById("addr_b").value, 
		apply_open_hour: document.getElementById("apply_open_hour").checked ? 'y':'n', 
		open_hour: document.getElementById("open_hour").value, 
		close_hour: document.getElementById("close_hour").value, 
		sniffing_mac: document.getElementById("sniffing_mac").value, 
		area: document.getElementById("area").value, 
		square_code: document.getElementById("square_code").value, 
		comment: document.getElementById("comment").value
	});
	posting.done(function(data) {
		console.log(data);
		document.getElementById('rs').innerHTML = data;
		if(data.indexOf("OK") >=0) {
			// setTimeout(() => location.reload(), 2000);
			// location.reload();
			url = "./inc/device_tree.php?mode=list&query=1";
			postingx = $.post(url,{});
			postingx.done(function(data) {
				// console.log(data)
				document.getElementById("device_tree_main_panel").innerHTML=data;
				if(mode == 'add'){
					document.getElementById("mode").value = "modify";
				}				
			});			
		}
	});	
}

function viewStoreInfox(pk, sqpk) {
	if (! sqpk) {
		sqpk = 0;
	}
	// console.log(sqpk);
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	var url = "./admin.php?href=admin&fr=device_tree&mode=view&info=store";
	console.log(url);
	var posting = $.post(url,{});
	posting.done(function(data) {
		document.getElementById("info_page").innerHTML = data;
		var url = "./inc/query.php?href=admin&fr=store&mode=view&pk=" + pk;
		if(sqpk){
			url += "&sqpk=" + sqpk;
		}
		// console.log(url);
		$.getJSON(url, function(response) {
			ApplyJsonToField(response);
		});	
	});	
}
function viewCameraInfoFields(response){
	z = document.getElementById("zone_config");
	z.style.background = 'url("' +  response["snapshot"] + '") no-repeat';
	z.style.backgroundSize = "800px 450px";	
	z.style.border = "1px";	
	draw_zone(z, response["zone"]);
	document.getElementById("regdate").innerHTML = response['regdate'];
	
	if (response['countrpt'] == 'y') {
		$('#countrpt_k').addClass('fa-check-square').removeClass('fa-square');
		document.getElementById('countrpt_k').style.color='#25d';
		document.getElementById("counter_label").style.display='';
		$("input:checkbox[id='enable_countingline']").removeAttr('disabled');
	}
	else {
		$("input:checkbox[id='enable_countingline']").attr("disabled","disabled");
	}

	if (response['face_det'] == 'y') {
		$('#face_det_k').addClass('fa-check-square').removeClass('fa-square');
		document.getElementById('face_det_k').style.color='#25d';
		$("input:checkbox[id='enable_face_det']").removeAttr('disabled');
	}
	else {
		$("input:checkbox[id='enable_face_det']").attr("disabled","disabled");
	}

	if (response['heatmap'] == 'y') {
		$('#heatmap_k').addClass('fa-check-square').removeClass('fa-square');
		document.getElementById('heatmap_k').style.color='#25d';
		$("input:checkbox[id='enable_heatmap']").removeAttr('disabled');
	}
	else {
		$("input:checkbox[id='enable_heatmap']").attr("disabled","disabled");
	}

	if (response['macsniff'] == 'y') {
		$('#macsniff_k').addClass('fa-check-square').removeClass('fa-square');
		document.getElementById('macsniff_k').style.color='#25d';
		$("input:checkbox[id='enable_macsniff']").removeAttr('disabled');
	}
	else {
		$("input:checkbox[id='enable_macsniff']").attr("disabled","disabled");
	}
	document.getElementById('counter_label').innerHTML = response['counter_table'];
	document.getElementById('store_code').innerHTML = response['store_options'];

	ApplyJsonToField(response);
}


function viewCameraInfo(code) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	let url = "./inc/device_tree.php?&mode=view&info=camera&code=" + code;
	console.log(url);
	var posting = $.post(url,{});
	posting.done(function(data) {
		// console.log(data);
		document.getElementById("info_page").innerHTML = data;
		// rs = data.split(/[<<,>>]/);
		// console.log(rs);
		dev_info = document.getElementById("device_info").value;
		url = "./inc/device_tree.php?mode=imageNzone&" + dev_info;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			imageNzone(response);
		});		
	});			

}

function viewCameraInfox(pk) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	var url = "./admin.php?href=admin&fr=device_tree&mode=view&info=camera&pk=" + pk;
	// console.log(url);
	var posting = $.post(url,{});
	posting.done(function(data) {
		document.getElementById("info_page").innerHTML = data;
		var url = "./inc/query.php?href=admin&fr=camera&mode=view&pk=" + pk;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			viewCameraInfoFields(response);
		});	
	});			

}
function showCounterLabel() {
	if(document.getElementById("enable_countingline").checked == true) {
		document.getElementById("counter_label").style.display ="";
	}
	else {
		document.getElementById("counter_label").style.display ="none";
	}
}

function set_counter_label(){
	ct_list = new Array();
	ct_old_list = new Array();
	lbl_list = new Array();
	document.getElementById('result_tag').innerHTML= '';
	for (i=0; i<10; i++) {
		ct = document.getElementById('ct['+i+']');
		if (ct == null) {
			continue;
		}
		else if (! ct.value.trim()) {
			continue;
		}
		ct_list.push(ct.value.trim());
		
		ct_old = document.getElementById('ct_old['+i+']');
		ct_old_list.push(ct_old.value.trim());

		lbl = document.getElementById('lbl['+i+']');
		lbl_list.push(lbl.value.trim());
	
	}
	console.log(ct_list, lbl_list);
	url = "/inc/query.php?fr=counter_label_set";
	var posting = $.post(url,{
		labels:ct_list,
		labels_old:ct_old_list,
		displays:lbl_list
		
	});
	posting.done(function(data) {
		console.log (data);
		document.getElementById('result_tag').innerHTML= data;
	});

}

function modifySquarexx() {
	var pk = document.getElementById("pk").value;
	if (!document.getElementById("name").value) {
		document.getElementById("name").style.borderColor = "#FF0000";
		return;
	}
	var url = "./inc/query.php?href=admin&fr=square&mode=modify&pk=" + pk;
	console.log(url);
	document.getElementById('rs').innerHTML = "";
	var posting = $.post(url,{
		pk: pk, 
		code: document.getElementById("code").value, 
		name: document.getElementById("name").value, 
		addr_state: document.getElementById("addr_state").value, 
		addr_city: document.getElementById("addr_city").value, 
		addr_b: document.getElementById("addr_b").value, 
		comment:document.getElementById("comment").value
	});
	posting.done(function(data) {
		console.log(data);
		document.getElementById('rs').innerHTML = data;
		if(data.indexOf("OK") >=0) {
			// rs = data.split(/[{,}]/);
			// rs.forEach(function(item){
			// 	if (item.indexOf("pk")>=0) {
			// 		pk = item.split("=")[1];
			// 	}
			// });
			// console.log(pk);
			location.reload();
			// setTimeout(location.reload(),1000);
			// viewSquareInfo(pk);

			
		}
	});	
}

function modifyStorexx() {
	var pk = document.getElementById("pk").value;
	if (!document.getElementById("name").value) {
		document.getElementById("name").style.borderColor = "#FF0000";
		return;
	}		
	var url = "./inc/query.php?href=admin&fr=store&mode=modify&pk=" + pk;
	console.log(url);
	document.getElementById('rs').innerHTML = "";
	var posting = $.post(url,{
		pk:pk, 
		code: document.getElementById("code").value, 
		name: document.getElementById("name").value, 
		phone: document.getElementById("phone").value, 
		fax: document.getElementById("fax").value, 
		contact_person: document.getElementById("contact_person").value, 
		contact_tel: document.getElementById("contact_tel").value, 
		addr_state: document.getElementById("addr_state").value, 
		addr_city: document.getElementById("addr_city").value, 
		addr_b: document.getElementById("addr_b").value, 
		open_hour: document.getElementById("open_hour").value, 
		close_hour: document.getElementById("close_hour").value, 
		sniffing_mac: document.getElementById("sniffing_mac").value, 
		area: document.getElementById("area").value, 
		square_code: document.getElementById("square_code").value, 
		comment: document.getElementById("comment").value
	});
	posting.done(function(data) {
		console.log(data);
		document.getElementById('rs').innerHTML = data;
		if(data.indexOf("OK") >=0) {
			// setTimeout(() => location.reload(), 2000);
			location.reload();
		}
	});	
}

function modifyDeviceInfo(dev_info) {
	document.getElementById('rs').innerHTML = "";
	if (!document.getElementById("name").value) {
		document.getElementById("name").style.borderColor = "#FF0000";
		document.getElementById('rs').innerHTML = "Type Camera Name";
		return;
	}		
	document.getElementById('delete_pad').style.display='none';

	let ct_list = (document.getElementById("ct_list").value).split(',');
	let counters = new Array();

	ct_list.forEach(function(item){
		if(item) {
			counters.push({'name':item, 'label':document.getElementById(item).value});
		}
	});
	console.log(counters);
	mode = document.getElementById("mode").value;
	let url = "./inc/device_tree.php?mode=" + mode + "&info=camera&" + dev_info;
	console.log(url);
	
	var posting = $.post(url,{
		code: document.getElementById("code").value, 
		name: document.getElementById("name").value, 
		mac: document.getElementById("mac").value, 
		usn: document.getElementById("usn").value, 
		model: document.getElementById("model").value, 
		brand: document.getElementById("brand").value, 
		product_id: document.getElementById("product_id").value, 
		store_code: document.getElementById("store_code").value, 
		enable_countingline: document.getElementById("enable_countingline").checked ? 'y': 'n', 
		enable_heatmap: document.getElementById("enable_heatmap").checked ? 'y' :'n', 
		enable_face_det: document.getElementById("enable_face_det").checked ? 'y' : 'n', 
		enable_macsniff: document.getElementById("enable_macsniff").checked ? 'y' : 'n', 
		enable_snapshot:'y',
		flag: document.getElementById("flag").checked ? 'y' : 'n', 
		comment: document.getElementById("comment").value,
		counters: counters,
		update_old_count_data: 'y'
	});
	posting.done(function(data) {
		// console.log(data);
		document.getElementById('rs').innerHTML = data;
		if(data.toUpperCase().indexOf("FAIL") < 0) {
			// setTimeout(() => location.reload(), 2000);
			// location.reload();
			url = "./inc/device_tree.php?mode=list&query=1";
			postingx = $.post(url,{});
			postingx.done(function(data) {
				// console.log(data)
				document.getElementById("device_tree_main_panel").innerHTML=data;
				if(mode == 'add'){
					document.getElementById("mode").value = "modify";
				}
			});
		}			
	});
}	


// function modifyDeviceInfoxx() {
// 	var pk = document.getElementById("pk").value;
// 	if (!document.getElementById("name").value) {
// 		document.getElementById("name").style.borderColor = "#FF0000";
// 		return;
// 	}		
// 	document.getElementById('delete_pad').style.display='none';

// 	var ct_list = (document.getElementById("ct_list").value).split(',');
// 	var ct_labels = new Array();
// 	var ct_names = new Array();
// 	for(i=0; i<ct_list.length; i++) {
// 		// console.log(ct_list[i]);
// 		if(ct_list[i]){
// 			ct_names.push(ct_list[i]);
// 			ct_labels.push(document.getElementById(ct_list[i]).value);
// 		}
// 	}
// 	var url = "./inc/query.php?href=admin&fr=camera&mode=modify&pk=" + pk;
// 	console.log(url);
// 	console.log('HH');
// 	document.getElementById('rs').innerHTML = "";
// 	var posting = $.post(url,{
// 		pk: pk,
// 		code: document.getElementById("code").value, 
// 		name: document.getElementById("name").value, 
// 		mac: document.getElementById("mac").value, 
// 		usn: document.getElementById("usn").value, 
// 		model: document.getElementById("model").value, 
// 		brand: document.getElementById("brand").value, 
// 		product_id: document.getElementById("product_id").value, 
// 		store_code: document.getElementById("store_code").value, 
// 		// enable_countingline: document.getElementById("enable_countingline").checked ? 'y' :'n', 
// 		enable_countingline: 'y', 
// 		enable_heatmap: document.getElementById("enable_heatmap").checked, 
// 		enable_face_det: document.getElementById("enable_face_det").checked, 
// 		enable_macsniff: document.getElementById("enable_macsniff").checked, 
// 		flag: document.getElementById("flag").checked, 
// 		comment: document.getElementById("comment").value,
// 		ct_labels: ct_labels,
// 		ct_names: ct_names,
// 		update_old_count_data: 'y'
// 	});
// 	posting.done(function(data) {
// 		console.log(data);
// 		document.getElementById('rs').innerHTML = data;
// 		if(data.indexOf("FAIL") < 0) {
// 			// location.reload();
// 			// setTimeout(() => location.reload(), 2000);
// 		}			
// 	});
// }	

function deleteInfo() {
	document.getElementById('rs').innerHTML = '';
	let code = document.getElementById("code").value;
	let fr = document.getElementById("fr").value;
	let passwd = document.getElementById("admin_password").value.trim();
	if (!passwd) {
		document.getElementById("admin_password").style.borderColor = "#FF0000";
		return false;
	}
	let url = "./inc/device_tree.php?fr=" + fr + "&mode=delete";
	console.log(url);	
	let posting = $.post(url,{
		code: code,
		passwd: passwd
	});
	posting.done(function(data) {
		console.log(data);
		if(data.indexOf("confirmation OK") >=0) {
			let c = confirm(data.split(/[{,}]/)[1]);
			if (c) {
				url = "./inc/device_tree.php?fr=" + fr + "&mode=delete_act";
				let postingx = $.post(url,{
					code: code,
				});
				postingx.done(function(data) {
					console.log(data);
					if (data.indexOf("delete OK") >=0 && data.indexOf("Fail")<0) {
						location.reload();
					}
				});
			}
		}
		else {
			alert (data);
		}
	});	
}
// function deleteInfoxx() {
// 	document.getElementById('rs').innerHTML = '';
// 	var pk = document.getElementById("pk").value;
// 	var fr = document.getElementById("fr").value;
// 	var passwd = document.getElementById("admin_password").value;
// 	var url = "./inc/query.php?href=admin&fr="+ fr +"&mode=delete&pk=" + pk;
// 	console.log(url);	
// 	var posting = $.post(url,{passwd:passwd});
// 	posting.done(function(data) {
// 		console.log(data);
// 		if(data.indexOf("delete OK") >=0) {
// 			location.reload();
// 		}
// 		else {
// 			document.getElementById('rs').innerHTML = data;
// 		}
// 	});	
// }

function showDeviceInfo(t, fpk){
	console.log(t)
	document.getElementById('deviceinfoTag').style.display="";
	document.getElementById('result').innerHTML="";

	if (fpk == 0) { // add camera manually
		document.getElementById('deviceinfotab_head').innerHTML="ADD";
		document.getElementById('mode').value = 'add';
		document.getElementById('IP').value = "";
		document.getElementById('userid').value = "root";
		document.getElementById('passwd').value = "pass";
		document.getElementById('fpk').value = 0;
	}
	else {
		document.getElementById('deviceinfotab_head').innerHTML="MODIFY";
		document.getElementById('mode').value = 'modify';
		document.getElementById('fpk').value = fpk;
		console.log(fpk);
		const url = "/inc/query.php?fr=camera&mode=view_simple&fpk=" + fpk;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			document.getElementById('IP').value = response['url'];
			document.getElementById('userid').value = response['user_id'];
			document.getElementById('passwd').value = response['user_pw'];
		});
	}
}

function checkDevice(t){
	const IP = document.getElementById('IP').value.trim();
	const userid =  document.getElementById('userid').value.trim();
	const passwd = document.getElementById('passwd').value.trim();
	const mode = document.getElementById('mode').value;
	const fpk = document.getElementById('fpk').value;

	document.getElementById('result').innerHTML="";

	if (!IP) {
		document.getElementById('result').innerHTML="CheckIP";
		return false;
	}
	// var url = "/inc/query.php?fr=modify_floating_camera";
	var url = "/inc/query.php?fr=camera&mode=" + mode + "&fpk=" + fpk;
	console.log(url);
	var posting = $.post(url,{
		IP: IP,
		userid: userid, 
		passwd: passwd, 
	});
	posting.done(function(data) {
		console.log(data);
		document.getElementById('result').innerHTML=data;
	});
}


function floatingCamera(st_code) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	var url = "./inc/device_tree.php?mode=floating_camera&st_code=" + st_code;
	console.log(url);
	document.getElementById("info_page").innerHTML = "";
	var posting = $.post(url,{});
	posting.done(function(data) {
//			console.log(data);
		document.getElementById("info_page").innerHTML = data;
	});	
}

// function floatingCamerxxx(st_code) {
// 	scroll_info();
// 	document.getElementById('delete_pad').style.display='none';
// 	var url = "./inc/query.php?href=admin&fr=floating_camera&mode=list&st_code=" + st_code;
// 	console.log(url);
// 	document.getElementById("info_page").innerHTML = "";
// 	var posting = $.post(url,{});
// 	posting.done(function(data) {
// 		console.log(data);
// 		document.getElementById("info_page").innerHTML = data;
// 	});	
// }

function imageNzone(response){
	z = document.getElementById("zone_config");
	z.style.background = 'url("' +  response["snapshot"] + '") no-repeat';
	z.style.backgroundSize = "800px 450px";	
	z.style.border = "1px";	
	draw_zone(z, response["zone"]);

}

function addDeviceToStore(st_code, dev_info) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';
	document.getElementById('rs').innerHTML = '';
	let url = "./inc/device_tree.php?&mode=view&info=camera&code=0&st_code=" + st_code + "&" + dev_info;
	console.log(url);
	document.getElementById("info_page").innerHTML = "";
	var posting = $.post(url,{});
	posting.done(function(data) {
		// console.log(data);
		document.getElementById("info_page").innerHTML = data;
		var url = "./inc/device_tree.php?mode=imageNzone&" + dev_info;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			imageNzone(response);
		});
	
	});

	// var url = "./inc/query.php?href=admin&fr=floating_camera&mode=addToStore&st_code=" + st_code + "&" + dev_info;
	
	// console.log(url);
	// var posting = $.post(url,{});
	// posting.done(function(data) {
	// 	console.log(data);
	// 	if(data.indexOf("OK") >=0) {
	// 		// location.reload();
	// 	}
	// 	// document.getElementById("info_page").innerHTML = data;
	// });	
}


// function addDeviceToStorexx(st_code, dev_info) {
// 	document.getElementById('delete_pad').style.display='none';
// 	var url = "./admin.php?href=admin&fr=device_tree&mode=view&info=camera&pk=0";
// 	console.log(url);
// 	document.getElementById("info_page").innerHTML = "";
// 	var posting = $.post(url,{});
// 	posting.done(function(data) {
// 		document.getElementById("info_page").innerHTML = data;
// 		var url = "./inc/query.php?href=admin&fr=floating_camera&mode=view&st_code=" + st_code + "&" + dev_info;
// 		console.log(url);
// 		$.getJSON(url, function(response) {
// 			viewCameraInfoFields(response);
// 		});
	
// 	});

// 	// var url = "./inc/query.php?href=admin&fr=floating_camera&mode=addToStore&st_code=" + st_code + "&" + dev_info;
	
// 	// console.log(url);
// 	// var posting = $.post(url,{});
// 	// posting.done(function(data) {
// 	// 	console.log(data);
// 	// 	if(data.indexOf("OK") >=0) {
// 	// 		// location.reload();
// 	// 	}
// 	// 	// document.getElementById("info_page").innerHTML = data;
// 	// });	
// }

function viewDeviceParamDetail(dev_info) {
	scroll_info();
	document.getElementById('delete_pad').style.display='none';		
	document.getElementById('rs').innerHTML = '';
	var url = "./admin.php?href=admin&fr=view_param&" + dev_info;
	window.open(url, 'info');
}

// ##############  web page config 
if (Get['fr'] == 'webpageConfig' ){
	function checkedLabels(ex_label, section, i){
		xar = [];
		for(j=0; j<ex_label.length; j++) {
			label_id = document.getElementById(section + '[' + i + '][' + ex_label[j] + ']');
			if (label_id && label_id.checked ) {
				xar.push(ex_label[j]);
			}
		}
		return xar;
	}
	function changeOption(t){
		console.log(Get['fr'], Get['db'], t.name, t.value, t.checked);
		if(Get['db'] == 'basic'){
			url = "/inc/query.php?fr=webpageConfig&db=basic&mode=update&name=" + t.name;
			console.log(url);
			arr = {};
			arr['document_title'] = document.getElementById('document_title').value;
			arr['host_title'] = document.getElementById('host_title').value;
			arr['title_logo'] = document.getElementById('title_logo').value;
			arr['developer'] = document.getElementById('developer').value;

			document.getElementById('basic_result').innerHTML = "";
			var posting = $.post(url,{
				arr:arr,
			});
			posting.done(function(data) {
				console.log(data);
				document.getElementById('basic_result').innerHTML = data;
			});				
		}
		else if(Get['db'] == 'sidemenu'){
			url = "/inc/query.php?fr=webpageConfig&db=sidemenu&mode=update&name=" + t.name + "&check=" + t.checked;
			console.log(url);
			var posting = $.post(url,{
			});
			posting.done(function(data) {
				console.log(data);
			});	
		}
		else if(Get['db'] == 'dashboard'){
			document.getElementById('card_banner_result').innerHTML = "";
			document.getElementById('footfall_result').innerHTML = "";
			document.getElementById('third_block_result').innerHTML = "";

			label_list_str = document.getElementById('label_list').value;
			ex_label = label_list_str.split(',');
			var arr = new Array();

			if (t.value == 'card_banner'){
				for (i=0; i<4; i++) {
					arr.push({
						title: document.getElementById("card_banner["+i+"][title]").value,
						display: document.getElementById("card_banner["+i+"][display]").value, 
						labels: checkedLabels(ex_label, t.value, i), 
						badge: document.getElementById("card_banner["+i+"][badge]").value, 
						color: document.getElementById("card_banner["+i+"][color]").value
					});
				}	
				postdata = arr;
			}
			else if (t.value == 'footfall'){
				for (i=0; i<4; i++) {
					arr.push({
						title: document.getElementById("footfall["+i+"][title]").value,
						display: document.getElementById("footfall["+i+"][display]").value, 
						labels: checkedLabels(ex_label, t.value, i), 
					});
				}
				postdata = {
					data:arr,
					main_title:document.getElementById("footfall_title").value
				};
			}
			else if (t.value == 'third_block'){
				selection = document.getElementById('third_block').value
				// console.log(selection);
				if (selection == 'curve_by_label'){
					for (i=0; i<6; i++){
						arr.push({
							display: document.getElementById("curve_label["+i+"][display]").value, 
							labels: checkedLabels(ex_label, 'curve_label', i), 
							color: document.getElementById("curve_label["+i+"][color]").value
						});
					}
				}
				postdata = {
					selection: selection,
					display: document.getElementById('third_block_display').value,
					data: arr,
				}

			}
			// console.log(postdata);
			url = "/inc/query.php?fr=webpageConfig&db=dashboard&mode=update&name=" + t.name;
			console.log(url);
			var posting = $.post(url, {postdata:postdata});
			posting.done(function(data) {
				// console.log(data);
				document.getElementById(t.name + '_result').innerHTML = data;
			});	
		}

		else if(Get['db'] == 'analysis'){
			document.getElementById('analysis_result').innerHTML = "";
			arr = {};
			list_label = document.getElementById('label_list').value;
			list_page = document.getElementById('page_list').value;
			// console.log(list_label, list_page);
			ex_labels = list_label.split(",");
			ex_pages = list_page.split(",");
			ex_pages.forEach(function(item){
				if (item == 'age_group' || item == 'traffic_reset_hour' ) {
					display='';
					labels = document.getElementById(item).value;
				}
				else {
					display = document.getElementById(item+'[display]').value;
					labels =  checkedLabels(ex_labels, item, 0);
				}
				arr[item] = {
					labels: labels,
					display: display
				};
			});
			postdata = arr;
			url = "/inc/query.php?fr=webpageConfig&db=analysis&mode=update&name=" + t.name;
			console.log(url);
			
			var posting = $.post(url,{postdata:postdata});
			posting.done(function(data) {
				console.log(data);
				document.getElementById('analysis_result').innerHTML = data;
			});				
			return false;
			for(i=0; i<ex_pages.length; i++){
				ex_pages[i] = ex_pages[i].trim();
				if (!ex_pages[i]){
					continue;
				}
				arr[ex_pages[i]] = {label:'', labels:[], display:''};

				for (j=0, n=0; j<ex_labels.length; j++){
					ex_labels[j] = ex_labels[j].trim();
					if (!ex_labels[j]) {
						continue;
					}
					id_tag = ex_pages[i] + '[' + ex_labels[j] + ']';
					if (document.getElementById(id_tag).checked) {
						arr[ex_pages[i]]['labels'].push(ex_labels[j]);
						if(arr[ex_pages[i]]['label']) {
							arr[ex_pages[i]]['label'] += ',';
						}
						arr[ex_pages[i]]['label'] += ex_labels[j];
					}
				}
				arr[ex_pages[i]]['display'] = document.getElementById(ex_pages[i] + '[display]').value;
			}
			t =  document.getElementById('age_group').value;
			console.log(t);
			arr['age_gender'] = {'age_group': document.getElementById('age_group').value};
			console.log(arr);

			url = "/inc/query.php?fr=webpageConfig&db=analysis&mode=update&name=" + t.name;
			console.log(url);
			document.getElementById('analysis_result').innerHTML = "";
			var posting = $.post(url,{
				arr: arr,
			});
			posting.done(function(data) {
				console.log(data);
				document.getElementById('analysis_result').innerHTML = data;
			});	

		}
		else if(Get['db'] == 'report'){


		}
	}
}