#!/usr/bin/python3

import argparse
import os
from pathlib import Path
import tempfile

class PW:
    PWFILE = str(Path(__file__).resolve().parent / "crypto")

    def __init__(self):
        self.password = self.getPassword()


    def getPassword(self):
        #if crypto file exists but password is empty string - return None as if file did not exist
        try:
            with open(PW.PWFILE, 'r', encoding="utf-8") as f:
                pw = f.readline().rstrip()
                return pw if len(pw) > 0 else None     
        except Exception:
            return None
    
    def savePassword(self,pw):
        if pw is not None:
            parent = os.path.dirname(os.path.abspath(PW.PWFILE))
            os.makedirs(parent, exist_ok=True)
            fd, tmp_path = tempfile.mkstemp(dir=parent, prefix=".crypto-")
            try:
                with os.fdopen(fd, 'w', encoding="utf-8") as f:
                    f.write(pw)
                    f.flush()
                    os.fsync(f.fileno())
                os.chmod(tmp_path, 0o600)
                os.replace(tmp_path, PW.PWFILE)
            finally:
                if os.path.exists(tmp_path):
                    os.unlink(tmp_path)

    def userPassword(self):
        new_password = ""
        done_once = False
        while len(new_password) < 4:
            print("\nNote:password must be 4 characters min, leading and trailing blanks are removed.")
            if done_once: print("\npassword invalid! - please try again.")
            new_password = input("Please enter a password [X to quit]:").strip()
            if new_password.lower() == "x": 
                print("password was not changed")
                return
            done_once = True
        print(f"New password is: {new_password}")
        self.savePassword(new_password)


if __name__ == "__main__":
    pwc = PW()
    if pwc.password is None:
        print("Password is not set yet.")
        pwc.userPassword()
    else:
        print(f"current password is: {pwc.password}")
        answer = input("Do you want to change it? [y/n]")
        if answer and (answer[0].lower() == 'y'):
             pwc.userPassword()
        else :
             print("password was not changed")