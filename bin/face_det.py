change_log = """
###############################################################################
# Face Detection via face_thumbnail , Face++ Recognize face, age, gender
2020-05-01, database common -> face_thumbnail and face analyze whit Face ++
2020-09-03, Version 8.0  // 9.0
2020-09-03, Python 2.7 to Python 3.8 , start on 2020-09-03
2021-02-23, parsePostData from functions, dbconn-> with state
2021-08-11, version 0.93. support configVars only and param_tbl
2022-03-27, transfer function face_det event push to proc_event.py

###############################################################################
"""

import os, time, sys
from http.client import HTTPConnection, HTTPSConnection
from urllib.parse import urlencode
import json
import threading

from functions_s import (configVars,  addSlashes, dbconMaster, modifyConfig, log, info_to_db, _DEBUG_DISPLAY)
# from parse_functions import parseEventData
# from db_functions import updateFaceThumnmail

info_to_db('face_det', change_log)
if _DEBUG_DISPLAY:
    print (change_log)

MYSQL = { 
    "commonParam": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.param'),
    "commonFace": configVars('software.mysql.db') + "." + configVars('software.mysql.db_common.table.face')
}


def fpp_query(path, params) :
	# global FPP
	params['api_key'] = configVars('software.fpp.api_key')
	params['api_secret'] = configVars('software.fpp.api_srct')

	headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
	fppconn = HTTPSConnection(configVars('software.fpp.host'), configVars('software.fpp.port'))
	param_url = urlencode(params)
	fppconn.request("POST", path, param_url, headers) 
	response = fppconn.getresponse()
	# print (response.status, response.reason)
	data = response.read()
	fppconn.close()
	return data


def fpp_detect(img_b64) :
	path = "/facepp/v3/detect"
	params = {}
	rs ={"gender":"", "age":0, "face_token":""}
	params['return_landmark'] = 0
	params['return_attributes'] =  "gender,age"
	params['image_base64'] = img_b64
	
	tmp = fpp_query(path, params)

	if tmp.find(b'"face_num"') > 0:
		json_str = json.loads(tmp.decode('ascii'))
		if json_str["face_num"] > 0:
			rs['gender'] = json_str['faces'][0]['attributes']['gender']['value']
			rs['age'] = json_str['faces'][0]['attributes']['age']['value']
			rs['face_token'] = json_str['faces'][0]['face_token']
			return rs
	
	return False
	
def face_update_age_gender() :
	dbconn0 = dbconMaster()
	with dbconn0:
		cur = dbconn0.cursor()
		sq = "select pk, thumbnail from " + MYSQL['commonFace'] + " where flag='y' and flag_fd='n' and (age is NULL or age=0) order by timestamp desc limit 10"
		cur.execute(sq)
		# row = cur.fetchone()
		rows = cur.fetchall()
		if not rows:
			cur.close()
			# dbconn0.close()
			return False
		
		for row in rows:
			fs = fpp_detect(row[1]) 
			# print (fs)
			if fs :
				sq = "update " + MYSQL['commonFace'] + " set age=%s, gender=%s, face_token=%s, flag_fd='y' where pk=%s" 
				cur.execute(sq, (fs['age'], fs['gender'], fs['face_token'], row[0]))
			
			else :
				sq = "update " + MYSQL['commonFace'] + " set age=0, gender='', face_token='', flag='n', flag_fd='n' where pk=%s "
				cur.execute(sq, row[0])
			print(sq)
			time.sleep(0.3)

		dbconn0.commit()
	return True

class thFaceDetTimer() : 
	def __init__(self, t=5):
		self.name = "face_det"
		self.t = t
		self.last = 0
		self.thread = threading.Timer(1, self.handle_function)

	def handle_function(self):
		self.main_function()
		self.last = int(time.time())
		self.thread = threading.Timer(self.t, self.handle_function)
		self.thread.start()

	def main_function(self):
		face_update_age_gender()

	def start(self):
		str_s = "Starting Face to Age Gender via transfering FPP server"
		print(str_s)
		log.info (str_s)
		self.last = int(time.time())
		self.thread.start()

	def is_alive(self) :
		if int(time.time()) - self.last > 100:
			return False
		return True

	def cancel(self):
		str_s = "Stopping process Face to Age Gender"
		print(str_s)
		log.info (str_s)
		self.thread.cancel()

	def stop(self):
		self.cancel()   


# #  mainly face thumbnail to fpp server and get result.
# class ThFaceDet(threading.Thread) : 
# 	def __init__ (self):
# 		threading.Thread.__init__(self, name='face_det')
# 		self.daemon= True
# 		self.running = True
	
# 	def run(self):
# 		str_s = "starting Face to Age Gender via transfering FPP server"
# 		print(str_s)
# 		log.info (str_s)

# 		while self.running:
# 			face_update_age_gender()
# 			time.sleep(5)
		
# 		str_s = "Stopping Face Detecting"
# 		print(str_s)
# 		log.info (str_s)
		
# 	def stop(self):
# 		self.running = False

# if __name__ == '__main__':
# 	# testRecvFace()
# 	tf = ThFaceDet()
# 	tf.start()

# 	while True:
# 		print (tf, tf.is_alive())
# 		if not tf.is_alive():
# 			tf.start()
# 		time.sleep(30)

