
import time, os, sys
import json
from tkinter import *
from tkinter import ttk
from tkinter import font
from tkinter import filedialog

import cv2 as cv
import numpy as np
from PIL import ImageTk, Image

from rt_main import ARR_CONFIG, loadConfig, saveConfig, loadTemplate, saveTemplate, loadLanguage, dbconMaster, getVariableNames, Running, cwd


var = dict()
root = Tk()
canvas = Canvas(root, width=root.winfo_screenwidth(), height=root.winfo_screenheight(),background='black', highlightthickness=0)
canvas.pack(padx=0, pady=0, side="top")
menus = dict()
bgbox = dict()
# canvas = None

oWin = None
eWin = None
geometry = root.geometry()
fullscrntag = False

ARR_CONFIG = loadConfig()
ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])
LANG = loadLanguage()
# print (ARR_CONFIG)

def exitProgramOpt():
    global Running
    Running = False

    root.destroy()
    root.quit()
    print ("destroyed root")
    sys.stdout.flush()

def fullScreen(e):
    global root, fullscrntag, geometry
    if fullscrntag == False:
        fullscrntag = True
        geometry = root.geometry()
        root.overrideredirect(True)

        if os.name == 'nt':
            root.state("zoomed")
        else :
            root.attributes("-fullscreen", True)
    else :
        fullscrntag = False
        root.state("normal")
        root.geometry(geometry)
        root.overrideredirect(False)
        root.attributes("-fullscreen", False)    

def mainScreen():
    global ARR_CONFIG, root, canvas
    arr_img = dict()
    canvas.bind('<Double-Button-1>', edit_screen)
    ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])
    for s in ARR_SCREEN:
        name = s.get("name")
        if s.get("role") == 'variable':
            continue
       
        if not name in menus:
            if s.get('role') in ['picture', 'snapshot', 'video']:
                menus[name] = canvas.create_image(0, 0, anchor="nw")
            else :
                bgbox[name] = canvas.create_rectangle(0, 0, 0, 0)
                menus[name] = canvas.create_text(0, 0, anchor="nw")
        if s.get('flag') == 'n':
            canvas.delete(menus[name])
            del(menus[name])
            continue

        w, h = int(s['size'][0]), int(s['size'][1]) if s.get('size') else (0, 0)
        posx, posy = (int(s['position'][0]), int(s['position'][1])) if s.get('position') else (0, 0)

        if s.get('position'):
            xo, yo =  canvas.coords(menus[name])
            canvas.move(menus[name], posx - int(xo), posy- int(yo))
            # canvas.moveto(menus[name], posx, posy ) # canvas.moveto bug, anchor will be w

        if s.get('role') == 'picture':
            imgPath = s.get('url')
            if not (imgPath and os.path.isfile(imgPath)):
                imgPath = "cam.jpg"

            # using CV
            img = cv.imread(imgPath)
            img = cv.cvtColor(img, cv.COLOR_BGR2RGB)
            img = Image.fromarray(img)

            # using othter
            # img = Image.open(imgPath)

            img = img.resize((w, h), Image.LANCZOS)
            arr_img[menus[name]] = ImageTk.PhotoImage(image=img)
            canvas.itemconfigure(menus[name], image=arr_img[menus[name]])
            # canvas.image_names=imgtk # phtoimage bug
            # canvas.photo=arr_img  # phtoimage bug
            canvas.lower(menus[name])

        elif s.get('role') == 'snapshot':
            pass

        elif s.get('role') == 'video':
            pass
        
        # if not s.get('role') in ['picture', 'snapshot', 'video']:
        else:
            canvas.itemconfigure(menus[name], width=w)
            if s.get('role') == 'datetime':
                dow = ["星期日","星期一","星期二","星期三","星期四","星期五","星期六"]
                s['text'] = time.strftime(s.get('format'))
                for i in range(0,7):
                    s['text'] = s['text'].replace("%dc" %i, dow[i])


            canvas.itemconfigure(menus[name], text=s['text'])
            ft = font.Font(family=s['font'][0], size=s['font'][1], weight=s['font'][2])
            canvas.itemconfigure(menus[name], font=ft)

            if s.get('align'):
                if s['align'] == 'left':
                    canvas.itemconfigure(menus[name], anchor='nw')
                elif s['align'] == 'right':
                    canvas.itemconfigure(menus[name], anchor='ne')
                else:
                    canvas.itemconfigure(menus[name], anchor='center')

            if s.get('color'):
                canvas.itemconfigure(menus[name], fill=s['color'][0])
                if s['color'][1] == 'transparent':
                    canvas.coords(bgbox[name], (0,0,0,0))
                else:
                    canvas.itemconfigure(bgbox[name], fill=s['color'][1])
                    canvas.itemconfigure(bgbox[name], outline=s['color'][1])
                    x0,y0,x1,y1 = canvas.bbox(menus[name])
                    x0 -= int(s['padding'][0])
                    x1 += int(s['padding'][0])
                    y0 -= int(s['padding'][1])
                    y1 += int(s['padding'][1])

                    canvas.coords(bgbox[name], (x0,y0,x1,y1))

        if s.get('role') =='number' and s.get("text")=="":
            canvas.itemconfigure(menus[name], text= '0000')

    # for m in menus:
    #     print(m, 'bbox',canvas.bbox(menus[m]))

    if eWin:
        selBlock()

    canvas.photo=arr_img  # phtoimage bug
    canvas.find_all()




#########################################################################################################
############################################## Option Config ############################################
#########################################################################################################
var_option = dict()
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
            message (LANG.get("check_mysql_conf"))
            return False

        for key in ARR_CONFIG['mysql']:
            ARR_CONFIG['mysql'][key] = str(var_option[key].get()).strip()

    try:
        ARR_CONFIG['refresh_interval'] = int(var_option['refresh_interval'].get())
    except:
        message (LANG.get("refresh_time_error"))
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
        # need_restart = True
        if ARR_CONFIG['full_screen'] == "yes":
            # root.overrideredirect(True)
            root.attributes("-fullscreen", True)
            root.resizable (False, False)
        else :
            # root.overrideredirect(False)
            root.attributes("-fullscreen", False)
            root.resizable (True, True)

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
    Button(btnFrame, text=LANG['close_option'], command=closeOption, width=16).pack(side="left", padx=5)
    Button(btnFrame, text=LANG['exit_program'], command=exitProgramOpt, width=16).pack(side="right", padx=5)

    dbFrame = Frame(oWin)
    dbFrame.pack(side="top", pady=10)

    Label(dbFrame, text=LANG['db_server']).grid(row=0, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['user']).grid(row=1, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['password']).grid(row=2, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['charset']).grid(row=3, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['port']).grid(row=4, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['db_name']).grid(row=5, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['refresh_interval']).grid(row=6, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['full_screen']).grid(row=7, column=0, sticky="w", pady=2, padx=4)
    Label(dbFrame, text=LANG['template']).grid(row=8, column=0, sticky="w", pady=2, padx=4)

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
    Button(dbFrame, text=LANG['save_changes'], command=saveOption, width=16).grid(row=9, column=0, columnspan=2)

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
var_screen = dict()
sel_box = None

def selBlock(x=0, y=0):
    global sel_box
    sel =  None
    if sel_box:
        canvas.delete(sel_box)

    if eWin:
        sel = var_screen['label'].get()
    else :
        d_min = 3000
        for m in menus:
            pos = canvas.bbox(menus[m])
            if pos and pos[0] < x <pos[2] and pos[1] <y <pos[3]:
                if min(x - pos[0], pos[2]-x, y-pos[1], pos[3]-y) < d_min:
                    d_min = min(x - pos[0], pos[2]-x, y-pos[1], pos[3]-y)
                    sel = m
        # print (sel, canvas.bbox(menus[sel]))
    if menus.get(sel):
        sel_box = canvas.create_rectangle(canvas.bbox(menus[sel]), outline="yellow", dash=(10,10), width=2)
    return sel
    

def movePos():
    global sel_box
    name = var_screen['label'].get()
    x, y = var_screen['posX'].get(), var_screen['posY'].get()
    if menus.get(name):
        xo, yo =  canvas.coords(menus[name])
        canvas.move(menus[name], int(x) - int(xo), int(y)- int(yo))
        canvas.move(sel_box, int(x) - int(xo), int(y)- int(yo))
        # canvas.moveto(menus[name], int(x), int(y)) # canvas.moveto bug 
        # canvas.moveto(sel_box, int(x), int(y)) # canvas.moveto bug 
        
def sizeFont():
    global sel_box
    name =var_screen['label'].get()
    if menus.get(name):
        ft = font.Font(family=var_screen['fontfamily'].get(), size=var_screen['fontsize'].get(), weight=var_screen['fontshape'].get())
        canvas.itemconfigure(menus[name], font=ft)
        canvas.coords(sel_box, canvas.bbox(menus[name]))

def edit_screen(e):
    global root, eWin, oWin, sel_box
    print (e)
    sel = selBlock(e.x, e.y)
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
    editScreen(sel)
    

def closeEdit():
    global eWin, canvas, sel_box
    canvas.delete(sel_box)
    eWin.destroy()
    eWin = None

scFrame = None
btFrame = None
def editScreen(sel_name):
    global ARR_CONFIG, ARR_SCREEN, eWin, var_screen, scFrame

    Button(eWin, text=LANG['close_option'], command=closeEdit, width=16).pack(side="bottom", padx=5, pady=10)
    Button(eWin, text=LANG['save_changes'], command=saveScreen, width=16).pack(side="bottom")
    var_screen['message_str'] = StringVar()
    Message(eWin, textvariable = var_screen['message_str'], width= 200,  bd=0, relief=SOLID, foreground='red').pack(side="bottom", pady=5)       

    labelFrame = Frame(eWin)
    labelFrame.pack(side="top", pady=10)

    ARR_SCREEN = loadTemplate(ARR_CONFIG['template'])
    listLabels = [x['name'] for x in ARR_SCREEN]

    Label(labelFrame, text=LANG['name']).grid(row=0, column=0, sticky="w", pady=2, padx=4)
    var_screen['label'] = ttk.Combobox(labelFrame, width=18, state="readonly", values=listLabels)
    var_screen['label'].bind("<<ComboboxSelected>>", updateEntry)
    var_screen['label'].grid(row=0, column=1, columnspan=2, sticky="w")
    for j, ft in enumerate(listLabels):
        if sel_name == ft:
            var_screen['label'].current(j)    
            updateEntry(0)
            break

    var_screen['message_str'].set("")

def updateEntry(e):
    global ARR_SCREEN, var_screen, scFrame, btFrame
    role = None

    listFont = {
        'fontfamily':['simhei', 'arial', 'fangsong', 'simsun', 'gulim', 'batang', 'ds-digital','bauhaus 93', 'HP Simplified' ],
        'fontshape': ['normal', 'bold', 'italic'],
        'fgcolor':   ['white', 'black', 'orange', 'blue', 'red', 'green', 'purple', 'grey', 'yellow', 'pink'],
        'bgcolor':   ['white', 'black', 'orange', 'blue', 'red', 'green', 'purple', 'grey', 'yellow', 'pink','transparent'],
        'align':     ['left', 'right', 'center']
    }

    list_keys ={
        'label':    ['text', 'fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor','width',  'posX', 'posY', 'align','role', 'use',],
        'number':   ['fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'posX', 'posY', 'align','role', 'min', 'max', 'use', 'rule'],
        'percent':  ['fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'posX', 'posY', 'align','role', 'rule', 'use',],
        'picture':  ['width', 'height', 'posX', 'posY', 'role', 'url','use',],
        'snapshot': ['width', 'height', 'posX', 'posY', 'role', 'device_info','use',],
        'datetime': ['fontfamily', 'fontsize', 'fontshape', 'fgcolor', 'bgcolor', 'width', 'posX', 'posY', 'align', 'role', 'format', 'use'],

        # 'variable': ['sql', 'use']
    }

    list_roles= ['label', 'number', 'percent', 'datetime', 'picture', 'snapshot', 'video']

    sel = var_screen['label'].get()
    if var_screen.get('role') in list_roles:
        role = var_screen['role'].get()
    # print(sel)
    selBlock(0,0)
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
        if role:
            arr_key = list_keys[role]

        x['fontfamily'],  x['fontsize'], x['fontshape'] = tuple(x['font'])     if x.get('font') else (0,0,0)
        x['fgcolor'], x['bgcolor']                      = tuple(x['color'])    if x.get('color') else (0,0)
        x['width'], x['height']                         = tuple(x['size'])     if x.get('size') else (0,0)
        x['posX'],  x['posY']                           = tuple(x['position']) if x.get('position') else (0,0)
        x['padX'],  x['padY']                           = tuple(x['padding'])  if x.get('padding') else (0,0)
        
        for i, key in enumerate(arr_key):
            var_screen[key]= StringVar()
            cat = None
            if (key=='use'):
                # print (x.get('flag'))
                Label(scFrame, text= LANG[key] if LANG.get(key) else key).grid(row=i+2, column=0, pady=2)
                chk = Checkbutton(scFrame, variable=var_screen[key])
                chk.grid(row=i+2, column=1, sticky="w")
                chk.select() if x.get('flag')=='y' else chk.deselect()

            # elif key == 'fontfamily' or key =='fontshape' or key =='fgcolor' or key == 'bgcolor' or key=='align':
            elif key in ['fontfamily', 'fontshape', 'fgcolor', 'bgcolor', 'align']:
                Label(scFrame, text= LANG[key] if LANG.get(key) else key).grid(row=i+2, column=0, pady=2)
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
                Label(scFrame, text= LANG[key] if LANG.get(key) else key).grid(row=i+2, column=0, pady=2)
                var_screen[key] =  ttk.Combobox(scFrame, width=18, state="readonly", values=list_roles)
                var_screen[key].grid(row=i+2, column=1)
                for j, ft in enumerate(list_roles):
                    # if role :
                    #     if role == ft:
                    #         var_screen[key].current(j)
                    # else :
                        if x.get(key) == ft:
                            var_screen[key].current(j)

                var_screen['role'].bind("<<ComboboxSelected>>", updateEntry)                        

            elif key=='sql' or key == 'rule':
                Label(scFrame, text= LANG[key] if LANG.get(key) else key).grid(row=i+2, column=0, pady=2, sticky='n')
                var_screen[key] = Text(scFrame, width=20, height=5)
                var_screen[key].grid(row=i+2, column=1)
                var_screen[key].insert(1.0, x.get(key))

            elif key == 'url':
                Button(scFrame, command=browseFile, text = LANG[key] if LANG.get(key) else key).grid(row=i+2, column=0, pady=2)
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
                Label(scFrame, text= LANG[key] if LANG.get(key) else key).grid(row=i+2, column=0, pady=2)
                Entry(scFrame, textvariable = var_screen[key], width=22).grid(row=i+2, column=1, columnspan=2)
                cat = 1

            if cat:
                var_screen[key].set(x.get(key))
        var_screen['message_str'].set("")
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
    global ARR_CONFIG, root, canvas, menus
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

            if var_screen.get('fontsize'):
                if not var_screen['fontsize'].get().isnumeric():
                    message("fontsize type error")
                    return False
                arr_template[i]['font']  = [var_screen['fontfamily'].get(), int(var_screen['fontsize'].get()), var_screen['fontshape'].get()]
                arr_template[i]['color'] = [var_screen['fgcolor'].get(), var_screen['bgcolor'].get()]
                
            if var_screen.get('width'):
                if not var_screen['width'].get().isnumeric():
                    message("size type error")
                    return False
                arr_template[i]['size'][0] = int(var_screen['width'].get())
            
            if var_screen.get('height'):
                if not  var_screen['height'].get().isnumeric():
                    message("size type error")
                    return False
                arr_template[i]['size'][1] = int(var_screen['height'].get())
            
            if var_screen.get('align'):
                arr_template[i]['align'] = var_screen['align'].get().strip()

            if var_screen.get('url'):
                arr_template[i]['url'] = var_screen['url'].get().strip()

            if var_screen.get('text'):
                arr_template[i]['text'] = var_screen['text'].get().strip()

            if var_screen.get('rule'):
                arr_template[i]['rule'] = var_screen['rule'].get(1.0, "end").strip()

            if var_screen.get('sql'):
                arr_template[i]['sql'] = var_screen['sql'].get().strip()

            if var_screen.get('max') and var_screen['max'].get().isnumeric():
                if not  var_screen['max'].get().isnumeric():
                    message("Max type error")
                    return False
                arr_template[i]['max'] = int(var_screen['max'].get())

            if var_screen.get('min') and var_screen['min'].get().isnumeric():
                if not  var_screen['min'].get().isnumeric():
                    message("Min type error")
                    return False
                arr_template[i]['min'] = int(var_screen['min'].get())


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
    mainScreen()
