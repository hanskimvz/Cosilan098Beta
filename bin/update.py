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
# update from github // V0.97

import time, sys, os
import socket
from http.client import HTTPConnection
import uuid

_ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(sys.argv[0])))
os.chdir(_ROOT_DIR + "/bin")
# print(os.getcwd())

args = ""
for i, v in enumerate(sys.argv):
    if i==0 :
        continue
    args += v + " "

_UPDATE_PAGE = "https://github.com/hanskimvz/Cosilan097Beta/"

def checkAvailabe():
    # check updage page and vaild page ? some sites have protect web page from external sites
    fname = _ROOT_DIR + "/bin/valid"
    mtime0 = 0
    if os.path.isfile(fname):
         mtime0 =  int(os.path.getmtime(fname))

    if os.name == 'nt':
        cmd = _ROOT_DIR + "/bin/wget.exe https://raw.githubusercontent.com/hanskimvz/Cosilan097Beta/main/bin/valid -O " + fname
    else :
        cmd = "wget https://raw.githubusercontent.com/hanskimvz/Cosilan097Beta/main/bin/valid -O " + fname

    os.system(cmd)
    mtime1 =  int(os.path.getmtime(fname))
    with open(fname, "r")  as f:
        body = f.read()
    if mtime1 - mtime0  >0 and body.find("valid") >=0:
        print (mtime0, mtime1, mtime1-mtime0, body)
        return True

    return False

def update():
    if not checkAvailabe():
        return False

    print ("Downloading update main file ....", end="")
    fname = _ROOT_DIR + "/bin/update_main.py"

    if os.name == 'nt':
        cmd = _ROOT_DIR + "/bin/wget.exe https://raw.githubusercontent.com/hanskimvz/Cosilan097Beta/main/bin/update_main.py -O " + fname
    else :
        cmd = "wget https://raw.githubusercontent.com/hanskimvz/Cosilan097Beta/main/bin/update_main.py -O " + fname

    os.system(cmd)
    print ("... done")
    time.sleep(1)

    os.chdir("%s/bin" %_ROOT_DIR)
    
    if os.name == 'nt':
        os.system("python3.exe %s %s" %(fname, args))
    
    elif os.name == 'posix':
        os.system("/usr/bin/python3 %s" %fname )
    

if __name__ == '__main__':
    update()
    sys.exit()

