
import time, os, sys
import json
from tkinter import *
from tkinter import ttk
from tkinter import filedialog
import cv2 as cv
import numpy as np
from PIL import ImageTk, Image

from rt_main import loadConfig, saveConfig, loadTemplate, saveTemplate, dbconMaster, getDevices, Running, cwd


var = dict()
root = Tk()
menus = dict()

oWin = None
eWin = None
var_option = dict()
var_screen = dict()

ARR_CONFIG = loadConfig()
ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])
# print (ARR_CONFIG)

def exitProgramOpt():
    global Running
    Running = False

    root.destroy()
    root.quit()
    print ("destroyed root")
    sys.stdout.flush()

# # Import module  
# from tkinter import *
  
# # Create object  
# root = Tk() 
  
# # Adjust size  
# root.geometry("400x400") 
  
# # Add image file 
# bg = PhotoImage(file = "Your_img.png") 
  
# # Create Canvas 
# canvas1 = Canvas( root, width = 400, 
#                  height = 400) 
  
# canvas1.pack(fill = "both", expand = True) 
  
# # Display image 
# canvas1.create_image( 0, 0, image = bg,  
#                      anchor = "nw") 
  
# # Add Text 
# canvas1.create_text( 200, 250, text = "Welcome") 
 

def mainScreen():
    ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])
    for s in ARR_SCREEN:
        name = s.get("name")
        if s.get("role") == 'variable':
            continue
        
        if not name in menus:
            menus[name] = Label(root)
            # menus[name] = Button(root)
            var[name] = StringVar()
            menus[name].configure(textvariable = var[name])
            print("create label %s" %name)

        if s.get("flag") == 'n':
            # if menus.get(name):
            #     menus[name].place_forget()
            continue


        if s.get('text'):
            var[name].set(s['text'])

        if s.get('font'):
            menus[name].configure(font=tuple(s['font']))
        if s.get('color'):
            menus[name].configure(fg=s['color'][0], bg=s['color'][1])

        if s.get('padding'):
            menus[name].configure(padx=s['padding'][0], pady=s['padding'][1])
        
        if s.get('align'):
            if s['align'] == 'left':
                menus[name].configure(anchor='w')
            elif s['align'] == 'right':
                menus[name].configure(anchor='e')
            else:
                menus[name].configure(anchor='center')

        w, h = int(s['size'][0]), int(s['size'][1]) if s.get('size') else (0, 0)
        posx, posy = (int(s['position'][0]), int(s['position'][1])) if s.get('position') else (0, 0)

        if s.get('role') =='number' and s.get("text")=="":
            var[name].set('0000')

        if s.get('role') == 'picture' :
            imgPath = s.get('url')
            if not (imgPath and os.path.isfile(imgPath)):
                imgPath = "cam.jpg"
            img = cv.imread(imgPath)
            img = Image.fromarray(img)
            img = img.resize((w, h), Image.LANCZOS)
            imgtk = ImageTk.PhotoImage(image=img)
            menus[name].configure(image=imgtk)
            menus[name].photo=imgtk # phtoimage bug

        elif s.get('role') == 'snapshot':
            if s.get('device_info') :
                USE_SNAPSHOT = True
        elif s.get('role') == 'video':
            if s.get('url') :
                USE_VIDEO = True

        menus[name].configure(width=w, height=h)

        menus[name].place(x=posx, y=posy)


    for m in menus:
        menus[m].configure(borderwidth=0)



def selBlock():
    for m in menus:
        menus[m].configure(borderwidth=0)
    name =var_screen['label'].get()
    if menus.get(name):
        menus[name].configure(borderwidth=2, relief="groove")
    

def movePos():
    name =var_screen['label'].get()
    print ("name", name)
    if menus.get(name):
        menus[name].place(x=var_screen['posX'].get(), y=var_screen['posY'].get())

def sizeFont():
    name =var_screen['label'].get()
    font = (var_screen['fontfamily'].get(), var_screen['fontsize'].get(), var_screen['fontshape'].get())
    if menus.get(name):
        menus[name].configure(font=font)

#########################################################################################################
############################################## Option Config ############################################
#########################################################################################################
def frame_option(e=None):
    global root, oWin, var
    # print(e)
    screen_width = root.winfo_screenwidth()
    screen_height = root.winfo_screenheight()

    var_option['refresh_interval'] = StringVar()
    var_option['full_screen']      = IntVar()
    var_option['template']         = StringVar()
    var_option['message_str']      = StringVar()

    for key in ARR_CONFIG['mysql']:
        var_option[key] = StringVar()
        var_option[key].set(ARR_CONFIG['mysql'][key])

    if eWin: 
        closeEdit()

    if oWin: 
        oWin.lift()
    else :
        oWin = Toplevel(root)		
        oWin.title("Configuration")
        oWin.geometry("300x400+%d+%d" %(int(screen_width/2-150), int(screen_height/2-200)))
        oWin.protocol("WM_DELETE_WINDOW", closeOption)
        oWin.resizable(False, False)
        optionMenu()
    # ths.delay =  ARR_CONFIG['refresh_interval']

def closeOption():
    global oWin
    oWin.destroy()
    oWin = None


def saveOption():
    global ARR_CONFIG, root, var_option
    need_restart = False
    message ("")
    chMysql = False

    for key in ARR_CONFIG['mysql']:
        if str(ARR_CONFIG['mysql'][key]).strip() != str(var_option[key].get()).strip():
            print ("%s : %s" %(ARR_CONFIG['mysql'][key], var_option[key].get()))
            chMysql = True
            break
    if chMysql:
        try:
            ret = dbconMaster(
                host = str(var_option['host'].get().strip()),
                user = str(var_option['user'].get().strip()), 
                password = str(var_option['password'].get().strip()),
                charset = str(var_option['charset'].get().strip()),
                port = int(var_option['port'].get().strip())
            )
            print (ret.ping(reconnect=False))
            need_restart = True
        except Exception as e:
            print ("MYSQL Error")
            print (e)
            message (ARR_CONFIG['language'].get("check_mysql_conf"))
            return False

        for key in ARR_CONFIG['mysql']:
            ARR_CONFIG['mysql'][key] = str(var_option[key].get()).strip()

    try:
        ARR_CONFIG['refresh_interval'] = int(var_option['refresh_interval'].get())
    except:
        message (ARR_CONFIG['language'].get("refresh_time_error"))
        return False

    if ARR_CONFIG['template'] != var_option['template'].get().strip():
    # if ARR_CONFIG['template'] != template.get().strip():
        ARR_CONFIG['template'] = var_option['template'].get().strip()
        # ARR_CONFIG['template'] = template.get().strip()
        print ("template changed")
        need_restart = True

    fx = "yes" if var_option['full_screen'].get() else "no"
    if ARR_CONFIG['full_screen'] != fx:
        ARR_CONFIG['full_screen'] = fx
        need_restart = True
        # if ARR_CONFIG['full_screen'] == "yes":
        #     root.overrideredirect(True)
        #     root.attributes("-fullscreen", True)
        #     root.resizable (False, False)
        # else :
        #     root.overrideredirect(False)
        #     root.attributes("-fullscreen", False)
        #     root.resizable (True, True)

    saveConfig("rtScreen.json", ARR_CONFIG)
    message("saved")
    if need_restart:
        #restart
        sys.stdout.flush()
        os.execv(sys.executable, ["python3.exe"] + sys.argv)
        # os.execv("python3.exe", sys.argv)

def optionMenu():
    global ARR_CONFIG, oWin, var_option
    # print (sys.executable)
    btnFrame = Frame(oWin)
    btnFrame.pack(side="bottom", pady=10)
    Button(btnFrame, text=ARR_CONFIG['language']['close_option'], command=closeOption, width=16).pack(side="left", padx=5)
    Button(btnFrame, text=ARR_CONFIG['language']['exit_program'], command=exitProgramOpt, width=16).pack(side="right", padx=5)

    dbFrame = Frame(oWin)
    dbFrame.pack(side="top", pady=10)

    Label(dbFrame, text=ARR_CONFIG['language']['db_server']).grid(row=0, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['user']).grid(row=1, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['password']).grid(row=2, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['charset']).grid(row=3, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['port']).grid(row=4, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['db_name']).grid(row=5, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['refresh_interval']).grid(row=6, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['full_screen']).grid(row=7, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=ARR_CONFIG['language']['template']).grid(row=8, column=0, sticky="w", pady=2, padx=4)

    Entry(dbFrame, textvariable=var_option['host']).grid(row=0, column=1, ipadx=3)
    Entry(dbFrame, textvariable=var_option['user']).grid(row=1, column=1, ipadx=3)
    Entry(dbFrame, textvariable=var_option['password']).grid(row=2, column=1, ipadx=3)
    Entry(dbFrame, textvariable=var_option['charset']).grid(row=3, column=1, ipadx=3)
    Entry(dbFrame, textvariable=var_option['port']).grid(row=4, column=1, ipadx=3)
    Entry(dbFrame, textvariable=var_option['db']).grid(row=5, column=1, ipadx=3)
    Entry(dbFrame, textvariable=var_option['refresh_interval']).grid(row=6, column=1, ipadx=3)
    cfs = Checkbutton(dbFrame, variable=var_option['full_screen'])
    cfs.grid(row=7, column=0, columnspan=2)
    # Entry(dbFrame, textvariable=var_option['template']).grid(row=8, column=1, ipadx=3)
    listTemplates = []
    for x in os.listdir(cwd):
        if x.startswith("template"):
            listTemplates.append(x)
    var_option['template'] = ttk.Combobox(dbFrame, width=16, values=listTemplates)
    var_option['template'].grid(row=8, column=1, ipadx=3)
    Button(dbFrame, text=ARR_CONFIG['language']['save_changes'], command=saveOption, width=16).grid(row=9, column=0, columnspan=2)

    var_option['refresh_interval'].set(ARR_CONFIG['refresh_interval'])
    for i, x in enumerate(listTemplates):
        if x == ARR_CONFIG['template']:
            var_option['template'].current(i)
    # var['template'].set(ARR_CONFIG['template'])
    if ARR_CONFIG['full_screen'] == 'yes':
        cfs.select()
    Message(oWin, textvariable = var_option['message_str'], width= 300,  bd=0, relief=SOLID, foreground='red').pack(side="top")

def message(strn):
    if (oWin) :
        var_option['message_str'].set(strn)

    else  :
        var_screen['message_str'].set(strn)


#########################################################################################################
############################################## Screen Edit ##############################################
#########################################################################################################
def edit_screen(e):
    global root, eWin
    screen_width = root.winfo_screenwidth()
    screen_height = root.winfo_screenheight()

    if oWin: 
        closeOption()

    if eWin: 
        closeEdit()

    eWin = Toplevel(root)		
    eWin.title("Edit Screen")
    eWin.geometry("260x640+%d+%d" %(int(screen_width/2-150), int(screen_height/2-200)))
    eWin.protocol("WM_DELETE_WINDOW", closeEdit)
    eWin.resizable(True, True)
    editScreen()
    

def closeEdit():
    global eWin
    for m in menus:
        menus[m].configure(borderwidth=0)

    eWin.destroy()
    eWin = None

scFrame = None
btFrame = None
def editScreen():
    global ARR_CONFIG, ARR_SCREEN, eWin, var_screen, scFrame
    x, y = root.winfo_pointerx(), root.winfo_pointery()

    Button(eWin, text=ARR_CONFIG['language']['close_option'], command=closeEdit, width=16).pack(side="bottom", padx=5, pady=10)
    Button(eWin, text=ARR_CONFIG['language']['save_changes'], command=saveScreen, width=16).pack(side="bottom")
    var_screen['message_str'] = StringVar()
    Message(eWin, textvariable = var_screen['message_str'], width= 200,  bd=0, relief=SOLID, foreground='red').pack(side="bottom", pady=5)       

    labelFrame = Frame(eWin)
    labelFrame.pack(side="top", pady=10)

    ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])
    listLabels = [x['name'] for x in ARR_SCREEN]

    ## which lable?
    dist  = 1000000
    sel_name = ""
    for scrn in ARR_SCREEN:
        if x > scrn["position"][0]  and y > scrn["position"][1] :
            if ((x - scrn["position"][0])^2) + ((y- scrn["position"][1])^2) < dist:
                dist = ((x - scrn["position"][0])^2) + ((y- scrn["position"][1])^2) 
                sel_name = scrn["name"]
                # print (sel_name)
    # print ()
    # print (sel_name, dist)

    Label(labelFrame, text=ARR_CONFIG['language']['name']).grid(row=0, column=0, sticky="w", pady=2, padx=4)
    var_screen['label'] = ttk.Combobox(labelFrame, width=18, state="readonly", values=listLabels)
    var_screen['label'].bind("<<ComboboxSelected>>", updateEntry)
    var_screen['label'].grid(row=0, column=1, columnspan=2, sticky="w")
    for j, ft in enumerate(listLabels):
        if sel_name == ft:
            var_screen['label'].current(j)    
            updateEntry(0)
            break

    var_screen['message_str'].set("hello")

def updateEntry(e):
    global ARR_SCREEN, var_screen, scFrame, btFrame

    listFont = dict()
    listFont['fontfamily'] = ['simhei', 'arial', 'fangsong', 'simsun', 'gulim', 'batang', 'ds-digital','bauhaus 93', 'HP Simplified' ]
    listFont['fontshape'] = ['normal', 'bold', 'italic']
    listFont['fgcolor']  = ['white', 'black', 'orange', 'blue', 'red', 'green', 'purple', 'grey', 'yellow', 'pink']
    listFont['bgcolor'] = ['white', 'black', 'orange', 'blue', 'red', 'green', 'purple', 'grey', 'yellow', 'pink','transparent']
    listFont['align'] = ['left', 'right', 'center']

    list_keys = dict()
    list_keys['label']    = ['text', 'fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'height', 'posX', 'posY', 'padX', 'padY', 'align','role', 'use',]
    list_keys['number']   = ['fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'height', 'posX', 'posY', 'padX', 'padY', 'align','role', 'rule','use',]
    list_keys['percent']  = ['fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'height', 'posX', 'posY', 'padX', 'padY', 'align','role', 'rule','use',]
    list_keys['picture']  = ['width', 'height', 'posX', 'posY', 'padX', 'padY', 'role', 'url','use',]
    list_keys['snapshot'] = ['width', 'height', 'posX', 'posY', 'padX', 'padY', 'role', 'device_info','use',]
    list_keys['datetime'] = ['fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'height', 'posX', 'posY', 'padX', 'padY', 'align', 'role', 'use']
    # list_keys['variable'] = ['sql', 'use']

    list_roles= ['label', 'number', 'percent', 'datetime', 'picture', 'snapshot', 'video']

    sel = var_screen['label'].get()
    # print(sel)
    selBlock()
    for x in list_keys:
        for key in list_keys[x]:
            if var_screen.get(key):
                del(var_screen[key])

    if scFrame:
        scFrame.pack_forget()

    scFrame = Frame(eWin)
    scFrame.pack(side="top", pady=2)

    if btFrame:
        btFrame.pack_forget()
    btFrame = Frame(eWin)
    btFrame.pack(side="top", pady=10)
    for x in ARR_SCREEN:
        if x['name'] != sel:
            continue
        
        # print (x)
        arr_key = list_keys[x.get('role')]

        x['fontfamily'],  x['fontsize'], x['fontshape'] = tuple(x['font'])     if x.get('font') else (0,0,0)
        x['fgcolor'], x['bgcolor']                      = tuple(x['color'])    if x.get('color') else (0,0)
        x['width'], x['height']                         = tuple(x['size'])     if x.get('size') else (0,0)
        x['posX'],  x['posY']                           = tuple(x['position']) if x.get('position') else (0,0)
        x['padX'],  x['padY']                           = tuple(x['padding'])  if x.get('padding') else (0,0)
        
        for i, key in enumerate(arr_key):
            var_screen[key]= StringVar()
            cat = None
            if (key=='use'):
                Label(scFrame, text= ARR_CONFIG['language'][key] if ARR_CONFIG['language'].get(key) else key).grid(row=i+2, column=0, pady=2)
                chk = Checkbutton(scFrame, variable=var_screen[key])
                chk.grid(row=i+2, column=1, sticky="w")
                chk.select() if x.get('flag')=='y' else chk.deselect()

            # elif key == 'fontfamily' or key =='fontshape' or key =='fgcolor' or key == 'bgcolor' or key=='align':
            elif key in ['fontfamily', 'fontshape', 'fgcolor', 'bgcolor', 'align']:
                Label(scFrame, text= ARR_CONFIG['language'][key] if ARR_CONFIG['language'].get(key) else key).grid(row=i+2, column=0, pady=2)
                var_screen[key] =  ttk.Combobox(scFrame, width=18, state="readonly", values=listFont[key])
                var_screen[key].grid(row=i+2, column=1)
                for j, ft in enumerate(listFont[key]):
                    if x.get(key) == ft:
                        var_screen[key].current(j)

            elif key=='posX':
                Button(btFrame, text="<", command=posLeft,   width=4).grid(row=1, column=0)
                Button(btFrame, text=">", command=posRight,  width=4).grid(row=1, column=3)
                Label(btFrame, text='X').grid(row=3, column=0)
                Entry(btFrame, textvariable=var_screen[key], width=4).grid(row=4, column=0)
                cat = 1

            elif key == 'posY':
                Button(btFrame, text="^", command=posUp, width=4).grid(row=0, column=1, columnspan=2)
                Button(btFrame, text="v", command=posDown,   width=4).grid(row=2, column=1, columnspan=2)
                Label(btFrame, text='Y').grid(row=3, column=1, columnspan=2)
                Entry(btFrame, textvariable=var_screen[key], width=4).grid(row=4, column=1, columnspan=2)
                cat = 1

            elif key == 'fontsize':
                Button(btFrame, text="+", command=fontSizeU, width=2).grid(row=1, column=1)
                Button(btFrame, text="-", command=fontSizeD, width=2).grid(row=1, column=2)
                Label(btFrame, text='S').grid(row=3, column=3)
                Entry(btFrame, textvariable=var_screen['fontsize'], width=4).grid(row=4, column=3)
                cat = 1
                
            elif key=='role':
                Label(scFrame, text= ARR_CONFIG['language'][key] if ARR_CONFIG['language'].get(key) else key).grid(row=i+2, column=0, pady=2)
                var_screen[key] =  ttk.Combobox(scFrame, width=18, state="readonly", values=list_roles)
                var_screen[key].grid(row=i+2, column=1)
                for j, ft in enumerate(list_roles):
                    if x.get(key) == ft:
                        var_screen[key].current(j)

            elif key=='sql' or key == 'rule':
                Label(scFrame, text= ARR_CONFIG['language'][key] if ARR_CONFIG['language'].get(key) else key).grid(row=i+2, column=0, pady=2, sticky='n')
                var_screen[key] = Text(scFrame, width=20, height=5)
                var_screen[key].grid(row=i+2, column=1)
                var_screen[key].insert(1.0, x.get(key))

            elif key == 'url':
                Button(scFrame, command=browseFile, text = ARR_CONFIG['language'][key] if ARR_CONFIG['language'].get(key) else key).grid(row=i+2, column=0, pady=2)
                Entry(scFrame, textvariable=var_screen[key], width=22).grid(row=i+2, column=1)
                cat = 1

            # elif sel.startswith('snapshot') or sel.startswith('video'):
            #     elb['device_info'].grid(row=1, column=0, sticky="w", pady=2, padx=4)
            #     ent['device_info'].grid(row=1, column=1, columnspan=2, sticky="w")
            #     lvar['device_info'].set(x.get('device_info'))
            #     for i, ft in enumerate(listDevice):
            #         if x.get('device_info') == ft:
            #             ent['device_info'].current(i)



            else : # label, entry
                Label(scFrame, text= ARR_CONFIG['language'][key] if ARR_CONFIG['language'].get(key) else key).grid(row=i+2, column=0, pady=2)
                Entry(scFrame, textvariable = var_screen[key], width=22).grid(row=i+2, column=1, columnspan=2)
                cat = 1

            if cat:
                var_screen[key].set(x.get(key))
         
        print ()


def posLeft():
    var_screen['posX'].set(str(int(var_screen['posX'].get())-5))
    movePos()
def posRight():
    var_screen['posX'].set(str(int(var_screen['posX'].get())+5))
    movePos()
def posUp():
    var_screen['posY'].set(str(int(var_screen['posY'].get())-5))
    movePos()
def posDown():
    var_screen['posY'].set(str(int(var_screen['posY'].get())+5))
    movePos()
def fontSizeU():
    var_screen['fontsize'].set(str(int(var_screen['fontsize'].get())+5))
    sizeFont()
def fontSizeD():
    var_screen['fontsize'].set(str(int(var_screen['fontsize'].get())-5))
    sizeFont()

def browseFile():
    global eWin, var_screen
    fdir = os.path.dirname(var_screen['url'].get())
    fname = filedialog.askopenfilename(initialdir=fdir , title="Select imagefile", filetypes=[("image", ".jpeg"),("image", ".png"),("image", ".jpg"),])
    print(fname)
    var_screen['url'].set(fname)
    eWin.lift()  
        

def saveScreen():
    global ARR_CONFIG
    arr_template = loadTemplate(ARR_CONFIG['template'])
    
    sel = var_screen['label'].get()
    print(sel)
    if not sel:
        return False
    
    for i, r in enumerate(arr_template):
        if r['name'] == sel:
            print (r['role'])
            if var_screen.get('posX') and var_screen.get('posY'):
                if not (var_screen['posX'].get().isnumeric() and var_screen['posY'].get().isnumeric()):
                    message("position type error")
                    return False
                arr_template[i]['position'] = [int(var_screen['posX'].get()), int(var_screen['posY'].get())]
                if r['flag'] == 'y':
                    movePos()
                else :
                   menus[sel].place_forget()

            if var_screen.get('padX') and var_screen.get('padY'):
                if not (var_screen['padX'].get().isnumeric() and var_screen['padY'].get().isnumeric()):
                    print ("padding type error")
                    message("padding type error")
                    return False
                arr_template[i]['padding']  = [int(var_screen['padX'].get()), int(var_screen['padY'].get())]
                menus[sel].configure(padx=arr_template[i]['padding'][0], pady=arr_template[i]['padding'][1])

            if var_screen.get('fontsize'):
                if not var_screen['fontsize'].get().isnumeric():
                    print ("fontsize type error")
                    message("fontsize type error")
                    return False
                arr_template[i]['font']  = [var_screen['fontfamily'].get(), int(var_screen['fontsize'].get()), var_screen['fontshape'].get()]
                menus[sel].configure(font=tuple(arr_template[i]['font']))

                if var_screen['bgcolor'].get() == 'transparent':
                    bg_color ='black'
                else :
                    bg_color = var_screen['bgcolor'].get()
                arr_template[i]['color'] = [var_screen['fgcolor'].get(), bg_color]
                menus[sel].configure(fg=arr_template[i]['color'][0], bg=arr_template[i]['color'][1])
                
            if var_screen.get('width') and var_screen.get('height'):
                if not (var_screen['width'].get().isnumeric() and var_screen['height'].get().isnumeric()):
                    print ("size type error")
                    message("size type error")
                    return False
                arr_template[i]['size'] = [int(var_screen['width'].get()), int(var_screen['height'].get())]
                menus[sel].configure(width=arr_template[i]['size'][0], height=arr_template[i]['size'][1])
            
            if var_screen.get('align'):
                arr_template[i]['align'] = var_screen['align'].get().strip()
                if arr_template[i]['align']  == 'left':
                    menus[sel].configure(anchor='w')
                elif arr_template[i]['align']  == 'right':
                    menus[sel].configure(anchor='e')
                else :
                    menus[sel].configure(anchor='center')

            if var_screen.get('url'):
                arr_template[i]['url'] = var_screen['url'].get().strip()
                imgPath = arr_template[i]['url']
                if not (imgPath and os.path.isfile(imgPath)):
                    imgPath = "cam.jpg"
                img = cv.imread(imgPath)
                img = Image.fromarray(img)
                img = img.resize(tuple(arr_template[i]['size']), Image.LANCZOS)
                imgtk = ImageTk.PhotoImage(image=img)
                menus[sel].configure(image=imgtk)
                menus[sel].photo=imgtk # phtoimage bug                

            if var_screen.get('text'):
                arr_template[i]['text'] = var_screen['text'].get().strip()
                var[sel].set(arr_template[i]['text'])

            if var_screen.get('rule'):
                arr_template[i]['rule'] = var_screen['rule'].get(1.0, "end").strip()
                # var[sel].set(arr_template[i]['rule'])

            if var_screen.get('sql'):
                arr_template[i]['sql'] = var_screen['sql'].get().strip()

            arr_template[i]['role'] = var_screen['role'].get().strip()
            arr_template[i]['flag'] = 'y' if int(var_screen['use'].get()) else 'n'

            # if sel.startswith('picture') or sel.startswith('video'):
            #     arr[i]['url'] = lvar['url'].get()

            # elif sel.startswith('snapshot'):
            #     if ent['device_info'].get() == 'all':
            #         continue
            #     arr[i]['device_info'] = ent['device_info'].get()

            # else:
            #     if not (lvar['fontsize'].get().isnumeric()):
            #         print ("fontsize type error")
            #         message("fontsize type error")
            #         return False
            #     arr[i]['font'] = [ent['font'].get(), int(lvar['fontsize'].get()), ent['fontshape'].get()]
            #     arr[i]['color'] = [ent['color'].get(), ent['bgcolor'].get()]

            #     if sel.startswith('number'):
            #         if not parseRule(lvar['rule'].get()):
            #             print (parseRule(lvar['rule'].get()))
            #             message("rule error \n sum/diff/div/percent(date:counter_label,), \nEx: sum(today:entrance, today:exit)")
            #             return False
            #         arr[i]['text'] = ""
            #         arr[i]['device_info'] = ent['device_info'].get()
            #         arr[i]['rule'] = lvar['rule'].get()
            #     else :
            #         arr[i]['text'] = lvar['display'].get()
                    

    # print (arr)
    message("saved")
    saveTemplate(ARR_CONFIG['template'], arr_template)
