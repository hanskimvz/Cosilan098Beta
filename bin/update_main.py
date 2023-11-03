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

# wget http://49.235.119.5/download.php?file=../bin/update.py -O /var/www/bin/update.py
# powershell wget -Uri http://49.235.119.5/download.php?file=bin/update_main.py -Outfile update_main.py

import time, sys, os, uuid

print ("update codes have NOT completed.")
sys.exit()


from http.client import HTTPConnection
import socket
import requests
import json, re
import pymysql, sqlite3
import py_compile
import logging
import locale
import optparse
from  configparser import ConfigParser
import uuid
import shutil

op = optparse.OptionParser()
op.add_option("-V", "--version", action="store_true", dest="_VERSION")
op.add_option("-W", "--windows-gui", action="store_true", dest="_WIN_GUI")
op.add_option("-S", "--server-address", action = "store", type="string", dest="_SERVER_IP")
op.add_option("-D", "--db", action = "store", type="string", dest="CUSTOM_DB")
opt, args = op.parse_args()

if opt._SERVER_IP:
    _SERVER_IP = opt._SERVER_IP
_WIN_GUI = True if opt._WIN_GUI and os.name == 'nt' else False
_CUSTOM_DB = opt.CUSTOM_DB if opt.CUSTOM_DB  else 'cnt_demo'

_ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(sys.argv[0])))
MYSQL = {'HOST':'localhost', 'USER':'root', 'PASS':'rootpass','PATH':'', 'PORT':0, 'VERSION':'', 'UPTIME':'', 'RUNNING':False}

version = {
    "bin": 0.96,
    "webpage":0.74,
    "param": 0.95,
    "update": 0.94,
    "code": int(time.time()),
    "server_st":""
}

config_db_file = _ROOT_DIR + "/bin/param.db"

if not os.path.isdir(_ROOT_DIR + "/bin/log"):
    os.mkdir(_ROOT_DIR + "/bin/log")

log = logging.getLogger("startBIupdate")
logging.basicConfig(
    filename = _ROOT_DIR + "/bin/log/update.log",
    format = "%(levelname)-8s  %(asctime)s %(module)s %(funcName)s %(lineno)s %(message)s %(threadName)s",
    level=logging.INFO
)


# DOWNPAGE=http://49.235.119.5/download.php?file=
# BASEDIR=/var/www/

def get_fileist():
    global _ROOT_DIR
    server = (_SERVER_IP, _SERVER_PORT)
    fname = "../bin/filelist.json"
    conn = HTTPConnection(*server)
    conn.putrequest("GET", "/download.php?file=%s" %fname) 
    conn.endheaders()
    rs = conn.getresponse()
    conn.close()

    body = ""
    for line in rs.read().decode().splitlines():
        line = line.split("#")[0]
        line = line.split("//")[0]
        line = line.strip()
        
        if not line:
            continue
        body += line

    return json.loads(body)

# def get_fileist():
#     global _ROOT_DIR
#     fname = _ROOT_DIR + "/bin/filelist.json"
#     with open(fname, "r", encoding="utf8") as f:
#         body = f.read()

#     text = ""
#     for line in body.splitlines():
#         line = line.split("#")[0]
#         line = line.split("//")[0]
#         line = line.strip()
        
#         if not line:
#             continue
#         text += line

#     return json.loads(text)

########################################################################################################################################################
#####################################################   File download   ################################################################################
########################################################################################################################################################

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
    
    return True	

def getMyPublicIP():
    try:
        _my_ip = requests.get("http://api.ipify.org").text
        return _my_ip
    except:
        _my_ip= requests.get("http://ip.42.pl/raw").text

    return _my_ip


def checkAvailabe():
    def showMsg():
        prints(msg)
        if _WIN_GUI:
            var['txt_server_state'].set(strn)
            if errFlag:
                label['txt_server_state'].configure(fg="red")

    errFlag = False
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server = (_SERVER_IP, _SERVER_PORT)
    s.settimeout(1)

    try:
        s.connect(server)
        msg = "Server is online"
        strn = "Online"
    except Exception as e:
        s.close()
        msg = "Server is offline"
        strn = "Offline"
        errFlag = True
    s.close()
    showMsg()
    
    if errFlag:
        return False

    conn = HTTPConnection(*server)
    conn.putrequest("GET", "/inc/check_valid.php") 
    conn.endheaders()
    rs = conn.getresponse()
    server_ts = rs.read().decode()
    conn.close()
    

    try :
        server_ts = int(server_ts)
    except Exception as e:
        errFlag = True
        server_ts = 0

    if abs(int(server_ts) - int(time.time())) > 10:
        msg = "Server page not accessable %d, %d" %(int(server_ts), int(time.time()))
        strn = "Page error"
        errFlag = True
    else:
        msg = "Server page is OK"
        strn = "Page OK"

    showMsg()        

    if errFlag:
        return False

    mac = "%012X" %(uuid.getnode())
    if _SERVER_MAC == mac :
        msg = "Server MAC and machine MAC are the same", "error"
        strn = "Unavailable"
        errFlag = True
    else :
        msg = "Server MAC and machine MAC are not the same"
        strn = "Available"
    showMsg()
    if errFlag:
        return False
    return True

def patchHtml():
    global _SERVER_IP, _SERVER_PORT
    global arrFiles
    global _ROOT_DIR

    prints ("=== Updating webpage html files ===")
    if checkAvailabe() == False:
        return False

    targetdir = _ROOT_DIR
    if  os.name == 'nt':
        targetdir = "%s/nginx" %_ROOT_DIR

    server = (_SERVER_IP, _SERVER_PORT)
    conn = HTTPConnection(*server)
    progress(0, 0, "patchHTML")

    for i, file in enumerate(arrFiles['html']):
        fname = "%s/%s" %(targetdir, file)
        if os.path.isfile(fname) and (file.startswith("html/css/") or file.startswith("html/js/app.js")):
            progress((i+1), len(arrFiles['html']),'patchHTML')
            continue
        conn.putrequest("GET", "/download.php?file=%s" %file) 
        conn.endheaders()
        rs = conn.getresponse()
        prints(fname, end="", flush=True)
        if not (os.path.isdir(os.path.dirname(fname))) :
            os.mkdir(os.path.dirname(fname))

        with open(fname, "wb")  as f:
            progress((i+1), len(arrFiles['html']), "patchHTML")
            f.write(rs.read())
    
    progress(100, 100, "patchHTML")
    conn.close()
    print ()

def patchBin():
    global _SERVER_IP, _SERVER_PORT
    global arrFiles
    global _ROOT_DIR

    prints ("Updating Binary python files")
    if checkAvailabe() == False:
        return False

    server = (_SERVER_IP, _SERVER_PORT)
    conn = HTTPConnection(*server)
    progress(0, 0, "patchBinary")
    
    for i, file in enumerate(arrFiles['bin']):
        fname = "%s/%s" %(_ROOT_DIR, file)
        prints(fname, end="", flush=True)
        if file == "bin/rtScreen.json" and os.path.isfile(fname):
            shutil.copyfile(fname, "%s.bk" %fname)
            # if os.name == 'nt':
            #     cmd_str = "copy \"%s\" \"%s.bk\"" %(fname, fname)
            #     cmd_str = cmd_str.replace("/", "\\")
            # else :
            #     cmd_str = "cp \"%s\" \"%s.bk\"" %(fname, fname)
            # os.system(cmd_str)
        if file.startswith("bin/template"):
            if os.path.isfile(fname):
                continue
        conn.putrequest("GET", "/download.php?file=%s" %file) 
        conn.endheaders()
        rs = conn.getresponse()

        if not (os.path.isdir(os.path.dirname(fname))) :
            os.mkdir(os.path.dirname(fname))
        

        with open(fname, "wb")  as f:
            progress((i+1), len(arrFiles['bin']), "patchBinary")
            f.write(rs.read())
    progress(100, 100, "patchBinary")
    conn.close()
    print ()

    # py_compile.compile("%s/bin/chkLic_s.py" %_ROOT_DIR, "%s/bin/chkLic.pyc" %_ROOT_DIR)
    # py_compile.compile("%s/bin/functions_s.py" %_ROOT_DIR, "%s/bin/functions.pyc" %_ROOT_DIR)
    # py_compile.compile("%s/bin/function4php.py" %_ROOT_DIR, "%s/bin/function4php.pyc" %_ROOT_DIR)
    # os.unlink("%s/bin/chkLic_s.py" %_ROOT_DIR)
    # os.unlink("%s/bin/functions_s.py" %_ROOT_DIR)


def delUnnessaries():
    global arrFiles
    global _ROOT_DIR

    if checkAvailabe() == False:
        return False    
    print ("Delete Unnessaries...")

    for i, fname in enumerate(arrFiles['delete']):
        if os.name == 'nt' and fname.startswith("html/"):
            fname = "%s/Nginx/%s" %(_ROOT_DIR, fname)
        else :
            fname = "%s/%s" %(_ROOT_DIR, fname)

        if os.path.isfile(fname):
            try:
                os.unlink(fname)
                prints (fname, " deleted")
            except:
                pass

def makeLink(): # windows only
    if os.name != 'nt':
        print ("This function is only for windows operationg system")
        return False

    rootdrive = _ROOT_DIR.split("\\")[0]

    if not os.path.exists( _ROOT_DIR + "\\DB_BACKUP\\"):
        os.mkdir("%s\\DB_BACKUP\\" %_ROOT_DIR)

    fname = _ROOT_DIR + "\\monitor.bat"
    with open(fname, "w") as f:
        f.write(rootdrive + "\n")
        f.write("cd \"" + _ROOT_DIR + "\\bin\"\n")
        f.write("python3.exe monitor.py\n")

    fname = _ROOT_DIR + "\\update.bat"
    with open(fname, "w") as f:
        f.write("@echo off\n")
        f.write("cd bin\n")
        f.write("python3.exe update.py -W\n")
        # f.write("pause\n")

    fname = _ROOT_DIR + "\\bin\\start.bat"
    with open(fname, "w") as f:
        f.write(rootdrive + "\n")
        f.write("cd \"" + _ROOT_DIR + "\\bin\"\n")
        f.write("python3.exe update.py\n")
        f.write("python3.exe startBI.py\n")

    fname = _ROOT_DIR + "\\backupDB.bat" 
    with open(fname, "w") as f:
        f.write(rootdrive + "\n")
        f.write("cd \"" + _ROOT_DIR + "\\bin\"\n")
        f.write("python3.exe sysdaemon.py backup\n")

    fname = _ROOT_DIR + "\\RtScreen.bat" 
    with open(fname, "w") as f:
        f.write(rootdrive + "\n")
        f.write("cd \"" + _ROOT_DIR + "\\bin\"\n")
        f.write("python3.exe rtScreen.py\n")

    fname = _ROOT_DIR + "\\RtScreenCanvas.bat" 
    with open(fname, "w") as f:
        f.write(rootdrive + "\n")
        f.write("cd \"" + _ROOT_DIR + "\\bin\"\n")
        f.write("python3.exe rtScreenCanvas.py\n")


    # fname = _ROOT_DIR + "\\repairDB.bat" 
    # with open(fname, "w") as f:
    #     f.write(rootdrive + "\n")
    #     f.write("cd \"" + _ROOT_DIR + "\\bin\"\n")
    #     f.write("python3.exe repair_db.py\n")
    #     f.write("pause\n")


def checkPhpIni():
    if os.name != 'nt':
        print ("This function is only for windows operationg system")
        return False    

    fname = _ROOT_DIR + "\\PHP\php.ini"
    with open(fname, "r") as f:
        body = f.read()
   
    lines = body.splitlines()
    body = ""
    flag = False
    for line in lines:
        line_f = line.strip().replace(" ", "").lower()
        if line_f.find("display_errors=") == 0:
            if  line_f.find("display_errors=off") == 0:
                print ("no need to modify", line)
            else :
                line = "display_errors = Off"
                print (line)
                flag = True
        elif line_f.find("error_reporting=") == 0 :
            if line_f.find("error_reporting=e_all&~e_deprecated&~e_strict") == 0:
                print ("need to modify", line)
            else :
                line = "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT"
                print (line)
                flag = True
        body += line + "\n"
    
    # for i, line in enumerate(body.splitlines()):
    #     print (i, line)
    if flag:
        prints ("writing php ini file")
        with open(fname, "w") as f:
            f.write(body)


def patchRtScreen():
    if not os.path.isfile("%s/bin/rtScreen.json.bk" %(_ROOT_DIR)) :
        return False

    with open("%s/bin/rtScreen.json.bk" %(_ROOT_DIR), "r", encoding="utf-8") as f:
        body = f.read()
    arr_old = json.loads(body)
    with open("%s/bin/rtScreen.json" %(_ROOT_DIR), "r", encoding="utf-8") as f:
        body = f.read()
    arr = json.loads(body)
    arr["mysql"] = arr_old["mysql"]
    arr["refresh_interval"] = arr_old["refresh_interval"]
    arr["full_screen"] = arr_old["full_screen"]
    json_str = json.dumps(arr, ensure_ascii=False, indent=4, sort_keys=True)
    with open("%s/bin/rtScreen.json" %(_ROOT_DIR), "w", encoding="utf-8") as f:
        f.write(json_str)


########################################################################################################################################################
##############################################  PARAM TABLE ############################################################################################
########################################################################################################################################################

def sqlDbMaster():
	global config_db_file
	# if not os.path.isfile(config_db_file):
	# 	print ("No config db file")
	# 	return False

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
        try:
            cur.execute(sq)
        except:
            return False
        rows = cur.fetchall()
        if not rows:
            return ''
        if len(rows) == 1 :
            return rows[0][0]
        
        for r in rows:
            arr_rs[r[2]+"."+r[1]] = r[0]
    return arr_rs

def patchParamDb():
    global _ROOT_DIR
    fname_ini = "%s/bin/param_tbl.ini" %_ROOT_DIR
    fname_db  = "%s/bin/param.db" %_ROOT_DIR
    # print(fname_ini)
    prints ("patching  Param DB from %s" %fname_ini)
    arr_list = list()
    arr_sq = list()
    arr_grps = list()
    
    if not os.path.isfile(fname_ini):
        prints ("Error, File %s is not exist" %fname_ini)
        return False

    with open (fname_ini, "r", encoding='utf-8') as f:
        body = f.read()

    for line in body.splitlines():
        line = line.strip()
        if not line or line[0] == "#":
            continue
        line = line.replace("'", "&#039;")
        arr = json.loads('['+line+']')
        arr_list.append(tuple(arr))
        arr_grps.append(arr[0])

    dbsqcon = sqlite3.connect(fname_db)
    cur = dbsqcon.cursor()
    sq = """CREATE TABLE IF NOT EXISTS param_tbl (\
        prino INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,\
        groupPath TEXT,\
        entryName TEXT,\
        entryValue TEXT,\
        description TEXT,\
        datatype TEXT default 'sz',\
        option TEXT,\
        create_permission INTEGER default 7,\
        delete_permission INTEGER default 7,\
        update_permission INTEGER default 7,\
        read_permission INTEGER default 7,\
        readonly INTEGER default 0,\
        writeonly INTEGER default 0,\
        group1 TEXT,\
        group2 TEXT,\
        group3 TEXT,\
        group4 TEXT,\
        group5 TEXT,\
        group6 TEXT,\
        made TEXT,\
        regdate NUMERIC\
    )"""
    arr_sq.append(sq)
    arr_sq.append('commit')

    sq = """CREATE TABLE IF NOT EXISTS info_tbl(\
        prino INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,\
        category TEXT,\
        entryName TEXT,\
        entryValue TEXT,\
        description TEXT,\
        regdate NUMERIC\
    )"""

    arr_sq.append(sq)
    arr_sq.append('commit')
    for i, sq in enumerate(arr_sq):
        # prints(sq)
        if sq == 'commit':
            dbsqcon.commit()
            continue
        cur.execute(sq)
    
    arr_sq = list()

    for r in arr_list:
        # sq = "select * from sqlite_master where name='param_tbl'"
        exp = r[0].split(".")
        grps = ["", "", "", "", "", ""]
        groupPath=""
        for i, e in enumerate(exp):
            grps[i] = e
            if i < len(exp)-1:
                if groupPath:
                    groupPath +="."
                groupPath += e

        entryName = exp.pop()
        sq = "select prino from param_tbl where groupPath='%s' and entryName='%s'" %(groupPath, entryName)
        # print (sq)
        cur.execute(sq)
        row = cur.fetchone()
        if (row == None):
            sq  = "INSERT INTO param_tbl( groupPath, entryName, entryValue, datatype, option, description, group1, group2, group3, group4, group5, group6, readonly, writeonly, made,  regdate, create_permission, delete_permission, update_permission, read_permission) "
            sq += "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s, '%s', %s, 0,0,7,7 )" %(groupPath, entryName, r[1], r[2], r[3], r[6], grps[0], grps[1], grps[2], grps[3], grps[4], grps[5], r[4], r[5], 'hanskim', int(time.time()))
        else:
            sq = "UPDATE param_tbl set datatype='%s', option='%s', description='%s', readonly='%s', writeonly='%s' where prino=%s" %(r[2], r[3], r[6], r[4], r[5], row[0])
        arr_sq.append(sq)

    arr_sq.append('commit')

    # MAC
    mac = "%012X" %(uuid.getnode())
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='system.network.eth0' and entryName='hwaddr'" %mac)
    
    # version
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.bin' and entryName='version'" %(str(version["bin"])))
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.webpage' and entryName='version'" %(str(version["webpage"])))
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.param' and entryName='version'" %(str(version["param"])))
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.build' and entryName='code'" %(str(version["code"])))

    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.mysql' and entryName='path'" %str(MYSQL['PATH']))
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.mysql' and entryName='port'" %str(MYSQL['PORT']))
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.mysql' and entryName='root_pw'" %str(MYSQL['PASS']))
    
    arr_sq.append('commit')
    
    # delete unnecessary
    sq = "select * from param_tbl"
    cur.execute(sq)
    rows = cur.fetchall()
    for row in rows:
        if not (row[1] + '.' + row[2]  in arr_grps) :
            arr_sq.append('delete from param_tbl where prino=%d' %row[0])

    for i, sq in enumerate(arr_sq):
        prints(sq, end="", flush=True)
        progress((i+1), len(arr_sq), "patchParamDB")
        if sq == 'commit':
            dbsqcon.commit()
            continue
        cur.execute(sq)
    
    dbsqcon.close()
    if _SERVER_MAC != mac :    
        # os.unlink(fname_ini)
        pass
    print()
    prints("patching Param DB Finished")
    print()


def migrateParam():
    global _ROOT_DIR
    fname_db  = "%s/bin/param.db" %_ROOT_DIR
    prints ("migrate Param tbl from old config tbl as version 0.5 or below")
    arr_sq = list()
    dbsqcon = sqlite3.connect(fname_db)
    with dbsqcon:
        cur =  dbsqcon.cursor()
        sq = "select entryValue from config_tbl where entryName='flag' and section='migrate'"
        try:
            cur.execute(sq)
            rs = cur.fetchone()
        except:
            prints("No table name for config_tbl")
            progress(100, 100, "migrateParamDB")
            return True

        if rs == None :
            sq = "insert into config_tbl (entryName, entryValue, section) values('flag', 'no', 'migrate')"
            cur.execute(sq)
            dbsqcon.commit()
        sq ="select entryValue from config_tbl where section='migrate' and entryName='flag'"
        cur.execute(sq)
        rs = cur.fetchone()
        if rs[0] == 'yes': # config table to param table
            prints("No needs to update param tbl from config_tbl")
            progress(100, 100, "migrateParamDB")
            return False

        sq = "select section, entryName, entryValue from config_tbl"
        cur.execute(sq)
        rows = cur.fetchall()
        for row in rows:
            if row[0] == 'LICENSE' and str(row[1])=='CODE' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service.licesnse' and entryName='code'" %row[2])
            elif row[0] == 'ROOT' and row[1]=='DOCUMENT_TITLE' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.root.webpage' and entryName='document_title'" %row[2])
            elif row[0] == 'ROOT' and row[1]=='HOST_TITLE':
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.root.webpage' and entryName='host_title'" %row[2])
            elif row[0] == 'ROOT' and row[1]=='LOGO_PATH' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.root.webpage' and entryName='logo_path'" %row[2])
            elif row[0] == 'SERVICE' and row[1]=='MODE' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service' and entryName='mode'" %row[2])
            elif row[0] == 'SERVICE' and row[1]=='COUNT_EVENT' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service' and entryName='count_event'" %row[2].lower())
            elif row[0] == 'SERVICE' and row[1]=='COUNTING' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service' and entryName='counting'" %row[2].lower())
            elif row[0] == 'SERVICE' and row[1]=='FACE' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service' and entryName='face'" %row[2].lower())
            elif row[0] == 'SERVICE' and row[1]=='MACSNIFF' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service' and entryName='macsniff'" %row[2].lower())
            elif row[0] == 'SERVICE' and row[1]=='SNAPSHOT' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.service' and entryName='snapshot'" %row[2].lower())
            elif row[0] == 'SERVICE' and row[1]=='PROBE_INTERVAL' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%d' where groupPath='software.service' and entryName='probe_interval'" %(int(row[2])))
            elif row[0] == 'MYSQL' and row[1]=='RECYCLING_TIMESTAMP' and row[2]:
                if row[2] =='30days':
                    recycling_time = 30*3600*24
                elif row[2] =='60days':
                    recycling_time = 60*3600*24
                elif row[2] =='90days':
                    recycling_time = 90*3600*24
                else:
                    recycling_time = 365*3600*24
                arr_sq.append("update param_tbl set entryValue='%d' where groupPath='software.mysql' and entryValue='recycling_time'" %recycling_time)
            elif row[0] == 'FPP' and row[1]=='HOST' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.fpp' and entryValue='host'" %row[2])
            elif row[0] == 'FPP' and row[1]=='API_KEY' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.fpp' and entryValue='api_key'" %row[2])
            elif row[0] == 'FPP' and row[1]=='API_SRCT' and row[2]:
                arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.fpp' and entryValue='api_srct'" %row[2])
        
        arr_sq.append("update config_tbl set entryValue='yes' where section='migrate' and entryname='flag'")
        for i, sq in enumerate(arr_sq):
            prints (sq)
            cur.execute(sq)
            progress((i+1), len(arr_sq), "migrateParamDB")

        dbsqcon.commit()
        prints("Param table migration finished")
        print()



########################################################################################################################################################
########################   MYSQL //  MariaDB  ##########################################################################################################
#sc create MariaDB binpath= E:\Cosilan\Mariadb\bin\mysqld.exe

def findMysqlPaths(): 
	#find all mysql, mariadb paths on system
	arr_path = list()
	if os.name == 'nt':
		cmd = " wmic process where name='mysqld.exe' get executablepath &\
				wmic service where name='mariadb' get pathname &\
				wmic service where name='mysql' get pathname"
		p = os.popen(cmd).read()
		for line in p.splitlines():
			tp = line.upper().find("MYSQLD.EXE")
			if tp >0:
				line = line.replace('"', '')
				line = line[:tp+len("MYSQLD.EXE")].strip()
				arr_path.append(line)
	else :
		cmd = "which mysqld"
		p = os.popen(cmd).read().upper()
		arr_path.append(p.strip())

	return arr_path

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

def dbconMaster(host='', user='', password='',  charset = 'utf8', port=0): #Mysql
    global MYSQL
    if not host:
        host=MYSQL['HOST']
    if not user :
        user = MYSQL['USER']
    if not password:
        password = MYSQL['PASS']
    if not port:
        port = MYSQL['PORT']

    try:
        dbcon = pymysql.connect(host=host, user=str(user), password=str(password),  charset=charset, port=port)
    except pymysql.err.OperationalError as e :
        print (str(e))
        return None
    return dbcon   

# def checkVersion():
#     global _SERVER_IP
#     dbConRemote = dbconMaster(host=_SERVER_IP, user = 'rt_user', password = '13579',  charset = 'utf8', port=3306)
#     dbConLocal = dbconMaster(port=_mysql_port)
#     arr_list = list()
    
#     with dbConRemote:
#         cur = dbConRemote.cursor()

# Caption     CommandLine  CreationClassName  CreationDate               CSCreationClassName   CSName  Description  ExecutablePath  ExecutionState  Handle  HandleCount  InstallDate  KernelModeTime  MaximumWorkingSetSize  MinimumWorkingSetSize  Name        OSCreationClassName    OSName                                                            OtherOperationCount  OtherTransferCount  PageFaults  PageFileUsage  ParentProcessId  PeakPageFileUsage  PeakVirtualSize  PeakWorkingSetSize  Priority  PrivatePageCount  ProcessId  QuotaNonPagedPoolUsage  QuotaPagedPoolUsage  QuotaPeakNonPagedPoolUsage  QuotaPeakPagedPoolUsage  ReadOperationCount  ReadTransferCount  SessionId  Status  TerminationDate  ThreadCount  UserModeTime  VirtualSize  WindowsVersion  WorkingSetSize  WriteOperationCount  WriteTransferCount
# mysqld.exe               Win32_Process      20220507202850.395913+480  Win32_ComputerSystem  H-PC    mysqld.exe                                   4436    169                       1250000                                                       mysqld.exe  Win32_OperatingSystem  Microsoft Windows 10 Pro|C:\Windows|\Device\Harddisk0\Partition3  1064                 21898               139119      4726388        676              4795784            9299783680       408192              8         4839821312        4436       24                      110                  72                          110                      288                 5421345            0                                   36           2031250       9241493504   10.0.19044      353406976       1809                 2149029

# AcceptPause  AcceptStop  Caption  CheckPoint  CreationClassName  DelayedAutoStart  Description              DesktopInteract  DisplayName  ErrorControl  ExitCode  InstallDate  Name     PathName                                                                            ProcessId  ServiceSpecificExitCode  ServiceType  Started  StartMode  StartName                    State    Status  SystemCreationClassName  SystemName  TagId  WaitHint
# TRUE         TRUE        MariaDB  0           Win32_Service      FALSE             MariaDB database server  FALSE            MariaDB      Normal        0                      MariaDB  "E:\MariaDB10\bin\mysqld.exe" "--defaults-file=E:\MariaDB10\data\my.ini" "MariaDB"  4436       0                        Own Process  TRUE     Auto       NT AUTHORITY\NetworkService  Running  OK      Win32_ComputerSystem     H-PC        0      0


def statusMysql(password=''):
    arr =  []
    expected_dir = os.path.abspath(_ROOT_DIR + "\\Mariadb\\bin").upper()
    for a_path in findMysqlPaths():
        a_path = os.path.dirname(a_path)
        arr.append({'path_flag': expected_dir == a_path.upper(), 'execute_path': a_path, 'port':0, 'version':'', 'uptime':'', 'running':False})

    if not password:
        password = MYSQL['PASS']

    for i in range(len(arr)):
        os.chdir(arr[i]['execute_path'])
        cmd = "mysqladmin -uroot -p%s version" %password
        p = os.popen(cmd).read().upper()
        for line in str(p).splitlines():
            line = line.strip()
            if line.startswith("SERVER VERSION"):
                arr[i]['version'] = line.split("\t")[-1].strip()
            elif line.startswith("UPTIME:"):
                arr[i]['uptime'] = line.split("\t")[-1].strip()
                arr[i]['running'] = True
            elif line.startswith("TCP PORT"):
                arr[i]['port'] = int(line.split("\t")[-1].strip())
    return arr


def checkMyIni():
# [mysqld]
# datadir=E:/MariaDB10/data
# port=3306
# innodb_buffer_pool_size=4088M
# character-set-server = utf8mb4
# collation-server = utf8mb4_unicode_ci

# [client]
# port=3306
# plugin-dir=E:/MariaDB10/lib/plugin
# default-character-set	= utf8mb4
    fname = os.path.dirname(configVars('software.mysql.path')) + "\\data\\my.ini"
    print()
    print(fname)
    cfg = ConfigParser(allow_no_value=True)
    cfg.read(fname)
    cfg.set('mysqld', 'character-set-server', 'utf8mb4')
    cfg.set('mysqld', 'collation-server', 'utf8mb4_unicode_ci')
    cfg.set('client', 'default-character-set', 'utf8mb4')
    with open(fname, "w") as cfname:
        cfg.write(cfname)

def killMysql(working_dir='', user='', passwd=''):
    if not user:
        user = MYSQL['USER']
    if not passwd:
        passwd = MYSQL['PASS']
    if not working_dir:
        working_dir = _ROOT_DIR
    os.chdir(working_dir + "\\MariaDB\\bin")
    a = os.popen("mysqladmin -u%s -p%s shutdown" %(user, passwd))
    p = a.read()
    print (p)

def killNginx():
    os.chdir(_ROOT_DIR + "\\Nginx")
    a = os.popen("nginx.exe -s quit")
    p = a.read()
    print (p)

def getCustomDBs(local=0):
    arr_db = set()
    arr_tbl = set()
    dbcon = dbconMaster()
    with dbcon:
        cur = dbcon.cursor()
        sq = "SHOW DATABASES WHERE `Database` != 'information_schema' and `Database` != 'mysql' and `Database` != 'performance_schema'" 
        if local:
            sq += " and `Database` != 'cnt_demo'" 
        cur.execute(sq)
        for db in cur.fetchall():
            arr_tbl.clear()
            sq = "show tables from %s" %db[0]
            cur.execute(sq)
            for tbl in cur.fetchall():
                arr_tbl.add(tbl[0])
            print(arr_tbl)
            if 'square' in arr_tbl and 'store' in arr_tbl and 'camera' in arr_tbl:
                arr_db.add(db[0])

    return list(arr_db)

def patchMariaDB():
    prints ("Patching Maria DB")
    if is_online(_SERVER_IP) == False:
        prints("Cannot reach mysql server", "error")
        return False
    
    dbConRemote = dbconMaster(host=_SERVER_IP, user = 'rt_user', password = '13579',  charset = 'utf8', port=3306)
    dbConLocal = dbconMaster()

    if dbConRemote == None:
        prints("Cannot reach remote mysql server!!", "error")
        return False
    
    if dbConLocal == None:
        prints("pleas check mysql or maria db running!!","error")
        return False        

    arrDatabase = ['common', 'cnt_demo']
    arrRemoteTables = list()
    arr_sq = list()

    regex_auto = re.compile('AUTO_INCREMENT=(\d+)', re.IGNORECASE)
    with dbConRemote:
        cur = dbConRemote.cursor()
        for db in arrDatabase:
            arr_sq.append("CREATE DATABASE IF NOT EXISTS `%s`" %db)
            arr_sq.append('commit')
            sq = "show tables from %s" %db
            cur.execute(sq)
            tables = cur.fetchall()
            for table_ in tables:
                table = table_[0]
                sq = "show create table %s.%s" %(db, table)
                cur.execute(sq)
                rows = cur.fetchall()
                for row in rows:
                    sql = row[1].replace("CREATE TABLE ", "CREATE TABLE IF NOT EXISTS `%s`." %db)
                    sql = sql.replace(regex_auto.search(sql).group(), "AUTO_INCREMENT=1")
                    arr_sq.append(sql)
                sq = "show fields from %s.%s" %(db, table)
                cur.execute(sq)
                rows = cur.fetchall()
                for row in rows:
                    arrRemoteTables.append((db, table, row))
            arr_sq.append("commit")

        arr_sq.append("CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY '13579';")
        arr_sq.append("CREATE USER IF NOT EXISTS 'ct_user'@'localhost' IDENTIFIED BY '13579';")
        arr_sq.append("CREATE USER IF NOT EXISTS 'rt_user'@'%' IDENTIFIED BY '13579';")
        
        if MYSQL['VERSION'].startswith('8.0'):
            arr_sq.append("ALTER USER 'admin'@'localhost' IDENTIFIED WITH mysql_native_password BY '13579';")
            arr_sq.append("ALTER USER 'ct_user'@'localhost' IDENTIFIED WITH mysql_native_password BY '13579';")
            arr_sq.append("ALTER USER 'rt_user'@'%' IDENTIFIED WITH mysql_native_password BY '13579';")

        if os.name == 'posix':
            arr_sq.append("UPDATE mysql.user SET plugin='auth_socket' WHERE User='admin';")
            arr_sq.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='root';")
            arr_sq.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='admin';")
            arr_sq.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='ct_user';")
            arr_sq.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='rt_user';")
            arr_sq.append("UPDATE mysql.user SET grant_priv='Y' where user='admin';")

        arr_sq.append("GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost';")
        arr_sq.append("GRANT insert, select, update, delete, alter ON common.* TO 'ct_user'@'localhost';")
        arr_sq.append("GRANT insert, select, update, delete, alter ON cnt_demo.* TO 'ct_user'@'localhost';")
        arr_sq.append("GRANT select ON common.* TO 'rt_user'@'%';")
        arr_sq.append("GRANT select ON cnt_demo.* TO 'rt_user'@'%';")

        arr_sq.append("FLUSH PRIVILEGES;")
        # arr_sq.append("INSERT INTO common.users(regdate, code, ID, passwd, db_name, flag, role) VALUES (now(),'U000000000001','root','rootpass','cnt_demo','y','admin') ON DUPLICATE KEY UPDATE ID = VALUES(ID)")
        arr_sq.append("INSERT INTO common.users(regdate, code, ID, passwd, db_name, flag, role) select now(), 'U000000000001','root','rootpass','cnt_demo','y','admin' FROM DUAL WHERE NOT EXISTS(SELECT ID FROM common.users where ID='root')")
        arr_sq.append('commit')

        arr_sq.append("alter table common.params modify usn varchar(127);")
        arr_sq.append("alter table common.params modify product_id varchar(127);")
        arr_sq.append("alter table cnt_demo.camera modify usn varchar(127);")
        arr_sq.append("alter table cnt_demo.camera modify product_id varchar(127);")
        arr_sq.append('commit')


    with dbConLocal:
        cur = dbConLocal.cursor()
        for i, sq in enumerate(arr_sq):
            prints (sq)
            if sq == 'commit':
                dbConLocal.commit() 
            else :
                try:
                    cur.execute(sq)
                except Exception as e:
                    prints(str(e))
            progress((i+1), len(arr_sq), "patchMariaDB")

        for tbl in arrRemoteTables:
            sq = "show fields from %s.%s like '%s'" %(tbl[0], tbl[1], tbl[2][0])
            cur.execute(sq)
            rs = cur.fetchall()
            if not rs:
                default = "" if tbl[2][4] == None else "default '%s'" %(str(tbl[2][4]))
                sq = "alter table %s.%s add %s %s %s" %(tbl[0], tbl[1], tbl[2][0], tbl[2][1],  default )
                prints (sq)
                cur.execute(sq)
        dbConLocal.commit()
        print()
        print ("""
If you want to use remote access, edit my.ini in windows or /etc/mysql/mariadb.conf.d/50-server.cnf in linux, nand block bind-address like #bind-address=localhost
If you have flush privileges Error "mysqlcheck -r mysql tables_priv -u root -p", "mysqlcheck -u root -p --auto-repair -c -o --all-databases
If you have trouble in runnig mysqld, delete files in data except ***ibdata1*** and my.ini or my.cnf
Got error 176 "Read page with wrong checksum" from storage engine Aria
        """)



def patchLanguage():
    prints ("Patching Language pack")

    if is_online(_SERVER_IP) == False:
        prints("Cannot reach remote server", "error")
        return False

    dbConRemote = dbconMaster(host=_SERVER_IP, user = 'rt_user', password = '13579',  charset = 'utf8', port=3306)
    dbConLocal = dbconMaster()

    if dbConRemote == None:
        prints("Cannot reach remote mysql server!!", "error")
        return False
    
    if dbConLocal == None:
        prints("pleas check mysql or maria db running!!", "error")
        return False        

    arr_list = list()
    arr_sq = list()
    
    with dbConRemote:
        cur = dbConRemote.cursor()
        sq = "select varstr, eng, chi, kor, page from cnt_demo.language "        
        cur.execute(sq)
        rows = cur.fetchall()
        for row in rows:
            arr_list.append(row)

    with dbConLocal:
        cur = dbConLocal.cursor()
        # ignore_varstrs = ['card_banner',]    
        for i, lang in enumerate(arr_list):
            # sq = "select pk from common.language  where varstr='%s' and eng='%s' and chi='%s' and kor='%s' and page='%s'"  %(lang)
            sq = "select pk from common.language  where varstr='%s' and page='%s'"  %(lang[0], lang[4])
            cur.execute(sq)
            rows = cur.fetchone()

            if (rows==None):
                sq = "insert into common.language(varstr, eng, chi, kor, page) values('%s', '%s', '%s', '%s', '%s')" %(lang)
                arr_sq.append(sq)

            # sq = "select pk from cnt_demo.language  where varstr='%s' and eng='%s' and chi='%s' and kor='%s' and page='%s'"  %(lang)
            sq = "select pk from cnt_demo.language  where varstr='%s' and page='%s'"  %(lang[0], lang[4])
            cur.execute(sq)
            rows = cur.fetchone()
            if (rows==None):
                sq = "insert into cnt_demo.language(varstr, eng, chi, kor, page) values('%s', '%s', '%s', '%s', '%s')" %(lang)
                arr_sq.append(sq)

        if(arr_sq) :
            arr_sq.append('commit')


        for i, sq in enumerate(arr_sq):
            prints (sq)
            if sq == 'commit':
                dbConLocal.commit() 
            else :
                try:
                    cur.execute(sq)
                except Exception as e:
                    prints(str(e))
            progress((i+1), len(arr_sq), "patchLanguage")
        progress(100, 100, "patchLanguage")
    print()

def patchWebConfig():
    prints ("Patching Webpge Config")

    if checkAvailabe() == False:
        prints("Cannot reach remote mysql server!!", "error")
        return False
    dbConRemote = dbconMaster(host=_SERVER_IP, user = 'rt_user', password = '13579',  charset = 'utf8', port=3306)
    dbConLocal = dbconMaster()

    if dbConRemote == None:
        prints("Cannot reach remote mysql server!!", "error")
        return False
    
    if dbConLocal == None:
        print("pleas check mysql or maria db running!!", "error")
        return False        

    arr_list = list()
    arr_db = list()
    arr_sq = list()
    
    with dbConRemote:
        cur = dbConRemote.cursor()
        sq = "select page, frame, depth, pos_x, pos_y, body, flag from cnt_demo.webpage_config "        
        cur.execute(sq)
        rows = cur.fetchall()
        for row in rows:
            arr_list.append(row)

    with dbConLocal:
        cur = dbConLocal.cursor()
        sq = "show databases"
        cur.execute(sq )
        rows = cur.fetchall()
        for row in rows:
            if row[0] in ['common','information_schema', 'mysql', 'performance_schema', 'test', 'sys'] :
                continue
            arr_db.append(row[0])
        prints(arr_db)
        for db_name in arr_db:
            sq = "show fields from " + db_name + ".webpage_config like 'name'"
            cur.execute(sq )
            if cur.fetchone() :
                sq = "alter table " + db_name + ".webpage_config drop name"
                arr_sq.append(sq)
                arr_sq.append('commit')

            for i, web_config in enumerate(arr_list):
                sq = "select pk from "+db_name+".webpage_config where page='%s' and frame='%s' and depth='%s' and pos_x='%s' and pos_y='%s'" %web_config[:5]
                cur.execute(sq )
                rows = cur.fetchone()

                if (rows==None or not rows):
                    arr = list(web_config)
                    arr[5] = re.escape(arr[5])
                    sq = "insert into "+db_name+".webpage_config(page, frame, depth, pos_x, pos_y, body, flag) values('%s', '%s', '%s', '%s','%s','%s', '%s')" %(tuple(arr))
                    arr_sq.append(sq)
        
        if(arr_sq) :
            arr_sq.append('commit')
        
        for i, sq in enumerate(arr_sq):
            prints(sq)
            if sq == 'commit':
                dbConLocal.commit() 
            else :
                try:
                    cur.execute(sq)
                except Exception as e:
                    prints(str(e))
            progress((i+1), len(arr_sq), "patchWebConfig")
        progress(100, 100, "patchWebConfig")

    print()

def postMYSQL():
    print (MYSQL)
    fname_db  = "%s/bin/param.db" %_ROOT_DIR
    prints ("Mysql parameter to param db")
    arr_sq = list()
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.mysql' and entryName='root_pw'" %MYSQL['PASS'])
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.mysql' and entryName='path'" %MYSQL['PATH'])
    arr_sq.append("update param_tbl set entryValue='%s' where groupPath='software.mysql' and entryName='port'" %MYSQL['PORT'])

    dbsqcon = sqlite3.connect(fname_db)
    with dbsqcon:
        cur =  dbsqcon.cursor()
        for i, sq in enumerate(arr_sq):
            prints (sq)
            cur.execute(sq)
        dbsqcon.commit()
        prints("Post maria DB  finished")
        print()    

# patchWebConfig()

# sys.exit()




        



       




def register_auto_start(flag):
    file_ex = '"%s\\bin\\start.bat" ' %(_ROOT_DIR)
    key = winreg.OpenKey(winreg.HKEY_CURRENT_USER,'Software\Microsoft\Windows\CurrentVersion\Run',0,winreg.KEY_SET_VALUE)
    if flag == 'yes' or flag==1 : 
        winreg.SetValueEx(key,'startBI',0,winreg.REG_SZ, file_ex) 
        print ("Register to auto start up")
    else :
        try:
            winreg.DeleteValue(key,'startBI') 
        except :
            pass
        print ("Cancel from auto start up")
    
    key.Close()



########################################################################################################################################################
##############################################  Windows GUI ############################################################################################
########################################################################################################################################################

def loadLangPack():
    LOCALE = locale.getdefaultlocale()
    if LOCALE[0] == 'zh_CN':
        key = 'Chinese'
    elif LOCALE[0] == 'ko_KR':
        key = 'Korean'
    else :
        key = 'English'
    # key = 'Chinese'


    fname = _ROOT_DIR + "/bin/update_main.json"
    if not os.path.isfile(fname):
        return {}
    with open(fname, 'r', encoding='utf-8') as f:
        lang_json = f.read()

    arr_lang = {}
    for r in json.loads(lang_json)['language']:
        arr_lang[r['key']] = r[key]

    return arr_lang




def progress(current, total, target):
    # left:20, down:18, right:19. up:17 : ascii decimal
    p = int(current * 100 / total) if total  else 0
    if os.name == 'nt' and _WIN_GUI :
        prog[target]["value"] = p
        prog[target].update()
    else :
        line = ""
        for i in range(0, 100, 2) :
            line += "=" if (i <= p) else " "

        print("\r %02d%%    [%s]" %(int(p), line), end="", flush=True)

def prints(strs, cls="info", **kwargs):
    if type(strs) is list or type(strs) is dict:
        strs = json.dumps(strs)
    strs = str(strs)

    print(strs, **kwargs)
    if os.name == 'nt' and _WIN_GUI and window :
        tx.insert("end", strs + "\n")
        if cls == "error" :
            s = tx.index("end").split('.')
            st = "%d.0" %(int(s[0])-2)
            tt = "%d.0" %(int(s[0])-1)
            #		print st	
            tx.tag_add("tag", st , tt)
            tx.tag_config("tag", foreground="red")
        try:
            tx.see('end')
        except:
            pass

def infoBox(pad=None):
    arr = ["txt_version_bin", "txt_version_webpage", "txt_version_param", "txt_version_code", "txt_server_state"]
    strn  = [version['bin'], version['webpage'], version['param'], version['code'], version['server_st']]

    if _WIN_GUI:
        global label
        for i, l in enumerate(arr):
            if not l in lang:
                lang[l] = l.replace("txt_", "")            
            var[l] = StringVar()
            var[l].set(strn[i])
            Label(pad, text=lang[l]).grid(row=0, column=i, sticky="news", ipadx=5)
            label[l] = Label(pad, textvariable=var[l], width=1, bg="#E0E5E5")
            label[l].grid(row=1, column=i, sticky="news", ipadx=5)
  
    else :
        strs = [""]*4
        strs[0]  = "======================================================================================================="
        for i, l in enumerate(arr):
            strs[1] += "%-20s " %(arr[i].replace("txt_", ""))
            strs[2] += "%-20s " %strn[i]
        strs[3]  = "======================================================================================================="
        prints ("\n".join(strs))


def progressPad(pad) :
    for i, l in enumerate(progLabel):
        if not l in lang:
            lang[l] = l

        Label(pad, text=lang[l], anchor="w").grid(row=i, column=0, pady=2, sticky="w", ipadx=2)
        prog[l] = ttk.Progressbar(pad, maximum=100, length=410, mode="determinate")
        prog[l].grid(row=i, column=1)
        prog[l]["value"] = 0    

def buttonPad(pad):
    global btnStart
    arr = ["start", "cancel"]
    for l in arr:
        if not l in lang:
            lang[l] = l

    btnStart = Button(pad, text=lang["start"],  command=startUpdate, width=6, height=1)
    btnStart.pack(side="left", padx=5)
    Button(pad, text=lang["cancel"], command=cancel, width=6, height=1).pack(side="left", padx=5)

def mysqldBox(pad=""):
    if _WIN_GUI:
        global label, mysqlPathCombo
        var['root_pw'] = StringVar()
        var['mysql_status'] = StringVar()

        Label(pad, text="[Mysqld]", width=8).pack(side="left")
        Label(pad, text="Path:").pack(side="left")
        mysqlPathCombo = ttk.Combobox(pad, width=40)
        mysqlPathCombo.pack(side="left", padx=2)
        Label(pad, text="root PW:").pack(side="left")
        Entry(pad, textvariable=var['root_pw'], width=10).pack(side='left')
        label['mysqlSt'] = Label(pad, textvariable=var['mysql_status'], width=8)
        label['mysqlSt'].pack(side="left")

        arr_path = findMysqlPaths()
        mysql_path = configVars("software.mysql.path")
        var['root_pw'].set(configVars("software.mysql.root_pw"))
        var['mysql_status'].set("Stopped")
        mysqlPathCombo['values'] = arr_path
        if mysql_path:
            for i, p in enumerate(arr_path):
                if p.upper().find(mysql_path.upper())>=0:
                    mysqlPathCombo.current(i)        
                    break


def cancel():
    window.destroy()
    pass

def windowsGUI(window):
    global tx
    tx=Text(window, height=20, width=70)

    window.protocol("WM_DELETE_WINDOW", window.destroy)
    if not lang.get('main_title'):
        lang['main_title'] = "Update Tool"
    window.title("%s %.2f" %(lang['main_title'], version['update']))
    window.geometry("600x700")
    window.resizable(True, True)

    frInfo = Frame(window, bd=0, relief="solid")
    frInfo.pack(side="top", expand=False, padx=10, pady=10)
    infoBox(frInfo)

    pathInfo = Frame(window, bd=0, relief="solid")
    pathInfo.pack(side="top", expand=False, padx=10, pady=10)
    mysqldBox(pathInfo)

    progPad = Frame(window, bd=0, relief="solid")
    progPad.pack(side="top", expand=False, padx=10, pady=10)
    progressPad(progPad)

    btnPad = Frame(window, bd=0, relief="solid")
    btnPad.pack(side="top", expand=False, padx=10, pady=10)
    buttonPad(btnPad)
    
    tx.pack(side="top", pady=5)

    prints("Update Tool for Windows")


def checkMysql(arr, sel, root_pw):
    if not root_pw:
        prints ("FAIL on MariaDB, root password is not correct!!", 'error')
        return False
    con = dbconMaster(user='root', password=root_pw, port=arr[sel]['port'])
    if not con:
        prints ("FAIL on MariaDB, root password is not correct!!", 'error')
        return False
    con.close()
    MYSQL['PASS'] = root_pw
    MYSQL['PATH'] = arr[sel]['execute_path']
    MYSQL['PORT'] = arr[sel]['port']
    MYSQL['VERSION'] = arr[sel]['version']
    MYSQL['UPTIME'] = arr[sel]['uptime']
    MYSQL['RUNNING'] = arr[sel]['running']
    return True
    

def startUpdate():
    if _WIN_GUI:
        global btnStart
        for label in progLabel:
            progress(0, 0, label)

        if not checkMysql(arr_MYSQL, mysqlPathCombo.current(), var['root_pw'].get().strip()):
            return False
        prints(MYSQL)
        btnStart['state'] = "disabled"
        patchHtml()
        patchBin()
        patchRtScreen()
        checkPhpIni()
        patchParamDb()
        migrateParam()
        checkMyIni()
        #restrt Mariadb
        patchMariaDB()
        patchLanguage()
        patchWebConfig()
        makeLink()
        postMYSQL()
        delUnnessaries()
        btnStart['state'] = "normal"

    
    else :
        st = checkAvailabe()
        version['server_st'] = "online" if st else "offline"
        infoBox(None)
        if not st:
            return False

        patchHtml()
        patchBin()
        patchRtScreen()
        checkPhpIni()
        patchParamDb()
        migrateParam()
        # checkMyIni()
        patchMariaDB()
        patchLanguage()
        patchWebConfig()
        makeLink()
        postMYSQL()
        delUnnessaries()


def patchLangPack():
    global lang
    for r in lang:
        try:
            var[r].set(lang[r])
        except:
            pass

def alert(msg):
    global window
    subwin = Toplevel(window)
    subwin.title("Alert")
    subwin.geometry("400x100")
    subwin.resizable(True, True)
    Label(subwin, text=msg, anchor="center").pack(side="top", padx=10, expand=False)




if __name__ == '__main__':
    window = None
    arrFiles = get_fileist()

    root_pw = configVars("software.mysql.root_pw")
    if root_pw:
        MYSQL['PASS'] = root_pw
    arr_MYSQL = statusMysql(MYSQL['PASS'])
    # print(arr_MYSQL)

    MYSQL["RUNNING"] = 'Running' if True in [dt['running'] for dt in arr_MYSQL] else 'Stopped'
    # print(MYSQL)

    var = {}
    if not _WIN_GUI :
        startUpdate()
        sys.exit()

    elif _WIN_GUI :
        from tkinter import *
        from tkinter import ttk
            
        lang = loadLangPack()
        
        btnStart = None
        prog = dict()
        var = dict()
        label = dict()
        tx = None
        progLabel = ["patchHTML", "patchBinary", "patchParamDB",  "migrateParamDB", "patchMariaDB", "patchLanguage", "patchWebConfig"]

        window = Tk()
        windowsGUI(window)
        
        var['txt_server_state'].set("Offline")
        var['mysql_status'].set(MYSQL["RUNNING"])

        if not checkAvailabe():
            btnStart['state'] = "disabled"

        if not MYSQL['RUNNING']:
            label['mysqlSt'].configure(fg='red')
            prints("MYSQL is not Running", "error")
            btnStart['state'] = "disabled"

        window.mainloop()


    
    


