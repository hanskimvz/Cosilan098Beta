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

import time, sys, os
import threading
import socket

from functions_s import (recv_timeout, configVars, addSlashes, log,  modifyConfig, info_to_db, message)
from parse_functions import  parseEventData
from db_functions import updateEventCount, updateFaceThumnmail, updateEventSnapshot

def getEventPush(conn):
    data= recv_timeout(conn)
    # print(data)
    conn.close()
    rs = parseEventData(data)
    return rs

def event_thread(conn):
    arr_event = getEventPush(conn)
    if not arr_event:
        return False
    for ev in arr_event:
        # print (ev)
        # if ev['type'] == 'counting':
        if ev['type'] == 'counting' or (ev['type'] == 'vca' and ev['ct_name']):
            updateEventCount(ev)
        elif ev['type'] == 'face':
            updateFaceThumnmail(ev)
        elif ev['type'] == 'snapshot':
            updateEventSnapshot(ev)

def test():
    port= 5031
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('', port))
    s.listen(1) 

    for i in range(10):
        conn, addr = s.accept()
        msg = getEventPush(conn)
        print(msg)
        print ()

    s.close()  

class  thEventPush(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self, name='event')
        self.daemon= True
        self.running= True
        self.last = int(time.time())

    def run(self):
        str_s = "Stating Event Push processing"
        message(str_s)
        log.info (str_s)
        try:
            self.s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        except socket.error as  msg:
            log.critical("Could not create socket. Error Code: {0}, Error: {1}".format(str(msg[0], msg[1])))
            # sys.exit(0)
            return False
        log.info("[-] Socket Created(EVENT PUSH Service)")

        try:
            self.s.bind((configVars('software.service.event.host'), int(configVars('software.service.event.port'))))
            log.info("[-] Socket Bound to port {0}".format(str(configVars('software.service.event.port'))))
        
        except socket.error as msg:
            log.critical("EventCounting, Bind Failed. Error: {0}".format(str(msg)))
            message ("EventCounting: Bind Failed. Error: {0}".format(str(msg)))
            self.s.close()
            # sys.exit()
            return False

        self.s.listen(30) 
        message("EVENT Engine: Listening...") 

        while self.running:
            self.conn, self.addr = self.s.accept()
            message ("EVENT PUSH: %s:%s connected" %(self.addr[0], str(self.addr[1])))
            modifyConfig('software.status.last_device_access', int(time.time()))
            self.t0 = threading.Thread(target=event_thread, args=(self.conn, ))
            self.t0.start()
            self.last = int(time.time())

        self.s.close()
        str_s = "Stopping Event Service"
        message(str_s)
        log.info (str_s)

    def stop(self):
        self.running = False


if __name__ == '__main__':
    # test()
    te = thEventPush()
    te.start()
    while True:
        print (te, te.is_alive())
        if not te.is_alive():
            te = thEventPush()
            te.start()
        time.sleep(30)