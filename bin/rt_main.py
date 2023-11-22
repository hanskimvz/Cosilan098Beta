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
# import pymysql
# from tkinter import *
# from tkinter import ttk
# from tkinter import filedialog
# import cv2 as cv
# import numpy as np
# from PIL import ImageTk, Image
# import threading
import locale
import uuid


cwd = os.path.abspath(os.path.dirname(sys.argv[0]))
os.chdir(cwd)

# not server, only view realtime screen, should be set TZ_OFFSET, else import TZ_OFFSET from functions_s
from functions_s import TZ_OFFSET, is_online, dbconMaster

TZ_OFFSET = 3600*8
ARR_CRPT = dict()
LANG = dict()

Running = True

def getMac():
	mac = "%012X" %(uuid.getnode())
	return mac

# def dbconMaster(host='', user='', password='',   charset = 'utf8', port=0): #Mysql
#     global ARR_CONFIG
#     if not host:
#         host=ARR_CONFIG['mysql']['host']
#     if not user :
#         user = ARR_CONFIG['mysql']['user']
#     if not password:
#         password = ARR_CONFIG['mysql']['password']
#     if not port:
#         port = int(ARR_CONFIG['mysql']['port'])
        

#     try:
#         dbcon = pymysql.connect(host=host, user=str(user), password=str(password), charset=charset, port=port)
#     except pymysql.err.OperationalError as e :
#         print (str(e))
#         return None
#     return dbcon   

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


def loadLanguage(filename = "rtScreen.lang"):
    lang = dict()
    body = ""
    with open (filename, 'r', encoding='utf8')  as f:
        for l in f.read().splitlines():
            b = l.split("//")[0].strip()
            if not b:
                continue
            body += b
            
    # print (body)
    arr = json.loads('[' + body +']')
    LOCALE = locale.getdefaultlocale()
    if LOCALE[0] == 'zh_CN':
        selected_language = 'Chinese'
    elif LOCALE[0] == 'ko_KR':
        selected_language = 'Korean'
    else :
        selected_language = 'English'

    for s in arr:
        lang[s['key']] = s[selected_language]
    return lang

def loadConfig(filename = "rtScreen.json"):
    with open (filename, 'r', encoding='utf8')  as f:
        body = f.read()
    arr = json.loads(body)

    if not arr['refresh_interval'] :
        arr['refresh_interval'] = 2

    if not arr['full_screen']:
        arr['full_screen'] = "no"
    return arr

def saveConfig(filename="rtScreen.json", arr=[]):
    if not arr:
        arr = ARR_CONFIG
    # with open (filename, 'r', encoding='utf8')  as f:
    #     body = f.read()
    # arr_t = json.loads(body)
    # arr["language"] = arr_t["language"]

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

        if arr_s.get("role") == "number" or arr_s.get("role") == "number":
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
        if scrn['role'] != 'number' and scrn['role'] !='percent':
            continue
        if scrn['flag'] == 'n':
            continue
        scrn['rule'] = scrn['rule'].replace("\n","")
        scrn['rule'] = scrn['rule'].replace(" ","")
        
        repl=dict()
        for i, m in enumerate(regex.findall(scrn['rule'])):
            repl["_variables_%d_" %i] = m
            scrn['rule'] = scrn['rule'].replace(m, "_variables_%d_" %i )

        # print (scrn['rule'])
        ex = re.split('[-|+|*|/|%]', scrn['rule'])

        for x in ex:
            if repl.get(x):
                vars.add(repl[x].strip())
            else :
                vars.add(x.strip())
    

    for v in vars:
        ARR_CRPT[v] = 0
    for n in ARR_CONFIG['constant']:
        # print (n)
        if n.get('flag') != 'n':
            ARR_CRPT[n['name']] = n['value']

#     for v in ARR_CRPT:
#         print (v, ARR_CRPT[v])

  


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

    return sqls


def getRtCountingX(cursor, arr_latest):
    arr_t = dict()
    ct_mask =  list()
    if not arr_latest:
        return False
    for lt in arr_latest:
        ct_mask.append("(device_info = '%s' and timestamp >= %d)" %(lt['device_info'], ts))

    if (ct_mask) :
        sq_s = ' or '.join(ct_mask)
    
    sq = "select device_info, counter_label, counter_val,  counter_name, timestamp, regdate from common.counting_event where db_name='%s' and (%s)  order by timestamp asc " %(ARR_CONFIG['mysql']['db'], sq_s) 
    # print (sq)
    cursor.execute(sq)
    for row in cursor.fetchall():
        print (row)
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


def getRtCounting(cursor, arr_latest):
    arr_t = dict()
    ct_mask =  list()
    if not arr_latest:
        return False
    for lt in arr_latest:
        ct_mask.append("(device_info = '%s' and timestamp < %d)" %(lt['device_info'], lt['ts']-TZ_OFFSET))

    if (ct_mask) :
        sq_s = ' or '.join(ct_mask)

    ct_mask = list()
    sq = "select device_info, counter_label, max(timestamp) from common.counting_event where db_name='%s' and (%s) group by device_info, counter_label " %(ARR_CONFIG['mysql']['db'], sq_s) 
    # print (sq)
    cursor.execute(sq)
    for row in cursor.fetchall():
        # print (row)
        ct_mask.append("(device_info = '%s' and counter_label= '%s' and timestamp >= %d)" %(row[0], row[1], row[2]))

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
    arr_crpt = dict()
    # arr_crpt = ARR_CRPT
    sqls = getSqls()

    sq = "select device_info, max(timestamp) as latest_ts from %s.count_tenmin where year = year(curdate()) and month=month(curdate()) and day= dayofmonth(curdate()) group by device_info" %( ARR_CONFIG['mysql']['db'])
    # print (sq)
    cursor.execute(sq)
    latest = list()
    for row in cursor.fetchall():
        tss = dateTss(time.gmtime(int(row[1])))
        latest.append({"device_info": row[0], "year": tss['year'], "month":tss['month'], "day": tss['day'], "hour":tss['hour'], "min":tss['min'], "ts":row[1]})

    for sq in sqls:
        # print (sq)
        cursor.execute(sq)
        # columns = cursor.description
        for row in cursor.fetchall():
            # print(row)
            arr_crpt[ row[0] + ':' + row[1] + ':' + row[2]] = int(row[3])

    return arr_crpt, latest
 

def getData():
    t = time.time()
    dbcon = dbconMaster(host=ARR_CONFIG['mysql']['host'], user = ARR_CONFIG['mysql']['user'], password=ARR_CONFIG['mysql']['password'], port=int(ARR_CONFIG['mysql']['port']))
    with dbcon:
        cursor = dbcon.cursor()
        arr_crpt, latest = getRptCounting(cursor)
        print (arr_crpt)
        diff = getRtCounting(cursor, latest)
        print ("rt_count", diff)
        for exp in ARR_CRPT:
            e = exp.split(":")
            if len(e) <3:
                continue

            ARR_CRPT[exp] = arr_crpt[exp]
            if e[0] in ['today', 'thisweek', 'thismonth', 'thisyear']: 
                if diff.get(e[1]) and diff[e[1]].get(e[2]):
                    ARR_CRPT[exp] = arr_crpt[exp] + diff[e[1]][e[2]]
            # else :
            #     ARR_CRPT[key] = arr_crpt[key]

    print (time.time()-t)

print ("TZ_OFFSET", TZ_OFFSET)
ARR_CONFIG = loadConfig()
LANG = loadLanguage()
getVariableNames()
print (ARR_CRPT)
if __name__ == '__main__':
    
    # for sq in getSqls():
    #     print (sq)
    #     print ()
    getData()
    print (ARR_CRPT)


    # rule = "limit_BB - today:mac=001323A0072F&brand=TSD&model=TSDC32P-12V:Likangcun_IN+today:mac=001323A0072F&brand=TSD&model=TSDC32P-12V:Likangcun_OUT"
    # rule = "limit_BB - today:all:entrance+today:mac=001323A0072F&brand=TSD&model=TSDC32P-12V:Likangcun_OUT"

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

    # pass
