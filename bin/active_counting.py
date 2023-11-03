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
import base64
import threading

from functions_s import (active_cgi, list_device, checkAuthMode, configVars, addSlashes, log, modifyConfig, CGIS, message)
from parse_functions import parseParam, parseCountReport, parseHeatmapData
from db_functions import(MYSQL, getWriteParam, putWriteParam, updateSimpleParam, updateParam, updateSnapshot, getLatestTimestamp, updateCountingReport, updateHeatmap, getDeviceListFromDB, getDeviceInfoFromDB)
# from cgis import arr_cgi_str, set_datetime_str

_TZ_NAME = "Seoul"
# _TZ_NAME = "Hong_Kong"

def putParam(device_ip=None, port=80, authkey=None, cgis=[]):
    data = []
    for cgi in cgis:
        rs = active_cgi(device_ip, authkey, cgi.strip(), port)
        data.append(rs)
    return data

def getParam(device_ip=None, port=80, authkey=None,  device_family='IPN'):
    if not device_family:
        return False
    data = b''
    for cgi in CGIS["query_device"]["param"][device_family]:
        rs = active_cgi(device_ip, authkey, cgi.strip(), port)
        if rs:
            data += rs

    data = data.replace(b"Brand.prodshortname", b"BRAND.Product.shortname")
    return (parseParam(data))

def getSnapshot(device_ip=None, port=80, authkey=None, device_family='IPN', format='b64'):
    if not device_family:
        return False    
    # cgi_str = arr_cgi_str["snapshot"][device_family]
    data = active_cgi(device_ip, authkey, CGIS["query_device"]["snapshot"][device_family], port)
    if format == 'b64':
        data = b'data:image/jpg;base64,' + base64.b64encode(data)
        data = addSlashes(data.decode('utf-8'))
    return data

def getCountReport(device_ip=None, port=80, authkey=None,  device_family='IPN', from_t='2022/01/01', to_t='now'):
    if not device_family:
        return False    
    # cgi_str = arr_cgi_str["countreport"][device_family] %(from_t, to_t)
    data = active_cgi(device_ip, authkey, CGIS["query_device"]["countreport"][device_family] %(from_t, to_t), port)
    data = data.replace(b'Time:', b'Records:')
    return (parseCountReport(data))

def getHeatmap(device_ip=None, port=80, authkey=None,  device_family='IPN', from_t='2022-01-01', to_t='now'):
    if not device_family:
        return False
    # if not arr_cgi_str["heatmap"][device_family]:
    if not CGIS["query_device"]["heatmap"][device_family]:
        return False

    # cgi_str = arr_cgi_str["heatmap"][device_family] %(from_t, to_t)
    data = active_cgi(device_ip, authkey, CGIS["query_device"]["heatmap"][device_family] %(from_t, to_t), port)
    if data.find(b"Not enough data") >0 :
        return []
    return (parseHeatmapData(data))

def testGetFunctions(dev_ip, userid, userpw):
    authkey, dev = checkAuthMode(dev_ip, userid, userpw)
    print (authkey, dev)
    param = getParam(device_ip=dev_ip, port=80, device_family=dev, authkey= authkey)
    print (param)
    snapshot = getSnapshot(device_ip=dev_ip, port=80, device_family=dev, authkey= authkey, format='b64')
    print (snapshot)
    crpt = getCountReport(device_ip=dev_ip, port=80, authkey=authkey,  device_family=dev, from_t='2022/01/01', to_t='now')
    print (crpt)
    hm = getHeatmap(device_ip=dev_ip, port=80, authkey=authkey,  device_family=dev, from_t='2022-01-01', to_t='now')
    print(hm)

# testGetFunctions("192.168.219.18", "root", "pass")
# sys.exit()

def writeParam(device_info='', device_ip=None, port=80, authkey=None,  device_family='IPN'):
    regdate = time.strftime("%Y-%m-%d %H:%M:%S")
    arr_cmd = getWriteParam(device_info)
    if (arr_cmd):
        message ("write cgi commands to %s at %s" %(device_info, regdate))
        putParam(device_ip=device_ip, port=port, authkey=authkey, cgis=arr_cmd)
        putWriteParam(device_info, [])
    return True  

def setDatetimeToDevice(device_ip=None, port=80, authkey=None,  device_family='IPN'):
    if not device_family:
        return False      
    # if not set_datetime_str["read"][device_family]:
    if not CGIS['datetime']['read'][device_family]:
        return False
    # cgi_str = set_datetime_str["read"][device_family]
    data = active_cgi(device_ip, authkey, CGIS['datetime']['read'][device_family], port)
    arr = dict()
    for line in data.splitlines():
        sp_line = line.split(b"=")
        if len(sp_line) <2:
            print (line)
            continue
        arr[sp_line[0].decode().lower().strip()] = sp_line[1].decode().lower().strip()

    # if arr.get('system.datetime.tz.name') != _TZ_NAME.lower().strip():
    #     print ("setting timezone")
    #     for tzname, desc, posixrule in CGIS['datetime']['timezone']:
    #         if tzname.lower().strip() == _TZ_NAME.lower().strip():
    #             break
        
    #     print (tzname, desc, posixrule)
    #     for cgi_str in CGIS['datetime']['set_tz'][device_family]:
    #         if (cgi_str.find("name=")) >0:
    #             cgi_str = cgi_str %tzname
    #         elif (cgi_str.find("posixrule=") >0):
    #             cgi_str = cgi_str %posixrule
    #         x = active_cgi(device_ip, authkey, cgi_str, port)
    #         print (x)
    #     time.sleep(2)

    x = active_cgi(device_ip, authkey, CGIS['datetime']['set_datetime'][device_family] %(time.strftime("%m%d%H%M%Y.%S")), port)
    print (x)
    log.info("%s: Setting datetimezone OK" %device_ip)

def testSetDatetime(dev_ip, userid, userpw) :
    authkey, dev = checkAuthMode(dev_ip, userid, userpw)
    setDatetimeToDevice(dev_ip, port=80, authkey=authkey,  device_family=dev)

# testSetDatetime("192.168.219.20", "root", "pass" )
# sys.exit()

def searchDeviceToDB():
# {'idx': 0, 'usn': 'HA0A0073A', 'url': 'http://192.168.1.58:49152/upnpdevicedesc.xml', 'location': '192.168.1.58', 'mac': '001323A0073A', 'model': 'NS202HD', 'brand': 'CAP'}
    arr_dev = list_device()
    
    for dev in arr_dev:
        if not (dev['mac'] and dev['brand'] and dev['model']) :
            msg = "device_info Error mac:%s, brand:%s, model:%s " %(dev['mac'], dev['brand'], dev['model'])
            log.warning (msg)
            message (msg)
            continue

        message(dev)
        device_info = "mac=%s&brand=%s&model=%s" %(dev['mac'], dev['brand'], dev['model'])
        updateSimpleParam(device_info, dev['usn'], dev['location'])
    strn = "browsing devices %d and info to db succeed" %len(arr_dev)
    message (strn)
    log.info(strn)

def procActive():
    n= 0 
    set_date_flag = False
    arr_dev = getDeviceListFromDB()
    if int(configVars('software.status.datetime_sync')) + 3600*24 < int(time.time()) :
        set_date_flag = True
        log.info("Setting device time")

    for dev in arr_dev:
        if not dev['online']:
            continue
        # print(dev)
        if not ( dev['authkey'] and dev['device_family']):
            strn = "%s: No authkey or device family" %dev['ip']
            message(strn)
            log.info(strn)
            continue

        try:
            writeParam(device_info=dev['device_info'], device_ip=dev['ip'], port=80, authkey=dev['authkey'],  device_family=dev['device_family'])

        except Exception as e:
            msg = "fail to write params: {0}".format(str(e))
            message(msg)
            log.error(msg)
        
        if set_date_flag:
            setDatetimeToDevice(device_ip=dev['ip'], port=80, authkey=dev['authkey'], device_family=dev['device_family'])
        
        param = getParam(device_ip=dev['ip'], port=80, authkey=dev['authkey'], device_family=dev['device_family'])
        if param:
            updateParam(device_info=dev['device_info'], param=param)
        else:
            log.error(dev['ip'] + " param retrieve wrong")

        snapshot = getSnapshot(device_ip=dev['ip'], port=80, authkey=dev['authkey'], device_family=dev['device_family'], format='b64')
        if snapshot:
            updateSnapshot(device_info=dev['device_info'], snapshot=snapshot)
        else:
            log.error(dev['ip'] + " snapshot retrieve wrong")
        
        #get counting report and heatmap report where db_name !=none and function=y
        dev_info = getDeviceInfoFromDB(dev['device_info'])
        if dev_info: # False if not in db
            if dev_info['db_name'] != 'none':
                if dev_info['countrpt'] == 'y':
                    from_t, dt, readflag, ts = getLatestTimestamp(MYSQL['commonCounting'], device_info=dev['device_info'])
                    if readflag > 600:
                        print("active.counting:", dev['device_info'], from_t, readflag, ts)
                        crpt = getCountReport(device_ip=dev['ip'], port=80, authkey=dev['authkey'], device_family=dev['device_family'], from_t=from_t, to_t='now')
                        if crpt:
                            updateCountingReport(device_info=dev['device_info'], arr_record=crpt)
                        else :
                            log.error(dev['ip'] + "CRTP is bool or wrong")

                if dev_info['heatmap'] == 'y':
                    from_t, dt, readflag, ts = getLatestTimestamp(MYSQL['commonHeatmap'], device_info=dev['device_info'])
                    if readflag >3600:
                        ts = time.mktime(dt)+3600
                        from_t = time.strftime("%Y/%m/%d%%20%H:%M", time.localtime(ts))
                        print("active.heatmap:", dev['device_info'], from_t, readflag, ts)
                        hm = getHeatmap(device_ip=dev['ip'], port=80, authkey=dev['authkey'], device_family=dev['device_family'], from_t=from_t, to_t='now')
                        if hm:
                            updateHeatmap(device_info=dev['device_info'], arr_record=hm)
                        else :
                            log.error(dev['ip'] + "Heatmap is bool or wrong")


        modifyConfig('software.status.last_device_access', int(time.time()))
        n+=1

    if set_date_flag:
        modifyConfig('software.status.datetime_sync', int(time.time()))
        set_date_flag = False

    return n

class thActiveCountingTimer():
    def __init__(self, t=60):
        self.name = "active_count"
        self.t = t
        self.last = 0
        self.i = 0
        self.thread = threading.Timer(1, self.handle_function)

    def handle_function(self):
        self.main_function()
        self.last = int(time.time())
        self.thread = threading.Timer(self.t, self.handle_function)
        self.thread.start()
    
    def main_function(self):
        ts = time.time()
        str_s = "======== Active Counting, starting %d ========" %self.i
        log.info (str_s)
        message (str_s)
        searchDeviceToDB()

        n = procActive()
        te = time.time()
        self.t = 300 - int(te - ts) 
        if self.t  < 0:
            self.t  = 1
        str_s = "Online %d, elaspe time: %d, need %d sec sleep" %(n, (te-ts), self.t )
        message (str_s)
        log.info(str_s)
        
        self.i += 1

    def start(self):
        str_s = "starting Active Counting Service"
        message(str_s)
        log.info (str_s)
        self.last = int(time.time())
        self.thread.start()

    def is_alive(self):
        if int(time.time()) - self.last > 600 :
            return False
        return True
    
    def cancel(self):
        str_s = "stopping Active Counting Servce"
        message(str_s)
        log.info (str_s)
        self.thread.cancel()
    
    def stop(self):
        self.cancel()

if __name__ == '__main__':
    dev_ip = "192.168.3.38" ;    userid = 'root';     userpw = 'pass'
    # procActive()
    tc = thActiveCountingTimer()
    tc.start()
    while True:
        time.sleep (100)
    
