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

# wget http://49.235.119.5/download.php?file=../bin/monitor.py -O /var/www/bin/monitor.py
#Windows system only

_version = 0.95
import os, sys, time

if (os.name != 'nt'):
	print ("This program runs on windows system only")
	sys.exit()

import socket
import json
import threading
import locale
import optparse
from tkinter import *
from tkinter import ttk
import winreg
import psutil

_ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(sys.argv[0])))

op = optparse.OptionParser()

op.add_option("-p", action="store", type="int", dest="port")
op.add_option("-s", action="store", type="string", dest="ip")

opt, args = op.parse_args()
# print (opt, args)


arr_process = [
	'mysqld', 
	'nginx', 
	'php-cgi', 
	'startbi'
]

arr_var = [
	"svc_service", 
	"svc_status", 
	"svc_path", 
	"svc_operation",
	"btn_status", 
	"btn_start", 
	"btn_stop", 
	"thr_title_name", 
	"thr_title_thread", 
	"thr_title_is_alive", 
	"thr_title_running", 
	"thr_title_last"
]


lang = {}
var = {}
thread_status = {}


PROBE_INTERVAL = 2
TZ_OFFSET = 3600*8

_MANAGER_IP = '127.0.0.1'
_MANAGER_PORT = 5999

if opt.ip:
	_MANAGER_IP = opt.ip

if opt.port:
	_MANAGER_PORT =opt.port




def loadLangPack():
	LOCALE = locale.getdefaultlocale()
	if LOCALE[0] == 'zh_CN':
		key = 'Chinese'
	elif LOCALE[0] == 'ko_KR':
		key = 'Korean'
	else :
		key = 'English'
	# key = 'Chinese'

	arr_lang = {}
	fname = _ROOT_DIR + "/bin/monitor.json"
	if not os.path.isfile(fname):
		return False
	with open(fname, 'r', encoding='utf-8') as f:
		json_str = f.read()

	for r in json.loads(json_str)['language']:
		arr_lang[r['key']] = r[key]
	
	return arr_lang



# def findMysqlPaths(): 
# 	#find all mysql, mariadb paths on system
# 	arr_path = list()
# 	if os.name == 'nt':
# 		cmd = " wmic process where name='mysqld.exe' get executablepath &\
# 				wmic service where name='mariadb' get pathname &\
# 				wmic service where name='mysql' get pathname"
# 		p = os.popen(cmd).read().upper()
# 		for line in p.splitlines():
# 			tp = line.find("MYSQLD.EXE")
# 			if tp >0:
# 				line = line.replace('"', '')
# 				line = line[:tp+len("MYSQLD.EXE")].strip()
# 				arr_path.append(line)
# 	else :
# 		cmd = "which mysqld"
# 		p = os.popen(cmd).read().upper()
# 		arr_path.append(p.strip())

# 	return arr_path

# def status_process(mode = 0):
# 	global PROBE_INTERVAL
# 	st = dict()
# 	arr_rs = {
# 		"mysqld" :{"status":"stopped","path":"wrong", "code":0},
# 		"nginx"  :{"status":"stopped","path":"wrong", "code":0},
# 		"php-cgi":{"status":"stopped","path":"wrong", "code":0},
# 		"startbi":{"status":"stopped","path":"wrong", "code":0},
# 	}
# 	a = os.popen("""wmic process where "name='mysqld.exe' or name='php-cgi.exe' or name='nginx.exe' or name='python.exe' or name='python3.exe'" get caption, processid, commandline, executablePath """)
# 	lines = str(a.read()).splitlines()
# 	for line in lines:
# 		for rs in arr_rs:
# 			if line.lower().find(rs) >=0 :
# 				arr_rs[rs]['status'] = "running"
# 				if line.lower().find(_ROOT_DIR.lower()) >=0:
# 					arr_rs[rs]['code'] = 1
# 				else :
# 					arr_rs[rs]['code'] = -1
# 				for tab in line.split(" "):
# 					if rs == 'startbi':
# 						if tab.lower().find("\\python") >=0 :
# 							arr_rs[rs]['path'] = tab.strip()
# 					else :	
# 						if tab.lower().find("\\"+rs.lower()) >=0 :
# 							arr_rs[rs]['path'] = tab.strip()
						
	
# 	return arr_rs
	
def getInstalledMysql():
	arr = {'name':None, 'path':None, 'start_type':None, 'status':None}
	for s in psutil.win_service_iter():
		s_name = s.name()
		if s_name.lower() == 'mariadb' or s_name.lower() == 'mysql' or s_name.lower() == 'mysqld':
			q = s.as_dict()
			path = q['binpath'].replace('"', '')
			p = path.lower().find('mysqld.exe') + len('mysqld.exe')
			path = path[:p] if p>0 else ''
			arr = {'name':q['name'], 'path':path, 'start_type': q['start_type'], 'status':q['status']}
	return arr

def getServiceSt():
	arr_rs = {
		"mysqld" :{"pid":0, "status":"stopped","path":"wrong", "code":0},
		"nginx"  :{"pid":0, "status":"stopped","path":"wrong", "code":0},
		"php"	 :{"pid":0, "status":"stopped","path":"wrong", "code":0},
		"startbi":{"pid":0, "status":"stopped","path":"wrong", "code":0},
	}	

	for p in psutil.process_iter(['exe', 'cwd', 'cmdline']):
		p_name = p.name()
		for t in arr_rs:
			px = t
			if t == 'startbi':
				px = 'python3'
			elif t == 'php':
				if os.name == 'nt':
					px = 'php-cgi'
				elif os.name == 'posix':
					px = 'php-pfm'

			if p_name.find(px) >=0:
				q = p.as_dict()
				if not q['cwd']:
					q['cwd'] = os.path.dirname(q['exe'])
				q['cmdline'] = " ".join(q['cmdline']) if q['cmdline'] else ""
				if px == 'python3' and q['cmdline'].lower().find('startbi') <0:
					continue
				# print (t, p_name, q['exe'], q['cwd'], q['cmdline'])
				arr_rs[t] = {'pid':q['pid'], 'status':'running', 'path':q['cwd'], 'code':1 }
				arr_rs[t]['code'] = 1 if arr_rs[t]['path'].lower().find(_ROOT_DIR.lower()) >=0 else -1

	return arr_rs

# print(getServiceSt())
# x= getInstalledMysql()
# print(x)
# sys.exit()

# def getServiceSt():
# 	arr_rs = {
# 			"mysqld" :{"status":"stopped","path":"wrong", "code":0},
# 			"nginx"  :{"status":"stopped","path":"wrong", "code":0},
# 			"php-cgi":{"status":"stopped","path":"wrong", "code":0},
# 			"startbi":{"status":"stopped","path":"wrong", "code":0},
# 		}

# 	cmd_str ="""wmic process where name='mysqld.exe' get executablePath &\
# 				wmic process where name='php-cgi.exe' get executablePath &\
# 		 		wmic process where name='nginx.exe' get executablePath &\
# 				wmic process where commandline like '%startBI.py%'" get executablePath &\
# 				wmic service where name='mariadb' get pathname"""
	
# 	print(cmd_str)
# 	for line in str(os.popen(cmd_str).read()).splitlines():
# 		line = line.lower().strip()
# 		if not line or line.find("wmic") >=0:
# 			continue
# 		for rs in arr_rs:
# 			if line.lower().find(rs) >= 0 :
# 				line = line.replace('"', '')
# 				arr_rs[rs]['status'] = "running"
# 				tabs  =  line.split(" ")
# 				arr_rs[rs]['path'] = os.path.dirname(tabs[-1]) + "\\"
# 				arr_rs[rs]['code'] = 1 if arr_rs[rs]['path'].find(_ROOT_DIR.lower()) >=0 else -1

# 	return arr_rs
		

def start_services_windows():
	a = os.popen("tasklist")
	p = a.read().upper()
	#MYSQL
	# if p.find("MYSQL"):
	# 	if os.path.isdir(_ROOT_DIR +  "\\MariaDB\\bin"):
	# 		os.chdir(_ROOT_DIR + "\\MariaDB\\bin")
	# 	elif os.path.isdir(_ROOT_DIR +  "\\Mysql\\bin"):
	# 		os.chdir(_ROOT_DIR + "\\MariaDB\\bin")
		
	# 	b = os.system("start RunHiddenConsole.exe mysqld.exe")	

	# 	print("Mysqld Startd")
	# 	time.sleep(10)
			
	#PHP
	if p.find("PHP-CGI") :
		os.chdir(_ROOT_DIR + "\\php")
		b = os.system('start "PHP-CGI 127.0.0.1:9000" RunHiddenConsole.exe php-cgi.exe -q -c php.ini -b 127.0.0.1:9000')
		print ("PHP-CGI Startd")
		time.sleep(2)
        
	#Nginx	
	if p.find("NGINX"):
		os.chdir(_ROOT_DIR + "\\NGINX")
		a = os.system("start nginx.exe")
		print("NGINX Startd")
		time.sleep(2)

	# status_process()

	
def stop_services_windows(cat = ''):
	if cat == 'nginx' or not cat:
		p = os.popen("taskkill /F /IM nginx.exe > nul")
		print (p.read())
	if cat == 'php' or not cat	:
		p = os.popen("taskkill /F /IM php-cgi.exe > nul")
		print (p.read())
	if cat == 'mysql' or not cat:
		# p = os.popen("taskkill /F /IM mysqld.exe > nul")
		p = os.popen("%s/Mariadb/bin/mysqladmin -uroot -prootpass shutown" %_ROOT_DIR)
		print (p.read())

	# status_process()

def start_commands_windows():
	if _MANAGER_IP == '127.0.0.1' or _MANAGER_IP == 'localhost':
		arr = getServiceSt()
		print(arr)
		print(arr['startbi']['pid'], arr['startbi']['code'])
		if arr['startbi']['code'] == 0 and arr['startbi']['pid'] == 0:
			os.chdir(_ROOT_DIR + "\\BIN")
			p = os.system("RunHiddenConsole.exe python3.exe startBI.py")
			print ("service started")

	else :
		print ("local only")

def stop_commands_windows():
	print (thread_status)
	s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
	s.connect((_MANAGER_IP, _MANAGER_PORT))
	for th in thread_status:
		if th['name'] == 'manager' or th['name'] == 'thread_status':
			continue
		msg = "stop %s" %th['name']
		print (msg)
		s.send(msg.encode())
		time.sleep(1)
	s.send(b"exit()")
	s.close()
	
	if _MANAGER_IP == '127.0.0.1' or _MANAGER_IP == 'localhost':
		arr = getServiceSt()
		print (arr)
		pid = arr["startbi"]['pid']
		if(pid):
			p = os.popen("taskkill /F /PID %d" %pid)
			print (p.read())
		time.sleep(1)
		
class PT():
	def __init__(self, t, hFunction):
		self.t = t
		self.hFunction = hFunction
		self.thread = threading.Timer(self.t, self.handle_function)

	def handle_function(self):
		self.hFunction()
		self.thread = threading.Timer(self.t, self.handle_function)
		self.thread.start()

	def start(self):
		self.thread.start()	

	def cancel(self):
		self.thread.cancel()	


def patchLangPack():
	global var
	global lang
	
	var['svc_service'].set('Service')
	var['svc_status'].set('Status')
	var['svc_path'].set('Path')
	var['svc_operation'].set('Operation')
	var['btn_status'].set('Status')
	var['btn_start'].set('Start')
	var['btn_stop'].set('Stop')
	var['thr_title_name'].set('Name')
	var['thr_title_thread'].set('Thread')
	var['thr_title_is_alive'].set('Is Alive')
	var['thr_title_running'].set('Running')
	var['thr_title_last'].set('Last')

	for r in lang:
		try:
			var[r].set(lang[r])
		except:
			pass
	
class thStatus(threading.Thread):
	def __init__(self):
		threading.Thread.__init__(self, name = "stMonitor")
		self.daemon = True
		self.running = True
		# self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
		self.ip = _MANAGER_IP
		self.port = _MANAGER_PORT
		self.frsvc = False
		self.frthd = False
		self.socket_status = [False, False]

	def run(self):
		while self.running :
			arr= {}
			if self.ip == '127.0.0.1' or self.ip == 'localhost':
				arr = getServiceSt()
				# arr = status_process()
			else:
				try:
					self.s.send(b'service')
					arr = json.loads(self.s.recv(4096).decode())
				except Exception as e:
					arr ={}
					print (1, e)

			if arr and not self.frsvc:
				packServiceStatusPanel(frSVC, arr)
				patchLangPack()
				self.frsvc = True

			for p in arr:
				var['%s_status' %p].set("%s(%d)"  %(lang[arr[p]['status']],  int(arr[p]['code']) ))
				var['%s_path' %p].set(arr[p]['path'])
   
			
			if self.socket_status[0] == False:
				self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
				self.s.settimeout(1)
				self.socket_status[0] = True
				print ("created")

			if self.socket_status[1] == False: # not connected
				try:
					self.s.connect((self.ip, self.port))
					self.socket_status[0] = True
					self.socket_status[1] = True
					print ("connected")
					
				except Exception as e:
					print(2, e)
					if str(e).find("A connect request was made on an already connected socket") >0:
						self.socket_status[1] = True

					# self.socket_status[1] = False
			
			
			# if socketSt(self.s)['create'] == False :
			# 	self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
			# 	self.s.settimeout(1)

			# if socketSt(self.s)['connect'] == False :
			# 	try:
			# 		self.s.connect((self.ip, self.port))
			# 	except Exception as e:
			# 		print(2, e)
			# 		pass
			print(self.socket_status)
			if self.socket_status[0] == False or self.socket_status[1] == False :
			# if socketSt(self.s)['create'] == False or socketSt(self.s)['connect'] == False:
				time.sleep(PROBE_INTERVAL)
				continue

			try:
				self.s.send(b'status')
				arr = json.loads(self.s.recv(4096).decode())
			
			except ConnectionResetError as e:
				print (3, e)
				# self.socket_status[0] = False
				self.socket_status[1] = False
				if str(e).find("An existing connection was forcibly closed by the remote host") >=0:
					self.socket_status[0] = False
					# self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
				time.sleep(PROBE_INTERVAL)
				continue
			
			if not self.frthd:
				packThreadStatusPanel(frTHD, arr)
				patchLangPack()
				self.frthd = True

			# print(arr)
			for th in arr:
				dts = time.gmtime(int(th['last']) +  int(TZ_OFFSET) )
				last_dts = time.strftime("%Y-%m-%d %H:%M:%S", dts)
				var[th['name']]['name'].set(th['name'])
				var[th['name']]['thread'].set(th['thread'])
				var[th['name']]['is_alive'].set(th['is_alive'])
				var[th['name']]['running'].set(th['running'])
				var[th['name']]['last'].set(last_dts)


			# print (threading.active_count())
			time.sleep(PROBE_INTERVAL)

	def stop(self):
		print ("running will be false")
		if socketSt(self.s)['create'] and socketSt(self.s)['connect'] :
			self.s.send(b'done')
		self.running = False
		time.sleep(2)
		self.s.close()


def socketSt(s):
	arr = { 'create': False, 'connect': False}
	strs = str(s)
	if strs.find('laddr=') > 0 and strs.find('raddr=(') >0 :
		arr['create'] = True
		arr['connect'] = True
		return arr

	if strs.find("fd=") >0 and strs.find("fd=-1") <0:
		arr['create'] = True

	if strs.find('laddr=(') >0 and strs.find("laddr=('0.0.0.0',") < 0:
		arr['connect'] = True
	# print(strs, arr)


	return arr

def packServiceStatusPanel(frSVC, arr):
	global var

	Label(frSVC, textvariable=var['svc_service']  ).grid(row=0, column=0, sticky="news", ipadx=5)
	Label(frSVC, textvariable=var['svc_status']	  ).grid(row=0, column=1, sticky="news", ipadx=10)
	Label(frSVC, textvariable=var['svc_path']     ).grid(row=0, column=2, sticky="news", ipadx=10)
	Label(frSVC, textvariable=var['svc_operation']).grid(row=0, column=3, sticky="news", ipadx=10)

	# for i, col in enumerate(arr_process):
	for i, col in enumerate(arr):
		var['%s_status' %col] = StringVar()
		var['%s_path' %col]   = StringVar()
		var['%s_button' %col] = StringVar()
		
		Label(frSVC, text=("%s" %col) ).grid(row=i+1, column=0, sticky="w", ipadx=10)
		Label(frSVC, textvariable = var['%s_status' %col]).grid(row=i+1, column=1, sticky="w", ipadx=20)
		Label(frSVC, textvariable = var['%s_path' %col]  ).grid(row=i+1, column=2, sticky="w", ipadx=20)

def packThreadStatusPanel(frTHD, arr):
	global var

	Label(frTHD, textvariable=var['thr_title_name']    ).grid(row=0, column=0, sticky="w", ipadx=5)
	Label(frTHD, textvariable=var['thr_title_thread']  ).grid(row=0, column=1, sticky="w", ipadx=5)
	Label(frTHD, textvariable=var['thr_title_is_alive']).grid(row=0, column=2, sticky="news", ipadx=5)
	Label(frTHD, textvariable=var['thr_title_running'] ).grid(row=0, column=3, sticky="news", ipadx=5)
	Label(frTHD, textvariable=var['thr_title_last']    ).grid(row=0, column=4, sticky="news", ipadx=5)


	for i, th in enumerate(arr):
		col = th['name']
		var[col] = {"name": StringVar(), "thread":StringVar(), "is_alive":StringVar(), "running":StringVar(), "last": StringVar()}
		Label(frTHD, textvariable=var[col]['name']    ).grid(row=i+1, column=0, sticky="w", ipadx=5)
		Label(frTHD, textvariable=var[col]['thread']  ).grid(row=i+1, column=1, sticky="w", ipadx=5)
		Label(frTHD, textvariable=var[col]['is_alive']).grid(row=i+1, column=2, sticky="news", ipadx=5)
		Label(frTHD, textvariable=var[col]['running'] ).grid(row=i+1, column=3, sticky="e", ipadx=5)
		Label(frTHD, textvariable=var[col]['last']    ).grid(row=i+1, column=4, sticky="news", ipadx=5)


if __name__ == '__main__':
	lang = loadLangPack()
	print (lang)
	print ("BI Monitor Version %.2f" %_version)

	window = Tk()
	window.protocol("WM_DELETE_WINDOW", window.destroy)
	window.title("%s %.2f - (%s)" %(lang['main_title'], _version, _MANAGER_IP ))
	# window.title("BI Monitor %.2f" %_version)

	window.geometry("600x400")
	window.resizable(True, True)

	for key in arr_var:
		var[key] = StringVar()

	frSVC = Frame(window, bd=0, relief="solid")
	frSVC.pack(side="top", expand=False, padx=10, pady=5)

	frCtrl = Frame(window, bd=0, relief="solid")
	frCtrl.pack(side="top", expand=False, padx=5, pady=10)

	# Button(frCtrl, textvariable = var['btn_status'], command=status_process,         width=6, height=1).pack(side="left", padx=5)
	Button(frCtrl, textvariable = var['btn_start'],  command=start_commands_windows, width=6, height=1).pack(side="left", padx=5)
	Button(frCtrl, textvariable = var['btn_stop'],   command=stop_commands_windows,  width=6, height=1).pack(side="left", padx=5)

	frTHD = Frame(window, bd=0, relief="solid")
	frTHD.pack(side="top", expand=False, padx=10, pady=0)

	t = thStatus()
	t.start()

	patchLangPack()
	window.mainloop()
	t.stop()
	
