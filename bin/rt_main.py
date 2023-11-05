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
import re, json, base64
import pymysql
# from tkinter import *
# from tkinter import ttk
# from tkinter import filedialog
import cv2 as cv
import numpy as np
from PIL import ImageTk, Image
import threading
import locale
import uuid


cwd = os.path.abspath(os.path.dirname(sys.argv[0]))
os.chdir(cwd)

TZ_OFFSET = 3600*8
ARR_CRPT = dict()

Running = True

def getMac():
	mac = "%012X" %(uuid.getnode())
	return mac

def dbconMaster(host='', user='', password='',   charset = 'utf8', port=0): #Mysql
    global ARR_CONFIG
    if not host:
        host=ARR_CONFIG['mysql']['host']
    if not user :
        user = ARR_CONFIG['mysql']['user']
    if not password:
        password = ARR_CONFIG['mysql']['password']
    if not port:
        port = int(ARR_CONFIG['mysql']['port'])
        

    try:
        dbcon = pymysql.connect(host=host, user=str(user), password=str(password), charset=charset, port=port)
    except pymysql.err.OperationalError as e :
        print (str(e))
        return None
    return dbcon   

def dateTss(tss):
    # tm_year=2021, tm_mon=3, tm_mday=22, tm_hour=21, tm_min=0, tm_sec=0, tm_wday=0, tm_yday=81, tm_isdst=-1
    arr = {
        "year"  : int(tss.tm_year),
        "month" : int(tss.tm_mon), 
        "day" : int(tss.tm_mday),
        "hour" : int(tss.tm_hour),
        "min" : int(tss.tm_min),
        "wday" : int((tss.tm_wday+1)%7),
        "week" : int(time.strftime("%U", tss)),
    }
    return arr

    
def getSquare(cursor):
    sq = "select * from %s.square " %(ARR_CONFIG['mysql']['db'])
    cursor.execute(sq)
    return cursor.fetchall()

def getStore(cursor):
    sq = "select * from %s.store " %(ARR_CONFIG['mysql']['db'])
    cursor.execute(sq)
    return cursor.fetchall()

def getCamera(cursor):
    sq = "select * from %s.camera " %(ARR_CONFIG['mysql']['db'])
    cursor.execute(sq)
    return cursor.fetchall()

def getCounterLabel(cursor):
    sq = "select * from %s.counter_label " %(ARR_CONFIG['mysql']['db'])
    cursor.execute(sq)
    return cursor.fetchall()

def getDevices(cursor, device_info=''):
    sq = "select pk, device_info, usn, product_id, lic_pro, lic_surv, lic_count, face_det, heatmap, countrpt, macsniff, write_cgi_cmd, initial_access, last_access, db_name, url, method, user_id, user_pw from common.params "
    if device_info:
        sq += " where device_info='%s'" %device_info
    else :
        sq += " where db_name='%s'" %(ARR_CONFIG['mysql']['db'])
    cursor.execute(sq)
    return cursor.fetchall()

def getSnapshot(cursor, device_info):
    sq = "select body from common.snapshot where device_info='%s' order by regdate desc limit 1" %(device_info)
    cursor.execute(sq)
    body = cursor.fetchone()

    if body:
        return body[0]
    return False

def getWorkingHour(cursor):
    arr_sq = list()
    sq_work = ""
    sq = "select code, open_hour, close_hour, apply_open_hour from %s.store " %(ARR_CONFIG['mysql']['db'])
    # print (sq)
    cursor.execute(sq)
    for row in cursor.fetchall():
        # print(db_name, row)
        if row[3]=='y' and  row[1] < row[2] :
            arr_sq.append("(store_code='%s' and hour>=%d and hour < %d)" %(row[0], row[1], row[2]) )
        else :
            arr_sq.append("(store_code='%s')" %row[0])
    
    if arr_sq:
        sq_work = ' or '.join(arr_sq)
        sq_work = "and (%s)" %sq_work
    return sq_work


def loadConfig(filename = "rtScreen.json"):
    lang = dict()
    with open (filename, 'r', encoding='utf8')  as f:
        body = f.read()
    arr = json.loads(body)

    LOCALE = locale.getdefaultlocale()
    if LOCALE[0] == 'zh_CN':
        selected_language = 'Chinese'
    elif LOCALE[0] == 'ko_KR':
        selected_language = 'Korean'
    else :
        selected_language = 'English'

    for s in arr['language']:
        lang[s['key']] = s[selected_language]

    arr['language'] = lang

    if not arr['refresh_interval'] :
        arr['refresh_interval'] = 2

    if not arr['full_screen']:
        arr['full_screen'] = "no"
    return arr

def saveConfig(filename="rtScreen.json", arr=[]):
    if not arr:
        arr = ARR_CONFIG
    with open (filename, 'r', encoding='utf8')  as f:
        body = f.read()
    arr_t = json.loads(body)
    arr["language"] = arr_t["language"]

    json_str = json.dumps(arr, ensure_ascii=False, indent=4)
    with open (filename, 'w', encoding="utf-8") as f:
        f.write(json_str)

def loadTemplate(template_file=""):
    with open ("%s\\%s" %(cwd, template_file), 'r', encoding="utf-8") as f:
        body = f.read()
    arr = json.loads(body)
    for i, arr_s in enumerate(arr):
        if (arr_s.get("category")):
            arr[i]["role"] = arr_s.get("category") 
            del(arr_s["category"])
        elif arr_s.get("name") and not arr_s.get('role'):
            if arr_s.get("name").startswith("label"):
                arr[i]["role"] = "label"
            elif arr_s.get("name").startswith("title"):
                arr[i]["role"] = "label"
            elif arr_s.get("name").startswith("number"):
                arr[i]["role"] = "number"
            elif arr_s.get("name").startswith("picture"):
                arr[i]["role"] = "picture"
            elif arr_s.get("name").startswith("snapshot"):
                arr[i]["role"] = "snapshot"
            elif arr_s.get("name").startswith("video"):
                arr[i]["role"] = "video"

        if arr_s.get("role") == "variable":
            pass

        elif not arr_s.get("align"):
            arr[i]["align"] = "center"

        if arr_s.get("role") == "number":
            if not arr_s.get("rule"):
                arr[i]["rule"] = ""

    return arr

def saveTemplate(template_file, arr):
    arr_rs = list()
    for r in arr:
        arr_rs.append(json.dumps(r, ensure_ascii=False))
    json_str = "[\n" + (",\n".join(arr_rs)) + "\n]"


    with open ("%s\\%s" %(cwd, template_file), 'w', encoding="utf-8") as f:
        f.write(json_str)




def getVariableNames():
    arr_screen = loadTemplate(ARR_CONFIG['template'])
    # regex= re.compile(r"(\w+:\w+\s*)", re.IGNORECASE)
    regex= re.compile(r"(\w+:[\w+\=\&\-]+:\w+)", re.IGNORECASE)

    vars = set()
    for scrn in arr_screen:
        if scrn['role'] != 'number':
            continue
        if scrn['flag'] == 'n':
            continue
        scrn['rule'] = scrn['rule'].replace("\n","")
        for m in regex.findall(scrn['rule']):
            vars.add(m.strip())
            # print (m)
        # ex = re.split('[-|+|*|/|%]', scrn['rule'])
        # for x in ex:
        #     vars.add(x.strip())
    
    for v in vars:
        ARR_CRPT[v] = 0
    for n in ARR_CONFIG['constant']:
        # print (n)
        if n.get('flag') != 'n':
            ARR_CRPT[n['name']] = n['value']


    # for v in ARR_CRPT:
    #     print (v, ARR_CRPT[v])



def getSqls():
    sql_ref = {
        "today" : "year = year(curdate()) and month=month(curdate()) and day= dayofmonth(curdate())",
        "yesterday": "year = year(date_sub(curdate(), interval 1 day)) and month=month(date_sub(curdate(), interval 1 day)) and day = dayofmonth(date_sub(curdate(), interval 1 day))",
        "thismonth": "year = year(curdate()) and month=month(curdate())",
        "last_month": "year = year(curdate()) and month=month(date_sub(curdate(), interval 1 month))",
        "thisyear": "year = year(curdate())",
        "lastyear": "year = year(date_sub(curdate(), interval 1 month))"
    }
    arr = dict()
    sqls = list()

    for v in ARR_CRPT:
        e = v.split(":")
        if len(e) <3:
            continue
        
        if not e[0] in arr:
            arr[e[0]] = {"device":set(), "ct_label": set()}
        
        arr[e[0]]['device'].add(e[1])    
        arr[e[0]]['ct_label'].add(e[2])

    for date_ref in arr:
        # print (date_ref, arr[date_ref])

        dev = list()
        label = list()
        arr_w  = list()

        if sql_ref.get(date_ref):
            arr_w.append(sql_ref[date_ref])

        for s in arr[date_ref]['ct_label']:
            label.append("counter_label='%s'" %s)

        arr_w.append("(" + (" or ".join(label)) + ")")

        for s in arr[date_ref]['device']:
            if s == 'all':
                sqls.append("select '" + date_ref +"', 'all', counter_label, sum(counter_val) as value from " + ARR_CONFIG['mysql']['db'] + ".count_tenmin where " + (" and ".join(arr_w)) + " group by counter_label")
            else:
                dev.append("device_info='%s'" %s)
        if dev:
            arr_w.append("(" + (" or ".join(dev)) + ")")
            sqls.append("select '" + date_ref + "', device_info, counter_label, sum(counter_val) as value from " + ARR_CONFIG['mysql']['db'] + ".count_tenmin where " + (" and ".join(arr_w)) + " group by device_info, counter_label")

        # if dev:
        #     sql_sel += ', device_info'
        #     arr_w.append("(" + (" or ".join(dev)) + ")")

        # whr_dev   = " or ".join(dev)        

        # if whr_dev:
        #     arr_w.append("(" + whr_dev + ")")
        #     sql_sel += ', device_info'
        # # else :
        # #     sql_sel += ", 'all'"

            

        # # sql_sel += ', counter_label, sum(counter_val) as value'
        # sqls.append("select " + sql_sel + " from " + ARR_CONFIG['mysql']['db'] + ".count_tenmin where " + (" and ".join(arr_w)) + " group by " + (", ".join(grp))  ) 

    # for sql in sqls:
    #     print (sql)
    # print()
    return sqls


def getRtCounting(cursor, arr_latest):
    arr_t = dict()
    ct_mask =  list()
    if not arr_latest:
        return False
    for lt in arr_latest:
        # print (lt)
        ct_mask.append("(device_info = '%s' and timestamp > %d)" %(lt['device_info'], lt['ts'] - TZ_OFFSET))

    if (ct_mask) :
        sq_s = ' or '.join(ct_mask)
    
    sq = "select device_info, counter_label, counter_val,  counter_name, timestamp, regdate from common.counting_event where db_name='%s' and (%s)  order by timestamp asc " %(ARR_CONFIG['mysql']['db'], sq_s) 
    # print (sq)
    cursor.execute(sq)
    for row in cursor.fetchall():
        # print (row)
        if not row[0] in arr_t:
            arr_t[row[0]] = dict()
        if not row[1] in arr_t[row[0]]:
            arr_t[row[0]][row[1]] = list()

        arr_t[row[0]][row[1]].append(row[2])

    diff = dict()
    diff['all'] = dict()
    for dev in arr_t:
        # print (dev)
        diff[dev] = dict()
        for label in arr_t[dev]:
            # print (label)
            if not diff['all'].get(label):
                diff['all'][label] = 0
            diff[dev][label] = max(arr_t[dev][label]) - min(arr_t[dev][label])
            diff['all'][label] += diff[dev].get(label)

    # print (diff)
    return diff

def getRptCounting(cursor):
    arr_crpt = ARR_CRPT
    sqls = getSqls()

    sq = "select device_info, year, month, day, hour, min, max(timestamp) as latest_ts from %s.count_tenmin where year = year(curdate()) and month=month(curdate()) and day= dayofmonth(curdate()) group by device_info" %( ARR_CONFIG['mysql']['db'])
    # print (sq)
    cursor.execute(sq)
    latest = list()
    for row in cursor.fetchall():
        latest.append({"device_info": row[0], "year":row[1], "month":row[2], "day": row[3], "hour":row[4], "min":row[5], "ts":row[6]})

    for sq in sqls:
        # print (sq)
        cursor.execute(sq)
        # columns = cursor.description
        for row in cursor.fetchall():
            # print(row)
            arr_crpt[ row[0] + ':' + row[1] + ':' + row[2]] = row[3]

    return arr_crpt, latest
 

def getData():
    t = time.time()
    dbcon = dbconMaster()
    with dbcon:
        cursor = dbcon.cursor()
        arr_crpt, latest = getRptCounting(cursor)
        print (arr_crpt)
        diff = getRtCounting(cursor, latest)
        print ("diff", diff)
        for exp in ARR_CRPT:
            e = exp.split(":")
            if len(e) <3:
                continue
            key = e[0] + ':' + e[1] + ':' + e[2]
            ARR_CRPT[key] = arr_crpt[key]
            if e[0] in ['today', 'thisweek', 'thismonth', 'thisyear']: 
                if diff.get(e[1]) and diff[e[1]].get(e[2]):
                    ARR_CRPT[key] = arr_crpt[key] + diff[e[1]][e[2]]
            # else :
            #     ARR_CRPT[key] = arr_crpt[key]

    # print (time.time()-t)

ARR_CONFIG = loadConfig()
getVariableNames()

if __name__ == '__main__':
    
    # for sq in getSqls():
    #     print (sq)
    #     print ()
    getData()
    print (ARR_CRPT)


    # rule = "limit_BB - today:mac=001323A0072F&brand=TSD&model=TSDC32P-12V:Likangcun_IN+today:mac=001323A0072F&brand=TSD&model=TSDC32P-12V:Likangcun_OUT"

    # vars = list()
    # oper = list()
    # repl = dict()
    # regex= re.compile(r"(\w+:[\w+\=\&\-]+:\w+)", re.IGNORECASE)
    # rule = rule.replace("\n","")
    # rule = rule.replace(" ","")
    # for i, m in enumerate(regex.findall(rule)):
    #     repl["_variables_%d_" %i] = m
    #     rule = rule.replace(m, "_variables_%d_" %i )

    # print (rule)    

    # ex = re.split('[-|+|*|/|%]', rule)
    # regex_oper = re.compile('[-|+|*|/|%]', re.IGNORECASE)

    # for x in ex:
    #     if repl.get(x):
    #         vars.append(repl[x])
    #     else :
    #         vars.append(x)
    
    # for m in regex_oper.finditer(rule):
    #     oper.append(m.group())

    # print (vars) 
    # print (oper)

    pass


# def parseRule(ss):
#     global limit_number
#     regex= re.compile(r"(\w+\s*:\s*\w+)", re.IGNORECASE)
#     calc_regex= re.compile(r"(\w+)\(", re.IGNORECASE)
#     m = calc_regex.search(ss)
#     calc = m.group(1) if m else 'sum'

#     if calc == 'number':
#         limit_number = re.sub(r'[^0-9]', '', ss)
#         return (calc, limit_number)

#     if not calc in ['sum', 'diff', 'div', 'percent', 'number', 'margin']:
#         return False
#     arr = list()
#     for m in regex.finditer(ss):
#         dt, ct = m.group().split(":")
#         arr.append((dt.strip(), ct.strip()))
#     if not arr:
#         return False
#     return (calc, arr)


# def getRptCounting(cursor): # counting db
#     arr_crpt = dict()
#     filename = "rtRule.json"
#     with open(filename, "r", encoding="utf-8") as f:
#         body = f.read()

#     arr = json.loads(body)

#     for r in arr:
#         if r.get('flag') != 'y':
#             continue
#         # print (r['sql'])
#         cursor.execute(r['sql'])
#         # columns = cursor.description
#         for row in cursor.fetchall():
#             st = r['name']
#             for i, x in enumerate(row):
#                 if i < len(row)-1:
#                     st += ":" + str(x)
#             arr_crpt[st] = row[i]

#     sq = "select device_info, year, month, day, hour, min, max(timestamp) as latest_ts from %s.count_tenmin where year = year(curdate()) and month=month(curdate()) and day= dayofmonth(curdate()) group by device_info" %( ARR_CONFIG['mysql']['db'])
#     # print (sq)
#     cursor.execute(sq)
#     arr_crpt['latest'] = list()
#     for row in cursor.fetchall():
#         arr_crpt['latest'].append({"device_info": row[0], "year":row[1], "month":row[2], "day": row[3], "hour":row[4], "min":row[5], "ts":row[6]})

#     return arr_crpt






# class getDataThread(threading.Thread):
#     def __init__(self):
#         threading.Thread.__init__(self)
#         self.delay = ARR_CONFIG['refresh_interval']
#         self.Running = True
#         self.exFlag = False
#         self.last = 0
#         self.i = 0

#     def run(self):
#         self.dbcon = dbconMaster()
#         while self.Running :
#             if self.i == 0 :
#                 self.cur = self.dbcon.cursor()
#                 if int(time.time())-self.last > 300:
#                 # if (int(time.time())%300) < 2: #every 5minute
#                     try:
#                         getRptCounting(self.cur)
#                         self.last = int(time.time())
#                     except Exception as e:
#                         print (e)
#                         time.sleep(5)
#                         self.dbcon = dbconMaster()
#                         print ("Reconnected")
#                         continue
                
#                 changeSnapshot(self.cur)
#                 try :
#                     arrn = getNumberData(self.cur)
#                     self.dbcon.commit()
#                 except pymysql.err.OperationalError as e:
#                     print (e)
#                     time.sleep(5)
#                     self.dbcon = dbconMaster()
#                     print ("Reconnected")
#                     continue

#                 # print(arrn)
#                 changeNumbers(arrn)
            
#             self.i += 1
#             if self.i > self.delay:
#                 self.i = 0
#             # print (self.i)
#             time.sleep(1)

#         self.cur.close()
#         self.dbcon.close()
#         self.exFlag = True
                
#     def stop(self):
#         self.Running = False    









# class getDataThread(threading.Thread):
#     def __init__(self):
#         threading.Thread.__init__(self)
#         self.delay = ARR_CONFIG['refresh_interval']
#         self.Running = True
#         self.exFlag = False
#         self.last = 0
#         self.i = 0

#     def run(self):
#         self.dbcon = dbconMaster()
#         while self.Running :
#             if self.i == 0 :
#                 self.cur = self.dbcon.cursor()
#                 if int(time.time())-self.last > 300:
#                 # if (int(time.time())%300) < 2: #every 5minute
#                     try:
#                         getRptCounting(self.cur)
#                         self.last = int(time.time())
#                     except Exception as e:
#                         print (e)
#                         time.sleep(5)
#                         self.dbcon = dbconMaster()
#                         print ("Reconnected")
#                         continue
                
#                 changeSnapshot(self.cur)
#                 try :
#                     arrn = getNumberData(self.cur)
#                     self.dbcon.commit()
#                 except pymysql.err.OperationalError as e:
#                     print (e)
#                     time.sleep(5)
#                     self.dbcon = dbconMaster()
#                     print ("Reconnected")
#                     continue

#                 # print(arrn)
#                 changeNumbers(arrn)
            
#             self.i += 1
#             if self.i > self.delay:
#                 self.i = 0
#             # print (self.i)
#             time.sleep(1)

#         self.cur.close()
#         self.dbcon.close()
#         self.exFlag = True
                
#     def stop(self):
#         self.Running = False    





# def updateRptCounting(cursor):
#     global ARR_CRPT
#     ARR_CRPT = dict()
#     # sq_work = getWorkingHour(cursor)
#     # print("sqwork:", sq_work)
#     sq_work = ""
    
#     ts_now = int(time.time() + TZ_OFFSET)
#     tsm = time.gmtime(ts_now)
#     arr_ref = [
#         {
#             "ref_date": 'today',
#             "start_ts" : int(ts_now //(3600*24)) * 3600*24,
#             "end_ts" : int(time.time() + TZ_OFFSET),
#         },
#         {
#             "ref_date" : 'yesterday',
#             "start_ts" :  int(ts_now //(3600*24)) * 3600*24 - 3600*24,
#             "end_ts" : int(ts_now //(3600*24)) * 3600*24,
            
#         },
#         {
#             "ref_date" : 'thismonth',
#             "start_ts" : int(time.mktime((tsm.tm_year, tsm.tm_mon, 1, 0, 0, 0, 0, 0, 0)) + TZ_OFFSET),
#             "end_ts" : ts_now
#         },
#         {
#             "ref_date" : 'thisyear',
#             "start_ts" : int(time.mktime((tsm.tm_year, 1, 1, 0, 0, 0, 0, 0, 0)) + TZ_OFFSET),
#             "end_ts" : ts_now
#         }
#     ]
#     for arr in arr_ref:
#         # print(arr)
        
#         sq = "select device_info, counter_label, sum(counter_val) as sum, max(timestamp) as latest_ts from %s.count_tenmin where timestamp >= %d and timestamp < %d %s group by counter_label, device_info" %( ARR_CONFIG['mysql']['db'], arr['start_ts'], arr['end_ts'], sq_work)
#         # print(arr['ref_date'], sq)
#         cursor.execute(sq)
#         for row in cursor.fetchall():
#             # print (row)
#             if not arr['ref_date'] in ARR_CRPT:
#                 ARR_CRPT[arr['ref_date']] = dict()
#             if not row[0] in ARR_CRPT[arr['ref_date']]:
#                 ARR_CRPT[arr['ref_date']][row[0]] = dict()
#             if not row[1] in ARR_CRPT[arr['ref_date']][row[0]]:
#                 ARR_CRPT[arr['ref_date']][row[0]][row[1]] = dict()

#             ARR_CRPT[arr['ref_date']][row[0]][row[1]]['counter_val'] = row[2]
#             ARR_CRPT[arr['ref_date']][row[0]][row[1]]['latest'] = row[3]
#             ARR_CRPT[arr['ref_date']][row[0]][row[1]]['datetime'] = time.strftime("%Y-%m-%d %H:%M:%S", time.gmtime(row[3]))

#             if not 'all' in ARR_CRPT[arr['ref_date']]:
#                 ARR_CRPT[arr['ref_date']]['all'] = dict()
#             if not row[1] in ARR_CRPT[arr['ref_date']]['all']:
#                 ARR_CRPT[arr['ref_date']]['all'][row[1]] = {'counter_val':0, 'latest':0}


#             ARR_CRPT[arr['ref_date']]['all'][row[1]]['counter_val'] += row[2]
#             if (row[3] > ARR_CRPT[arr['ref_date']]['all'][row[1]]['latest']):
#                 ARR_CRPT[arr['ref_date']]['all'][row[1]]['latest'] = row[3]
#                 ARR_CRPT[arr['ref_date']]['all'][row[1]]['datetime'] = time.strftime("%Y-%m-%d %H:%M:%S", time.gmtime(row[3]))

#     for x in ARR_CRPT:
#         for y in ARR_CRPT[x]:
#             print (x, y, ARR_CRPT[x][y])
    








# def getNumberData(cursor):
#     global ARR_CRPT, ARR_SCREEN, limit_number
#     arr_number = list()
   
#     for n in ARR_SCREEN:
#         if n['name'].startswith('number'):
#             exp = parseRule(n['rule'])
#             if not (exp):
#                 continue
#             calc, rule = exp
#             arr_number.append({
#                 "name": n['name'],
#                 "device_info": n['device_info'],
#                 "calc": calc,
#                 "rule": rule,
#                 "text": 0,
#                 "flag": n['flag']
#             })
#     arr_rt = getRtCounting(cursor)
#     for i, arr in enumerate(arr_number):
#         if arr['flag'] == 'n':
#             continue
#         if arr['calc'] == 'number':
#             arr_number[i]['text'] = arr_number[i]['rule']
#             continue
        
#         if arr.get('device_info'):
#             dev_info = arr['device_info']
#         else :
#             arr_number[i]['text'] = 0
#             continue
#         num=0
#         n = 0
#         for j, (dt, ct) in enumerate(arr['rule']):
#             if ARR_CRPT.get(dt) and ARR_CRPT[dt].get(dev_info) and ARR_CRPT[dt][dev_info].get(ct):
#                 n = ARR_CRPT[dt][dev_info][ct]['counter_val']
#             else :
#                 print ("Error on rpt >> dt:", dt, "dev_info:", dev_info, "ct:", ct)

#             if dt != 'yesterday' :
#                 if arr_rt :
#                     if arr_rt.get(dev_info) and arr_rt[dev_info].get(ct):
#                         n += arr_rt[dev_info][ct]['diff']
#                     else :
#                         print ("Error on rt >> dev_info:", dev_info, "ct:", ct)
#                 else:
#                     print ("Error on rt >> arr_rt is null")
#             if j == 0:
#                 num = n
            
#             elif arr['calc'] == 'sum':
#                 num += n
            
#             elif arr['calc'] == 'diff':
#                 num -= n
#             elif arr['calc'] == 'margin':
#                 num -= n                
                    
#         if arr['calc'] == 'div' or arr['calc'] == 'percent' and n:
#             num = "%3.2f %%"  %(num/n *100) if  arr['calc'] == 'percent' else "%3.2f"  %(num/n)
#         elif arr['calc'] == 'margin':
#             num = int(limit_number) - int(num)
#         arr_number[i]['text'] = num

#     for n in arr_number:
#         print (n)
    
#     return arr_number  

# def changeNumbers(arr):
#     for rs in arr:
#         if var.get(rs['name']):
#             var[rs['name']].set(rs.get('text'))


# def changeSnapshot(cursor):
#     global ARR_SCREEN, menus
#     for rs in ARR_SCREEN:
#         name = rs.get('name')
#         w, h = int(rs['size'][0]), int(rs['size'][1]) if rs.get('size') else (0, 0)
#         if name.startswith('snapshot'):
#             imgb64 = getSnapshot(cursor, rs.get('device_info'))
#             if imgb64:
#                 imgb64 = imgb64.decode().split("jpg;base64,")[1]
#                 body = base64.b64decode(imgb64)
#                 imgarr = np.asarray(bytearray(body), dtype=np.uint8)
#                 img = cv.imdecode(imgarr, cv.IMREAD_COLOR)
#             else :
#                 img = cv.imread("./cam.jpg")
#             img = Image.fromarray(img)
#             img = img.resize((w, h), Image.LANCZOS)
#             imgtk = ImageTk.PhotoImage(image=img)
#             # menus[name].create_image(0, 0, anchor="nw", image=imgtk)
#             menus[name].configure(image=imgtk)
#             menus[name].photo=imgtk # phtoimage bug
#             # imgPathOld[name] = imgPath

# class playVideo():
#     def __init__(self, label_n, cap):
#         self.cap = cap
#         self.interval = 10 
#         self.label= label_n
#         self.w = 640
#         self.h = 320
#     def run(self):
#         self.update_image()

#     def update_image(self):    
#         # Get the latest frame and convert image format
#         self.OGimage = cv.cvtColor(self.cap.read()[1], cv.COLOR_BGR2RGB) # to RGB
#         self.OGimage = Image.fromarray(self.OGimage) # to PIL format
#         self.image = self.OGimage.resize((self.w, self.h), Image.ANTIALIAS)
#         self.image = ImageTk.PhotoImage(self.image) # to ImageTk format
#         # Update image
#         self.label.configure(image=self.image)
#         # Repeat every 'interval' ms
#         self.label.after(self.interval, self.update_image)

# class showPicture(threading.Thread):
#     def __init__(self):
#         threading.Thread.__init__(self)
#         self.delay = ARR_CONFIG['refresh_interval']
#         self.Running = True
#         self.exFlag = False
#         self.i = 0

#     def run(self):
#         imgPathOld =  dict()
#         thx = dict()
#         cap=None
#         while self.Running :
#             if self.i == 0:
#                 for rs in ARR_SCREEN:
#                     name  = rs.get('name')
#                     if rs.get('flag')=='n':
#                         continue
#                     if not name in menus:
#                         menus[name] = Label(root, borderwidth=0)
#                         # menus[name] = Canvas(root)

#                     if name.startswith('picture') :
#                         imgPath = rs.get('url')
#                         w, h = rs.get('size')
#                         if not imgPath :
#                             continue
#                         print (imgPath)
#                         img = cv.imread(imgPath)
#                         # img = cv.resize(img, (int(w), int(h)))
#                         img = Image.fromarray(img)
#                         img = img.resize((int(w), int(h)), Image.LANCZOS)
#                         imgtk = ImageTk.PhotoImage(image=img)
#                         # menus[name].create_image(0, 0, anchor="nw", image=imgtk)
#                         menus[name].configure(image=imgtk)
#                         menus[name].photo=imgtk # phtoimage bug
#                         menus[name].configure(width=int(w), height=int(h))
#                         menus[name].place(x=int(rs['position'][0]), y=int(rs['position'][1]))
#                         imgPathOld[name] = imgPath
                    
#                     elif name.startswith('video'):
                       
#                         imgPath = rs.get('url')
#                         w, h = rs.get('size')
#                         if not imgPath:
#                             continue
#                         print (imgPath)
#                         if imgPathOld.get(name) != imgPath:
#                             if cap:
#                                 cap.release()
#                             cap = cv.VideoCapture(imgPath)
#                             thx[name] = playVideo(menus[name], cap)
#                             thx[name].run()
#                             print ("cap init")
#                             imgPathOld[name] = imgPath
#                         menus[name].configure(width=int(w), height=int(h))
#                         thx[name].w = int(w)
#                         thx[name].h = int(h)
#                         menus[name].place(x=int(rs['position'][0]), y=int(rs['position'][1]))
                            
                            
                        
#                         if self.Running == False:
#                             cap.release()
#                             cv.destroyAllWindows()
#                             break
                    
#             self.i += 1
#             if self.i > self.delay:
#                 self.i = 0
#             # print (self.i)
#             time.sleep(1)
#         # if cap:
#         #     cap.release()
#         self.exFlag = True       

#     def stop(self):
#         self.Running = False

# class procScreen(threading.Thread):
#     def __init__(self):
#         threading.Thread.__init__(self)
#         self.delay = ARR_CONFIG['refresh_interval']
#         self.Running = True
#         self.exFlag = False
#         self.i = 0

#     def run(self):
#         while self.Running :
#             if self.i == 0 :
#                 getScreenData()
#                 putSections()

#             self.i += 1
#             if self.i > self.delay:
#                 self.i = 0
#             # print (self.i)
#             time.sleep(1)
#         self.exFlag = True
                
#     def stop(self):
#         self.Running = False

# class getDataThread(threading.Thread):
#     def __init__(self):
#         threading.Thread.__init__(self)
#         self.delay = ARR_CONFIG['refresh_interval']
#         self.Running = True
#         self.exFlag = False
#         self.last = 0
#         self.i = 0

#     def run(self):
#         self.dbcon = dbconMaster()
#         while self.Running :
#             if self.i == 0 :
#                 self.cur = self.dbcon.cursor()
#                 if int(time.time())-self.last > 300:
#                 # if (int(time.time())%300) < 2: #every 5minute
#                     try:
#                         updateRptCounting(self.cur)
#                         self.last = int(time.time())
#                     except Exception as e:
#                         print (e)
#                         time.sleep(5)
#                         self.dbcon = dbconMaster()
#                         print ("Reconnected")
#                         continue
                
#                 changeSnapshot(self.cur)
#                 try :
#                     arrn = getNumberData(self.cur)
#                     self.dbcon.commit()
#                 except pymysql.err.OperationalError as e:
#                     print (e)
#                     time.sleep(5)
#                     self.dbcon = dbconMaster()
#                     print ("Reconnected")
#                     continue

#                 # print(arrn)
#                 changeNumbers(arrn)
            
#             self.i += 1
#             if self.i > self.delay:
#                 self.i = 0
#             # print (self.i)
#             time.sleep(1)

#         self.cur.close()
#         self.dbcon.close()
#         self.exFlag = True
                
#     def stop(self):
#         self.Running = False
# def forgetLabel(label):
#     global menus
#     menus[label].place_forget()



# def updateVariables(_root=None, _menus=None, _var=None, _lang=None, _editmode=None, _selLabel=None):
#     global root, menus, var, lang, editmode, selLabel
#     if _root !=None:
#         root = _root
#     if _menus != None:
#         menus = _menus
#     if _var != None:
#         var = _var
#     if _lang != None:
#         lang= _lang
#     if _editmode != None:
#         editmode = _editmode
#     if _selLabel != None:
#         selLabel = _selLabel
# loadConfig()

# def getCRPT():
#     return ARR_CRPT

# def getSCREEN():
#     return ARR_SCREEN

# def getCONFIG():
#     loadConfig()
#     return ARR_CONFIG


# getScreenData()
# for x in ARR_CONFIG:
#     print (x)
