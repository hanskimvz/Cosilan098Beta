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
import socket

from functions_s import (send_tlss_command, recv_tlss_message, configVars, addSlashes, log, modifyConfig, CGIS, message)
from parse_functions import parseParam, parseCountReport, parseHeatmapData
from db_functions import(MYSQL, getWriteParam, putWriteParam, updateParam, updateSnapshot, getLatestTimestamp, updateCountingReport, updateHeatmap, getDeviceInfoFromDB)
# from cgis import arr_cgi_str, device_family_str

def tlss_cgi(conn, cgi_str, timeout=2):
    send_tlss_command(conn, cgi_str)
    # time.sleep(0.1)
    data  = recv_tlss_message(conn, timeout)
    return data

def check_device_family(conn):
    for dev in CGIS['device_family']:
        data = tlss_cgi(conn, CGIS['device_family'][dev])
        if data and data.find(b'No input file specified') < 0:
            return dev
    return False

def putParam(conn, cgis=[]):
    data = []
    for cgi in cgis:
        rs = tlss_cgi(conn, cgi.strip(), timeout=2)
        data.append(rs)
    return data

def getParam(conn=None, device_family='IPN'):
    data = b''
    for cgi in CGIS["query_device"]["param"][device_family]:
        # print (cgi)
        rs = tlss_cgi(conn, cgi.strip(), timeout=2)
        spos = 0
        if device_family == 'IPN' or device_family == 'IPE':
            # spos = rs.index(b"\n\r")
            spos = rs.find(b"\n\r")
        data += rs[spos:].lstrip()
        
    data = data.replace(b"Brand.prodshortname", b"BRAND.Product.shortname")
    # print(data)
    return (parseParam(data))

def getSnapshot(conn=None, device_family='IPN', format='b64'):
    data = tlss_cgi(conn, CGIS["query_device"]["snapshot"][device_family], timeout=2)
    if device_family == 'IPN' or device_family == 'IPE':
        # spos = data.index(b"\n\r")
        spos = data.find(b"\n\r")
        data = data[spos:].lstrip()

    if format == 'b64':
        data = b'data:image/jpg;base64,' + base64.b64encode(data)
        data = addSlashes(data.decode('utf-8'))
    return data

def getCountReport(conn=None, device_family='IPN', from_t='2022/01/01', to_t='now'):
    data = tlss_cgi(conn, CGIS["query_device"]["countreport"][device_family] %(from_t, to_t), timeout=2)
    if device_family == 'IPN' or device_family == 'IPE':
        # spos = data.index(b"\n\r")
        spos = data.find(b"\n\r")
        data = data[spos:].lstrip()

    data = data.replace(b'Time:', b'Records:')
    # print(data)
    return (parseCountReport(data))

def getHeatmap(conn=None, device_family='IPN', from_t='2022-01-01', to_t='now'):
    if not CGIS["query_device"]["heatmap"][device_family]:
        return False

    data = tlss_cgi(conn, CGIS["query_device"]["heatmap"][device_family] %(from_t, to_t), timeout=2)
    if device_family == 'IPN' or device_family == 'IPE':
        # spos = data.index(b"\n\r")
        spos = data.find(b"\n\r")
        data = data[spos:].lstrip()
    # print (data)
    return (parseHeatmapData(data))

def testGetFunctions(port=65000):
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('', port))
    s.listen(1) 

    conn, addr = s.accept()
    print (addr)
    device_info = recv_tlss_message(conn, timeout=2)
    print (device_info)
    dev_family = check_device_family(conn)
    print(dev_family)
    param = getParam(conn=conn, device_family=dev_family)
    print(param)
    snapshot = getSnapshot(conn, device_family=dev_family, format='b64')
    print(snapshot)
    crpt = getCountReport(conn, device_family=dev_family, from_t='2022/01/01', to_t='now')
    print (crpt)
    hm = getHeatmap(conn,  device_family=dev_family, from_t='2022-01-01', to_t='now')
    print(hm)

    send_tlss_command(conn,"done\0")
    time.sleep(2) # wait for client disconnect
    conn.close()
    s.close()  
  
# testGetFunctions(port=5000)
# sys.exit()


def writeParam(conn, device_info=''):
    regdate = time.strftime("%Y-%m-%d %H:%M:%S")
    arr_cmd = getWriteParam(device_info)
    if arr_cmd:
        print ("write cgi commands to %s at %s" %(device_info, regdate))
        putParam(conn, cgis=arr_cmd)
        putWriteParam(device_info, [])
    return True  

def tlss_client_thread(conn):
    device_info_b = recv_tlss_message(conn)
    print (device_info_b)
    try:
        device_info = device_info_b.decode('ascii')
    except Exception as e:
        msg = "Invalid device info: {0}, {1}".format(device_info_b[:10].decode('ascii'), str(e))
        log.error(msg)
        print (msg)
        conn.close()
        return False

    tabs = device_info.split("&")
    if (len(tabs) != 3) or  (tabs[0].find("mac=") < 0) or (tabs[1].find("brand=") < 0) or (tabs[2].find("model=") < 0)  :
        log.error("Invalid device info: {0}".format(device_info))
        conn.close()
        return False

    start_timestamp = time.time()
    regdate = time.strftime("%Y-%m-%d %H:%M:%S")
    dev_family = check_device_family(conn)
    log.info("{0} connected tlss: {1}, {2}".format(device_info, regdate, dev_family))

    writeParam(conn=conn, device_info=device_info)
    param = getParam(conn, device_family=dev_family)
    # print (param)
    if param['ret'] :
        updateParam(device_info, param)
    snapshot = getSnapshot(conn, device_family=dev_family, format='b64')
    if snapshot:
        updateSnapshot(device_info, snapshot)

    dev_info = getDeviceInfoFromDB(device_info)
    # print(dev_info)
    if dev_info: # False if not in db
        if dev_info['db_name'] != 'none':
            if dev_info['countrpt'] == 'y':
                from_t, dt, readflag, ts = getLatestTimestamp(MYSQL['commonCounting'], device_info=device_info)
                if readflag > 600:
                    print("tlss.counting:", device_info, from_t, readflag, ts)
                    crpt = getCountReport(conn, device_family=dev_family, from_t=from_t, to_t='now')
                    if crpt:
                        updateCountingReport(device_info, crpt)

            if dev_info['heatmap'] == 'y':
                from_t, dt, readflag, ts = getLatestTimestamp(MYSQL['commonHeatmap'], device_info=device_info)
                if readflag >3600:
                    ts = time.mktime(dt)+3600
                    from_t = time.strftime("%Y/%m/%d%%20%H:%M", time.localtime(ts))
                    print("tlss.heatmap:", device_info, from_t, readflag, ts)

                    hm = getHeatmap(conn, device_family=dev_family, from_t=from_t, to_t='now')
                    if hm:
                        updateHeatmap(device_info, hm)

    send_tlss_command(conn, "done\0")
    log.info("%s, elaspe time : %d sec" %(device_info,  int(time.time()-start_timestamp)))

    conn.close()

class thTLSS(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self, name='tlss')
        self.running = True
        self.daemon = True
        self.i = 0
        self.last = 0
        
    def run(self):
        str_s = "=====  Starting TLSS Counting  ====="
        print (str_s)
        log.info (str_s)
        try:
            self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        except socket.error as  msg:
            log.critical("Could not create socket. Error Code: {0}, Error: {1}".format(str(msg[0], msg[1])))
            # sys.exit(0)
            return False
        log.info("[-] Socket Created(TLSS)")

        try:
            self.s.bind((configVars('software.service.tlss.host'), int(configVars('software.service.tlss.port'))))
            log.info("[-] Socket Bound to port {0}".format(str(configVars('software.service.tlss.port'))))
        
        except socket.error as msg:
            log.critical("TLSS, Bind Failed. Error: {0}".format(str(msg)))
            print ("TLSS: Bind Failed. Error: {0}".format(str(msg)))
            self.s.close()
            # sys.exit()
            return False

        self.s.listen(30) 
        print("TLSS Engine: Listening...") 

        while self.running:
            self.conn, self.addr = self.s.accept()
            # log.info("TLSS: %s:%s connected, %d" %(self.addr[0], str(self.addr[1]), self.i))
            print ("TLSS: %s:%s connected, %d" %(self.addr[0], str(self.addr[1]), self.i))
            modifyConfig('software.status.last_device_access', int(time.time()))
            self.t0 = threading.Thread(target=tlss_client_thread, args=(self.conn, ))
            self.t0.start()
            
            self.i += 1
            self.last = int(time.time())

        self.s.close()
        str_s = "Stopping TLSS Counting"
        print(str_s)
        log.info (str_s)

    def stop(self):
        self.running = False

if __name__ == '__main__':
    # testGetFunctions()
    tc = thTLSS()
    tc.start()
    while True:
        time.sleep(300)
