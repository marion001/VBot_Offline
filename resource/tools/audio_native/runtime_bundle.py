"""Pack/check/install VBot native runtime; no recorder, SIP endpoint or Config writes."""
import argparse
import ctypes
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import platform
import re
import shutil
import stat
import subprocess
import sys
import sysconfig
import tempfile
import uuid
import zipfile

WRAPPER='webrtc-2.1/libvbot_aec3_music_2_1.so'
MANIFEST='runtime-manifest.json'


def environment():
    release={}
    for line in Path('/etc/os-release').read_text().splitlines():
        if '=' in line:
            key,value=line.split('=',1);release[key]=value.strip('"')
    return dict(machine=platform.machine(),bits=ctypes.sizeof(ctypes.c_void_p)*8,
        architecture=subprocess.check_output(['dpkg','--print-architecture'],text=True).strip(),
        python=f'{sys.version_info.major}.{sys.version_info.minor}',soabi=sysconfig.get_config_var('SOABI'),
        os_id=release.get('ID'),os_version=release.get('VERSION_ID'),libc=list(platform.libc_ver()),
        neon=bool(re.search(r'\bneon\b',Path('/proc/cpuinfo').read_text().lower())))


def check_platform(saved,current):
    for key in ('machine','bits','architecture','python','soabi','os_id','os_version'):
        if saved.get(key)!=current.get(key):raise RuntimeError(f'Incompatible {key}: bundle={saved.get(key)}, device={current.get(key)}')
    version=lambda value:tuple(int(n) for n in re.findall(r'\d+',value))
    if saved['libc'][0]!=current['libc'][0] or version(current['libc'][1])<version(saved['libc'][1]):
        raise RuntimeError('Device libc is older or incompatible; build on the target OS')
    if saved.get('neon') and not current.get('neon'):raise RuntimeError('Device CPU must support NEON')


def safe_name(name):
    p=PurePosixPath(name)
    return bool(name and p.as_posix()==name and '\\' not in name and not p.is_absolute() and '..' not in p.parts
        and (name==WRAPPER or name.startswith(('pjsip/python/','webrtc-2.1/vendor/lib/','tools/'))))


def load_manifest(archive):
    # Check declared sizes before reading/decompressing any member.
    if archive.getinfo(MANIFEST).file_size>1024*1024:raise RuntimeError('Runtime manifest is too large')
    if sum(i.file_size for i in archive.infolist())>512*1024*1024:raise RuntimeError('Runtime ZIP is too large')
    manifest=json.loads(archive.read(MANIFEST))
    if manifest.get('format')!=1 or not isinstance(manifest.get('files'),dict):raise RuntimeError('Invalid manifest')
    names=archive.namelist()
    if len(names)!=len(set(names)) or set(names)!=set(manifest['files'])|{MANIFEST}:raise RuntimeError('Unexpected ZIP members')
    if WRAPPER not in manifest['files'] or 'pjsip/python/pjsua2.py' not in manifest['files']:raise RuntimeError('Missing runtime components')
    if not any(n.startswith('pjsip/python/_pjsua2') and n.endswith('.so') for n in manifest['files']):raise RuntimeError('Missing Python native binding')
    if sum(i.file_size for i in archive.infolist())>512*1024*1024:raise RuntimeError('Runtime ZIP is too large')
    for name,digest in manifest['files'].items():
        if not safe_name(name) or not re.fullmatch('[a-f0-9]{64}',digest):raise RuntimeError('Invalid runtime path/checksum')
        info=archive.getinfo(name);mode=stat.S_IFMT(info.external_attr>>16)
        if info.is_dir() or mode not in (0,stat.S_IFREG):raise RuntimeError('ZIP must contain regular files')
    for package in manifest.get('system_packages',[]):
        if not re.fullmatch(r'[a-z0-9][a-z0-9+.-]*(?::[a-z0-9]+)?',package):raise RuntimeError('Invalid package name')
    return manifest


def native_files(root):
    result={WRAPPER:root/WRAPPER}
    directory=root/'webrtc-2.1/vendor/lib'
    for p in directory.glob('*.so*'):
        if p.is_file():result[p.relative_to(root).as_posix()]=p
    for p in (root/'pjsip/python').rglob('*'):
        if p.is_file() and '__pycache__' not in p.parts and not p.name.endswith(('.pyc','.pyo')):
            if p.suffix in ('.py','.so') or any(x.endswith('.dist-info') for x in p.parts):result[p.relative_to(root).as_posix()]=p
    if not any(n.startswith('webrtc-2.1/vendor/lib/') for n in result):raise RuntimeError('Missing WebRTC vendor shared libraries')
    for name,p in result.items():
        if not p.is_file() or root not in p.resolve().parents:raise RuntimeError('Missing/outside runtime file: '+name)
    return result


def check_native(root):
    code="""import ctypes,sys
from pathlib import Path
r=Path(sys.argv[1]);lib=ctypes.CDLL(str(r/'webrtc-2.1/libvbot_aec3_music_2_1.so'))
lib.vbot_apm_version.restype=ctypes.c_char_p
assert lib.vbot_apm_version()==b'2.1'
assert lib.vbot_aec_abi()==1
for name in ('vbot_mic_create','vbot_aec_create','vbot_aec_render','vbot_aec_capture','vbot_aec_destroy'):getattr(lib,name)
sys.path.insert(0,str(r/'pjsip/python'))
import pjsua2
for name in ('Endpoint','Account','Call','AudioMediaPort'):assert hasattr(pjsua2,name)
print('WebRTC 2.1 + PJSUA2 import OK')
"""
    subprocess.run([sys.executable,'-c',code,str(root)],check=True)


def dependency_packages(files):
    packages=set();reports={}
    for name,p in files.items():
        if '.so' not in p.name:continue
        result=subprocess.run(['ldd',str(p)],capture_output=True,text=True)
        if result.returncode or 'not found' in result.stdout:raise RuntimeError('Unresolved native dependencies: '+name+'\n'+result.stdout+result.stderr)
        reports[name]=result.stdout
        for path in re.findall(r'(/[^\s()]+)',result.stdout):
            for candidate in (path,str(Path(path).resolve())):
                query=subprocess.run(['dpkg-query','-S',candidate],capture_output=True,text=True)
                if query.returncode:continue
                for line in query.stdout.splitlines():
                    package=line.split(': ',1)[0]
                    if re.fullmatch(r'[a-z0-9][a-z0-9+.-]*(?::[a-z0-9]+)?',package):packages.add(package)
                break
    return sorted(packages),reports


def pack(root,output):
    files=native_files(root);check_native(root)
    packages,reports=dependency_packages(files)
    versions=[]
    for name,path in files.items():
        if name.endswith('.dist-info/METADATA'):
            match=re.search(r'^Version: (.+)$',path.read_text(errors='replace'),re.M)
            if match:versions.append(match.group(1).strip())
    files['tools/runtime_bundle.py']=Path(__file__).resolve()
    manifest=dict(format=1,created_utc=datetime.now(timezone.utc).isoformat(),environment=environment(),
                  webrtc='2.1',pjsua2_distribution_version=versions[0] if len(versions)==1 else 'not recorded',
                  system_packages=packages,ldd=reports,files={})
    output=output.absolute()
    if output.exists():raise RuntimeError('Output already exists; choose a new ZIP name')
    output.parent.mkdir(parents=True,exist_ok=True)
    temporary=output.with_name(output.name+'.new')
    try:
        with zipfile.ZipFile(temporary,'w',zipfile.ZIP_DEFLATED) as archive:
            for name,path in files.items():
                data=path.read_bytes();manifest['files'][name]=hashlib.sha256(data).hexdigest()
                archive.writestr(name,data)  # Includes SONAME aliases as regular files.
            archive.writestr(MANIFEST,json.dumps(manifest,indent=2))
        os.replace(temporary,output)
    finally:
        if temporary.exists():temporary.unlink()
    digest=hashlib.sha256(output.read_bytes()).hexdigest()
    output.with_name(output.name+'.sha256').write_text(digest+'  '+output.name+'\n')
    print('PACKED',output,'FILES',len(files))


def unpack_checked(archive,manifest,stage):
    for name,digest in manifest['files'].items():
        data=archive.read(name)
        if hashlib.sha256(data).hexdigest()!=digest:raise RuntimeError('Checksum mismatch: '+name)
        target=stage/name;target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(data)


def ensure_stopped():
    for process in Path('/proc').glob('[0-9]*'):
        try:args=[x.decode(errors='replace') for x in (process/'cmdline').read_bytes().split(b'\0') if x]
        except OSError:continue
        if any(Path(x).name in ('Start.py','VBot.py') for x in args[1:]) or any('calls_pjsip_test/run.py' in x for x in args[1:]):
            raise RuntimeError('Stop VBot and call test programs before installing')


def install(stage,manifest,root):
    import fcntl
    ensure_stopped();root.mkdir(parents=True,exist_ok=True)
    lock_path=root/'.runtime-install.lock'
    if lock_path.is_symlink():raise RuntimeError('Install lock must not be a symlink')
    with lock_path.open('a') as lock:
        fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
        backup=root/'runtime_backups'/(datetime.now().strftime('%Y%m%d_%H%M%S')+'_'+uuid.uuid4().hex[:8])
        if root not in backup.resolve().parents:raise RuntimeError('Backup escapes library root')
        backup.mkdir(parents=True);changed=[]
        try:
            for name in manifest['files']:
                target=root/name
                if root not in target.resolve().parents:raise RuntimeError('Target escapes library root')
                previous=backup/name
                if target.exists() or target.is_symlink():
                    previous.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(target,previous,follow_symlinks=False)
                else:previous=None
                changed.append((target,previous));target.parent.mkdir(parents=True,exist_ok=True)
                descriptor,temporary_name=tempfile.mkstemp(prefix=target.name+'.runtime-',dir=target.parent)
                temporary=Path(temporary_name)
                try:
                    with os.fdopen(descriptor,'wb') as output, (stage/name).open('rb') as source:
                        shutil.copyfileobj(source,output)
                        output.flush();os.fsync(output.fileno())
                    temporary.chmod(0o755 if '.so' in target.name else 0o644)
                    os.replace(temporary,target)
                finally:
                    if temporary.exists():temporary.unlink()
            check_native(root)
            (backup/'installed-files.json').write_text(json.dumps(list(manifest['files']),indent=2))
        except BaseException:
            for target,previous in reversed(changed):
                if target.exists() or target.is_symlink():target.unlink()
                if previous is not None:shutil.copy2(previous,target,follow_symlinks=False)
            raise
        print('INSTALLED',root,'BACKUP',backup)


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command',choices=['pack','inspect','packages','check-platform','verify','install'])
    parser.add_argument('archive',type=Path)
    parser.add_argument('--root',type=Path,default=Path('/home/pi/VBot_AEC3'))
    args=parser.parse_args();root=args.root.resolve()
    if args.command=='pack':pack(root,args.archive);return
    with zipfile.ZipFile(args.archive) as archive:
        manifest=load_manifest(archive)
        if args.command=='inspect':print(json.dumps({k:v for k,v in manifest.items() if k!='files'},indent=2));return
        if args.command=='packages':print(' '.join(manifest.get('system_packages',[])));return
        check_platform(manifest['environment'],environment())
        if args.command=='check-platform':print('PLATFORM_COMPATIBLE');return
        with tempfile.TemporaryDirectory(prefix='vbot-native-') as folder:
            stage=Path(folder);unpack_checked(archive,manifest,stage);check_native(stage)
            if args.command=='install':install(stage,manifest,root)
            else:print('VERIFIED_RUNTIME_ARCHIVE')


if __name__=='__main__':
    try:main()
    except Exception as error:raise SystemExit('ERROR: '+str(error))
