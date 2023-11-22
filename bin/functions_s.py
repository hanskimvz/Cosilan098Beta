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

import os, time, sys
# from http.client import HTTPConnection
from configparser import ConfigParser
import socket
import re, base64, struct
# from urllib.parse import urlparse, parse_qsl, unquote
import threading
import pymysql
import logging, logging.handlers
import sqlite3
import signal
import json
import requests
from requests.auth import HTTPBasicAuth, HTTPDigestAuth
import optparse

op = optparse.OptionParser()
op.add_option("-V", "--version", action="store_true", dest="_VERSION")
op.add_option("-D", "--debug-display", action="store_true", dest="_DEBUG_DISPLAY")
op.add_option("-P", "--debug-port", action="store", dest="_MANAGER_PORT")
opt, args = op.parse_args()

def getPlatformName():
	HOST = (socket.gethostname()).upper()
	if HOST.startswith("COSILAN") and os.name == 'posix':
		HOST = "OPAVIS"
	return HOST

_VERSION = True if opt._VERSION  else False
_DEBUG_DISPLAY = True if opt._DEBUG_DISPLAY  else False
_MANAGER_PORT = opt._MANAGER_PORT if opt._MANAGER_PORT else 5999

_PLATFORM = getPlatformName()
_ROOT_DIR = os.path.dirname( os.path.dirname(os.path.abspath(sys.argv[0])) )

config_db_file = _ROOT_DIR + "/bin/param.db"
log_file_name  = _ROOT_DIR + "/bin/log/bi.log"
cgis_file_name = _ROOT_DIR + "/bin/cgis.json"

# Orange Pi
if _PLATFORM == 'OPAVIS':
	try: 
		import OPi.GPIO as GPIO
		GPIO.cleanup()
		GPIO.setboard(GPIO.PCPCPLUS)
		GPIO.setmode(GPIO.BOARD)
		PIN = {
			'ACT':7,
			'DATA':11,
			'LINK':10,
			'FAN':28
		}
		GPIO.setup(PIN['ACT'], GPIO.OUT) 
		GPIO.setup(PIN['DATA'], GPIO.OUT) 
		GPIO.setup(PIN['LINK'], GPIO.OUT) 
		GPIO.setup(PIN['FAN'], GPIO.OUT) 
		GPIO.output(PIN['FAN'], 1)
	except:
		pass

# from chkLic import  chkLicMachine, getMac


################ CONFIG and Variables ####################################
_SERVER = ''
_SERVER_MAC = ''
PROBE_INTERVAL = 0
CGIS = dict()

########################### log  ###########################
log = logging.getLogger("startBI")
if not os.path.exists(os.path.dirname(log_file_name)):
	os.makedirs(os.path.dirname(log_file_name))

log.setLevel(logging.INFO)
file_handler = logging.handlers.TimedRotatingFileHandler(filename = log_file_name, when = 'midnight', interval=1, encoding='utf-8')
file_handler.suffix = '%Y%m%d'
log.addHandler(file_handler)
formatter = logging.Formatter("%(levelname)-8s  %(asctime)s %(module)s %(funcName)s %(lineno)s %(message)s %(threadName)s")
file_handler.setFormatter(formatter)

_mysql_port = 3306
def message(strs):
	if not _DEBUG_DISPLAY :
		return False
	print (strs)

########################### config, parameters ###########################
def sqlDbMaster():
	global config_db_file
	if not os.path.isfile(config_db_file):
		message ("No config db file")
		return False

	conn = sqlite3.connect(config_db_file)
	conn.execute("PRAGMA journal_mode=WAL")
	return conn

def configVars(groupPath=''):
	arr_rs = dict()
	arr= []
	sq = ""
	if groupPath.strip():
		for i, x in enumerate(groupPath.split(".")):
			arr.append("group%d = '%s'" %((i+1),x))
		
		sq = " and ".join(arr)
	if sq:
		sq = " where " + sq + " "

	sq = "select entryValue, entryName, groupPath from param_tbl " + sq 
	# print(sq)
	configdbconn = sqlDbMaster()
	with configdbconn:
		cur = configdbconn.cursor()
		cur.execute(sq)
		rows = cur.fetchall()
		if not rows:
			return ''
		if len(rows) == 1 :
			return rows[0][0]
		
		for r in rows:
			arr_rs[r[2]+"."+r[1]] = r[0]
	return arr_rs

def info_to_db(title, change_log_str) :
	date_regex    = re.compile("(\d{4}-\d{2}-\d{2}),", re.IGNORECASE)
	sq_rows = []

	lines = change_log_str.splitlines()
	for line in lines:
		line = line.strip()
		if not line or line[0:1] == '#':
			continue
		
		m = date_regex.match(line)
		if m :
			st, en = m.span()
			date = m.group()[:-1]
			description = line[en:].strip()
			sq_rows.append((title, date, description))
		else :
			continue

	configdbconn = sqlDbMaster()
	with configdbconn:
		cur = configdbconn.cursor()
		sqs = "select prino from info_tbl where category='change_log' and entryName=? and entryValue=? and description=? "
		sqi = "insert into info_tbl(category, entryName, entryValue, description) values('change_log', ?, ?, ?)"
		for row in sq_rows:
			cur.execute(sqs, row)
			r = cur.fetchone()
			if not r:
				message(row)
				cur.execute(sqi, row)
		configdbconn.commit()

def info_from_db(title='', type='txt'):
	configdbconn = sqlDbMaster()
	with configdbconn:
		cur = configdbconn.cursor()
		sq = "select entryName, entryValue, description from info_tbl where category = 'change_log' "
		if title :
			sq += " and entryName = '" + title + "' "
		sq += " order by entryName, entryValue asc"
		# print(sqs)
		cur.execute(sq)
		rows = cur.fetchall()
		body = ''
		for row in rows:
			if type == 'txt':
				body += "%-16s %-12s  %s\n" %row
			elif type == 'html':
				body += "<tr><td>%s</td><td>%s</td><td>%s</td></tr>" %row
			# print(row)
		if type == 'html':
			body = '<table>' + body +'</table>'

	return body


def modifyConfig(groupPath, entryValue) :
	ex = groupPath.split(".")
	entryName = ex.pop()
	groupPath_ = ""
	for x in ex:
		if groupPath_:
			groupPath_ += "." 
		groupPath_ += x
	
	configdbconn = sqlDbMaster()
	with configdbconn:
		cur = configdbconn.cursor()
		sq = "update param_tbl set entryValue = '%s' where groupPath='%s' and entryName='%s'" %(entryValue, groupPath_, entryName)
		# print (sq)
		try:
			cur.execute(sq)
			configdbconn.commit()
		except Exception as e:
			message (str(e) + ", No groupPath or Entry Name")
			return False
	return True

def load_cgis():
	with open (cgis_file_name, "r", encoding="utf8") as f:
		body = f.read()

	return json.loads(body)

#################### FUNCTIONS ##############################

def callCommand(comm):
    p = os.popen(comm)
    return p.read()

def outLED(led, flag):
	global _PLATFORM	

	if _PLATFORM !='OPAVIS':
		return False

	if flag == 'ON':
		flag = 0
	elif flag == 'OFF':
		flag = 1

	GPIO.output(PIN[led], flag)
	
def addSlashes(strings):
	if isinstance(strings, bytes):
		try:
			strings = strings.decode("utf-8")
		except :
			strings = strings.decode("utf-16")
	symbols = ["\\", '"', "'", "\0", "$" ]
	for i in symbols:
		if i in strings: 
			strings = strings.replace(i, '\\' + i)
	return strings


def is_online(ip, port=80):
	#  if port is not 80 ??
	s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
	server = (ip, port)
	s.settimeout(1)
	try:
		s.connect(server)
	except Exception as e:
		# print(e)
		s.close()
		return False
	
	s.close()
	return True	


def checkServerStatus():
	if is_online (_SERVER):
		return True
	return False

def checkNetworkLink():
	if os.name == 'posix':
		a = callCommand('cat /sys/class/net/eth0/operstate' )
		# print (a)
		if a.strip().lower() == 'up':
			return True
	if os.name == 'nt':
		return is_online(_SERVER, port=80)
	return False

def checkAuthMode(dev_ip, userid='root', userpw='pass'):
	dev_type= None
	auth = None
	arr_dev = [
		('IPN',  'http://' + dev_ip + '/uapi-cgi/network.cgi'),
		('IPAI', 'http://' + dev_ip + '/cgi-bin/admin/network.cgi'),
		('IPE',  'http://' + dev_ip + '/cgi-bin/admin/tcpstatus.cgi')
	]
	arr_auth = [HTTPBasicAuth(userid, userpw), HTTPDigestAuth(userid, userpw)]
	if not is_online(dev_ip):
		return (auth, dev_type)
	
	for authkey in arr_auth:
		for dev, url in arr_dev:
			try:
				r = requests.get(url,  auth=authkey)
				if int(r.status_code) == 200:
					return authkey, dev
			except Exception as e:
				log.error(url + "," +str(e))
				pass
	return (auth, dev_type)
	# auth,   	dev_type
	# None, 	x		 ==> userid and password are not correct
	# x         None     ==> dev_ip cannot be reachable or not valid device

def active_cgi(dev_ip, authkey='', cgi_str='', port=80):
	url = '%s:%d/%s' %(dev_ip, port, cgi_str)
	url = 'http://'+ url.replace("//", "/").strip()
	# print(url)
	outLED('DATA', 'ON')
	try:
		r= requests.get(url , auth=authkey)
	except Exception as e:
		log.error(url + "," + str(e))
		return False
		
	outLED('DATA', 'OFF')
	return r.content

def recv_timeout(conn,timeout=2): 
	# TLSS only since 2022.03.20
	conn.setblocking(0)
	total_data=[]
	data=''
	begin=time.time()
	while 1:
		if total_data and time.time()-begin > timeout:
			break
		elif time.time()-begin > timeout*2:
			break
			
		try:
			data = conn.recv(1024)
			if data:
				total_data.append(data)
				begin=time.time()
			else:
				time.sleep(0.1)
		except:
			pass
	return  b''.join(total_data)

def send_tlss_command(conn, cmd=''):
    length = len(cmd)
    s_num = struct.pack("BBBB", length&0xFF, (length>>8)&0xFF, (length>>16)&0xFF, (length>>24)&0xFF)
    rs = "send_message: length:%d, num:%d, %s" %(length, len(s_num), cmd)
    outLED('ACT', 'ON')
    try:
        conn.send(s_num)
        conn.send(cmd.encode('ascii')) # send byte type
    except:
        pass
    outLED('ACT', 'OFF')
    return rs

def recv_tlss_message(conn, timeout=2):
    conn.setblocking(1)
    outLED('DATA', 'ON')
    data_num =  conn.recv(4)
    try: 
        # num = ord(data_num[0]) + (ord(data_num[1])<<8) + (ord(data_num[2])<<16) + (ord(data_num[3])<<24)
        num = int("%02X%02X%02X%02X" %(data_num[3],data_num[2],data_num[1],data_num[0]), 16)
    except:
        num=0

    rs = recv_timeout(conn, timeout)
    outLED('DATA', 'OFF')
    return rs

def getUpnpInfo(url, timeout=2) :
	rs = dict()
	url_regex      = re.compile(r"<modelURL>http://[ ]*(.+):(\d+)</modelURL>", re.IGNORECASE)
	usn_regex  = re.compile(r"<serialNumber>(.+)</serialNumber>", re.IGNORECASE)	
	mac_regex  = re.compile(r"<UDN>uuid:((.+))</UDN>", re.IGNORECASE)	
	model_regex    = re.compile(r"<modelName>(.+)</modelName>", re.IGNORECASE)	
	brand_regex    = re.compile(r"<manufacturer>(.+)</manufacturer>", re.IGNORECASE)	
	
	try:
		r = requests.get(url, timeout=timeout)
		# print (r.reason)
		if r.reason != 'OK':
			return False
		rs_t = r.content.decode()
	except Exception as e:
		# print(e)
		return False

	rs['url']   = url_regex.search(rs_t).group(1) if url_regex.search(rs_t) else ''
	rs['port']   = url_regex.search(rs_t).group(2) if url_regex.search(rs_t) else ''
	rs['model'] = model_regex.search(rs_t).group(1) if model_regex.search(rs_t) else ''
	rs['brand'] = brand_regex.search(rs_t).group(1) if brand_regex.search(rs_t) else ''
	rs['usn']   = usn_regex.search(rs_t).group(1) if usn_regex.search(rs_t) else ''
	rs['mac']   = mac_regex.search(rs_t).group(2) if mac_regex.search(rs_t) else ''
	rs['mac'] = rs['mac'].replace(':','').strip()

	return rs


def arp_device():
	dev_idx =[]
	try_port = [80, 49152, 49153]
	try_page = ['upnpdevicedesc.xml', 'DigitalSecurityCamera1.xml']
	locations = set()
	if os.name == 'nt':
		cmd = 'arp -a |findstr "00-13-2"'
	else :
		cmd = "arp -n |grep 00:13:2"

	arp_regex = re.compile(r"([0-9.]+)(\s+)([\w:]+)(.+)", re.IGNORECASE)
	p = os.popen(cmd).read()
	p = p.replace("ether",""); 	p = p.replace("-",""); 	p = p.replace(":","")
	# print (p)
	
	lines = p.splitlines()
	for i, line in enumerate(lines):
		regex = arp_regex.search(line)
		if regex:
			location = regex.group(1)
			mac = regex.group(3).upper()
			# print (location, mac)
		else :
			continue

		if not mac.startswith("00132"):
			continue

		if not is_online(location):
			continue
		
		for port in try_port:
			for page in try_page:
				if location in locations :
					continue
				url = 'http://%s:%d/%s' %(location, port, page)
				arr= getUpnpInfo(url,1)
				if not arr:
					continue
				if not mac:
					mac = arr['mac']

				dev_idx.append({"idx":i, "usn" : arr['usn'], "url":url, "location":location, "mac":mac, "model":arr['model'], "brand":arr['brand']})
				locations.add(location) # add succeeded ip
	
	return dev_idx


def ssdp_device(): # discover upnp devices
	dev_idx =[]
	locations = set()
	body = set()
	ST = 'ssdp:all'
	# ST = 'urn:schemas-upnp-org:device:nvcdevice'
	msg = \
		'M-SEARCH * HTTP/1.1\r\n' \
		'HOST:239.255.255.250:1900\r\n' \
		'ST:' + ST + '\r\n'\
		'MX:2\r\n' \
		'MAN:"ssdp:discover"\r\n' \
		'\r\n'
	s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
	s.settimeout(2)
	try:
		s.sendto(msg.encode('ascii'),('239.255.255.250', 1900))
	except:
		s.close()
		return dev_idx	


	try:
		while True: 
			# buffer size is 1024 bytes
			data, addr = s.recvfrom(1024) 
			# body.add(data.decode('ASCII'))
			body.add(data)

	except socket.error as e:
		s.close()

	if not body: #upnp not working
		return False

# HTTP/1.1 200 OK\r\n
# CACHE-CONTROL: max-age=100\r\n
# DATE: Sat, 05 Sep 2020 12:11:34 GMT\r\n
# EXT:\r\n
# LOCATION: http://192.168.4.173:49152/upnpdevicedesc.xml\r\n
# OPT: "http://schemas.upnp.org/upnp/1/0/"; ns=01\r\n
# 01-NLS: 1246cd92-ef52-11ea-9c79-dc56e6e75747\r\n
# SERVER: Linux/2.6.18_IPNX_PRODUCT_1.1.2-g3532e87a, UPnP/1.0, Portable SDK for UPnP devices/1.8.4\r\n
# X-User-Agent: redsonic\r\n
# ST: urn:schemas-upnp-org:device:nvcdevice\r\n
# USN: uuid:H10A004AD-00:13:24:A0:04:AD::urn:schemas-upnp-org:device:nvcdevice\r\n\r\n'

# HTTP/1.1 200 OK\r\n
# CACHE-CONTROL: max-age=360\r\n
# DATE: Sat, 19 Mar 2022 04:59:43 GMT\r\n
# EXT:\r\n
# LOCATION: http://192.168.132.6/DigitalSecurityCamera1.xml\r\n
# OPT: "http://schemas.upnp.org/upnp/1/0/"; ns=01\r\
# n01-NLS: d6baf0b4-a666-11ec-8f24-d7a4623af24a\r\n
# SERVER: Linux/5.4.61, UPnP/1.0, Portable SDK for UPnP devices/1.6.25\r\n
# X-User-Agent: redsonic\r\n
# ST: urn:schemas-upnp-org:device:DigitalSecurityCamera:1\r\n
# USN: uuid:30623834-3036-6666-3661-0013230b8406::urn:schemas-upnp-org:device:DigitalSecurityCamera:1\r\n\r\n'
	
	url_regex = re.compile(r"location:(.+)\r\n", re.IGNORECASE)
	location_regex = re.compile(r"http://[ ]*(\d+).(\d+).(\d+).(\d+)(.+)", re.IGNORECASE)
	uuid_regex  = re.compile(r"USN: uuid:(.+)::urn:schemas-upnp-org:device:(.+):1", re.IGNORECASE)	
	i=0
	for block in body:
		try:
			block = block.decode('utf-8')
		except:
			continue
		if block.find('ST: urn:schemas-upnp-org:device:DigitalSecurityCamera') <0 and  block.find('ST: urn:schemas-upnp-org:device:nvcdevice') <0:
			continue
		url_result = url_regex.search(block)
		url = url_result.group(1).strip() if url_result else None
		if (not url) or (url in locations) :
			continue

		uuid = uuid_regex.search(block)
		if uuid:
			ex = uuid.group(1).split("-")
			usn = ex[0]
			mac = ''.join(ex.pop().split(':'))

		loc = location_regex.search(url)
		location = loc.group(1) + '.' + loc.group(2) + '.' + loc.group(3) + '.' + loc.group(4) if loc else ''
		if not is_online(location):
			continue
		
		arr = getUpnpInfo(url)
		if not arr:
			print (arr)
			continue
		if not mac:
			mac = arr['mac']		
		if not usn:
			usn = arr['usn']
		
		dev_idx.append({"idx":i, "usn" : usn, "url": url, "location": location, "mac":mac, "model":arr['model'], "brand":arr['brand']})
		locations.add(url)
		i+=1
	
	return dev_idx

def list_device() :
	dev_idx = ssdp_device()
	if not dev_idx:
		print ("discover with arp")
		return arp_device()
	return dev_idx


################################### MYSQL MARIA DB ################################################

def findMysqlPaths(): 
	#find all mysql, mariadb paths on system
	arr_path = list()
	if os.name == 'nt':
		cmd = " wmic process where name='mysqld.exe' get executablepath &\
				wmic service where name='mariadb' get pathname &\
				wmic service where name='mysql' get pathname"
		p = os.popen(cmd).read().upper()
		for line in p.splitlines():
			tp = line.find("MYSQLD.EXE")
			if tp >0:
				line = line.replace('"', '')
				line = line[:tp+len("MYSQLD.EXE")].strip()
				arr_path.append(line)
	else :
		cmd = "which mysqld"
		p = os.popen(cmd).read().upper()
		arr_path.append(p.strip())

	return arr_path

def dbconMaster(host = '', user = '', password = '', db = '', charset ='', port=0): #Mysql
	if not host :
		host = str(configVars('software.mysql.host'))
	if not user:
		user = str(configVars('software.mysql.user'))
	if not password :
		password = str(configVars('software.mysql.password'))
	if not db:
		db = str(configVars('software.mysql.db'))
	if not charset:
		charset = str(configVars('software.mysql.charset'))
	if not port:
		port = int(configVars('software.mysql.port'))


	try:
		dbcon = pymysql.connect(host=host, user=str(user), password=str(password),  charset=charset, port=int(port))
	# except pymysql.err.OperationalError as e :
	except Exception as e :
		print ('dbconerr', str(e))
		return None
	
	return dbcon


def getMysqlPort(fname=''):
	if os.name =='nt' and not fname:
		fname = os.path.dirname(configVars('software.mysql.path')) + "\\data\\my.ini"
	else :
		return 3306
	# print (fname)
	if not os.path.isfile(_ROOT_DIR + "/MariaDB/data/my.ini"):
		return 3306
	cfg = ConfigParser(allow_no_value=True)
	cfg.read(fname)
	port = cfg.get("mysqld","port") 
	if not port:
		port = 3306
	return port

########################  PARSING DATA ###################################################################################################################
# ==> move to parse_func.py


################################################ Main #############################

modifyConfig("software.status.start_time", int(time.time()))
modifyConfig("software.service.root_dir", _ROOT_DIR)

# _mysql_port = getMysqlPort()
# print('mysql_port', _mysql_port)


_SERVER = configVars('software.root.update_server.address')
_SERVER_MAC = configVars('software.root.update_server.mac')
TZ_OFFSET =  configVars('system.datetime.timezone.offset')

# print (TZ_OFFSET)
try :
    TZ_OFFSET = int(TZ_OFFSET)
except:
    TZ_OFFSET = 0
if not TZ_OFFSET:
    TZ_OFFSET = 3600*8

PROBE_INTERVAL = configVars('software.service.probe_interval')
try:
    PROBE_INTERVAL = int(PROBE_INTERVAL)
except:
    PROBE_INTERVAL = 0
if not PROBE_INTERVAL:
    PROBE_INTERVAL = 30


MYSQL = {
    "commonParam": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.param'),
    "commonSnapshot": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.snapshot'),
    "commonCount": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.counting'),
    "commonHeatmap": configVars('software.mysql.db') +"." + configVars('software.mysql.db_common.table.heatmap'),
    "commonCountEvent": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.count_event'),
    "commonFace": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.face'),
    "customCount": configVars('software.mysql.db_custom.table.count'),
    "customHeatmap": configVars('software.mysql.db_custom.table.heatmap'),
    "customAgeGender": configVars('software.mysql.db_custom.table.age_gender'),
    "customSquare": configVars('software.mysql.db_custom.table.square'),
    "customStore": configVars('software.mysql.db_custom.table.store'),
    "customCamera": configVars('software.mysql.db_custom.table.camera'),
    "customCounterLabel": configVars('software.mysql.db_custom.table.counter_label'),
    "customRtCount": configVars("software.mysql.db_custom.table.rtscreen")
}
CGIS = load_cgis()
# print(MYSQL)
# a = list_device()
# print(a)