// function getUrlVars() {
//     var vars = [], hash;
//     var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
//     for (var i = 0; i < hashes.length; i++) {
//         hash = hashes[i].split('=');
//         // vars.push(hash[0]);
//         vars[hash[0]] = hash[1];
//     }
//     return vars;
// }
// Get = getUrlVars();

function debugLog(str){
	if (Get['debug']){
		return console.log(str);
	}
}

console.log(Get);

const a = document.getElementById(Get['fr']);
const b = '';	

if (a) {
	a.classList.add("active");
}

const thisDate = new Date();
const thisTime = new Date().getTime();

let configDateTo ={
	singleDatePicker: true,
	showDropdowns: true,
	"locale": date_picker_option_locale[_selected_language],
};
let configDateFrom ={
	singleDatePicker: true,
	showDropdowns: true,
	"locale": date_picker_option_locale[_selected_language],
};



document.addEventListener("DOMContentLoaded", function(event) {
	$("input[name=\"refdate\"]").daterangepicker(configDateTo);
	$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);
});

function addJavascript(jsname) {
	let th = document.getElementsByTagName('head')[0];
	let s = document.createElement('script');
	s.setAttribute('type','text/javascript');
	s.setAttribute('src',jsname);
	th.appendChild(s);
}

function listSquare(squareId) {
  	let url_query = "./inc/query.php?f=square";
	if(!squareId) {
		squareId = "#square";
	}
	$.getJSON(url_query, function(data) {
		// console.log(data);
		data.forEach(function(item){
			// console.log(item);
			$(squareId).append("<option value='"+ item.code + "'>"+ item.name +"</option>");
		});
	});	
}

// function listSquarex(squareId) {
// 	let url_query = "./inc/query.php?f=square";
//   if(!squareId) {
// 	  squareId = "#square";
//   }
//   $.getJSON(url_query, function(data) {
// 	  for(i=0; i< data["code"].length; i++) {
// 		  $(squareId).append("<option value='"+ data["code"][i] + "'>"+ data["name"][i] +"</option>");
// 	  }
//   });	
// }

function timeToDatetime(arr_label, dateformat) {
	let cat_res = new Array();
	for(let i=0; i<arr_label.length; i++) {
		const outDate = moment(new Date(arr_label[i]*1000)).format(dateformat);
		cat_res.push(outDate);
	}
	return cat_res;
}


function viewSnapshot(e, ix){
	// console.log(e.src);
	const dev_info = document.getElementById('modal_device_info');
	const id = document.getElementById('modal_snapshot');
	if (dev_info) {
		dev_info.innerHTML = '<a href = "admin.php?fr=view_param&' + ix + '" target="aaa">' + ix +'</a>';
	}
	id.innerHTML = '<img src="' + e.src + '" height="620" />';
}

// function viewSnapshot(e){
// 	const id = document.getElementById('snapShot');
// 	id.innerHTML = '<img src="' + e.src + '" height="620" />';
// }

function changeSpot(num){
	if(!num) {
		id = "square";
		store = "store";
	}
	else if( num == 1) {
		id = "square1";
		store = "store1";
	}
	else if( num == 2) {
		id = "square2";
		store = "store2";
	}
	else if( num == 3) {
		id = "square3";
		store = "store3";
	}
	
	const square = document.getElementById(id).value;
	const st_id = document.getElementById(store);
	st_id.length = 1;
	const url = "./inc/query.php?f=store&sq_code=" + square;

	$.getJSON(url, function(data) {
		data.forEach(function(item){
			st_id.add(new Option (item.name, item.code));
		});
	});
	if(Get['fr'] == "heatmap") {
		listDevice();
	}
	else {
		doAnalysis();
	}
}  

function changeSpotx(num){
	if(!num) {
		id = "square";
		store = "store";
	}
	else if( num == 1) {
		id = "square1";
		store = "store1";
	}
	else if( num == 2) {
		id = "square2";
		store = "store2";
	}
	else if( num == 3) {
		id = "square3";
		store = "store3";
	}
	
	const square = document.getElementById(id).value;
	const st_id = document.getElementById(store);
	st_id.length = 1;
	const url = "./inc/query.php?f=store&sq_code=" + square;

	$.getJSON(url, function(data) {
		for (i=0; i<data["code"].length; i++) {
			st_id.add(new Option (data["name"][i], data["code"][i], i+1));
		}
	});
	if(Get['fr'] == "heatmap") {
		listDevice();
	}
	else {
		doAnalysis();
	}
}  

function changeStore() {
	if(Get['fr'] == "heatmap") {
		listDevice();
	}
	else {
		doAnalysis();
	}
}


let refresh = 1;
function changeDate(ref) {
	// console.log(refresh);
	if(refresh) {
		refresh -=1;
		return false;
	}
	const view_by = document.getElementById("view_by").value;
	// console.log(view_by, ref);

	if((ref == -1) || (ref == 1)) {
		const ref_time = document.getElementById("refdate").value;
		const d_t = new Date(Date.parse(ref_time));

		if(view_by == "month") {
			const myDate = new Date(d_t.getFullYear(),d_t.getMonth() + ref + 1,0, 0,0,0,0);
			// console.log(myDate.getTime() - (new Date(thisDate.getFullYear(),thisDate.getMonth(),0,0,0,0,0)));
			if((myDate.getTime() - (new Date(thisDate.getFullYear(),thisDate.getMonth()+1,1,0,0,0,0))) >0) {
				return false;
			}
			configDateTo.startDate = moment(myDate).format(date_picker_option_locale[_selected_language].format);
		}
		else {
			const myDate = new Date(d_t.getFullYear(),d_t.getMonth(),d_t.getDate()+ref, 0,0,0,0);
			if((myDate.getTime() - thisDate.getTime()) >0) {
				return false;
			}
			configDateTo.startDate = moment(myDate).format(date_picker_option_locale[_selected_language].format);
		}
		// console.log(configDateTo.startDate );
		$("input[name=\"refdate\"]").daterangepicker(configDateTo);
	}
	
	else if( (ref == -2) || (ref == 2)) {
		const ref_time = document.getElementById("refdate_from").value;
		const d_t = new Date(Date.parse(ref_time));
		const myTime = d_t.getTime();
		if(myTime > thisTime) {
//			ref = 0;
//			return false;
		}

		if(view_by == "month") {
			configDateFrom.startDate = moment(new Date(d_t.getFullYear(),d_t.getMonth()+(ref/2), 1, 0,0,0,0)).format(date_picker_option_locale[_selected_language].format);
		}
		else {
			configDateFrom.startDate = moment(new Date(d_t.getFullYear(),d_t.getMonth(),d_t.getDate()+(ref/2), 0,0,0,0)).format(date_picker_option_locale[_selected_language].format);
		}
		// console.log(configDateFrom.startDate );
		$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);
	}
	else {
		doAnalysis();
	}
}

function changeViewBy(view_by) {
	document.getElementById("view_by").value = view_by;
	if(document.getElementById("tenmin")) {
		document.getElementById("tenmin").style.backgroundColor = "";
	}
	if(document.getElementById("hour")) {
		document.getElementById("hour").style.backgroundColor="";
	}
	if(document.getElementById("day")) {
		document.getElementById("day").style.backgroundColor="";
	}
	if(document.getElementById("week")) {
		document.getElementById("week").style.backgroundColor="";
	}
	if(document.getElementById("month")) {
		document.getElementById("month").style.backgroundColor="";
	}
	document.getElementById(view_by).style.backgroundColor="#fcc100";

	if(view_by == "tenmin") {
		document.getElementById("date_additional").style.display='none';
		configDateTo.startDate = moment(new Date()).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate\"]").daterangepicker(configDateTo);
		configDateFrom.startDate = moment(new Date()).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);	

	}
	else if(view_by == "hour") {
		document.getElementById("date_additional").style.display='none';
		const ref_time = document.getElementById("refdate").value;
		const d_t = new Date(Date.parse(ref_time));
		configDateTo.startDate = moment(new Date()).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate\"]").daterangepicker(configDateTo);
		configDateFrom.startDate = moment(new Date(d_t.getFullYear(),d_t.getMonth(),d_t.getDate()-1, 0,0,0,0)).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);	
	}
	else if (view_by == "day") {
		document.getElementById("date_additional").style.display='';
		const ref_time = document.getElementById("refdate").value;
		const d_t = new Date(Date.parse(ref_time));
		configDateTo.startDate = moment(new Date()).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate\"]").daterangepicker(configDateTo);
		configDateFrom.startDate = moment(new Date(d_t.getFullYear(),d_t.getMonth(),d_t.getDate()-7, 0,0,0,0)).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);		
	}
	else if(view_by == "month") {
		document.getElementById("date_additional").style.display='';
		const ref_time = document.getElementById("refdate").value;
		const d_t = new Date(Date.parse(ref_time));
		configDateTo.startDate = moment(new Date()).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate\"]").daterangepicker(configDateTo);
		configDateFrom.startDate = moment(new Date(d_t.getFullYear()-1,d_t.getMonth(),1, 0,0,0,0)).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);		
	}
	
	if(document.getElementById("fr").value == 'trendAnalysis') {
		document.getElementById("date_additional").style.display='none';
	}
}

function arraySum(arr) {
	let sum = 0;
	arr.forEach(function(item){
		sum += Number(item);
	});
	return sum;
}
// function maxFromArray(arr) {
function arrayMax(arr) {
	let max = 0;
	arr.forEach(function(item){
		if (Number(item) > max) {
			max = Number(item);
		}
	});
	return max;
}

// var	tooltip_time = "HH:mm"; 
// var	tooltip_date = "yyyy-MM-dd"; 
// var	tooltip_datetime = "yyyy-MM-dd HH:mm"; 

const colors_ref = [ '#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'];
const colors_gender_ref = ['rgb(54, 162, 235)', 'rgb(255, 99, 132)'];
const colors_age_ref =  ['rgb(255, 99, 132)', 'rgb(255, 159, 64)', 'rgb(255, 205, 86)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)', 'rgb(201, 203, 207)', 'rgb(60, 60, 60)','rgb(255,255,255)'];

let options_curve = {
	chart: {
		height: 180,
		type: "line",
		zoom:{ enabled:false, },
		toolbar: {
			show: false,
		},
	},
	colors: colors_ref,
	dataLabels: {enabled: true,	},
	series: [],
	title: { text: "", },
	legend: { show:true, showForSingleSeries: true, position:"top", offsetX: 0, floating: true,},
	stroke: { curve: "smooth", width:3,},
	markers: { size:0 },
	noData: { text: "Loading..." },
	xaxis: {
		type: "datetime",
		labels:{
			show:true,
			showDuplicates: true,
			datetimeFormatter: {
				year: "yyyy",
				month: "yyyy-MM",
				day: "MM/dd",
				hour: "HH:mm",
			},
		}		
	},
	tooltip: {
		y:{
			enable: true,
			formatter: function (val) {
				if(val) {
					const reg = /(^[+-]?\d+)(\d{3})/; 
					val +='';
					while (reg.test(val)) {
						val = val.replace(reg, '$1' + ',' + '$2'); 
					}
					return val;
				}
			},
		},
	},
	dataLabels: {
		enabled: true,	
		formatter: function (val) {
			const reg = /(^[+-]?\d+)(\d{3})/; 
			val +='';
			while (reg.test(val)) {
				val = val.replace(reg, '$1' + ',' + '$2'); 
			}
			return val;
		},
	}	
};	

let options_area = {
	series: [],
	chart: {
		type: 'area',
		height: 350,
		stacked: true,
		zoom:{ enabled:false, },
		toolbar: {
			show: false,
		},
		events: {
			// selection: function (chart, e) {
			// 	debugLog(new Date(e.xaxis.min))
			// }
		},
	},
	colors: colors_ref,
	dataLabels: { enabled: false},
	stroke: { curve: "smooth", width:0,},
	fill: {
		type: 'solid',
		// gradient: {
		// 	// shade: 'dark',
		// 	type: "vertical",
		// 	shadeIntensity: 0.1,
		// 	gradientToColors: undefined, // optional, if not defined - uses the shades of same color in series
		// 	inverseColors: true,
		// 	opacityFrom: 1,
		// 	opacityTo: 1,
		// 	// stops: [0, 50, 100],
		// 	colorStops: []
		// } 
	},
	legend: { show:true, showForSingleSeries: true, position:"top", offsetX: 0, offsetY:-5, horizontalAlign: 'center', floating: true,},
	xaxis: {
		type: 'datetime',
		labels:{
			show:true,
			showDuplicates: true,
			datetimeFormatter: {
				year: "yyyy",
				month: "yyyy-MM",
				day: "MM/dd",
				hour: "HH:mm",
			},
		},
	},
	grid: {show: false,	}	
};

let options_bar = {
	chart: { type: "bar", height: 180, zoom:{ enabled:false, }, toolbar: {show: false,}},
	series: [],
	colors: colors_ref,
	legend: { show:true, showForSingleSeries: true, position:"top", offsetX: 0, floating: true,},
	plotOptions: {
		bar: {
			columnWidth: '75%',
			// distributed: true,
			dataLabels: {
				enabled: true,
				position: "top",
			},				
		}
	},
	dataLabels: {
	  enabled: true,
	  formatter: function (val) {
		const reg = /(^[+-]?\d+)(\d{3})/; 
		val +='';
		while (reg.test(val)) {
			val = val.replace(reg, '$1' + ',' + '$2'); 
		}
		return val;
	  },		  
	  offsetY: -16,
	  style: {
		fontSize: '12px',
		colors: ["#A04758"]
	  }
	},
	stroke: {
		show: true,
		width: 1,
		colors: ['transparent']
	},
	xaxis: {
		type: 'category',
		categories: [],
		labels: {
			show:true,
//			rotate: -30,
//			rotateAlways: true,
			trim:true,
			style: {
				colors: [],
				fontSize: '12px',
				fontFamily: 'Helvetica, Arial, sans-serif',
				cssClass: 'apexcharts-xaxis-label',
			},
			// offsetY: -8,
			minHeight: 30,
		},
	},
	// xaxis: {
	// 	type: "datetime",
	// 	labels:{
	// 		show: true,
	// 		trim: true,
	// 		showDuplicates: true,
	// 		datetimeFormatter: {
	// 			year: "yyyy",
	// 			month: "yyyy-MM",
	// 			day: "MM-dd",
	// 			hour: "HH:mm",
	// 		},
	// 	}		
	// },	
	yaxis: {
		floating: false,
		labels: {
			show: true,
			align: 'right',
			minWidth: 0,
			maxWidth: 160,
			style: {
				colors: [],
				fontSize: '12px',
				fontFamily: 'Helvetica, Arial, sans-serif',
				cssClass: 'apexcharts-yaxis-label',
			},
			offsetX: 0,
			offsetY: 0,
			rotate: 0,
			formatter: function (val) {
				return Math.round(val);				 
			},
		},
	},
	grid: {
		show: true,
		xaxis: {
			lines: { show: true, }
		}, 			
		yaxis: {
			lines: { show: true }
		}, 			
	}
};

function mkTable(response, viewby) {
	let str_table = '<tr><th>Datetime</th>';
	response['data'].forEach(function(item){
		str_table += '<th>'+ item.name +'</th>';
	});
	str_table += '</tr>';

	if (viewby == 'tenmin' || viewby == 'hour') {
		date_format = datetime_picker_option_locale[_selected_language].format;
	}
	else {
		date_format = date_picker_option_locale[_selected_language].format;
	}
	response['category']['timestamps'].forEach(function(item_ts, i){
		date_string = moment(new Date((item_ts-3600*8)*1000)).format(date_format);
		str_table += '<tr>';
		str_table += '<td>' + date_string + '</td>';
		response['data'].forEach(function(item_data){
			if (item_data.data[i] == null){
				item_data.data[i] = '';
			}
			str_table += '<td>' + item_data.data[i] +'</td>';
		});
		str_table += '</tr>';
	});
	str_table ='<table class="table table-striped table-bordered table-hover no-margin">'+str_table+'</table>';
	
	return str_table;
}	

// function curve_chart(chart_id='', table_id='', response, view_by='hour', chart_height=500, stroke_width=3, updoption={}){
function curve_chart(chart_id, table_id, response, viewby, updoption){
	viewby = typeof viewby !== 'undefined' ? viewby : 'hour';
	updoption = typeof updoption !== 'undefined' ? updoption : {};

	if (chart_id) {
		
		const chart = new ApexCharts(document.querySelector("#"+chart_id), options_curve);
		chart.render();		
		const tooltip_fmt = datetime_tooltip[_selected_language][viewby];

		chart.updateSeries(response["data"]);
		chart.updateOptions({
			title: {text: response["title"]["chart_title"], },
		});
		if (viewby == 'tenmin' || viewby=='hour' || viewby == 'day' || viewby=='week' || viewby == 'month' || viewby == 'year'){
			chart.updateOptions({
				xaxis: { categories: timeToDatetime(response["category"]['timestamps'], datetime_picker_option_locale[_selected_language].format),},
				tooltip: { x: { format: tooltip_fmt, },}
			});
		}
		else {
			chart.updateOptions({  xaxis: {type: 'category',categories : viewby,} });
		}		
		if(updoption){
			chart.updateOptions(updoption);
		}
	}

	if (table_id) {
		document.getElementById(table_id).innerHTML = mkTable(response, viewby);
	}	
}

function bar_chart(chart_id, table_id, response, viewby, updoption)	{
	viewby = typeof viewby !== 'undefined' ? viewby : 'hour';
	updoption = typeof updoption !== 'undefined' ? updoption : {};

	if (chart_id) {
		const chart = new ApexCharts(document.querySelector("#"+ chart_id), options_bar);
		chart.render();
		const tooltip_fmt = datetime_tooltip[_selected_language][viewby];

		chart.updateOptions({title: {text: response["title"]["chart_title"], },	});
		if (viewby == 'tenmin' || viewby=='hour' || viewby == 'day' || viewby=='week' || viewby == 'month' || viewby == 'year'){
			chart.updateOptions({
				xaxis: { categories: timeToDatetime(response["category"]['timestamps'], datetime_picker_option_locale[_selected_language].format),},
				tooltip: { x: { format: tooltip_fmt, },}
			});
		}
		else {
			chart.updateOptions({ xaxis: {type: 'category',categories : viewby,} });
		}

		chart.updateSeries(response['data']);	
		if (updoption) {
			chart.updateOptions(updoption);
		}
	}
	if (table_id) {
		document.getElementById(table_id).innerHTML = mkTable(response, viewby);
	}	

}

function area_chart(chart_id, table_id, response, view_by, updoption){
	viewby = typeof viewby !== 'undefined' ? viewby : 'hour';
	updoption = typeof updoption !== 'undefined' ? updoption : {};

	if (chart_id) {
		const chart = new ApexCharts(document.querySelector("#"+chart_id), options_area);
		chart.render();		
		const tooltip_fmt = datetime_tooltip[_selected_language][view_by];		
		chart.updateSeries(response["data"]);
		chart.updateOptions({ title:{ text: response["title"]["chart_title"], }, });
		if (viewby == 'tenmin' || viewby=='hour' || viewby == 'day' || viewby=='week' || viewby == 'month' || viewby == 'year'){
			chart.updateOptions({
				xaxis: { categories: timeToDatetime(response["category"]['timestamps'], datetime_picker_option_locale[_selected_language].format),},
				tooltip: { x: { format: tooltip_fmt, },}
			});
		}
		else {
			chart.updateOptions({ xaxis: {type: 'category',categories : viewby,} });
		}		
		if(updoption){
			chart.updateOptions(updoption);
		}
	}

	if (table_id) {
		document.getElementById(table_id).innerHTML = mkTable(response, viewby);
	}	
}

function age_bar_graph(graph_id, response, updoption){
	updoption = typeof updoption !== 'undefined' ? updoption : {};

	const age_graph = new ApexCharts(document.querySelector("#"+graph_id), options_bar);
	age_graph.render();		
	let total = arraySum(response['data'][0]['data']);
	age_graph.updateSeries(response['data']);
	
	age_graph.updateOptions({
		chart: { type: "bar", height: 200},
		colors: colors_age_ref,
		dataLabels: {
			enabled: true,
			formatter: function (val) {
				return Math.round(val*100/total)+"%";
			},		  
			offsetY: -16,
			style: { fontSize: '12px', colors: ["#2047F8"] }
		},	
		xaxis: { type: "categories", categories:response['category']['labels'],	},
		grid: { yaxis: { lines: { show: false }}, },
		plotOptions: {
			bar: {
				columnWidth: '75%',
				distributed: true,
			},
		},				
	});
	if(updoption) {
		age_graph.updateOptions(updoption);
	}
}


if(Get['fr'] == "dashboard") {	
	function doAnalysis() {
		let time_ref = document.getElementById("refdate").value;
		const square = document.getElementById("square").value;
		const store = document.getElementById("store").value;

		let url = "./inc/query.php?fr=dashBoard&page=card&fm=json&sq="+square+"&st="+store+"&time_ref="+time_ref;
		// console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			for(let i=0; i<4; i++) {
				document.getElementById("card["+i+"][title]").innerHTML = response[i]["display"];
				document.getElementById("card["+i+"][badge]").innerHTML = response[i]["badge"];
				document.getElementById("card["+i+"][badge]").style.backgroundColor = response[i]["color"];
				document.getElementById("card["+i+"][value]").innerHTML = response[i]["value"].toLocaleString('en-US', { minimumFractionDigits: 0 });
				document.getElementById("card["+i+"][percent]").innerHTML = response[i]["percent"] + "%";
				document.getElementById("card["+i+"][progress]").style.width = response[i]["percent"] + "%";
				document.getElementById("card["+i+"][progress]").style.backgroundColor = response[i]["color"];
			}			
		});

		url = "./inc/query.php?fr=dashBoard&page=footfall&fm=json&sq="+square+"&st="+store+"&time_ref="+time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			document.getElementById('footfall_title').innerHTML = response['title']['display'];
			let maxval = 0, tmpmax=0;
			for(let i=0; i<2; i++) {
				tmpmax = arrayMax(response['bar']['data'][i]['data']);
				if (tmpmax > maxval) {
					maxval = tmpmax;
				}
			}
			maxval = Math.round(maxval*1.2);
			let l = (maxval.toString()).length;
			if (l>2) {
				let div = Math.pow(10, l-2);
				maxval = Math.ceil(maxval/div)*div;
			}
			let bar_option = {
				chart: {
					height: 150,
				},
				colors: ["rgb(201, 203, 207)","rgb(153, 102, 255)"],
				plotOptions:{
					bar: {
						horizontal: false, 
						columnWidth: '60%', 
						dataLabels: { enabled: true, position: "top", hideOverflowingLabels: false, },
					} 
				},
				yaxis:{
					max: maxval,
					// tickAmount: 10,
				},
				// xaxis: {
				// 	labels: {
				// 		showDuplicates:true,
				// 	  	// formatter: function(datetimes) {
				// 		// 	return datetimes + "\n...";
				// 	  	// }
				// 	}
				// }
			};
			let x_categories = response['bar']['category']['datetimes'];
			bar_chart("footfall_bar_chart", 0, response['bar'], x_categories, bar_option);
			curve_chart("footfall_curve_chart", 0, response['curve'], 'day', {chart:{height:150}, colors: ["rgb(153, 102, 255)","#FEB019"], dataLabels:{enabled:false}});
		});

		url = "./inc/query.php?fr=dashBoard&page=third_block&fm=json&sq="+square+"&st="+store+"&time_ref="+time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			document.getElementById('third_block').innerHTML = response['text_body'];
			third = response['third_block'];
			if (third == 'none') {
				document.getElementById('third_block').innerHTML = "";
			}
			else if (third == 'curve_by_label') {
				colors = new Array();
				response["data"].forEach(function(item){
					colors.push(item.color);
				});
				curve_chart('third_block_body', 0, response, 'day', {chart:{height:300}, stroke:{ width: 3,}, colors:colors});
			}
			else if (third == 'age_gender') {
				addJavascript("../js/genderGraph.js");
				let from_date = new Date(time_ref);
				from_date.setDate(from_date.getDate()-83);
				time_ref = from_date.getFullYear() + "-" + from_date.getMonth() + "-" + from_date.getDate() + "~" + time_ref;
				url = "./inc/query.php?fr=age&fm=json&sq=" + square + "&st=" + store + "&view_by=day&time_ref=" + time_ref;
				// console.log(url);
				$.getJSON(url, function(response) {
					console.log(response);
					if (response['result'] == 'no data') {
						document.getElementById('age_curve_chart').innerHTML= 'NO DATA...';
					}
					else {
						let options = {
							chart: {height: 250,},
							dataLabels: {enabled: false,}, 
							yaxis:{
								floating: false, show: false,max: 100,
								labels: {formatter: function (val) { return Math.round(val) +"%";},	},
							},
						};					
						area_chart("age_curve_chart", 0, response, 'day', options);
						age_average = {
							"data": [{name: response['title']["Average12Weeks"], data:response['total']['data']}],
							"category": {"labels": response['total']['name']},
						};
						let arr_today_data = new Array();
						for (i=0; i <response['data'].length; i++) {
							arr_today_data[i] = response['data'][i]['data'][response['data'][i]['data'].pop()];
						}
						age_today = {
							"data": [{name: response['title']["Today"], data:arr_today_data}],
							"category": {"labels": response['total']['name']},
						};
						age_bar_graph("age_ave_chart", age_average);
						age_bar_graph("age_today_chart", age_today);
					}
				});

				url = "./inc/query.php?fr=gender&fm=json&sq=" + square + "&st=" + store + "&view_by=day&time_ref=" + time_ref;
				// console.log(url);
				$.getJSON(url, function(response) {
					// console.log(response);
					if (response['result'] == 'no data') {
						document.getElementById('age_curve_chart').innerHTML= 'NO DATA...';
					}
					else {					
						options = {
							chart: {height: 250,},
							dataLabels: {enabled: false,}, 
							colors: ["rgb(54, 162, 235)","rgb(255, 99, 132)"],
							yaxis:{
								floating: false,
								show: false,
								max: 100,
								labels: {
									formatter: function (val) {
										return Math.round(val) +"%";				 
									},
								},
							},
						};				
						area_chart("gender_curve_chart", 0, response, 'day', options );

						g_option ={ height:160, width:80,};
						gender_ave = {
							data:[{name: response['total']['name'][0], data: response['total']['data'][0]},{name: response['total']['name'][1], data: response['total']['data'][1]}],
							title: response['title']['Average12Weeks'],
						};
						gender_today = {
							data:[{name: response['total']['name'][0], data:response['data'][0]['data'][response['data'][0]['data'].pop()]},{name: response['total']['name'][1], data:response['data'][1]['data'][response['data'][1]['data'].pop()]}],
							title: response['title']['Today'],
						};
						gender_bar_graph("gender_ave", gender_ave, g_option);
						gender_bar_graph("gender_today", gender_today, g_option);
					}
				});
			}			
		});
		

		// if (third == 'age_gender') {
		// 	addJavascript("../js/genderGraph.js");
		// 	let from_date = new Date(time_ref);
		// 	from_date.setDate(from_date.getDate()-83);
		// 	time_ref = from_date.getFullYear() + "-" + from_date.getMonth() + "-" + from_date.getDate() + "~" + time_ref;

		// 	url = "./inc/query.php?fr=age&fm=json&sq=" + square + "&st=" + store + "&view_by=day&time_ref=" + time_ref;
		// 	// console.log(url);
		// 	$.getJSON(url, function(response) {
		// 		// console.log(response);
		// 		let options = {
		// 			chart: {height: 250,},
		// 			dataLabels: {enabled: false,}, 
		// 			yaxis:{
		// 				floating: false,
		// 				show: false,
		// 				max: 100,
		// 				labels: {
		// 					formatter: function (val) {
		// 						return Math.round(val) +"%";				 
		// 					},
		// 				},
		// 			},
		// 		};					
		// 		area_chart("age_curve_chart", 0, response, 'day', options);

		// 		age_average = {
		// 			"data": [{name: response['title']["Average12Weeks"], data:response['total']['data']}],
		// 			"category": {"labels": response['total']['name']},
		// 		};
		// 		let arr_today_data = new Array();
		// 		for (i=0; i <response['data'].length; i++) {
		// 			arr_today_data[i] = response['data'][i]['data'][response['data'][i]['data'].pop()];
		// 		}
		// 		age_today = {
		// 			"data": [{name: response['title']["Today"], data:arr_today_data}],
		// 			"category": {"labels": response['total']['name']},
		// 		};
		// 		age_bar_graph("age_ave_chart", age_average);
		// 		age_bar_graph("age_today_chart", age_today);
		// 	});

		// 	url = "./inc/query.php?fr=gender&fm=json&sq=" + square + "&st=" + store + "&view_by=day&time_ref=" + time_ref;
		// 	// console.log(url);
		// 	$.getJSON(url, function(response) {
		// 		// console.log(response);
		// 		options = {
		// 			chart: {height: 250,},
		// 			dataLabels: {enabled: false,}, 
		// 			colors: ["rgb(54, 162, 235)","rgb(255, 99, 132)"],
		// 			yaxis:{
		// 				floating: false,
		// 				show: false,
		// 				max: 100,
		// 				labels: {
		// 					formatter: function (val) {
		// 						return Math.round(val) +"%";				 
		// 					},
		// 				},
		// 			},
		// 		};				
		// 		area_chart("gender_curve_chart", 0, response, 'day', options );

		// 		g_option ={ height:160, width:80,};
		// 		gender_ave = {
		// 			data:[{name: response['total']['name'][0], data: response['total']['data'][0]},{name: response['total']['name'][1], data: response['total']['data'][1]}],
		// 			title: response['title']['Average12Weeks'],
		// 		};
		// 		gender_today = {
		// 			data:[{name: response['total']['name'][0], data:response['data'][0]['data'][response['data'][0]['data'].pop()]},{name: response['total']['name'][1], data:response['data'][1]['data'][response['data'][1]['data'].pop()]}],
		// 			title: response['title']['Today'],
		// 		};
		// 		gender_bar_graph("gender_ave", gender_ave, g_option);
		// 		gender_bar_graph("gender_today", gender_today, g_option);

		// 	});
		// }
		// else if (third == 'curve_by_label') {
		// 	url = "./inc/query.php?fr=dashBoard&page=curveByLabel&fm=json&sq="+square+"&st="+store+"&time_ref="+time_ref;
		// 	// console.log(url);
		// 	colors = new  Array();
		// 	$.getJSON(url, function(response) {
		// 		// console.log(response);
		// 		response["data"].forEach(function(item){
		// 			colors.push(item.color);
		// 		});
		// 		curve_chart('third_block_curve_chart', 0, response, 'day', {chart:{height:300}, stroke:{ width: 3,}, colors:colors});
		// 	});
		// }

		// else if (third == 'table') {
		// 	url = "./inc/query.php?fr=dashBoard&page=table&fm=json&sq="+square+"&st="+store+"&time_ref="+time_ref;
		// 	console.log(url);
		// }		
	}
}

else if(Get['fr'] == "dataGlunt") {
	function doAnalysis() {
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;
		const time_ref = document.getElementById("refdate_from").value + '~' + document.getElementById("refdate").value;
		if(!time_ref) {
			return false;
		}
		const url = "./inc/query.php?fr=dataGlunt&fm=json&labels=" + Get['labels'] + "&sq=" + square_ref + "&st=" + store_ref + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			curve_chart("chart_curve", "footfall_table", response, view_by,  {chart:{height:500}, stroke:{ width: 3,}});
		});
	}
}

else if(Get['fr'] == "latestFlow") {
	function changeViewOn(viewon) {
		document.getElementById("view_on").value = viewon;
		document.getElementById("7day").style.backgroundColor="";
		document.getElementById("4week").style.backgroundColor="";
		document.getElementById("12week").style.backgroundColor="";
		document.getElementById(viewon).style.backgroundColor="#fcc100";
		doAnalysis();
	}
	
	function doAnalysis() {
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		const view_on = document.getElementById("view_on").value;
		const url = "./inc/query.php?fr=latestFlow&fm=json&labels=" + Get['labels'] + "&sq=" + square_ref + "&st=" + store_ref + "&view_on=" + view_on;
		// debugLog(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			curve_chart("chart_curve", "footfall_table", response, 'day',  {chart:{height:500}, stroke:{ width: 3,}});
		});		
	}
	$(document).ready(function(){
		doAnalysis();
	});
}

else if(Get['fr'] == "trendAnalysis") {
	function doAnalysis() {
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;
		const time_ref = document.getElementById("refdate").value;

		const url = "./inc/query.php?fr=trendAnalysis&fm=json&sq=" + square_ref + "&st=" + store_ref + "&view_by=" + view_by + "&time_ref=" + time_ref;
		// console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			curve_chart("chart_curve", "footfall_table", response, view_by, {chart:{height:500}, stroke:{ width: 3,}});
			response['data'] = [
				{name: response['data'][0]['name'], data: [arraySum(response["data"][0]["data"])]},
				{name: response['data'][1]['name'], data: [arraySum(response["data"][1]["data"])]},
				{name: response['data'][2]['name'], data: [arraySum(response["data"][2]["data"])]}
			];
			bar_chart("chart_bar", 0, response, ['Total'], {chart: {height:500,}, legend: {floating: false,},});
		});
	};
}

else if(Get['fr'] == "advancedAnalysis" || Get['fr'] == "compareByLabel" ) {
	function doAnalysis() {
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;
		const time_ref = document.getElementById("refdate_from").value + '~' + document.getElementById("refdate").value;
		if(!time_ref) {
			return false;
		}
		const url = "./inc/query.php?fr=" + Get['fr'] +"&fm=json&sq=" + square_ref + "&st=" + store_ref + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			curve_chart("chart_curve", "footfall_table", response, view_by, {chart:{height:500}, stroke:{ width: 3,}});
		});
	}
}

else if(Get['fr'] == "kpi") {
	function doAnalysis() {
		console.log("kpi Analysis");
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;
		const time_ref = document.getElementById("refdate_from").value + '~' + document.getElementById("refdate").value;
		if(!time_ref) {
			return false;
		}
		const url = "./inc/query.php?fr=kpi&fm=json&sq=" + square_ref + "&st=" + store_ref + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			for(i=0; i<response["card_val"].length; i++) {
				document.getElementById("card_val["+i+"]").innerHTML  = response["card_val"][i];
			}

		});
	}
}

else if(Get['fr'] == "promotionAnalysis") {
	function doAnalysis() {
		console.log("Promotion Analysis");
		
		
		
	}
}

else if(Get['fr'] == "brandOverview") {
	function doAnalysis() {
		console.log("Brand Overview");
		
		
		
	}
}

else if(Get['fr'] == "weatherAnalysis") {
	function doAnalysis() {
		console.log("Weather Analysis");
		
		
		
	}
}

else if(Get['fr'] == "compareByTime") {
	document.addEventListener("DOMContentLoaded", function(event) {
		$("input[name=\"refdate1\"]").daterangepicker(configDateTo);
		configDateTo.startDate = moment(new Date()-3600*24*7*1000).format(date_picker_option_locale[_selected_language].format); 
		$("input[name=\"refdate2\"]").daterangepicker(configDateTo);
		configDateTo.startDate = moment(new Date()-3600*24*14*1000).format(date_picker_option_locale[_selected_language].format); 
		$("input[name=\"refdate3\"]").daterangepicker(configDateTo);
	});
	
	let load_num = 3;
	function changeDate() {
		if(load_num) {
			load_num--;
			return false;
		}
		const ref_time1 = document.getElementById("refdate1").value;
		const ref_time2 = document.getElementById("refdate2").value;
		const ref_time3 = document.getElementById("refdate3").value;
		myTime1 = new Date(Date.parse(ref_time1)).getTime();
		myTime2 = new Date(Date.parse(ref_time2)).getTime();
		myTime3 = new Date(Date.parse(ref_time3)).getTime();
		
		if(myTime1 > thisTime) {
			configDateTo.startDate = moment(thisTime).format(date_picker_option_locale[_selected_language].format);
			$("input[name=\"refdate1\"]").daterangepicker(configDateTo);
		}
		if(myTime2 > thisTime) {
			configDateTo.startDate = moment(thisTime).format(date_picker_option_locale[_selected_language].format);
			$("input[name=\"refdate2\"]").daterangepicker(configDateTo);
		}
		if(myTime3 > thisTime) {
			configDateTo.startDate = moment(thisTime).format(date_picker_option_locale[_selected_language].format);
			$("input[name=\"refdate3\"]").daterangepicker(configDateTo);
		}
		doAnalysis();
	}
	$(document).ready(function(){
		document.getElementById("refdate1").style.borderColor = colors_ref[0];
		document.getElementById("refdate2").style.borderColor = colors_ref[1];
		document.getElementById("refdate3").style.borderColor = colors_ref[2];
	});	
	
	function doAnalysis() {
		const time_ref1 = document.getElementById("refdate1").value;
		const time_ref2 = document.getElementById("refdate2").value;
		const time_ref3 = document.getElementById("refdate3").value;
		const square = document.getElementById("square").value;
		const store = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;

		const url = "./inc/query.php?fr=compareByTime&fm=json&sq="+square+"&st="+store+"&view_by="+view_by+"&time_ref1="+time_ref1+"&time_ref2="+time_ref2+"&time_ref3="+time_ref3;
		// console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			curve_chart("chart_curve", "footfall_table", response, 'hour',  {chart:{height:500}, stroke:{ width: 3,}});
			response['data'].forEach(function(item){ //response['data'][i] => item
				item.data = [arraySum(item.data)];
			});
			bar_chart("chart_bar", 0, response, ['Total'], {chart: {height:500,}, legend: {floating: false,},});

		});
	};

	$(document).ready(function(){
		doAnalysis();
	});
}

else  if(Get['fr'] == "compareByPlace") {
	listSquare("#square1");
	listSquare("#square2");
	listSquare("#square3");

	$(document).ready(function(){
		document.getElementById("square1").style.borderColor = colors_ref[0];
		document.getElementById("square2").style.borderColor = colors_ref[1];
		document.getElementById("square3").style.borderColor = colors_ref[2];
		document.getElementById("store1").style.borderColor = colors_ref[0];
		document.getElementById("store2").style.borderColor = colors_ref[1];
		document.getElementById("store3").style.borderColor = colors_ref[2];
	});		

	function doAnalysis() {
		const square_ref1 = document.getElementById("square1").value;
		const square_ref2 = document.getElementById("square2").value;
		const square_ref3 = document.getElementById("square3").value;
		const store_ref1 = document.getElementById("store1").value;
		const store_ref2 = document.getElementById("store2").value;
		const store_ref3 = document.getElementById("store3").value;
		const view_by = document.getElementById("view_by").value;
		const time_ref = document.getElementById("refdate_from").value + '~' + document.getElementById("refdate").value;
		
		if((square_ref1==0) && (square_ref2==0) && (square_ref3==0)) {
			return false;
		}
		const url = "./inc/query.php?fr=compareByPlace&fm=json&labels=" + Get['labels'] + "&sq1=" + square_ref1 + "&sq2=" + square_ref2 + "&sq3=" + square_ref3 + "&st1=" + store_ref1 + "&st2=" + store_ref2 + "&st3=" + store_ref3 + "&view_by=" + view_by + "&time_ref=" + time_ref;
		// console.log(url);
		
		$.getJSON(url, function(response) {
			// console.log(response);
			curve_chart("chart_curve", "footfall_table", response, view_by ,  {chart:{height:500}, stroke:{ width: 3,}});
			response['data'].forEach(function(item){ //response['data'][i] => item
				item.data = [arraySum(item.data)];
			});			
			bar_chart("chart_bar", 0, response, ['Total'], {chart: {height:500,}, legend: {floating: false,},});
		});
	};
	
}

else if(Get['fr'] == "trafficDistribution") {
	function changeViewOn(view_on) {
		document.getElementById("view_on").value = view_on
		document.getElementById("visit").style.backgroundColor="";
		document.getElementById("occupy").style.backgroundColor="";
		document.getElementById(view_on).style.backgroundColor="#fcc100";
		doAnalysis();
	}
	let options = {
		chart: {
			height: 350,
			type: "heatmap",
		},
		legend: { show: false},
		dataLabels: {
			enabled: true,
			style: {
				fontSize: '12px',
				colors: ["#3047D8"]
			}	
		},
		colors: ["#330040"], 
		series: [],
		xaxis: { type: "category",
		},
		noData: { text: "Loading..." },
		plotOptions: {
			heatmap: {
			  shadeIntensity: 0,
			  radius: 0,
			  useFillColorAsStroke: true,
			  colorScale: {
				ranges: []
			  }
			}
		  },
	}
	
	const chart = new ApexCharts(document.querySelector("#apexcharts-heatmap"),options);
	chart.render();

	function doAnalysis() {
		if(document.getElementById("refdate_from").value == document.getElementById("refdate").value ) {
			return false;
		}
		const square = document.getElementById("square").value;
		const store = document.getElementById("store").value;
		const view_on = document.getElementById("view_on").value;
		const time_ref = document.getElementById("refdate_from").value + '~' + document.getElementById("refdate").value;

		const url = "./inc/query.php?fr=trafficDistribution&fm=json&sq=" + square + "&st=" + store +"&view_on=" + view_on + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			response["data"].forEach(function(item){
				item['data'].forEach(function(d,j){
					if (d == null){
						item['data'][j] = '';
					}
				});
			});
			chart.updateSeries(response["data"]);
			let c_height = response["data"].length*50;
			if (c_height > 350 ){
				chart.updateOptions({
					chart: {height: c_height,}
				});
			} 
			for (i=0, maxval=0; i<response['data'].length; i++){
				for (j=0; j<24; j++){
					if (response['data'][i]['data'][j] > maxval){
						maxval = response['data'][i]['data'][j];
					}
				}
			}
			steps = Math.round(maxval / 6);
			chart.updateOptions({
				// chart: {height: c_height,},
				xaxis: {categories : response["label"],	},
				title:{	text: response["title"]["chart_title"],},
				plotOptions: {
					heatmap: {
						// colorScale: {
						// 	ranges: [
						// 		{ from: 0,			to: steps,		name: 'low',	color: '#D1E6D4' },
						// 		{ from: steps+1,	to: steps*2,	name: 'medium',	color: '#472C32' },
						// 		{ from: steps*2+1,	to: steps*3,	name: 'high',	color: '#ECDC32' },
						// 		{ from: steps*3+1, 	to: steps*4,	name: 'extreme',color: '#EB510F' },
						// 		{ from: steps*4+1,	to: steps*5,	name: 'extreme',color: '#EB0D1A' },
						// 		{ from: steps*5+1,	to: steps*6,	name: 'extreme',color: '#240002' }
								
						// 	]
						// }
					}
				}
			});
		});
	};

	$(document).ready(function(){
		configDateFrom.startDate = moment(new Date()-3600*24*7*1000).format(date_picker_option_locale[_selected_language].format);
		$("input[name=\"refdate_from\"]").daterangepicker(configDateFrom);
	});
}

else if(Get['fr'] == "heatmap") {
	function changeTime(ref) {
		const ref_hour = document.getElementById("reftime").value;
		let myHour = ref_hour.split(":")[0]*1;
		const thisTime = new Date().getTime();
		if(ref == -1) {
			myHour -= 1;
		}
		else if(ref == 1){
			myHour += 1;
		}

		if(myHour>23) {
			myHour = 0;
			let ref_time = document.getElementById("refdate").value;
			myTime = new Date(Date.parse(ref_time)).getTime() + 3600*24*1000;
			document.getElementById("refdate").value = moment(myTime).format("YYYY-MM-DD");
		}
		else if (myHour <0){
			myHour = 23;
			let ref_time = document.getElementById("refdate").value;
			myTime = new Date(Date.parse(ref_time)).getTime() - 3600*24*1000;
			document.getElementById("refdate").value = moment(myTime).format("YYYY-MM-DD");
		}
		
		if(myTime > thisTime) {
			myTime = thisTime;
			document.getElementById("refdate").value = moment(myTime).format("YYYY-MM-DD");
		}
		document.getElementById("reftime").value = myHour + ":00";
		doAnalysis();
	}	
	
	function changeViewBy(view_by) {
		document.getElementById("view_by").value = view_by;
		document.getElementById("hour").style.backgroundColor="";
		document.getElementById("day").style.backgroundColor="";
		document.getElementById(view_by).style.backgroundColor="#fcc100";
		if(view_by == 'hour') {
			document.getElementById("time_plane").style.display="";
		}
		else {
			document.getElementById("time_plane").style.display="none";
		}
		doAnalysis();
	}

	function listDevice() {
		const d_id =  document.getElementById("deviceSlider");
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		// const time_ref = document.getElementById("refdate_from").value + "~" + document.getElementById("refdate").value;
		const pad_id = document.getElementById("heatmapPad");
		pad_id.style.display = 'none';

		const url = "./inc/query.php?fr=heatMap&act=list&fm=json&sq=" + square_ref + "&st=" + store_ref;
		console.log(url);

		d_id.innerHTML ='';
		$.getJSON(url, function(response) {
			// console.log(response);
			let image_tag = '';
			for(i=0; i<response.length; i++ ) {
				image_tag = '<img src="'+ response[i]["image"] +'" width="160" height="90" type="button"></img>';
				d_id.innerHTML += '<div class="card ml-2"  OnClick="setDeviceId(\''+response[i]["device_info"]+'\');">' +
					'<div class="card-body w-100" align="center">' + image_tag + '</div>'+ 
				'</div>';
			}
		});
	}
	
	function setDeviceId(str) {
		const dev_id = document.getElementById("s_device");
		dev_id.value = str;
		doAnalysis();
	}
	
	let hm_option = {
		config: {
			radius: 45,
			maxOpacity: .7
		},
		image:{
			src :"",
			width: 800,
			height:450,
		},
		data: {
			max:0,
			data:[{x:0,y:0,value:0}],
		},
		tooltip:{
			id:"tooltipInstance",
		},
	}
	
	const hmc = document.getElementById("heatmapContainer");
	hmc.style.background = "url(" + hm_option.image.src + ") no-repeat";
	hmc.style.backgroundSize = hm_option.image.width + "px "+ hm_option.image.height + "px";
	hmc.style.height = hm_option.image.height + "px";
	hmc.style.width = hm_option.image.width + "px";
	
	let heatmapInstance = h337.create({
		container:hmc, 
		radius:hm_option.config.radius,
		maxOpacity:hm_option.config.radius
	});	
	
	function doAnalysis() {
		const view_by = document.getElementById("view_by").value;
		const time_ref = document.getElementById("refdate").value + " " + document.getElementById("reftime").value;
		const device_ref = document.getElementById("s_device").value;
		
		if(!time_ref) {
			return false;
		}
		if(!device_ref) {
			return false;
		}
		const pad_id = document.getElementById("heatmapPad");
		pad_id.style.display = '';
		
		const url = "./inc/query.php?fr=heatMap&act=draw&fm=json&" + device_ref + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			hm_option.data = {  
				max: response["max"], 
				data: response["data"],
			};
			
			document.getElementById("heatmapHeader").innerHTML = '<h5 class="card-title float-right mb-0">' + response["subtitle"] + '</h5>'+ 
				'<h5 class="card-title mb-0">' + response["title"] + '</h5>';

			if(response["image"]) {
				hm_option.image.src = response["image"];
				hmc.style.background = "url(" + hm_option.image.src + ") no-repeat";
				hmc.style.backgroundSize = hm_option.image.width + "px "+ hm_option.image.height + "px";
			}
			heatmapInstance.setData(hm_option.data);
			
			if(hm_option.tooltip.id) {
				const tooltip = document.getElementById(hm_option.tooltip.id);
				hmc.onmousemove = function(ev) {
					let x = ev.layerX;
					let y = ev.layerY;
					let value = heatmapInstance.getValueAt({
						x: x,
						y: y
					}); 
					tooltip.style.display = "block";
					updateHmTooltip(tooltip, x, y, value);
				};
				hmc.onmouseout = function() {
					tooltip.style.display = "none";
				};
			}
			
		});
	}
	function updateHmTooltip(tooltip, x, y, value) {
		let transform = "translate(" + (x + 15) + "px, " + (y + 15) + "px)";
		tooltip.style.MozTransform = transform; 
		tooltip.style.msTransform = transform;
		tooltip.style.OTransform = transform; 
		tooltip.style.WebkitTransform = transform;
		tooltip.style.transform = transform;
		tooltip.innerHTML = value;
	};

	$(document).ready(function(){
		listDevice()
	});
	
}

else if (Get['fr'] =="agegender") {

	function doAnalysis() {
		const time_ref = document.getElementById("refdate_from").value + "~" + document.getElementById("refdate").value;
		const square = document.getElementById("square").value;
		const store = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;

		let url = "./inc/query.php?fr=age&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			if (response['result'] == 'no data') {
				document.getElementById('ageBarChart').innerHTML= 'NO DATA...';
				document.getElementById('ageBGraph').innerHTML= 'NO DATA...';
			}
			else {			
				let options = {
					chart: {height: 250,},
					colors: colors_age_ref,
					dataLabels: {enabled: false,}, 
					yaxis:{
						floating: false,
						show: false,
						max: 100,
						labels: {
							formatter: function (val) {
								return Math.round(val) +"%";				 
							},
						},
					},
				};
				area_chart("ageBarChart", 0, response, view_by, options);

				age_average = {
					"data": [{name: response['title']["Average"], data:response['total']['data']}],
					"category": {"labels": response['total']['name']},
				};
				options = {chart:{height:250},colors: colors_age_ref,};
				// console.log(age_average);
				age_bar_graph('ageBGraph', age_average, options);		
			}	

		});
		
		url = "./inc/query.php?fr=gender&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			if (response['result'] == 'no data') {
				document.getElementById('genderBarChart').innerHTML= 'NO DATA...';
				document.getElementById('genderGraph').innerHTML= 'NO DATA...';
			}			
			else {
				options = {
					chart: {height: 250,},
					dataLabels: {enabled: false,}, 
					colors: colors_gender_ref,
					yaxis:{
						floating: false,
						show: false,
						max: 100,
						labels: {
							formatter: function (val) {
								return Math.round(val) +"%";				 
							},
						},
					},
				};			
				area_chart("genderBarChart", 0, response, view_by, options);
				g_option ={ height:180, width:80,};
				gender_ave = {
					data:[{name: response['total']['name'][0], data: response['total']['data'][0]},{name: response['total']['name'][1], data: response['total']['data'][1]}],
					title: response['title']['Average12Weeks'],
				};			
				gender_bar_graph("genderGraph", gender_ave, g_option);
			}
		});
	}
}

else if(Get['fr'] == "macsniff") {
	let _seed = 42
		Math.random = function() {
		_seed = (_seed * 16807) % 2147483647
		return (_seed - 1) / 2147483646
	}
	function generateData(baseval, count, yrange) {
		let i = 0;
		let series = [];
		while (i < count) {
			let x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;;
			let y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
			let z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;

			series.push([x, y, z]);
			baseval += 86400000;
			i++;
		}
		return series;
	}

	let options = {
		series: [
			{ name: 'Bubble1', data: generateData(new Date('11 Feb 2017 GMT').getTime(), 20, { min: 10, max: 60	}) },
			{ name: 'Bubble2', data: generateData(new Date('11 Feb 2017 GMT').getTime(), 20, { min: 10, max: 60	}) },
			{ name: 'Bubble3', data: generateData(new Date('11 Feb 2017 GMT').getTime(), 20, { min: 10,	max: 60	}) },
			{ name: 'Bubble4', data: generateData(new Date('11 Feb 2017 GMT').getTime(), 20, { min: 10, max: 60	}) }
		],
		chart: {
			height: 350,
			type: 'bubble',
		},
		dataLabels: {
			enabled: false
		},
		fill: {
			opacity: 0.8
		},
		title: {
			text: 'Simple Bubble Chart'
		},
		xaxis: {
			tickAmount: 12,
			type: 'category',
		},
		yaxis: {
			max: 70
		}
	};

	const chart = new ApexCharts(document.querySelector("#chart"), options);
	chart.render();
	
	function doAnalysis() {
		const time_ref = document.getElementById("refdate_from").value + "~" + document.getElementById("refdate").value;
		const square = document.getElementById("square").value;
		const store = document.getElementById("store").value;
		const view_by = document.getElementById("view_by").value;
		
		tooltip_fmt = tooltip_datetime;
		if(view_by == "day") {
			tooltip_fmt = tooltip_date;
		}
		// console.log(options.series);
		const url = "./inc/query.php?fr=macSniff&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		// console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
//			chart.updateSeries( response["data"] );

		});
		
	}
}

else if(Get['fr'] == "summary") {
	var footfall_curve = new ApexCharts(document.querySelector("#footfall_curve_chart"), options_curve);
	var age_total = new ApexCharts(document.querySelector("#ageGraph"), options_bar);
	var gender_total = new ApexCharts(document.querySelector("#genderGraph"), options_bar);
	footfall_curve.render();
	age_total.render();
	gender_total.render();
	
	var config_gender_graph = {
		data:[],
		title:"Gener Total",
		label:["male", "female"],
		width: "60",
		height: "120",
//		fontsize: "15px",
	}
	
	function doAnalysis() {
		var time_ref = document.getElementById("refdate").value;
		var square = document.getElementById("square").value;
		var store = document.getElementById("store").value;
		var view_by = document.getElementById("view_by").value;
		
		var url = "./inc/query.php?fr=summary&page=footfall&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		console.log(url);
		$.getJSON(url, function(response) {
			console.log(response);
			curve_chart("footfall_curve_chart", 0, response, response['label'], {chart:{height:450}});
// 			footfall_curve.updateSeries( response["data"] );
// 			footfall_curve.updateOptions({
// 				chart: { height: 450, },				
// 				xaxis: {
// 					type:"categories",
// 					categories: response["label"],
// 				},
				// tooltip: { x: {	format: tooltip_fmt,},	},
// 			});	

			for(i=0; i<response["card"].length; i++) {
				document.getElementById("card["+i+"]").innerHTML  = card_small(response["card"][i][0], response["card"][i][1], response["card"][i][2], response["card"][i][3], response["card"][i][4], response["card"][i][5], response["card"][i][6]);
			}
		});

		var url = "./inc/query.php?fr=summary&page=ageGender&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			var total_ave = 0;
			var max_ave = 0;
			
			var arr_data = new Array();
			var arr_label = new Array();
			var arr_color = new Array();
			for(i=0; i<(response["data"].length-2); i++) {
				if(response["data"][i]["total"] > max_ave) {
					max_ave = response["data"][i]["total"];
				}
				total_ave += response["data"][i]["total"]*1;
			}
			for(i=0; i<(response["data"].length-2); i++) {
				arr_label.push(response["data"][i]["name"]);
				arr_data.push(response["data"][i]["total"]*100 / total_ave);
				if(response["data"][i]["total"] == max_ave) {
					arr_color.push("rgb(255, 99, 132)");
				}
				else {
					arr_color.push("rgb(201, 203, 207)");
				}
			}
			age_total.updateSeries([{name:response["title"]["chart_title"][0], data:arr_data}]);				
			age_total.updateOptions({
				colors: arr_color,
				dataLabels: {
					enabled: true,
					formatter: function (val) {
						return Math.round(val)+"%";
					},
				},
				xaxis: {
					type: "categories",
					categories: arr_label,
				},
				yaxis:{
					max: Math.round(max_ave*100/total_ave) + 10,
					labels: {
						formatter: function (val) {
							return Math.round(val);				 
						},
					},
				},
				grid: {
					yaxis: {
						lines: { show: false }
					}, 			
				},
				plotOptions: {
					bar: {
						columnWidth: '75%',
						distributed: true,
					},
				},				
			});	

			var g_id = document.getElementById("genderGraph");
			config_gender_graph.data = [response["data"][response["data"].length-2]["total"], response["data"][response["data"].length-1]["total"]];
			config_gender_graph.label = [response["data"][response["data"].length-2]["name"], response["data"][response["data"].length-1]["name"]];
			config_gender_graph.title = response["title"]["chart_title"][1];
			genderBar(g_id, config_gender_graph);
			
			debugLog(config_gender_graph);
		});
	}
	
	function card_small(title, date, value, line1_title, line1_val, line2_title, line2_val) {
		var str = ''+
			'<div class="float-right text-info">'+ date + '</div>' +
			'<h4 class="mb-2">' + title + '</h4>' +
			'<div class="mb-1"><strong>' + value + '</strong>' + '</div>'+
			'<div class="float-right">'+ line1_val + '</div>' +
			'<div>' + line1_title + '</div>' +
			'<div class="float-right">' + line2_val + '</div>' +
			'<div>' + line2_title +' </div>' ;
		return str;
	}
	
}

else if(Get['fr'] == "standard") {

	var footfall_curve = new ApexCharts(document.querySelector("#footfall_rising_rank"), options_curve);
	var footfall_hourly = new ApexCharts(document.querySelector("#footfall_hourly"), options_curve);
	var footfall_device = new ApexCharts(document.querySelector("#footfall_device"), options_curve);
	
	footfall_curve.render();
	footfall_hourly.render();
	footfall_device.render();

	function doAnalysis() {
		var time_ref = document.getElementById("refdate").value;
		var square = document.getElementById("square").value;
		var store = document.getElementById("store").value;
		var view_by = document.getElementById("view_by").value;
		
		var url = "./inc/query.php?fr=standard&page=footfall_rising_rank&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			footfall_curve.updateSeries( response["data"] );
			footfall_curve.updateOptions({
				chart: { height: 350, },	
				colors : ['#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'],
				xaxis: {
					type: "categories",
					categories: response["label"],
				},
				tooltip: {
					x: { format: "",},
				},
			});	
		});

		var url = "./inc/query.php?fr=standard&page=footfall_hourly&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			footfall_hourly.updateSeries( response["data"] );
			footfall_hourly.updateOptions({
				chart: { height: 350, },	
				colors:['#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'],
				xaxis: {
					categories: timeToDatetime(response["label"],"YYYY-MM-DD HH:mm"),
				},
				tooltip: { x: { format: "HH:mm", }, },
			});
		});
		
		var url = "./inc/query.php?fr=standard&page=footfall_device&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			
			debugLog(response);
			footfall_device.updateSeries(response["data"]);
			footfall_device.updateOptions({
				chart: { height: 350, },	
				xaxis: {
					categories: timeToDatetime(response["label"],"YYYY-MM-DD HH:mm"),
				},
				tooltip: {
					x: { format: "HH:mm", },
				},
			});
		});
	}
}

else if(Get['fr'] == "premium") {
	var footfall_chart = new ApexCharts(document.querySelector("#footfall_chart"), options_curve);
	var footfall_square = new ApexCharts(document.querySelector("#footfall_square"), options_curve);
	var footfall_store = new ApexCharts(document.querySelector("#footfall_store"), options_curve);
	var footfall_device = new ApexCharts(document.querySelector("#footfall_device"), options_curve);
	footfall_chart.render();
	footfall_square.render();
	footfall_store.render();
	footfall_device.render();

	function doAnalysis() {
		var time_ref = document.getElementById("refdate").value;
		var square = document.getElementById("square").value;
		var store = document.getElementById("store").value;
		var view_by = document.getElementById("view_by").value;
		
		var url = "./inc/query.php?fr=premium&page=footfall&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			footfall_chart.updateSeries(response["data"]);
			footfall_chart.updateOptions({
				chart: { height: 350, },	
				colors:['#008FFB'],
				legend: { showForSingleSeries: false},
				stroke: {width:5,},
				xaxis: {
					categories: timeToDatetime(response["label"], date_picker_option_locale[_selected_language].format),
				},
				tooltip: {
					x: {format: "yyyy-MM-dd",},
				},
			});
		});
		
		var url = "./inc/query.php?fr=premium&page=footfall_square&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			footfall_square.updateSeries(response["data"]);
			footfall_square.updateOptions({
				chart: { height: 350, },	
				xaxis: {
					categories: timeToDatetime(response["label"], date_picker_option_locale[_selected_language].format),
				},
				tooltip: {
					x: {format: "yyyy-MM-dd",},
				},
			});
		});

		var url = "./inc/query.php?fr=premium&page=footfall_store&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			footfall_store.updateSeries( response["data"]);
			footfall_store.updateOptions({
				chart: { height: 350, },	
				xaxis: {
					categories: timeToDatetime(response["label"], date_picker_option_locale[_selected_language].format),
				},
				tooltip: {
					x: { format: "yyyy-MM-dd", },
				},
			});
		});
		
		var url = "./inc/query.php?fr=premium&page=footfall_device&fm=json&sq=" + square + "&st=" + store + "&view_by=" + view_by + "&time_ref=" + time_ref;
		debugLog(url);
		$.getJSON(url, function(response) {
			debugLog(response);
			footfall_device.updateSeries( response["data"] );
			footfall_device.updateOptions({
				chart: { height: 350, },	
				stroke: {width:3,},
				xaxis: {
					categories: timeToDatetime(response["label"], date_picker_option_locale[_selected_language].format),
				},
				tooltip: {
					x: { format: "yyyy-MM-dd", },
				},
			});
		});
	}
}

else if(Get['fr'] == "export") {
	document.addEventListener("DOMContentLoaded", function(event) {
		configDateFrom.startDate = moment(new Date()-3600*24*7*1000).format(date_picker_option_locale[_selected_language].format)
		$("input[id=\"refdate_from\"]").daterangepicker(configDateFrom);
		$("input[id=\"refdate\"]").daterangepicker(configDateTo);
	});

	let place = {};
	let config = {
		square : [],
		store : [],
		camera: [],
		counter_label: [],
		group: "none",
		startDate: "",
		endDate: "",
		interval: "daily",
		reportfmt: "table",
		order: "asc",
		api_key: "",
		
	};

	function getDeviceTree(){
		const url = "./inc/query.php?f=device_tree";
		$.getJSON(url, function(data) {			
			// console.log(data);
			place = data['place'];
			config.counter_label = data['counter_label'];
			for(let i=0; i<data['sq_size']; i++) {
				config.square.push({code: place[i]['sq_code'], name: place[i]['sq_name'], checked:'n'});
				document.getElementById("place_pad").innerHTML += '<label class="custom-control custom-checkbox">' +
					'<input type="checkbox" class="custom-control-input" id="' + place[i]['sq_code'] + '" OnChange="changeSquare(this)">' +
					'<span class="custom-control-label">' + place[i]['sq_name'] + '</span>' +
				'</label>';
				for(let j=0; j<place[i]['st_size']; j++){
					config.store.push({code: place[i][j]['st_code'], name: place[i][j]['st_name'], checked:'n'});
					document.getElementById("place_pad").innerHTML += '<div class="ml-4"><label class="custom-control custom-checkbox ml-4">' +
						'<input type="checkbox" class="custom-control-input ml-3" id="' + place[i][j]['st_code'] + '" OnChange="changeStore(this)">' +
						'<span class="custom-control-label">' + place[i][j]['st_name'] + '</span>' +
					'</label></div>';
				}
				document.getElementById("place_pad").innerHTML +=  '<div class="row mb-3"></div>';
			}
			for(let i=0; i<data['ct_size']; i++) {
				config.counter_label[i].checked='n';
				document.getElementById("counter_label_pad").innerHTML += '<label class="custom-control custom-checkbox">' +
					'<input type="checkbox" class="custom-control-input" id="' + config.counter_label[i]['label'] + '" OnChange="setConfig(this)">' +
					'<span class="custom-control-label">' + config.counter_label[i]['name'] + '</span>' +
				'</label>';
			}
		});
	}

	function changeSquare(t){
		// console.log(t, t.id, t.checked);
		const store = place.find(function(item){
			return item.sq_code == t.id;
		});
		for (let i=0; i<store.st_size; i++) {
			document.getElementById(store[i].st_code).checked = t.checked;
		}
		setConfig();
	}

	function changeStore(t){
		// console.log(t, t.id, t.checked);
		place.forEach(function (item) {
			document.getElementById(item.sq_code).checked = false;
			for (let i=0; i<item.st_size; i++){
				if(document.getElementById(item[i].st_code).checked) {
					document.getElementById(item.sq_code).checked = true;
				}
			}
		});
		setConfig();
	}


	getDeviceTree();
	function setConfig() {
		config.square.forEach(function(item){
			item.checked = document.getElementById(item.code).checked ? 'y' : 'n';
		});
		config.store.forEach(function(item){
			item.checked = document.getElementById(item.code).checked ? 'y' : 'n';
		});
		config.counter_label.forEach(function(item){
			item.checked = document.getElementById(item.label).checked ? 'y' : 'n';
		});
		
		document.getElementsByName('interval').forEach(function(item){
			if (item.checked) {
				config.interval = item.value;
			}
		});
		document.getElementsByName('groupby').forEach(function(item){
			if (item.checked) {
				config.group = item.value;
			}
		});
						
		config.startDate = document.getElementById("refdate_from").value;
		config.endDate = document.getElementById("refdate").value;
		
		document.getElementsByName('output_format').forEach(function(item){
			if (item.checked) {
				config.reportfmt = item.value;
			}
		});
		document.getElementsByName('order').forEach(function(item){
			if (item.checked) {
				config.order = item.value;
			}
		});	
		config.api_key = document.getElementById("api_key").value;
		console.log(config);
		mkAPI();
	}
	
	
	function mkAPI() {
		const server_address = document.getElementById("server_address").value;
		let str = '' +
			'reportfmt=' + config.reportfmt +
			'&from=' + config.startDate + 
			'&to=' + config.endDate +
			'&interval=' + config.interval +
			'&order=' + config.order +
			'&group=' + config.group +
			'&api_key=' + config.api_key;
			
		temps = config.square.filter(function(item){
			return item.checked == 'y';
		});
		str += '&square=' + temps.map(function(item){
			return item.code;
		}).join(',');

		temps = config.store.filter(function(item){
			return item.checked == 'y';
		});
		str += '&store=' + temps.map(function(item){
			return item.code;
		}).join(',');	
		temps = config.counter_label.filter(function(item){
			return item.checked == 'y';
		});
		str += '&label=' + temps.map(function(item){
			return item.label;
		}).join(',');

		str = 'http://' + server_address + '/countreport.php?' + str;
		document.getElementById("query_api").value = str;
	}
	
	function QueryAPI() {
		document.getElementById("error_board").innerHTML= '';
		const url = document.getElementById("query_api").value;
		const hashes = url.slice(url.indexOf('?') + 1).split('&');
		let errors = [], hash;
		hashes.forEach(function(item){
			hash = item.split('=');
			if (hash[1] == '') {
				errors.push("'" + hash[0] + "' cannot be empty");
			}
		});
		document.getElementById("error_board").innerHTML= errors.join('</br>');
		if (!errors.length){
			window.open(url);
		}
	}
}

else if(Get['fr'] == "sensors") {
	function deviceInfo(pk) {
		const info_id = document.getElementById("device_info");
		info_id.style.display="";
		const url = "./inc/query.php?fr=sensors&act=info&fm=json&pk=" + pk;
		console.log(url);
		$.getJSON(url, function(response) {
			// console.log(response);
			document.getElementById("code").innerHTML = response["info"]["code"];
			document.getElementById("name").innerHTML = response["info"]["name"];
			document.getElementById("mac").innerHTML = response["info"]["mac"];
			document.getElementById("brand").innerHTML = response["info"]["brand"];
			document.getElementById("model").innerHTML = response["info"]["model"];
			document.getElementById("usn").innerHTML = response["info"]["usn"];
			document.getElementById("product_id").innerHTML = response["info"]["product_id"];
			document.getElementById("store_name").innerHTML = response["info"]["store_name"];
			document.getElementById("initial_access").innerHTML = response["info"]["initial_access"];
			document.getElementById("last_access").innerHTML = response["info"]["last_access"];
			document.getElementById("license").innerHTML = response["info"]["license"];
			for(let i=0; i<4; i++) {
				document.getElementById("functions[" + i + "]").innerHTML = response["info"]["functions"][i];
				document.getElementById("features[" + i + "]").innerHTML = response["info"]["features"][i];
			}
			document.getElementById("comment").innerHTML = response["info"]["comment"];
			z = document.getElementById("zone_id");
			z.style.background = 'url("' + response["info"]["snapshot"] + '") no-repeat';
			z.style.backgroundSize = "800px 450px";
			draw_zone(z, response["info"]["zone"]);
		});
	}
	
	function draw_zone(id, zone) {
		// console.log(zone);
		const context = id.getContext("2d");
		const width = 800; height =  450;
		context.clearRect(0, 0, width, height);
		let P = new Array();
		let x = new Array();
		let y = new Array();
		for (let i=0; i<zone.length; i++) {
			P = zone[i]['points'].split(',');
			if(zone[i]['style'] == 'polygon'){
				P.push(P[0]);
			}
			for (let j=0; j<P.length; j++) {
				p_xy = P[j].split(":");
				x[j] = Math.round((width*p_xy[0])/65535);
				y[j] = Math.round((height*p_xy[1])/65535);
			}
			context.beginPath(); 	
			context.moveTo(x[0], y[0]);
			for (let j=1; j<P.length; j++) {
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
	
	function doAnalysis() {
		const square_ref = document.getElementById("square").value;
		const store_ref = document.getElementById("store").value;
		const list_id = document.getElementById("device_list");
		const info_id = document.getElementById("device_info");
		info_id.style.display="none";		

		const url = "./inc/query.php?fr=sensors&act=list&fm=json&sq=" + square_ref + "&st=" + store_ref;
		console.log(url);
		list_id.innerHTML ='';
		$.getJSON(url, function(response) {
			console.log(response);
			for(i=0; i< response["list"].length; i++) {
				list_id.innerHTML += '<div class="col-12 col-md-6 col-lg-4">'+
					'<div class="card">'+
						'<div class="card-header">'+
							'<span class="float-right">'+response["list"][i]['regdate']+'</span>'+
							'<h3 class="card-title mb-0"><b>'+response["list"][i]['name']+'</b></h3>'+
						'</div>'+
						'<img class="card-img-top" src="' + response["list"][i]['snapshot']+'" alt="Unsplash" ></img>'+		
						'<div class="card-body">'+
							'<h5 class="mt-2">' + response["lang"]["square name"] + ': '+response["list"][i]['square_name']+'</h5>'+
							'<h5>' + response["lang"]["store name"] + ': '+ response["list"][i]['store_name']+'</h5>'+
							'<h5>' + response["lang"]["device info"] +': '+ response["list"][i]['device_info']+'</h5> '+
							'<!--p class="card-text">' + response["lang"]["memo"] + '</p-->' +
							'<button class="btn btn-primary btn-sm" OnClick="deviceInfo(' + response["list"][i]["pk"] + ');">' + response["lang"]['detail'] +'</button >' +
						'</div>'+
					'</div>'+
				'</div>';
			}
		});
	}
	$(document).ready(function(){
		doAnalysis();
	});
	
	
}

else if(Get['fr'] == "sitemap") {
	const url = "./inc/query.php?fr=sitemap";
	// console.log(url);
	let posting = $.post(url,{});
	posting.done(function(data) {
		// console.log(data);
		document.getElementById("table_body").innerHTML = data;
		
	});

}

else if(Get['fr'] == "version") {
	function getContents() {
		const url = "./inc/query.php?fr=version&fm=json";
		// console.log(url);
		document.getElementById("changelog").innerHTML = '';
		$.getJSON(url, function(response) {
			// console.log(response);
			for(let i=0; i <response.length ; i++) {
				document.getElementById("changelog").innerHTML += ''+
					'<h4 class="d-inline-block"><span class="badge badge-primary">' + response[i]["title"] + '</span></h4>' +
					'<h5 class="d-inline-block ml-2">– ' + response[i]["date"] + '</h5>' + response[i]["body"];
			}
			
		});
	}

	getContents();
}

else if(Get['fr'] == "feedback") {
	$(function() {
		if (!window.Quill) {
			return $("#quill-editor,#quill-toolbar").remove();
		}
		let editor = new Quill("#quill-editor", {
			modules: {
				toolbar: "#quill-toolbar"
			},
			placeholder: "Type something",
			theme: "snow"
		});
	});
	
	function writeContents() {
		const body = document.getElementById("quill-editor").innerHTML;
		const title = document.getElementById("title").value;
		console.log(body);
		const url = "./inc/query.php?fr=feedback&act=write&fm=json";
		const posting = $.post(url,{body:body, title:title});
		posting.done(function(data) {
			debugLog(data);
			
		});
	}
	function listContents() {
		const url = "./inc/query.php?fr=feedback&act=list&fm=json";
		debugLog(url);
		document.getElementById("listContents").innerHTML = '';
		$.getJSON(url, function(response) {
			debugLog(response);
			for(i=0; i <response.length ; i++) {
				document.getElementById("listContents").innerHTML += ''+
				'<h4 class="d-inline-block"><span class="badge badge-primary">' + response[i]["title"] + '</span></h4>' +
				'<h5 class="d-inline-block ml-2">– ' + response[i]["date"]  + '</h5><h5 class="d-inline-block ml-2">(' + response[i]["from"] + ')</h5>' + response[i]["body"];
			}
		});
	}
	
listContents();
	
}


document.addEventListener("DOMContentLoaded", function(){
	listSquare();

});		

	