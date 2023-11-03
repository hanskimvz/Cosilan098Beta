#!/usr/bin/env python
# wget http://49.235.119.5/download.php?file=../bin/sysdaemon.py -O /var/www/bin/sysdaemon.py

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
import socket
import threading

from functions_s import (_PLATFORM, _SERVER, _ROOT_DIR, log, configVars, callCommand, modifyConfig, dbconMaster, outLED)


def systemDatetime():
    lt = time.localtime()
    gt = time.gmtime()
    tz = time.tzname
    tz_offset = time.timezone
    local_time = time.strftime("%Y-%m-%d %H:%M:%S", lt)
    utc_time   = time.strftime("%Y-%m-%d %H:%M:%S", gt)
    return {"timezone": tz, "tz_offset": tz_offset *-1, "local_time": local_time, "utc_time": utc_time }

# print (systemDatetime())
# print(time.strftime("%z", time.gmtime()))
# gt = time.gmtime()
# lt = time.localtime()
# print ((time.mktime(lt) - time.mktime(gt))/3600)
# # print (time.time() - time.mktime())

# sys.exit()
if os.name == "nt":
    import winreg

# def register_auto_start(flag):
#     file_ex = '"%s/bin/start.bat" ' % (_ROOT_DIR)
#     key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, 'Software\Microsoft\Windows\CurrentVersion\Run', 0, winreg.KEY_SET_VALUE)
#     if flag == 'yes':
#         winreg.SetValueEx(key, 'startBI', 0, winreg.REG_SZ, file_ex)
#         print("register to auto start up")
#     else:
#         try:
#             winreg.DeleteValue(key, 'startBI')
#         except:
#             pass
#         print("cancel from auto start up")

#     key.Close()



# register_auto_start('yes')



# def register_auto_start(flag):
#     file_ex = '"%s\\bin\\start.bat" ' %_ROOT_DIR
#     key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, 'Software\Microsoft\Windows\CurrentVersion\Run', 0, winreg.KEY_SET_VALUE)
#     if flag == 'yes':
#         winreg.SetValueEx(key, 'startBI', 0, winreg.REG_SZ, file_ex)
#         print("register to auto start up")
#     else:
#         try:
#             winreg.DeleteValue(key, 'startBI')
#         except:
#             pass
#         print("cancel from auto start up")

#     key.Close()


# info_to_db('proc_db', change_log)
# if _DEBUG_DISPLAY:
#     print (change_log)

# date_str = '2023-01-31 21:30:00'
# ts = 1675168200
# dt = time.strptime(date_str, "%Y-%m-%d %H:%M:%S")
# ts2 = time.mktime(dt)
# dd = time.gmtime(ts2)
# ts3 = time.mktime(dd)

# tx = time.strftime("%Y-%m-%d %H:%M:%S", dd)
# print (ts, ts2, ts3, tx)
# sys.exit()

TEMP_MAX = 0

argv = ''
try:
    argv = sys.argv[1]
except:
    argv=""
    pass

def getCpuInfo():
    cpuInfo = str(callCommand("cat /proc/cpuinfo")).split('\n')
    cpuData = {'processor':[]}
    core = 0
    for section in cpuInfo:
        if ":" in section:#search key for processors
            section = str(section).split(':')
            if 'processor' in section[0]:
                cpuData['processor'].append({'core' : section[1].strip(), 'BogoMIPS' : ""})
            elif 'BogoMIPS' in section[0]:
                cpuData['processor'][core]['BogoMIPS'] = section[1].strip()
                core = core + 1
            else:
                cpuData[section[0].strip()] = section[1].strip()

    return cpuData

def getMemoryInfo():
    memInfo = str(callCommand("cat /proc/meminfo")).split('\n')
    memData = {}
    for section in memInfo:
        if ":" in section:
            section = str(section).split(':')
            memData[section[0].strip()] = section[1].strip()

    return memData

def getSystemInfo():
    sysData = {
        'Kernel_version':str(callCommand("uname -mrs")),
        'System':str(callCommand("uname -a")),
        'System_Date':str(callCommand("date")),
        'Distribution':{}
    }

    lsbData = str(callCommand("lsb_release -a")).split('\n')

    for section in lsbData:
        if ":" in section:
            section = str(section).split(':')
            sysData['Distribution'][section[0].strip()] = section[1].strip()

    return sysData

def updateTemperature():
    global TEMP_MAX
    if os.name == 'posix' and _PLATFORM=='OPAVIS':
        temp = str(callCommand('cat /sys/class/thermal/thermal_zone0/temp'))
        temp = int(temp.strip())
        if temp > TEMP_MAX:
            TEMP_MAX = temp
            modifyConfig("software.status.temperature_max", temp)

        modifyConfig("software.status.temperature", temp)
    else :
        print ("OPAVIS only")
    

def register_auto_start(flag):
    return True
    if not os.name == 'nt':
        print ("windows only")
        return False
    rName = 'startBI'
    rPath = 'Software\Microsoft\Windows\CurrentVersion\Run'
    key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, rPath, 0, winreg.KEY_READ)
    try:
        xset = winreg.QueryValueEx(key, rName)
    except:
        xset = False

    key.Close()

    file_ex = '"%s/bin/start.bat" ' % (_ROOT_DIR)
    key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, rPath, 0, winreg.KEY_SET_VALUE)
    if flag == 'yes' and  not xset:
        winreg.SetValueEx(key, rName, 0, winreg.REG_SZ, file_ex)
        print("register to auto start up")
    elif flag == 'no' and xset:
        try:
            winreg.DeleteValue(key, rName)
        except:
            pass
        print("cancel from auto start up")

    key.Close()

##################### Network ##############################
# only for Orange pi 
def netmask_to_cidr(netmask):
    x = sum([bin(int(x)).count('1') for x in netmask.split('.')])
    return int(x)

def cidr_to_netmask(cidr):
    cidr = int(cidr)
    mask = (0xffffffff >> (32 - cidr)) << (32 - cidr)
    netmask = "%d.%d.%d.%d" %((0xff000000 & mask) >> 24, (0x00ff0000 & mask) >> 16, (0x0000ff00 & mask) >> 8 , (0x000000ff & mask))
    return netmask

def getNetworkInfo(nic="eth0"):
    # only for Orange pi 
    arr_rs = {
        'ip4mode': 'static', 
        'ip6mode': 'static',
    }
    body = (callCommand("/usr/bin/nmcli device show %s" %nic))
    lines = body.splitlines()
    # for line in lines:
    #     print(line)

    for line in lines:
        if not line:
            continue
        if line.startswith("IP4.ROUTE["):
            continue
        if line.startswith("IP6.ROUTE["):
            continue
        if line.startswith("GENERAL.CON-PATH"):
            continue
        if line.startswith("WIRED-PROPERTIES.CARRIER"):
            continue
        
        if line.startswith("GENERAL.DEVICE"):
            _, b = line.split(':')
            arr_rs["device"] = b.strip()
            continue

        if line.startswith("GENERAL.HWADDR"):
            _, b = line.split(": ")            
            arr_rs["hwaddress"] = b.strip().split("/")[0]

        elif line.startswith("GENERAL.STATE"):
            _, b = line.split(": ")            
            arr_rs["state"] = b.strip().split("/")[0]


        elif line.startswith("IP4.ADDRESS[1]"):
            _, b = line.split(": ")            
            arr_rs["ip4address"] = b.strip().split("/")[0]
            arr_rs["ip4cidr"] = int(b.strip().split("/")[1])
            arr_rs["ip4subnetmask"] = cidr_to_netmask(arr_rs["ip4cidr"])

        elif line.startswith("IP4.GATEWAY"):
            _, b = line.split(": ")            
            arr_rs["ip4gateway"] = b.strip()
            
        elif line.startswith("IP4.DNS[1]"):
            _, b = line.split(": ")
            arr_rs["ip4dns1"] = b.strip()

        elif line.startswith("IP4.DNS[2]"):
            _, b = line.split(": ")
            arr_rs["ip4dns2"] = b.strip()


        elif line.startswith("IP4.DOMAIN["):
            _, b = line.split(": ")
            if b.strip() == 'DHCP':
                arr_rs["ip4mode"] = 'dhcp'

        elif line.startswith("IP6.ADDRESS[1]"):
            _, b = line.split(": ")            
            arr_rs["ip6address"] = b.strip().split("/")[0]
            arr_rs["ip6cidr"] = b.strip().split("/")[1]

        elif line.startswith("IP6.GATEWAY"):
            _, b = line.split(": ")            
            arr_rs["ip6gateway"] = b.strip().split("/")[0]

        elif line.startswith("IP6.DNS[1]"):
            _, b = line.split(": ")            
            arr_rs["ip6dns1"] = b.strip().split("/")[0]

    # print(arr_rs)
    return arr_rs

def getNetworkParamDb(nic):
    arr_rs = configVars("system.network.%s" %nic) 
    arr_rs['ip4cidr'] = netmask_to_cidr(arr_rs['ip4subnetmask'])
    print (arr_rs)
    return arr_rs

def compareNetworkSysParam(nic):
    arr_sys = getNetworkInfo(nic) # from system
    arr_db = getNetworkParamDb(nic) #from db
    
    if arr_db["ip4mode"] == 'dhcp':
        arr_cmpr = ["ip4mode"]

    else :
        arr_cmpr = ["ip4mode", "ip4address", "ip4gateway", "ip4dns1", "ip4dns2", "ip4cidr"]
    for cmpr in arr_cmpr:
        # print (str(arr_sys[cmpr]) +"\t" + str(arr_db[cmpr]))
        if arr_sys[cmpr] != arr_db[cmpr] :
            return False

    return True


def updateNetworkParamDB(nic="eth0"):
    arr_sys = getNetworkInfo(nic)
    modifyConfig("system.network.%s.ip4.mode" %nic, arr_sys['ip4mode'])
    modifyConfig("system.network.%s.ip4.address" %nic, arr_sys['ip4address'])
    modifyConfig("system.network.%s.ip4.gateway" %nic, arr_sys['ip4gateway'])
    modifyConfig("system.network.%s.ip4.subnetmask" %nic, arr_sys['ip4subnetmask'])
    modifyConfig("system.network.%s.ip4.dns1" %nic, arr_sys['ip4dns1'])
    modifyConfig("system.network.%s.ip4.dns2" %nic, arr_sys['ip4dns2'])

    modifyConfig("system.network.%s.ip6.mode" %nic, arr_sys['ip6mode'])
    # modifyConfig("system.network.%s.ip6.address" %nic, arr_sys['ip6address'])


def updateNetworkConf(nic):
    arr_sys = getNetworkInfo(nic) # from system
    arr_db = getNetworkParamDb(nic) #from db
    
    a_p = list()
    if arr_db['ip4changed'] == 'yes':
        if arr_db['ip4mode'] == 'dhcp' and arr_sys['ip4mode'] !='dhcp':
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.method auto')
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.gateway ""')
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.address ""')
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.dns ""')
        
        if arr_db['ip4mode'] == 'static':
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.address "%s/%d"' %(arr_db['ip4address'], arr_db['ip4cidr']))
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.gateway "%s"' %arr_db['ip4gateway'])
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.dns "" ')
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.dns "%s" ' %arr_db['ip4dns1'])
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' +ipv4.dns "%s"' %arr_db['ip4dns2'])
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' +ipv4.dns "%s"' %arr_db['ip4gateway'])
            a_p.append('/usr/bin/nmcli device modify ' + nic + ' ipv4.method manual ') 

    for s in a_p:
        print (s, end = ": ")
        x = callCommand(s)
        print (x)
        time.sleep(0.5)
    
    for i in range (0,10):
        x = compareNetworkSysParam(nic)
        print(x)
        if (x):
            break
        time.sleep(1)

    if (x):
        if arr_db['ip4mode'] == 'dhcp':
            updateNetworkParamDB(nic)
 
        callCommand("systemctl stop upnpd")
        time.sleep(2)
        callCommand("systemctl start upnpd")

def procNetworkUpdate():
#         # network
    st = callCommand("/usr/bin/nmcli device show eth0 |grep GENERAL.STATE")
    if st.find("connected") < 0:
        outLED('LINK', 'OFF')
    else :
        outLED('LINK', 'ON')
    
    if configVars("system.network.eth0.ip4.changed") == 'yes' or configVars("system.network.eth0.ip6.changed") == 'yes':
        updateNetworkConf("eth0")
        log.info("network eth0 conf. in param database changed")

    elif st.find("connected") > 0:
        updateNetworkParamDB("eth0")

    if configVars("system.network.wlan0.ip4.changed") == 'yes':
        updateNetworkConf("wlan0")
        log.info("network wlan0 conf. in param database changed")


def getLocalIP():
    # s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    # s.connect(('localhost', 80))
    # print (s.getsockname())
    # s.close()

    a = socket.getaddrinfo(socket.gethostname(), None, 2, 1, 0)
    print (a)



def reportToServer():
    paramStatus         = configVars('software.status')
    start_time          = int(paramStatus['software.status.start_time'])
    last_time           = int(paramStatus['software.status.last_time'])
    connecting_device   = int(paramStatus['software.status.connecting_device'])
    active_device       = int(paramStatus['software.status.active_device'])
    last_device_access  = int(paramStatus['software.status.last_device_access'])
    temp_max            = int(paramStatus['software.status.temperature_max'])
    temp_cur            = int(paramStatus['software.status.temperature'])
   
    hwaddr              = configVars('system.network.eth0.hwaddr')
    update_lic          = configVars('software.service.license.changed')
    
    if os.name=='posix':
        ip_addr         = callCommand("hostname -I | awk '{print $1}'").strip() 
    else :
        ip_addr = os.popen("hostname").read().strip()

    dbConRemote = dbconMaster(host=_SERVER, user = 'rt_user', password = '13579',  charset = 'utf8', port=3306)
    if not dbConRemote :
        str_s = "%s RT connection Error" %_SERVER
        print(str_s)
        log.error(str_s)
        return False

    with dbConRemote:
        cur = dbConRemote.cursor()
        sq = "select pk from cosilanStatus.status where mac='%s'" %hwaddr
        cur.execute(sq)
        rs = cur.fetchone()
        if not rs:
            sq= "insert into cosilanStatus.status(regdate,  ID, mac, ip_addr) values(now(), 'unknown', '%s', '%s')" %(hwaddr, ip_addr)
            cur.execute(sq)
            dbConRemote.commit()

        sq = "update cosilanStatus.status set timestamp='%d', start_time='%d', last_time='%d', connecting_device='%d', active_device='%d', last_device_access='%d', ip_addr='%s', temp_max='%s', temp_cur='%s'" %(time.time(), start_time, last_time, connecting_device, active_device, last_device_access, ip_addr, temp_max, temp_cur)
        if update_lic == 'yes':
            sq += ", license_code='%s'" %(configVars("software.service.license.code"))
            modifyConfig('software.service.license.changed', 'no')
        sq += " where mac='%s'" %(hwaddr)
        # print(self.sq)
        try:
            cur.execute(sq)
            dbConRemote.commit()
        except Exception as e:
            log.error(str(e))
            log.error(sq)

def checkUpdate():
    # TO DO : query update version from server and do python update_main.py, update_main.py will be changed so execute "python3 update_main.py"
    
    dbConRemote = dbconMaster(host=_SERVER, user = 'rt_user', password = '13579',  charset = 'utf8', port=3306)
    if not dbConRemote :
        log.error("%s RT connection Error" %_SERVER)
        print("%s RT connection Error" %_SERVER)
        return False
    
    with dbConRemote:
        cur = dbConRemote.cursor()
        sq = "select code, version_web, version_bin, ldate, comment from cosilanStatus.sw_update order by code desc limit 1"
        cur.execute(sq)
        rs = cur.fetchone()
        
        if int(rs[0]) > int(configVars("software.build.code")) : 
            print ("trying to update software")
            log.info("trying to update software")
            os.chdir("%s/bin" %_ROOT_DIR)
            try:
                os.system('python3 update.py')
            except Exception as e:
                log.error(str(e))
                log.error(sq)

            print ("sw updated")
            log.info("sw updated ")
            os.chdir(_ROOT_DIR)
        else :
            print ("already up to date")
            # log.info("already up to date ")

def backupDB(db_name = '', rootpasswd=''):
    regdate = time.strftime("%Y%m%d")
    if not db_name :
        db_name = configVars('software.mysql.db_custom.db')
    if not rootpasswd:
        rootpasswd = configVars('software.mysql.root_pw')

    if os.name=='nt':
        db_backup_path = _ROOT_DIR + '\\DB_BACKUP' 
        exe_dbdump =   '%s\\mysqldump.exe' %configVars('software.mysql.path')
        print(exe_dbdump)

    else :
        db_backup_path = "/root/db_backup"
        exe_dbdump = "/usr/bin/mysqldump"
    
    if not os.path.exists(db_backup_path):
            os.mkdir(db_backup_path)

    if not os.path.isfile(exe_dbdump) :
        print ("Excutable file 'mysqldump' is not found!")
        log.info ("Excutable file 'mysqldump' is not found!")
        return False

    if os.name=='nt':
        exe_dbdump =   '"%s"' %exe_dbdump

    prefix = configVars('software.mysql.autobackup.prefix')
    if not prefix:
        prefix = 'db_backup'

    # shutil.copy2('%s/MariaDB/data/ibdata1' %rootdir, '%s/ibdata1.%s' %(db_backup_path, regdate))
    fname = "%s/%s_%s.sql"  %(db_backup_path, prefix, regdate)
    cmd = "%s -uroot -p%s --databases %s > %s" %(exe_dbdump, rootpasswd, db_name, fname)
    print (cmd)
    p = os.popen(cmd).read()
    if p.find('Access denied') >=0:
        log.info("Backup failed, %s" %str(p), "error")
        return False
    with open(fname, 'a') as f:
        f.write("\n--------------------------------------------------------\n")
        f.write("CREATE DATABASE /*!32312 IF NOT EXISTS*/ `common` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;\n")
        f.write("use `common`;\n")
        
    cmd = "%s -uroot -p%s common params >> %s" %(exe_dbdump, rootpasswd, fname)
    os.system(cmd)
    cmd = "%s -uroot -p%s common users  >> %s" %(exe_dbdump, rootpasswd, fname)
    os.system(cmd)
    log.info("backuped at  %s" %(fname))

    return True







class sysControlTimer():
    # backup, update 
    def __init__(self, t=5):
        self.name="sys_ctrl"
        self.t = t
        self.last = 0
        self.i = 0
        self.datemon = True
        self.thread = threading.Timer(self.t, self.handle_function)
        self.startonboot = configVars('software.service.start_on_boot')
        register_auto_start(self.startonboot)

    def handle_function(self):
        self.main_function()
        self.last = int(time.time())
        self.thread = threading.Timer(self.t, self.handle_function)
        self.thread.start()
    
    def main_function(self):
        # OPAVIS
        if _PLATFORM == 'OPAVIS':
            updateTemperature()
            procNetworkUpdate()

        if os.name == 'nt':
            # auto start on boot
            if self.startonboot != configVars('software.service.start_on_boot'):
                self.startonboot = configVars('software.service.start_on_boot')
                register_auto_start(self.startonboot)

        # COMMON
        if self.i == 23 : #5*24 = 120, every 2 minute
            # if configVars('software.root.update.autoupdate') == 'yes':
                # checkUpdate()
            
            if configVars('software.mysql.autobackup.enable') == 'yes':
                tss_now = time.gmtime(time.time() + int(configVars('system.datetime.timezone.offset')))
                bkd_datetime = configVars('software.mysql.autobackup.backed') 
                if not bkd_datetime or bkd_datetime == '':
                    bkd_datetime = "1900-01-01 02:00"
                tss_bkd = time.strptime(bkd_datetime,"%Y-%m-%d %H:%M") 
                
                if tss_now.tm_year != tss_bkd.tm_year or tss_now.tm_mon != tss_bkd.tm_mon or tss_now.tm_mday != tss_bkd.tm_mday :
                    tss_sch = time.strptime(configVars('software.mysql.autobackup.schedule'),"%H:%M")
                    if (tss_now.tm_hour*3600 + tss_now.tm_min*60 + tss_now.tm_sec) > (tss_sch.tm_hour*3600 + tss_sch.tm_min*60 + tss_sch.tm_sec):
                        print ("ready to backup")
                        log.info("starting backup on schedule")
                        backupDB()
                        modifyConfig ('software.mysql.autobackup.backed', time.strftime("%Y-%m-%d %H:%M"))

            
            # if configVars('software.status.report') =='yes':
            #     reportToServer()
        
        self.i +=1
        if self.i >23 :
            self.i = 0

    def start(self):
        str_n = "Starting System Conrtol service as Auto update, reportting, Auto Backup"
        print(str_n)
        log.info(str_n)

        self.last = int(time.time())
        self.thread.start()

    def is_alive(self) :
        if int(time.time()) - self.last > 240:
            return False
        return True

    def cancel(self):
        str_n = "Stopping System Conrtol service"
        print(str_n)
        log.info(str_n)
        self.thread.cancel()
    
    def stop(self):
        self.cancel()

if __name__ == '__main__':
    if argv == 'backup':
        backupDB()

