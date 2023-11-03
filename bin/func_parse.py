# Copyright (c) 2022, Hans kim

# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
# 1. Redistributions of source code must retain the above copyright
# notice, this list of conditions and the following disclaimer.
# 2. Redistributions in binary form must reproduce the above copyright
# notice, this list of conditions and the following disclaimer in the
# documentation and/or other materials provided with the distribution.

# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
# CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
# INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
# MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
# CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
# SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
# SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
# INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
# WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
# NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
# OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


import time
from urllib.parse import urlparse, parse_qsl, unquote
import re, base64

from functions_s import (addSlashes, TZ_OFFSET, log)

def parseParam(body, device_family='IPN'): #body <type byte>
    dict_rs = {'usn':None, 'brand':None, 'productid':None, 'model':None, 'mac':None, 'ip4mode':None, 'ip4address_dhcp':None,  'ip4address':None, 'url':None, 'lic_pro':None, 'lic_count':None, 'lic_surv':None, 'heatmap':None, 'countrpt':None, 'macsniff':None, 'face_det':None, 'param':None, 'device_family':device_family, 'ret':False}

    try:
        body =  body.decode('utf-8')
    except Exception as e:
        log.warning(str(e))
        return dict_rs
    body = body.strip()

    regex = dict()
    regex['usn']        = re.compile(r"VERSION.serialno=(\w+)\n", re.IGNORECASE)
    regex['brand']      = re.compile(r"BRAND.brand=(.+)\n", re.IGNORECASE)
    regex['productid']  = re.compile(r"BRAND.Model.productid=(\w+)\n", re.IGNORECASE)
    regex['model']      = re.compile(r"BRAND.Product.shortname=(.+)\n", re.IGNORECASE)
    regex['mac']        = re.compile(r"NETWORK.Eth0.mac=(.+)\n", re.IGNORECASE)
    regex['ip4address'] = re.compile(r"NETWORK.Eth0.ipaddress=(.+)\n", re.IGNORECASE)
    regex['dhcp_enable']= re.compile(r"NETWORK.Eth0.dhcp.enable=(.+)\n", re.IGNORECASE)
    regex['dhcp_ip']    = re.compile(r"NETWORK.Eth0.dhcp.ipaddress=(.+)\n", re.IGNORECASE)

    regex['lic_pro']    = re.compile(r".licenseinfo=Pro", re.IGNORECASE)
    regex['lic_count']  = re.compile(r".licenseinfo=Count", re.IGNORECASE)
    regex['lic_surv']   = re.compile(r".licenseinfo=Surveillance", re.IGNORECASE)
    regex['heatmap']    = re.compile(r"VCA.Ch0.Hm.enable=yes", re.IGNORECASE)
    regex['countrpt']   = re.compile(r"VCA.Ch0.Crpt.Db.enable=yes", re.IGNORECASE)
    regex['macsniff']   = re.compile(r"UART.Ch1.mode=overip", re.IGNORECASE)
    regex['face_det']   = re.compile(r"FD.enable=yes", re.IGNORECASE)
    regex['face_det_c'] = re.compile(r"FD.Ch0.enable=yes", re.IGNORECASE)
    
    for k in regex:
        m = regex[k].search(body)
        if  k in ['lic_pro','lic_pro_ai','lic_count', 'lic_surv', 'heatmap', 'countrpt', 'macsniff', 'face_det', 'face_det_c']:
            dict_rs[k] = 'y' if m else 'n'
            continue
        if m:
            dict_rs[k] = m.group(1).strip()
    
    dict_rs['mac'] = dict_rs['mac'].replace(":", "").upper()
    print ('face_det', dict_rs['face_det'], dict_rs['face_det_c'])
    dict_rs['face_det'] = 'y' if (dict_rs['face_det'] == 'y' and dict_rs['face_det_c'] == 'y') else 'n'
    dict_rs['url'] = dict_rs['ip4address_dhcp'] if dict_rs['ip4mode'] == "dhcp" else dict_rs['ip4address']
    dict_rs['ip4mode'] = "dhcp" if dict_rs['dhcp_enable'] =='yes' else "static"
    dict_rs['param'] = addSlashes(body)
    
    del(dict_rs['face_det_c'])
    del(dict_rs['dhcp_enable'])
    # del(dict_rs['dhcp_ip'])

    dict_rs['ret'] = True
    # print (dict_rs)
    return dict_rs

def parseCountReport(body): # body type byte
    arr_record= []
    try:
        body =  body.decode('utf-8')
    except Exception as e:
        log.warning(str(e))
        return False
    body = body.strip()
    line = body.splitlines()
    #  Records:6 Counter:2,0:Counter 0,1:Counter 1
    regex_ctdb = re.compile(r"(\w+)\:(\d+) Counter:(\d+),(.*)", re.IGNORECASE)
    m = regex_ctdb.search(line[0])
    if m and ((m.group(1) ==  'Records') or (m.group(1) == 'Time')) :
        numberofrecords, numberofcounters, counterstring = m.group(2,3,4)
    else :
        log.warning("Invalid Record: %s" %line[0])
        return False

    numberofrecords, numberofcounters = int(numberofrecords), int(numberofcounters)
    counter_id = [None]*numberofcounters
    counter_name = [None]*numberofcounters
    tabs = counterstring.split(",")
    for i, tab in enumerate(tabs):
        counter_id[i], counter_name[i] = tab.split(":")
        counter_id[i] = int(counter_id[i].strip())
        counter_name[i] = counter_name[i].strip()

    for i in range(2, numberofrecords+1): #read the third line, because first line is tab, sencond line value = 0
        field = line[i].split(",") 
        try:
            tss = time.strptime(field[0], "%Y/%m/%d %H:%M:%S")
        except:
            tss = time.strptime(field[0], "%Y-%m-%d %H:%M:%S")
        timestamp = int(time.mktime(tss)) + TZ_OFFSET
        for j in range(0, numberofcounters):
            counter_value = int(field[j+1])
            if counter_value < 0 :
                counter_value = 0
            if counter_value :
                arr_record.append({'datetime':field[0].strip(), 'timestamp':timestamp, 'ct_id': counter_id[j], 'ct_name': counter_name[j], 'ct_value':counter_value})

    # print (arr_record)
    return arr_record


def parseHeatmapData(body):
	arr_record = []
	try:
		body =  body.decode('utf-8')
	except Exception as e:
		log.warning(str(e))
		return False
	body = body.strip()

	line = body.splitlines()
	numberofrecords = int(len(line)/46)
	for i in range (0, numberofrecords) :
		tss = time.strptime(line[i*46], "%Y-%m-%d_%H:%M:%S")
		datetime = time.strftime("%Y-%m-%d %H:00:00", tss)
		tss = time.strptime(datetime, "%Y-%m-%d %H:%M:%S")
		timestamp = int(time.mktime(tss)) + TZ_OFFSET
		str_csv = ""
		for j in range(1, 45) :
			str_csv += (line[j+i*46]+"\r\n")
		arr_record.append({"timestamp":timestamp, "datetime":datetime, "heatmap":str_csv})
	
	return arr_record

def parseEventData(body, tunnel="HTTP"):
	arr_rs = []
	# regex_info = re.compile(r"info=ch=0\&type=counting\&ct(\d+)\[id=(\d+),name=(.+?),val=(\d+)\]\&timestamp=(\d+).[0-9]*\&", re.IGNORECASE)
	regex_info = re.compile(r"ct(\d+)\[id=(\d+),name=(.+?),val=(\d+)\]\&timestamp=(\d+).[0-9]*\&", re.IGNORECASE)
	try:
		body =  body.decode('utf-8')
	except Exception as e:
		log.warning(str(e))
		return False
	body = body.strip()

	if tunnel == "TCP":

# DOOFTEN305EVENT/1.0
# ip=192.168.1.28
# unitname=NS1402HD-6117
# datetime=Fri Jan 15 05:11:59 2021
# dts=1610658719.939152
# type=vca
# info=ch=0&type=counting&ct1[id=1,name=Counter 1,val=4014813]&timestamp=1610658719.939152
# id=76071E71-161E-4B87-9C78-923DDE2F61C4
# rulesname=counter
# rulesdts=1610658719.976864

		body = body.replace("DOOFTEN", "DOOFTEN?")
		body = body.replace("\n", "&")

# GET /count?ip%3d192.168.1.28%26unitname%3dNS1402HD-6117%26datetime%3dTue%20Jan%2019%2021:29:53%202021%26dts%3d1611062993.685058%26type%3dvca%26info%3dch%3d0%26type%3dcounting%26ct1%5bid%3d1%2cname%3dCounter%201%2cval%3d4106028%5d%26timestamp%3d1611062993.685058%26id%3d35248F55-71C1-4D82-9DB0-838ABAD4B62D%26rulesname%3dcounter%26rulesdts%3d1611062993.734832%26usn%3dG90A0031C HTTP/1.1
# Host: 192.168.1.2:5300
# Accept: */*
	body = unquote(body).strip()                      
	                                                                                                                  
# GET /count?ip=192.168.1.28&unitname=NS1402HD-6117&datetime=Thu May 06 19:42:27 2021&dts=1620301347.322955&type=vca&info=                   ct0[id=0,name=Counter 0,val=4851990]&timestamp=1620301347.322955&id=24433493-3842-48AC-988F-DA6CE92790AF&rulesname=counter&rulesdts=1620301347.413734&usn=G90A0031C
# GET /count?ip=192.168.1.28&unitname=NS1402HD-6117&datetime=Thu May 06 19:47:54 2021&dts=1620301674.949150&type=vca&info=ch=0&type=counting&ct1[id=1,name=Counter 1,val=6046910]&timestamp=1620301674.94915&id=920E856B-EB71-4FD3-B364-9BAE48454A97&rulesname=counter&rulesdts=1620301674.163225&usn=G90A0031C

	string = body[:body.index("HTTP/1.1")].strip()
	# print (string)
# {'ip': '192.168.1.28', 'unitname': 'NS1402HD-6117', 'datetime': 'Thu May 06 19:42:27 2021', 'dts': '1620301347.322955', 'type': 'vca', 'info': 'ct0[id=0,name=Counter 0,val=4851990]', 'timestamp': '1620301347.322955', 'id': '24433493-3842-48AC-988F-DA6CE92790AF', 'rulesname': 'counter', 'rulesdts': '1620301347.413734', 'usn': 'G90A0031C'}
	rs_t = dict(parse_qsl(urlparse(string).query))
	# print (rs_t)
	m_info = regex_info.finditer(string)
	# print (m_info)
	for m in m_info:
		arr_rs.append({'ip':rs_t['ip'], 'ct_id':int(m.group(2)), 'ct_name': m.group(3), 'ct_val':int(m.group(4)), 'timestamp':int(m.group(5)),'message':body.replace("\n", " ")})

	return arr_rs

def parsePostData(body): # body type byte, including image
	tmp =""
	rs = {'getstr':None, 'eventinfo':None, 'usn':None, 'productid':None, 'img_b64':None, 'snapshot_b64':None}

	regex_boundary = re.compile(b"Content-Type: multipart/form-data; boundary=([\-\w]+)", re.IGNORECASE)
	regex_getstr = re.compile(b"POST /(\S+) HTTP/1.1", re.IGNORECASE)
	regex_eventinfo = re.compile(b'Content-Disposition: form-data; name="eventinfo"\r\n\r\n(.+)\n', re.IGNORECASE)
	regex_thumbnail = re.compile(b'Content-Disposition: form-data; name="image_(\d+)"; filename="image_(\d+).jpg"\r\nContent-Type: image/jpeg', re.IGNORECASE)
	regex_snapshot = re.compile(b'Content-Disposition: form-data; name="snapshot"; filename="([\w_]+).jpg"\r\nContent-Type: image/jpeg', re.IGNORECASE)

	m = regex_boundary.search(body[0:600])
	if m:
		sbound = m.group(1)

	blocks = body.split(sbound)

	for block in blocks:
		if regex_getstr.search(block):
			rs['getstr'] = unquote((regex_getstr.search(block)).group(1).decode('ascii')).strip()
			rs['getstr'] = rs['getstr'][:255]

		elif regex_eventinfo.search(block):
			rs['eventinfo'] = regex_eventinfo.search(block).group(1).decode('ascii').strip()
			rs_t = dict(parse_qsl(urlparse("?" + rs['eventinfo']).query))
			rs['usn'], rs['productid'] = rs_t['usn'], rs_t['productid']
			tss = time.strptime(rs_t['isotime'], "%Y-%m-%dT%H:%M:%S 0800")
			rs['timestamp'] = int(time.mktime(tss) + TZ_OFFSET)
			rs['datetime'] = time.strftime("%Y-%m-%d %H:%M:%S", tss)			

		elif regex_thumbnail.search(block):
			st = block.find(b'Content-Type: image/jpeg') + len(b'Content-Type: image/jpeg')
			ed = len(block)-2
			tmp = block[st:ed]
			tmp = b'data:image/jpg;base64,' + base64.b64encode(tmp.strip())
			rs['img_b64'] =  tmp.decode('ascii')

		elif regex_snapshot.search(block):
			st = block.find(b'Content-Type: image/jpeg') + len(b'Content-Type: image/jpeg')
			ed = len(block)-2
			tmp = block[st:ed]
			tmp =  b'data:image/jpg;base64,' + base64.b64encode(tmp.strip())			
			rs['snapshot_b64'] = tmp.decode('ascii')

	return rs