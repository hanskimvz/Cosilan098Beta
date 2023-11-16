import sys, time

t = 1699877400
from_t = time.strftime("%Y/%m/%d%%20%H:%M", time.gmtime(t))

print (from_t)


sys.exit()



import tkinter as tk
from PIL import ImageTk, Image
import cv2 as cv


def show_pic(window, imgPath):
    canvas = tk.Canvas(window, width=1280, height=1024, background='gray')
    canvas.pack(padx=20, pady=20)

    image = Image.open(imgPath)
    image = image.resize((800, 800))
    img = ImageTk.PhotoImage(image)
    # img = tk.PhotoImage(image)
    canvas.create_image(100, 100, image=img)


window = tk.Tk()

# canvas = tk.Canvas(window, width=300, height=300, background='gray')
# canvas.pack(padx=20, pady=20)

imgPath = "cam.png"
# show_pic(window, imgPath)

screen_width = 1280
screen_height = 1024
bg_canvas = tk.Canvas(window, width=screen_width, height=screen_height)
bg_img = cv.imread(imgPath)
bg_img = cv.cvtColor(bg_img, cv.COLOR_BGR2RGB)
bg_img = Image.fromarray(bg_img)

bg_img = bg_img.resize((screen_width, screen_height), Image.LANCZOS)
imgtk = ImageTk.PhotoImage(image=bg_img)
# menus[name].create_image(0, 0, anchor="nw", image=imgtk)
# bg_label.configure(image=imgtk)
bg_canvas.create_image(0,0, image=imgtk, anchor="nw")
bg_canvas.photo=imgtk # phtoimage bug
xt = bg_canvas.create_text(500,130, text="HELLO", fill="red", font=('simhei', 20, 'bold'))
bg_canvas.place(x=0, y=0)
bg_canvas.itemconfig(xt,text="Hans Kim")
bg_canvas.move(xt, 400,200)

label = tk.Entry(window, )
bg_canvas.create_window(100,100, window=label)
# image = Image.open("cam.png")
# image = image.resize((800, 800))
# img = ImageTk.PhotoImage(image)
# # img = tk.PhotoImage(image)
# canvas.create_image(100, 100, image=img)

window.mainloop()