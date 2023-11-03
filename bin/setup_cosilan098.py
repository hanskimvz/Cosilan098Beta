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

# Because of expire Cloud, download and update from github https://github.com/hanskimvz/Cosilan097Beta

import time, sys, os, uuid
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

_ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(sys.argv[0])))

op = optparse.OptionParser()
op.add_option("-V", "--version", action="store_true", dest="_VERSION")
op.add_option("-W", "--windows-gui", action="store_true", dest="_WIN_GUI")
op.add_option("-S", "--server-address", action = "store", type="string", dest="_SERVER_IP")
op.add_option("-D", "--db", action = "store", type="string", dest="CUSTOM_DB")
op.add_option("-P", "--password", action = "store", type="string", dest="ROOT_PASS")
opt, args = op.parse_args()

if opt._SERVER_IP:
    _SERVER_IP = opt._SERVER_IP
_WIN_GUI = True if opt._WIN_GUI and os.name == 'nt' else False
_CUSTOM_DB = opt.CUSTOM_DB if opt.CUSTOM_DB  else 'cnt_demo'
_root_pass = opt.ROOT_PASS

MYSQL = {'HOST':'localhost', 'USER':'root', 'PASS':'rootpass','PATH':'', 'PORT':0, 'VERSION':'', 'UPTIME':'', 'RUNNING':False}

version = {
    "bin": 0.97,
    "webpage":0.75,
    "param": 0.95,
    "update": 0.97,
    "code": int(time.time()),
    "server_st":""
}

config_db_file = _ROOT_DIR + "/bin/param.db"

if not os.path.isdir(_ROOT_DIR + "/bin/log"):
    os.mkdir(_ROOT_DIR + "/bin/log")
if not os.path.isdir(_ROOT_DIR + "/DB_BACKUP"):
    os.mkdir(_ROOT_DIR + "/DB_BACKUP")

log = logging.getLogger("startBIupdate")
logging.basicConfig(
    filename = _ROOT_DIR + "/bin/log/update.log",
    format = "%(levelname)-8s  %(asctime)s %(module)s %(funcName)s %(lineno)s %(message)s %(threadName)s",
    level=logging.INFO
)



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
    key = 'Chinese'

    fname = _ROOT_DIR + "/bin/language.json"
    if not os.path.isfile(fname):
        return {}
    with open(fname, 'r', encoding='utf-8') as f:
        lang_json = f.read()

    arr_lang = {}
    for r in json.loads(lang_json)['install']:
        arr_lang[r['key']] = r[key]

    return arr_lang


def progress(current, total):
    # left:20, down:18, right:19. up:17 : ascii decimal
    global prog_bar
    p = int(current * 100 / total) if total  else 0
    if os.name == 'nt' and _WIN_GUI :
        prog_bar["value"] = p
        prog_bar.update()
    else :
        line = ""
        for i in range(0, 100, 2) :
            line += "=" if (i <= p) else " "

        print("\r %d%%    [%s]" %(int(p), line), end="")

def prints(strs, cls="info"):
    if type(strs) is list or type(strs) is dict:
        strs = json.dumps(strs)
    strs = str(strs)

    print(strs)
    if os.name == 'nt' and _WIN_GUI:
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

def windowsGUI():
    global window, tx, lang, var, prog_bar
    window = Tk()
    
    window.protocol("WM_DELETE_WINDOW", window.destroy)
    if not lang.get('main_title'):
        lang['main_title'] = "Update Tool"
    window.title("%s %.2f" %(lang['main_title'], version['update']))
    window.geometry("600x360")
    window.resizable(True, True)

    var['mysql_path'] = StringVar()
    var['root_pw'] = StringVar()
    var['mysql_status'] = StringVar()

    frInfo = Frame(window, bd=0, relief="solid")
    frInfo.pack(side="top", expand=False, padx=10, pady=5)
    arr = [("txt_version_bin",version['bin']) , ("txt_version_webpage", version['webpage']), ("txt_version_param",version['param']), ("txt_version_code", version['code'])]
    for i, (l, m) in enumerate(arr):
        Label(frInfo, text=lang[l]).grid(row=0, column=i, sticky="news", ipadx=5, padx=20)
        Message(frInfo, text=m, width=150, bg="#E0E5E5").grid(row=1, column=i,  sticky="news", ipadx=10)

    
    # Mysql maria db
    pathInfo = Frame(window, bd=0, relief="solid")
    pathInfo.pack(side="top", expand=False, padx=10, pady=5)
    
    Label(pathInfo, text="[Mysqld]", width=8).pack(side="left")
    Label(pathInfo, text=lang['path']+":").pack(side="left")
    Entry(pathInfo, textvariable=var['mysql_path'], width=36).pack(side="left")
    Label(pathInfo, text=lang['root_pw'] + ":").pack(side="left", padx=5)
    Entry(pathInfo, textvariable=var['root_pw'], width=10).pack(side='left')
    Label(pathInfo, textvariable=var['mysql_status'], width=8).pack(side="left", padx=5)
    
    var['mysql_path'].set(MYSQL['PATH'])
    var['root_pw'].set(MYSQL['PASS'])
    var['mysql_status'].set(lang['Running'] if MYSQL['RUNNING'] else lang['Stopped'])
    
    prog_bar = ttk.Progressbar(window, maximum=100, length=540, mode="determinate")
    prog_bar.pack(side="top", expand=False, padx=10, pady=5)
    prog_bar["value"] = 0    

    tx = Text(window, height=14, width=76)
    tx.pack(side="top", pady=5)

    btnPad = Frame(window, bd=0, relief="solid")
    btnPad.pack(side="bottom", expand=False, padx=10, pady=5)
    btnStart = Button(btnPad, text=lang["start"],  command=startInstall, width=6, height=1)
    btnStart.pack(side="left", padx=10)
    Button(btnPad, text=lang["cancel"], command=window.destroy, width=6, height=1).pack(side="left", padx=10)
    
    prints("Installation Cosilan for Windows")
    window.mainloop()

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
    
    if flag:
        prints ("writing php ini file")
        with open(fname, "w") as f:
            f.write(body)



########################################################################################################################################################
##############################################  PARAM TABLE ############################################################################################
########################################################################################################################################################

def sqlDbMaster():
	global config_db_file
	conn = sqlite3.connect(config_db_file)
	conn.execute("PRAGMA journal_mode=WAL")
	return conn

def configVars(groupPath='', value=None):
    arr_rs = dict()
    arr= []
    sq = ""
    if groupPath.strip():
        for i, x in enumerate(groupPath.split(".")):
            arr.append("group%d='%s'" %((i+1),x))
        
        sq = " and ".join(arr)
    if sq:
        sq = " where " + sq + " "
    
    sq = "select entryValue, entryName, groupPath, prino from param_tbl " + sq 
    # print(sq)
    
    prino = 0
    configdbconn = sqlDbMaster()
    with configdbconn:
        cur = configdbconn.cursor()
        try:
            cur.execute(sq)
        except:
            return False
        rows = cur.fetchall()
        if not rows:
            arr_rs = ''
        #     return ''
        elif len(rows) == 1 :
            arr_rs = rows[0][0]
            prino = rows[0][3]
        #     return rows[0][0]
        else :
            for r in rows:
                arr_rs[r[2]+"."+r[1]] = r[0]

    if value != None:
        if prino:
            sq = "update param_tbl set entryValue='%s' where prino=%d" %(value, prino)
            print (sq)
            cur.execute(sq)
            configdbconn.commit()
            return True
        return False
    return arr_rs


########################################################################################################################################################
########################   MYSQL //  MariaDB  ##########################################################################################################
#sc create MariaDB binpath= E:\Cosilan\Mariadb\bin\mysqld.exe

def getMysqlPort(fname=''):
	if os.name =='nt' and not fname:
		fname = os.path.dirname(MYSQL['PATH']) + "\\data\\my.ini"
	else :
		return 3306
	# print (fname)

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

def statusMysql(password=''):
    global MYSQL
    # arr = {'path_flag': False, 'execute_path': '', 'port':0, 'version':'', 'uptime':'', 'running':False}
    
    if os.name == 'posix': #Linux Centos 8 stream
        cmd = "ps -ef |grep -v grep| grep mysqld"
    else : #Windows
        cmd = "wmic service where name='mariadb' get pathname" 

    p = os.popen(cmd).read()
    if p:
        # linux : mysql     258045       1  0 15:48 ?        00:00:00 /usr/libexec/mysqld --basedir=/usr
        # windows: "C:\Program Files\MariaDB 10.4\bin\mysqld.exe" "--defaults-file=C:\Program Files\MariaDB 10.4\data\my.ini" "MariaDB"
        MYSQL['RUNNING'] = True
        ex = str(p).split(" ") if os.name == 'posix' else re.findall('"([^"]*)"', str(p))

        for x in ex:
            if (x.find('mysqld') >0 and os.name=='posix') or (x.find('mysqld.exe') >0 and os.name=='nt'):
                MYSQL['PATH'] = os.path.dirname(x)
                break
        if os.name == 'posix':
            cmd = "mysqladmin -uroot -p%s version" %password
        else :
            cmd = "\"%s/mysqladmin.exe\" -uroot -p%s version" %(MYSQL['PATH'], password)
        # print (cmd)
        p = os.popen(cmd).read().upper()

        MYSQL['LOGIN'] = 'fail'
        for line in str(p).splitlines():
            if line.startswith("SERVER VERSION"):
                MYSQL['VERSION'] = line.split("\t")[-1].strip()
                MYSQL['LOGIN'] = "success"
            elif line.startswith("UPTIME:"):
                MYSQL['UPTIME'] = line.split("\t")[-1].strip()
            elif line.startswith("TCP PORT"):
                MYSQL['PORT'] = int(line.split("\t")[-1].strip())
    return True

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
    if os.name == 'nt':
        fname = os.path.dirname(MYSQL['PATH']) + "\\data\\my.ini"
        print (fname)
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

def getCustomDBs():
    arr_db = dict()
    dbcon = dbconMaster()
    with dbcon:
        cur = dbcon.cursor()
        sq = "SHOW DATABASES WHERE `Database` != 'information_schema' and `Database` != 'mysql' and `Database` != 'performance_schema'" 
        cur.execute(sq)
        for db in cur.fetchall():
            arr_tbl = list()
            sq = "show tables from %s" %db[0]
            cur.execute(sq)
            for tbl in cur.fetchall():
                arr_tbl.append(tbl[0])

            if 'square' in arr_tbl and 'store' in arr_tbl and 'camera' in arr_tbl:
                arr_db[db[0]] = arr_tbl

    return arr_db

def backupTable(table_name):
    db_name, tb_name = table_name.split(".")
    dbConLocal = dbconMaster()
    if dbConLocal == None:
        prints("please check mysql or maria db running!!","error")
        return False
    with dbConLocal:
        cur = dbConLocal.cursor()
        sq = "show databases like '%s'" %db_name
        cur.execute(sq)
        row = cur.fetchone()
        # print ("show db", row)
        if not row:
            print ("database '%s' not exists" %db_name)
            sq = "create database %s" %db_name
            cur.execute(sq)
        
        sq = "CREATE TABLE if not exists `common`.`misc` (`p_key` varchar(127), `p_val` text)"
        cur.execute(sq)
        dbConLocal.commit()

        sq = "select p_val from common.misc where p_key='db_backup_%s'" %table_name
        cur.execute(sq)
        row = cur.fetchone()
        if not row:
            sq = "insert into common.misc(p_key, p_val) values('db_backup_%s', 'no')" %table_name
            cur.execute(sq)
            dbConLocal.commit()

        elif row[0] == 'yes':
            prints ("%s already  backuped" %table_name)  
            return True

        sq = "show tables from %s like '%s'" %(db_name, tb_name)
        cur.execute(sq)
        row = cur.fetchone()
        if row:
            prints("table %s backup starts" %table_name)
            sq = "alter table %s rename %s_old" %(table_name, table_name)
            # print (sq)
            cur.execute(sq)
            dbConLocal.commit()

        sq = "update common.misc set p_val='yes' where p_key='db_backup_%s'" %table_name
        cur.execute(sq)
        dbConLocal.commit()
    return True


def patchMariaDB():
    prints ("Patching Maria DB from file")
    fname = _ROOT_DIR + '//DB_BACKUP//db_struct.sql'
    if not os.path.isfile(fname):
        prints ("file not found: %s" %fname)
        return False

    with open(fname, "r") as f:
        body = f.read()

    regex_auto = re.compile('AUTO_INCREMENT=(\d+)', re.IGNORECASE)
    regex_db   = re.compile('USE `(\w+)`;', re.IGNORECASE)
    arr_sql = list()
    sql = ''
    db = ''
    for line in body.splitlines():
        line = line.strip()
        if not line or line[0:2] == "--":
            continue
        if line.startswith('DROP TABLE'):
            continue
        m = regex_db.search(line)
        if m:
            db = m.group(1)
            continue
        if not db in ['common', 'cnt_demo','']:
            continue

        if line.startswith('CREATE TABLE'):
            line = line.replace("CREATE TABLE ", "CREATE TABLE IF NOT EXISTS `%s`." %db)
        m = regex_auto.search(line)
        if m:
            line = line.replace(m.group(), "AUTO_INCREMENT=1")
        sql += line
        if line[-1] == ';':
            arr_sql.append(sql)
            sql = ""
    arr_sql.append("commit")
    arr_sql.append("CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY '13579';")
    arr_sql.append("CREATE USER IF NOT EXISTS 'ct_user'@'localhost' IDENTIFIED BY '13579';")
    arr_sql.append("CREATE USER IF NOT EXISTS 'rt_user'@'%' IDENTIFIED BY '13579';")

    if MYSQL['VERSION'].startswith('8.0'):
        arr_sql.append("ALTER USER 'admin'@'localhost' IDENTIFIED WITH mysql_native_password BY '13579';")
        arr_sql.append("ALTER USER 'ct_user'@'localhost' IDENTIFIED WITH mysql_native_password BY '13579';")
        arr_sql.append("ALTER USER 'rt_user'@'%' IDENTIFIED WITH mysql_native_password BY '13579';")

    if os.name == 'posix':
        arr_sql.append("UPDATE mysql.user SET plugin='auth_socket' WHERE User='admin';")
        arr_sql.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='root';")
        arr_sql.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='admin';")
        arr_sql.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='ct_user';")
        arr_sql.append("UPDATE mysql.user SET plugin='mysql_native_password' where User='rt_user';")
        arr_sql.append("UPDATE mysql.user SET grant_priv='Y' where user='admin';")

    arr_sql.append("GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost';")
    arr_sql.append("GRANT insert, select, update, delete, alter ON common.* TO 'ct_user'@'localhost';")
    arr_sql.append("GRANT insert, select, update, delete, alter ON cnt_demo.* TO 'ct_user'@'localhost';")
    arr_sql.append("GRANT select ON common.* TO 'rt_user'@'%';")
    arr_sql.append("GRANT select ON cnt_demo.* TO 'rt_user'@'%';")

    arr_sql.append("FLUSH PRIVILEGES;")
    # arr_sql.append("INSERT INTO common.users(regdate, code, ID, passwd, db_name, flag, role) VALUES (now(),'U000000000001','root','rootpass','cnt_demo','y','admin') ON DUPLICATE KEY UPDATE ID = VALUES(ID)")
    arr_sql.append("INSERT INTO common.users(regdate, code, ID, passwd, db_name, flag, role) select now(), 'U000000000001','root','rootpass','cnt_demo','y','admin' FROM DUAL WHERE NOT EXISTS(SELECT ID FROM common.users where ID='root')")
    arr_sql.append('commit')

    arr_sql.append("alter table common.params modify usn varchar(127);")
    arr_sql.append("alter table common.params modify product_id varchar(127);")
    arr_sql.append("alter table cnt_demo.camera modify usn varchar(127);")
    arr_sql.append("alter table cnt_demo.camera modify product_id varchar(127);")
    arr_sql.append('commit')

    dbConLocal = dbconMaster()
    if dbConLocal == None:
        prints("pleas check mysql or maria db running!!","error")
        return False  
    
    with dbConLocal:
        cur = dbConLocal.cursor()
        for i, sql in enumerate(arr_sql):
            print (sql)
            if sql == 'commit':
                dbConLocal.commit() 
            else :
                try:
                    cur.execute(sql)
                except Exception as e:
                    prints(str(e))
            progress((i+1), len(arr_sql))

#         for tbl in arrRemoteTables:
#             sq = "show fields from %s.%s like '%s'" %(tbl[0], tbl[1], tbl[2][0])
#             cur.execute(sq)
#             rs = cur.fetchall()
#             if not rs:
#                 default = "" if tbl[2][4] == None else "default '%s'" %(str(tbl[2][4]))
#                 sq = "alter table %s.%s add %s %s %s" %(tbl[0], tbl[1], tbl[2][0], tbl[2][1],  default )
#                 prints (sq)
#                 cur.execute(sq)
#         dbConLocal.commit()
    print()
    print ("""
If you want to use remote access, edit my.ini in windows or /etc/mysql/mariadb.conf.d/50-server.cnf in linux, nand block bind-address like #bind-address=localhost
If you have flush privileges Error "mysqlcheck -r mysql tables_priv -u root -p", "mysqlcheck -u root -p --auto-repair -c -o --all-databases
If you have trouble in runnig mysqld, delete files in data except ***ibdata1*** and my.ini or my.cnf
Got error 176 "Read page with wrong checksum" from storage engine Aria
    """)


def patchLanguage():
    prints ("Patching Language pack")
    dbConLocal = dbconMaster()
    if dbConLocal == None:
        prints("please check mysql or maria db running!!", "error")
        return False    

    with dbConLocal:
        cur = dbConLocal.cursor()
        # ignore_varstrs = ['card_banner',]
        sq = "show tables from cnt_demo like 'language'"
        cur.execute(sq)
        row = cur.fetchone()
        if row:
            sq = "select pk from cnt_demo.language"
            cur.execute(sq)
            if cur.rowcount:
                print ("already exist")
                return True

    fname = _ROOT_DIR + '//DB_BACKUP//language.sql'
    if not os.path.isfile(fname):
        prints ("file not found: %s" %fname)
        return False

    with open(fname, "r", encoding="utf8") as f:
        body = f.read()

    regex_auto = re.compile('AUTO_INCREMENT=(\d+)', re.IGNORECASE)
    db = 'cnt_demo'
    arr_sql = ["USE `%s`" %db, "commit"]
    sql = ''
    
    for line in body.splitlines():
        line = line.strip()
        if not line or line[0:2] == "--":
            continue
        if line.startswith('DROP TABLE'):
            continue

        if line.startswith('CREATE TABLE'):
            line = line.replace("CREATE TABLE ", "CREATE TABLE IF NOT EXISTS `%s`." %db)
        m = regex_auto.search(line)
        if m:
            line = line.replace(m.group(), "AUTO_INCREMENT=1")
        
        sql += line
        if line[-1] == ';':
            arr_sql.append(sql)
            sql = ""
    arr_sql.append('commit')

    dbConLocal = dbconMaster()
    with dbConLocal:
        cur = dbConLocal.cursor()
        # ignore_varstrs = ['card_banner',]
        for i, sq in enumerate(arr_sql):
            prints (sq)
            if sq == 'commit':
                dbConLocal.commit() 
            else :
                try:
                    cur.execute(sq)
                except Exception as e:
                    prints(str(e))
            progress((i+1), len(arr_sql))
    
    print()
 

def patchWebConfig():
    prints ("Patching Webpge Config")
    dbConLocal = dbconMaster()
    if dbConLocal == None:
        print("pleas check mysql or maria db running!!", "error")
        return False        
    with dbConLocal:
        cur = dbConLocal.cursor()
        # ignore_varstrs = ['card_banner',]
        sq = "show tables from cnt_demo like 'webpage_config'"
        cur.execute(sq)
        row = cur.fetchone()
        if row:
            sq = "select pk from cnt_demo.webpage_config"
            cur.execute(sq)
            if cur.rowcount:
                print ("already exist")
                return True

    fname = _ROOT_DIR + '//DB_BACKUP//webpage_config.sql'
    if not os.path.isfile(fname):
        prints ("file not found: %s" %fname)
        return False

    with open(fname, "r", encoding="utf8") as f:
        body = f.read()

    regex_auto = re.compile('AUTO_INCREMENT=(\d+)', re.IGNORECASE)
    db = 'cnt_demo'
    arr_sql = ["USE `%s`" %db, "commit"]
    sql = ''
    
    for line in body.splitlines():
        line = line.strip()
        if not line or line[0:2] == "--":
            continue
        if line.startswith('DROP TABLE'):
            continue

        if line.startswith('CREATE TABLE'):
            line = line.replace("CREATE TABLE ", "CREATE TABLE IF NOT EXISTS `%s`." %db)
        m = regex_auto.search(line)
        if m:
            line = line.replace(m.group(), "AUTO_INCREMENT=1")
        
        sql += line
        if line[-1] == ';':
            arr_sql.append(sql)
            sql = ""
    arr_sql.append('commit')

    dbConLocal = dbconMaster()
    with dbConLocal:
        cur = dbConLocal.cursor()
        for i, sq in enumerate(arr_sql):
            prints (sq)
            if sq == 'commit':
                dbConLocal.commit() 
            else :
                try:
                    cur.execute(sq)
                except Exception as e:
                    prints(str(e))
            progress((i+1), len(arr_sql))
    
    print()

##
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

def systemDatetime():
    lt = time.localtime()
    gt = time.gmtime()
    tz = time.tzname
    tz_offset = time.timezone
    local_time = time.strftime("%Y-%m-%d %H:%M:%S", lt)
    utc_time   = time.strftime("%Y-%m-%d %H:%M:%S", gt)
    return {"timezone": tz[0], "tz_offset": tz_offset *-1, "local_time": local_time, "utc_time": utc_time }

def startInstall():
    if _WIN_GUI:
        MYSQL['PASS'] = var['root_pw'].get()
        statusMysql(MYSQL['PASS'])
        if MYSQL['LOGIN'] == 'fail':
            prints ("Wrong root password")
            return False
    print (MYSQL)
    # backupTable('common.counting_report_10min')
    # backupTable('cnt_demo.count_tenmin')
    # backupTable('cnt_demo.age_gender')
    # backupTable('cnt_demo.language')
    patchMariaDB()
    checkMyIni()
    patchLanguage()
    patchWebConfig()
    makeLink()
    configVars("software.mysql.root_pw", MYSQL['PASS'])
    configVars("software.mysql.path", MYSQL['PATH'])
    configVars("software.mysql.port", MYSQL['PORT'])
    dt_info = systemDatetime()
    configVars("system.datetime.datetime", dt_info['local_time'])
    configVars("system.datetime.timezone.tz_name", dt_info['timezone'])
    configVars("system.datetime.timezone.offset", dt_info['tz_offset'])
    
    # for x in configVars("software.mysql"):
    #     print(x)




def startUpdate():
    if _WIN_GUI:
        global btnStart
        for label in progLabel:
            progress(0, 0, label)

        if not checkMysql(arr_MYSQL, mysqlPathCombo.current(), var['root_pw'].get().strip()):
            return False
        prints(MYSQL)
        btnStart['state'] = "disabled"

        if checkUpdateServer():
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
        btnStart['state'] = "normal"

    
    else :
        return False
        ServerSt = checkAvailabe()
        infoBox(None, ServerSt)
        patchHtml()
        patchBin()
        patchParamDb()
        migrateParam()
        patchMariaDB()
        patchLanguage()
        patchWebConfig()
        makeLink()


def deDuplicate(table_name):
    dbCon = dbconMaster()
    db_name, tb_name = table_name.split(".")
    with dbCon:
        cur =  dbCon.cursor()
        if tb_name == 'count_tenmin':
            sq = "select pk, device_info, timestamp, year, month, day, hour, min, counter_name, counter_label from " + table_name + ""
            wsq =  "device_info='%s' and timestamp=%d and year=%d and month=%d and day=%d and hour=%d and min=%d and counter_name='%s'and counter_label='%s'"
        elif tb_name == 'heatmap':
            sq = "select pk, device_info, timestamp, year, month, day, hour, counter_name, counter_label from " + table_name + ""
            wsq =  "device_info='%s' and timestamp=%d and year=%d and month=%d and day=%d and hour=%d and counter_name='%s'and counter_label='%s'"
        else:
            print ("check table name")
            return False
        cur.execute(sq + "  order by timestamp desc  limit 0,200")
        rows = cur.fetchall()
        i=0
        for row in rows:
            cur.execute(sq + " where " + wsq %row[1:])
            # print (row, cur.rowcount)
            if cur.rowcount >1 :
                del_sq = "delete from "+ table_name + " where pk = %d"  %row[0]
                print (i, row, del_sq)
                cur.execute(del_sq)
            i += 1
        print(row)
        dbCon.commit()




if __name__ == '__main__':
    # deDuplicate('cnt_demo.count_tenmin')
    # sys.exit()

    root_pw = configVars("software.mysql.root_pw")
    if root_pw:
        MYSQL['PASS'] = root_pw
    if _root_pass:
        MYSQL['PASS'] = _root_pass

    statusMysql(MYSQL['PASS'])
    # print (MYSQL)
    if  not _WIN_GUI:
        if MYSQL['RUNNING'] == False:
            prints ("Mysql, Mariadb not running")
            sys.exit()
        if MYSQL['LOGIN'] == 'fail':
            prints ("Wrong root password")
            sys.exit()

        startInstall()
        sys.exit()

    # windows GUI
    from tkinter import *
    from tkinter import ttk
    
    window=None
    lang = loadLangPack()
    var = dict()
    prog_bar = None
    tx = None
    windowsGUI()
