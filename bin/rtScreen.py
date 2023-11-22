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

import time, os, sys
import re, json
from tkinter import *
from tkinter import ttk
from tkinter import filedialog
import cv2 as cv
import numpy as np
from PIL import ImageTk, Image
import pymysql
import threading



# from rt_main import var, menus, lang, cwd, ARR_SCREEN, ARR_CONFIG, getSCREEN, getCRPT, dbconMaster, parseRule, procScreen, getDataThread, updateVariables
from rt_main import ARR_CONFIG, ARR_CRPT, dbconMaster, loadTemplate, getRptCounting, getRtCounting, is_online
from rt_edit import ARR_SCREEN, root, menus, canvas, mainScreen, frame_option, edit_screen, fullScreen

# ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])

ths = None
thd = None
thv = None

Running = True

def exitProgram(event=None):
    global Running
    Running = False

    root.destroy()
    root.quit()
    print ("destroyed root")
    sys.stdout.flush()

    return False

def displayDatetime():
    global canvas
    for scrn in ARR_SCREEN:
        if scrn['flag'] == 'n':
            continue
        if scrn['role'] != 'datetime':
            continue

        fmt = scrn.get('format')
        if not fmt:
            continue

        dow = ["星期日","星期一","星期二","星期三","星期四","星期五","星期六"]
        text = time.strftime(fmt)
        for i in range(0,7):
            text = text.replace("%dc" %i, dow[i])

        canvas.itemconfigure(menus[scrn['name']], text=text)
        canvas.after(200, displayDatetime)

def numberByRule(rule):
    vars = list()
    oper = list()
    repl = dict()
    
    rule = rule.replace("\n","")
    rule = rule.replace(" ","")

    regex= re.compile(r"(\w+:[\w+\=\&\-]+:\w+)", re.IGNORECASE)
    regex_oper = re.compile('[-|+|*|/|%]', re.IGNORECASE)

    for i, m in enumerate(regex.findall(rule)):
        repl["_variables_%d_" %i] = m
        rule = rule.replace(m, "_variables_%d_" %i )

    ex = re.split('[-|+|*|/|%]', rule)
    for x in ex:
        if repl.get(x):
            vars.append(repl[x])
        else :
            vars.append(x)
    
    for m in regex_oper.finditer(rule):
        oper.append(m.group())

    num = int(vars[0]) if vars[0].isnumeric() else ARR_CRPT[vars[0]]
    for i, o in enumerate(oper):
        # print (ARR_CRPT[vars[i+1]])
        n = int(vars[i+1]) if vars[i+1].isnumeric() else ARR_CRPT[vars[i+1]]
        if o == '+' :
            num += n
        elif o == '-' :
            num -= n
        elif o == '*' :
            num *= n
        elif o == '/':
            if n >0:
                num /= n

    return num

def changeNumbers():
    for scrn in ARR_SCREEN:
        if scrn.get('flag') == 'n':
            continue

        if scrn['role'] == 'number':
            num = numberByRule(scrn['rule'])
            if scrn.get('max') != None and num > scrn.get('max'):
                num = scrn['max']
            if scrn.get('min') != None and num < scrn.get('min'):
                num = scrn['min']

        elif scrn['role'] == 'percent':
            num = "%3.2f %%"  %(numberByRule(scrn['rule']) * 100)
        else :
            continue

        if menus.get(scrn['name']):
            canvas.itemconfigure(menus[scrn['name']], text=str(num))


class getDataThread(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)
        self.delay = ARR_CONFIG['refresh_interval']
        self.Running = True
        self.last = 0
        self.arr_crpt = dict()
        self.diff = dict()
        self.latest = 0
        self.dbcon = None
        self.cur=None
        self.daemon = True

    def run(self):
        self.dbcon = dbconMaster()
        while self.Running :
            self.cur = self.dbcon.cursor()
            if int(time.time())-self.last > 300:
            # if True:
                try:
                    print ("get rpt")
                    self.arr_crpt, self.latest = getRptCounting(self.cur)
                    self.last = int(time.time())
                except Exception as e:
                    print (e)
                    time.sleep(5)
                    self.dbcon = dbconMaster()
                    print ("Reconnected")
                    continue
            
            try :
                self.diff = getRtCounting(self.cur, self.latest)
                self.dbcon.commit()
            except pymysql.err.OperationalError as e:
                print (e)
                time.sleep(5)
                self.dbcon = dbconMaster()
                print ("Reconnected")
                continue

            self.cur.close()
            # self.diff['all'] = {'entrance':10, 'exit':10}
            print ("self", self.arr_crpt)
            for exp in ARR_CRPT:
                e = exp.split(":")
                if len(e) <3:
                    continue
                
                if self.arr_crpt.get(exp):
                    ARR_CRPT[exp] = self.arr_crpt[exp] 

                if e[0] in ['today', 'thisweek', 'thismonth', 'thisyear']:
                    if self.arr_crpt.get(exp) and self.diff and self.diff.get(e[1]) and self.diff[e[1]].get(e[2]):
                        ARR_CRPT[exp] = self.arr_crpt[exp] + self.diff[e[1]][e[2]]

            if canvas:          
                changeNumbers()


            # print ("self", self.arr_crpt)
            print ("tota", ARR_CRPT)
            # print ("diff", self.diff)
            print()
            time.sleep(self.delay)

        self.cur.close()
        self.dbcon.close()

    def stop(self):
        self.Running = False 


class thGetDataTimer():
    def __init__(self, t=20):
        self.name = "realtime_screen"
        self.t = ARR_CONFIG['refresh_interval']
        self.last = 0
        self.arr_crpt = dict()
        self.diff = dict()
        self.latest = 0
        self.dbcon = dbconMaster(host=ARR_CONFIG['mysql']['host'], user = ARR_CONFIG['mysql']['user'], password=ARR_CONFIG['mysql']['password'], port=int(ARR_CONFIG['mysql']['port']))
        self.cur=None
        self.daemon = True
        self.thread = threading.Timer(0, self.handle_function)

    def handle_function(self):
        self.main_function()
        # self.last = int(time.time())
        self.thread = threading.Timer(self.t, self.handle_function)
        self.thread.start()
    
    def main_function(self):
        ts = time.time()
        if not self.dbcon:
            self.dbcon = dbconMaster(host=ARR_CONFIG['mysql']['host'], user = ARR_CONFIG['mysql']['user'], password=ARR_CONFIG['mysql']['password'], port=int(ARR_CONFIG['mysql']['port']))
            self.last = 0
            print ("self.dbcon:", self.dbcon)
            if self.dbcon:
                print ("Reconnected")
            return False
        self.cur = self.dbcon.cursor()
        # print ("dbcon", self.dbcon, "cursor", self.cur)

        if int(time.time())-self.last > 300:
            try:
                print ("get rpt")
                self.arr_crpt, self.latest = getRptCounting(self.cur)
                self.last = int(time.time())
                # print (self.latest)
            except Exception as e:
                print (e)
                self.dbcon.close()
                self.dbcon = None
                time.sleep(5)
                # self.dbcon = dbconMaster(host=ARR_CONFIG['mysql']['host'], user = ARR_CONFIG['mysql']['user'], password=ARR_CONFIG['mysql']['password'], port=int(ARR_CONFIG['mysql']['port']))
                # self.last = 0
                # print ("Reconnected")
                return False
            
        try :
            self.diff = getRtCounting(self.cur, self.latest)
            self.dbcon.commit()
        except Exception as e:
            print (e)
            self.dbcon.close()
            self.dbcon = None
            time.sleep(5)
            # self.dbcon = dbconMaster(host=ARR_CONFIG['mysql']['host'], user = ARR_CONFIG['mysql']['user'], password=ARR_CONFIG['mysql']['password'], port=int(ARR_CONFIG['mysql']['port']))
            # print ("Reconnected")
            return False

        self.cur.close()
        # print ("self", self.arr_crpt)
        # print ("diff", self.diff)
        for exp in ARR_CRPT:
            e = exp.split(":")
            if len(e) <3:
                continue
            
            if self.arr_crpt.get(exp):
                ARR_CRPT[exp] = self.arr_crpt[exp] 

            if e[0] in ['today', 'thisweek', 'thismonth', 'thisyear']:
                if self.arr_crpt.get(exp) and self.diff and self.diff.get(e[1]) and self.diff[e[1]].get(e[2]):
                    ARR_CRPT[exp] = self.arr_crpt[exp] + self.diff[e[1]][e[2]]

        if canvas:          
            changeNumbers()
        print ("tota", ARR_CRPT)
        print ("elaspe time: %2.2f" %(time.time()-ts))
        print ()
   
    def start(self):
        str_s = "Starting processing realtime counting"
        print(str_s)
        # self.last = int(time.time())
        self.thread.start()

    def is_alive(self) :
        if int(time.time()) - self.last > 600:
            return False
        return True

    def cancel(self):
        self.dbcon.close()
        str_s = "Stopping realtime counting"
        print(str_s)
        self.thread.cancel()

    def stop(self):
        self.cancel()

if __name__ == '__main__':
    # root =Tk()
    screen_width = root.winfo_screenwidth()
    screen_height = root.winfo_screenheight()

    root.geometry("%dx%d+0+0" %((screen_width), (screen_height)))

    if not canvas:
        root.bind('<Double-Button-1>', edit_screen)
        root.wm_attributes("-transparentcolor", 'grey')
    root.bind('<Button-3>', frame_option)
    root.bind("<F11>", fullScreen)
    root.configure(background="black")
    

    root.protocol("WM_DELETE_WINDOW", exitProgram)

    mainScreen()
    displayDatetime()

    if ARR_CONFIG['full_screen'] == "yes":
        # root.overrideredirect(True)
        root.attributes("-fullscreen", True)
        root.resizable (False, False)
    else :
        root.resizable (True, True)


    # thd = getDataThread()
    thd = thGetDataTimer()
    thd.start()
    print ("thd alive", thd.is_alive())
   
    root.mainloop()

    thd.stop()
    # for i in range(100):
    #     if thd:
    #         thd.stop()
    #         thd.Running = False
    #         if not thd.is_alive():
    #             print ("thd alive", thd.is_alive())
    #             break
    #         time.sleep(0.5)

raise SystemExit()
sys.exit()
