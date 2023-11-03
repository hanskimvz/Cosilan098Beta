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


Version = 0.95

import os, sys, time
from functions_s import _VERSION

if _VERSION :
    from functions_s import info_from_db
    print (info_from_db())
    sys.exit()

import json
import socket
import threading
from functions_s import (configVars, log, info_to_db, _PLATFORM,  modifyConfig, checkNetworkLink, _ROOT_DIR, message, _MANAGER_PORT)
from tlss_counting import thTLSS
from proc_event import thEventPush
from active_counting import thActiveCountingTimer
from face_det import thFaceDetTimer
from proc_db import thProcDBCustomTimer
from sysdaemon import sysControlTimer

    
_FORCE_STOP =  False

def is_alive(cmd_str) :
    PID = []
    if os.name == 'nt' :
        a = os.popen('tasklist /fi "imagename eq ' + cmd_str +'" /nh')
    elif os.name == 'posix':
        a = os.popen('ps -ef |grep {0}'.format(cmd_str))

    p = a.read()
    for line in p.splitlines() :
        if line.find('grep') >=0:
            continue

        if line.find(cmd_str) >=0:
            for i in range(10):
                line = line.replace("  "," ")
            s_line = line.split(" ")
            PID.append(int(s_line[1]))
    
    return PID

def execute_services() :	
    if os.name == 'nt':
        #maria db	
        if not is_alive("mysqld.exe"):
            path = configVars('software.mysql.path')
            if os.path.isfile(path+"\\mysqld.exe") :
                os.chdir(path)
                os.system("start mysqld.exe")
                log.info("Mysqld Startd")
            else :
                log.info('file "mysqld.exe" not exist')
            os.chdir(_ROOT_DIR + "\\bin")
            time.sleep(10)
            
        #PHP
        if not is_alive("php-cgi.exe") :
            os.chdir(_ROOT_DIR + "\\php")
            a = os.system('start "PHP-CGI 127.0.0.1:9000" RunHiddenConsole.exe php-cgi.exe -q -c php.ini -b 127.0.0.1:9000')
            log.info("PHP-CGI Startd")
            os.chdir(_ROOT_DIR + "\\bin")
            time.sleep(2)
        
        #Nginx	
        if not is_alive("nginx.exe"):
            os.chdir(_ROOT_DIR + "\\NGINX")
            a = os.system("start nginx.exe")
            log.info("NGINX Startd")
            os.chdir(_ROOT_DIR + "\\bin")
            time.sleep(2)
        
        return {"mysqld": is_alive("mysqld.exe"), "php": is_alive("php-cgi.exe"), "nginx": is_alive("nginx.exe")}

    elif os.name == 'posix':
        if not is_alive("mysqld"):
            message ("Mysql:" + is_alive('mysql'))

        if not is_alive("php-fpm"):
            message ("PHP: " + is_alive('php-fpm'))

        if not is_alive("nginx"):
            message ("Nginx: " + is_alive('nginx'))
        
        return {"mysqld": is_alive("mysqld"), "php": is_alive("php-fpm"), "nginx": is_alive("nginx")}


def execThread(thread, go):
    global _th

    if go =='stop':
        _th[thread].stop()

    elif go == 'start':
        if thread == 'tlss':
            _th['tlss'] = thTLSS()
            _th['tlss'].start()

        elif thread=='active_count':
            _th['active_count'] = thActiveCountingTimer()
            _th['active_count'].start()

        elif thread=='proc_custom_db':
            _th['proc_custom_db'] = thProcDBCustomTimer()
            _th['proc_custom_db'].start()

        elif thread=='event':
            _th['event'] = thEventPush()
            _th['event'].start()

        elif thread=='face_det':
            _th['face_det'] = thFaceDetTimer()
            _th['face_det'].start()

        elif thread=='sys_ctrl':
            _th['sys_ctrl'] = sysControlTimer()
            _th['sys_ctrl'].start()

        elif thread=='thread_status':
            _th['thread_status'] = thThreadStatusTimer()
            _th['thread_status'].start()

        elif thread=='manager':
            _th['manager'] = thManager()
            _th['manager'].start()


def execute_commands():
    if _FORCE_STOP == True:
        return True

    global _th
    # st_network = checkNetworkLink()

    if configVars('software.service.counting.enable') == 'yes':
        if not _th['tlss'] or _th['tlss'].is_alive() == False:
            execThread('tlss', 'start')
            time.sleep(1)
        
        if not _th['active_count'] or _th['active_count'].is_alive() == False:
            if  _th['active_count']:
                _th['active_count'].stop()
                time.sleep(2)
            execThread('active_count', 'start')
            time.sleep(1)
        
        if not _th['proc_custom_db'] or _th['proc_custom_db'].is_alive() == False:
            if _th['proc_custom_db']:
                _th['proc_custom_db'].stop()
                time.sleep(2)
            execThread('proc_custom_db', 'start')
            time.sleep(1)

    elif configVars('software.service.counting.enable') == 'no':
        if _th['tlss'] and _th['tlss'].is_alive() ==True:
            _th['tlss'].stop()

        if _th['active_count'] and _th['active_count'].is_alive() == True:
            _th['active_count'].stop()

        if _th['proc_custom_db'] and _th['proc_custom_db'].is_alive() == True:
            _th['proc_custom_db'].stop()

    if configVars('software.service.event.enable') == 'yes':
        if not _th['event'] or _th['event'].is_alive() == False:
            execThread('event', 'start')
            time.sleep(1)        

    elif configVars('software.service.event.enable') == 'no':
        if _th['event'] and _th['event'].is_alive() == True:
            _th['event'].stop()

    if configVars('software.service.face.enable') == 'yes':
        if not _th['face_det'] or _th['face_det'].is_alive() == False:
            if _th['face_det']:
                _th['face_det'].stop()
                time.sleep(2)
            execThread('face_det', 'start')
            time.sleep(1)
    
    elif configVars('software.service.face.enable') == 'no':
        if _th['face_det'] and _th['face_det'].is_alive() == True :
            _th['face_det'].stop()

    if not _th['sys_ctrl'] or _th['sys_ctrl'].is_alive() == False:
        if _th['sys_ctrl']:
            _th['sys_ctrl'].stop()
            time.sleep(5)
        execThread('sys_ctrl', 'start')
        time.sleep(1)

    if not _th['thread_status'] or _th['thread_status'].is_alive() == False:
        if _th['thread_status']:
            _th['thread_status'].stop()
            time.sleep(5)
        execThread('thread_status', 'start')
        time.sleep(1)



########################## Manager ########################
Running = {}
_th = {}
def getServiceSt():
    if os.name == 'posix':
        arr_rs = {
            "mysqld" :{"status":"stopped","path":"", "code":0},
            "nginx"  :{"status":"stopped","path":"", "code":0},
            "php-fpm":{"status":"stopped","path":"", "code":0},
            "startbi":{"status":"stopped","path":"", "code":0},
        }
        # for line in os.popen(""" ps -ef |grep -P "nginx|php|startBi|mysqld" | grep -v "grep" """).read().splitlines():
        for line in os.popen(""" ps -ef """).read().splitlines():
            line = line.lower().strip()
            if not line:
                continue
            for rs in arr_rs:
                if line.find(rs) >= 0 :
                    arr_rs[rs]['status'] = "running"
                    arr_rs[rs]['code'] = 1

        # for rs in arr_rs:
        #     if arr_rs[rs]['status'] == "running":
        #         arr_rs[rs]['path'] = os.popen("which %s " %rs).read().strip()

    elif os.name == 'nt':
        arr_rs = {
            "mysqld" :{"status":"stopped","path":"wrong", "code":0},
            "nginx"  :{"status":"stopped","path":"wrong", "code":0},
            "php-cgi":{"status":"stopped","path":"wrong", "code":0},
            "startbi":{"status":"stopped","path":"wrong", "code":0},
        }

        cmd_str = """wmic process where "name='mysqld.exe' or name='php-cgi.exe' or name='nginx.exe' or commandline like '%startBI.py%'" get caption, commandline, executablePath"""
        for line in str(os.popen(cmd_str).read()).splitlines():
            line = line.lower().strip()
            if not line or line.find("wmic") >=0:
                continue
            for rs in arr_rs:
                if line.lower().find(rs) >= 0 :
                    arr_rs[rs]['status'] = "running"
                    tabs  =  line.split(" ")
                    arr_rs[rs]['path'] = os.path.dirname(tabs[-1]) +"\\"
                    arr_rs[rs]['code'] = 1 if arr_rs[rs]['path'].find(_ROOT_DIR.lower()) >=0 else -1

    return arr_rs

def getThreadSt():
    global _th
    global Running
    thd = []
    for t in _th:
        try:
            Running[t] = int(time.time()) - int(_th[t].last)
        except:
            pass
            
        thd.append({
            "name": t, 
            "thread": str(_th[t].__class__.__name__), 
            "is_alive": str(_th[t].is_alive()), 
            "running": Running[t],
            "last": str(_th[t].last)
        })
    return thd

def logStatus():
    thd = getThreadSt()
    st = execute_services()
    message("================================================================")
    message("%-26s %-18s %-8s %-8s" %("Thread", "name", "is alive", "running"))
    message("----------------------------------------------------------------")
    for t in thd:
        message("%-26s %-18s %-8s %-8s" %(t['thread'], t['name'], t['is_alive'], t['running']))
    
    e = ""
    for t in thd:
        e += "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>" %(t['thread'], t['name'], t['is_alive'], t['running'])

    e += "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>" %("mysqld", "mysqld", str(st['mysqld']), "")
    e += "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>" %("php", "php", str(st['php']), "")
    e += "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>" %("nginx", "nginx", str(st['nginx']), "")
    e = '<table>' + e + '</table>'
    log.info(e)

class thThreadStatusTimer():
    def __init__(self):
        self.name = "thread_status"
        self.t = 120
        self.last = int(time.time())
        self.thread = threading.Timer(1, self.handle_function)

    def handle_function(self):
        self.main_function()
        self.last = int(time.time())
        
        self.thread = threading.Timer(self.t, self.handle_function)
        self.thread.start()

    def main_function(self):
        logStatus()
 
    def start(self):
        message ("starting thread status")
        try:
            self.thread.start()
        except:
            self.thread = threading.Timer(self.t, self.handle_function)
            self.thread.start()

        self.last = int(time.time())
        
    def is_alive(self):
        if int(time.time()) - self.last > 240 :
            return False
        return True

    def cancel(self):
        message ("stopping  thread status")
        self.thread.cancel()

    def stop(self):
        self.cancel()
    
    # def pause(self):
    #     self.exe_flag = False

    # def play(self):
    #     self.exe_flag = True


def com_manager(conn):
    command_list = [
        "exit()",
        "status",
        "service",
        "start",
        "stop",
        "done",
        "config",
        "param",
        "run"
    ]	
    while True:
        recv = conn.recv(1024)
        for i in range(10):
            recv = recv.replace(b"  ", b" ")

        recv = recv.decode().strip().lower()
        if not True in [recv.startswith(x) for x in command_list] :
            conn.send(b'Invalid Command\n')
            continue

        ex = recv.split(' ')
        if ex[0] == 'exit()':
            _FORCE_STOP = True
            for t in _th:
                if t == 'manager' or t=='thread_status':
                    continue
                _th[t].stop()
            # conn.close()
            break

        if ex[0] == 'run':
            _FORCE_STOP = False
            break


        if ex[0] == 'done':
            conn.close()
            break

        if ex[0] == 'status':
            thd = getThreadSt()
            msg = str(json.dumps(thd, indent=2))
            conn.send(msg.encode())
            continue
        
        if ex[0] == 'service':
            thd = getServiceSt()
            msg = str(json.dumps(thd, indent=2))
            conn.send(msg.encode())
            continue

        if (ex[0] == 'start' or ex[0] == 'stop') and len(ex) == 2:
            thread_name = ex[1].strip()
            if not thread_name in [_th[x].name for x in _th]:
                conn.send(b"Invalid thread name\n")
                continue

            if ex[0] == 'start' and _th[thread_name].is_alive() == False:
                execThread(thread_name, 'start')
                msg = thread_name + "starting"
                conn.send(msg.encode())

            elif ex[0] == 'stop':
                _th[thread_name].stop()
                _th[thread_name].stop()
                msg = thread_name + " stopping"
                conn.send(msg.encode())
        
        elif (ex[0] == 'config'  and len(ex) == 3 and ex[1]=='view'):
            rs = configVars(ex[2])
            try :
                msg = str(json.dumps(rs, indent=2))
            except:
                msg = rs
            conn.send(msg.encode())

        elif (ex[0] == 'config' and len(ex) == 4 and ex[1]=='set'):
            conn.send(b'config set')
        
        elif (ex[0] == 'param'  and len(ex) == 3):
            thread_name = ex[1].strip()
            param = ex[2].strip()
            val = str(_th[thread_name][param])
            conn.send(val.encode())

    conn.close()


class thManager(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self, name = "manager")
        self.daemon = True
        self.port =  _MANAGER_PORT
        self.running = True
        self.last = 0

    def run(self):
        try:
            self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        except socket.error as  msg:
            return False
        try:
            self.s.bind(('', self.port))
        except socket.error as msg:
            # log.critical("Manager, Bind Failed. Error: {0}".format(str(msg)))
            message ("Manager: Bind Failed. Error: {0}".format(str(msg)))
            self.s.close()
            return False

        self.s.listen(5) 
        message("Manager: Listening...") 

        while self.running :
            self.conn, self.addr = self.s.accept()
            self.last = int(time.time())
            message ("Manager: %s:%s connected" %(self.addr[0], str(self.addr[1])))
            self.t0 = threading.Thread(target=com_manager, args=(self.conn, ))
            self.t0.start()

        self.s.close()

    def stop(self):
        message ("stopping Manager, running will be false")
        self.running = False


if __name__ == '__main__':
    config = {
        'counting': configVars('software.service.counting.enable'),
        'event': configVars('software.service.event.enable'),
        'face': configVars('software.service.face.enable'),
    }
    _th['manager']          = thManager()
    _th['thread_status']    = thThreadStatusTimer()
    _th['tlss']             = thTLSS()
    _th['event']            = thEventPush()
    _th['active_count']     = thActiveCountingTimer()
    _th['face_det']         = thFaceDetTimer()
    _th['proc_custom_db']   = thProcDBCustomTimer()
    _th['sys_ctrl']         = sysControlTimer()

    _th['manager'].start()
    # _th['thread_status'].start()



    # execute_commands()
    while True:
        # if Running['manager'] == -10 and   _th['manager'].is_alive() == False:
        #     break

        if _th['manager'].is_alive() == False:
            _th['manager'] = thManager()
            _th['manager'].start()

        execute_services()
        execute_commands()

        time.sleep(5)

