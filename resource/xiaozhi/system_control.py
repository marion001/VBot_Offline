"""Built-in MCP controls. Only saved targets; no raw commands or caller paths."""
import asyncio
import copy
import hashlib
import json
from pathlib import Path
import re
import uuid

ROOT = Path(__file__).resolve().parents[2]
CATALOG = Path(__file__).with_name('system_tools.json')


def merge_tools(data):
    data = copy.deepcopy(data)
    if not isinstance(data, dict) or not isinstance(data.get('tools'), list):
        return data
    names = {t.get('name') for t in data['tools'] if isinstance(t, dict)}
    for tool in json.loads(CATALOG.read_text(encoding='utf-8'))['tools']:
        if tool['name'] not in names:
            data['tools'].append(tool)
    extensions=json.loads(CATALOG.read_text(encoding='utf-8')).get('extensions',{})
    for tool in data['tools']:
        extension=extensions.get(tool.get('name')) if isinstance(tool,dict) else None
        if not extension:continue
        suffix=extension.get('description_append','')
        if suffix and suffix not in tool.get('description',''):tool['description']=tool.get('description','')+' '+suffix
        schema=tool.setdefault('inputSchema',{})
        schema.setdefault('properties',{}).update(copy.deepcopy(extension.get('properties',{})))
        actions=schema['properties'].get('action',{}).get('enum',[])
        for action in extension.get('actions_add',[]):
            if action not in actions:actions.append(action)
    return data


def scheduled_actions(data,kind=None):
    rows=[]
    parent=data.get('vbot_action',{})
    if not isinstance(parent,dict) or not isinstance(parent.get('schedules',[]),list):return rows
    for row in parent.get('schedules',[]):
        if not isinstance(row,dict):continue
        slots=row.get('slots')
        slot=slots[0] if isinstance(slots,list) and slots and isinstance(slots[0],dict) else {}
        value=str(slot.get('parameter',row.get('parameter','')))
        detected='routine' if value.startswith('routine:') else 'wake_on_lan' if value.startswith('wol:') else None
        if detected and (kind is None or kind==detected):
            rows.append(dict(row,kind=detected))
    return rows


def scheduler_control(args,lib,build_notification):
    """Persist native Scheduler vbot_action entries; never execute while creating."""
    require_enabled('_VBot_Scheduler',lib)
    kind=args.get('kind');action=args.get('action')
    if kind not in ('routine','wake_on_lan'):raise ValueError('Chọn kind=routine hoặc wake_on_lan')
    if action in ('create','targets'):
        if kind=='routine':
            import Voice_Routines as routines
            if not routines.is_enabled():raise ValueError('Kịch bản nhiều bước đang tắt')
            targets=routines.load()['routines'];prefix='routine:'
        else:
            import Wake_On_LAN as wol
            data=wol.load()
            if not data['active']:raise ValueError('Wake-on-LAN đang tắt')
            targets=data['devices'];prefix='wol:'
        if action=='targets':
            return result(action,[{k:row[k] for k in ('id','name','enabled')} for row in targets],'Các mục đã cấu hình')
        selected=target(targets,{'id':args.get('target_id'),'name':args.get('target_name')})
        if not selected.get('enabled'):raise ValueError('Mục được chọn đang tắt')
        if args.get('home_assistant_control') is not None or args.get('repeat',1)!=1:
            raise ValueError('Lịch chạy kịch bản/Wake-on-LAN không kèm Home Assistant và repeat phải bằng 1')
        if 'active' in args and type(args['active']) is not bool:raise ValueError('active cần true/false')
        notification=build_notification(dict(args,kind='reminder',message=selected['name'],repeat=1))
        if notification['recurrence']['type']=='once':
            from datetime import datetime
            due=datetime.fromisoformat(notification['recurrence']['date']+'T'+notification['time'][0])
            if due<datetime.now().replace(second=0,microsecond=0):raise ValueError('Thời điểm lập lịch đã qua')
        parameter=prefix+selected['id']
        new=dict(notification,slots=[{'time':notification['time'][0],'parameter':parameter}],parameter=parameter,max_duration_seconds=0)
        new.pop('data',None)
    import Lib_System
    path=Path(lib.schedule_data_json_file)
    path.parent.mkdir(parents=True,exist_ok=True)
    with lib.schedule_data_lock, Lib_System._json_lock_for(str(path)), Lib_System._interprocess_json_lock(str(path)):
        if path.exists() and path.stat().st_size>4*1024*1024:raise ValueError('Dữ liệu Scheduler vượt quá 4 MB')
        data=json.loads(path.read_text(encoding='utf-8-sig')) if path.exists() else {}
        if not isinstance(data,dict):raise ValueError('Dữ liệu Scheduler không hợp lệ')
        parent=data.get('vbot_action',{})
        if not isinstance(parent,dict) or not isinstance(parent.get('schedules',[]),list):raise ValueError('Danh sách lịch tác vụ không hợp lệ')
        rows=parent.setdefault('schedules',[])
        if action=='list':return result(action,scheduled_actions(data,kind),'Danh sách lịch tác vụ')
        if action=='create':
            if len(rows)>=300:raise ValueError('Tối đa 300 lịch tác vụ hệ thống')
            if parent.get('time') and not rows:raise ValueError('Hãy chuyển lịch tác vụ cũ sang lịch độc lập trên Scheduler.php trước')
            if any(row.get('name')==new['name'] for row in rows if isinstance(row,dict)) or any(
                    row.get('name')==new['name'] for row in data.get('notification_schedule',[]) if isinstance(row,dict)):
                raise ValueError('Tên lịch đã tồn tại; hãy chọn tên khác')
            if parent.get('active') is False:
                rows[:]=[dict(row,active=False) for row in rows]
            rows.append(new);parent['active']=True;selected_schedule=new
        elif action in ('delete','set_active'):
            selected_schedule=target(scheduled_actions(data,kind),args)
            index=next(i for i,row in enumerate(rows) if row['id']==selected_schedule['id'])
            if action=='delete':rows.pop(index)
            else:
                if type(args.get('active')) is not bool:raise ValueError('active cần true/false')
                rows[index]['active']=args['active']
                if args['active']:
                    if parent.get('active') is False:
                        for i,row in enumerate(rows):
                            if i!=index:row['active']=False
                    parent['active']=True
                selected_schedule=dict(rows[index],kind=kind)
        else:raise ValueError('Thao tác Scheduler không hợp lệ')
        data['vbot_action']=parent
        Lib_System.atomic_write_json(str(path),data,indent=2,_process_lock=False)
    return result(action,selected_schedule,'Đã lưu lịch tác vụ. Scheduler '+('đang bật' if getattr(lib,'schedule_active',False) else 'đang tắt; cần bật để chạy'))


def require_enabled(name, lib):
    if not lib.config.get('xiaozhi', {}).get('mcp_system_control'):
        raise ValueError('MCP hệ thống đang tắt')
    config = lib.load_json_file(lib.config['xiaozhi']['call_tools_file']) or {}
    enabled = config.get('enabledTools')
    if not isinstance(enabled, list):
        enabled = [t.get('name') for t in config.get('tools', []) if isinstance(t, dict) and t.get('active') is True]
    if name not in enabled:
        raise ValueError('MCP '+name+' đang tắt; bật trong XiaoZhi_MCP.php')


def result(action, data=None, message='Đã thực hiện', ok=True):
    return {'content': [{'type':'text','text':message+' '+json.dumps(data,ensure_ascii=False)}],
            'structuredContent': {'action':action,'success':ok,'data':data}, 'isError':not ok}


def target(rows, args):
    identifier, name = args.get('id'), args.get('name')
    if not identifier and not name:
        raise ValueError('Cần id hoặc tên chính xác; hãy liệt kê trước')
    matches = [row for row in rows if row.get('id') == identifier] if identifier else [
        row for row in rows if str(row.get('name','')).strip().casefold()==str(name).strip().casefold()]
    if len(matches)!=1:
        raise ValueError('Không tìm thấy mục duy nhất; hãy liệt kê và chọn đúng ID')
    return matches[0]


def calendar_change(args):
    import Calendar_Events as events
    import Lib_System
    action = args['action']
    path = events.DEFAULT_FILE
    path.parent.mkdir(parents=True,exist_ok=True)
    # Same sidecar lock as Calendar_Events.php. Preserve every unrelated event.
    with events._events_file_lock, Lib_System._json_lock_for(str(path)), Lib_System._interprocess_json_lock(str(path)):
        if path.exists() and path.stat().st_size>4*1024*1024:
            raise ValueError('Tệp sự kiện vượt quá 4 MB')
        document = json.loads(path.read_text(encoding='utf-8-sig')) if path.exists() else {'version':1,'events':[]}
        rows = document.get('events') if isinstance(document,dict) else None
        if not isinstance(rows,list) or len(rows)>events.MAX_EVENTS or any(events.validate_event(row) is None for row in rows):
            raise ValueError('Dữ liệu sự kiện không hợp lệ; chưa ghi thay đổi')
        if action=='create':
            date = str(args.get('date',''))
            if not re.fullmatch(r'\d{4}-\d{2}-\d{2}',date):
                raise ValueError('date phải có dạng YYYY-MM-DD')
            year,month,day = map(int,date.split('-'))
            row = events.validate_event({'id':'mcp_'+uuid.uuid4().hex[:16], 'name':args.get('name'),
                'calendar':args.get('calendar','solar'),'year':year,'months':[month],'days':[day],
                'recurrence':args.get('recurrence','once'),'times':args.get('times',[]),
                'description':args.get('description',''), 'active':True,
                'notification':{'active':True,'before_days':[0]}, 'execution':{'active':False}})
            if row is None or len(rows)>=events.MAX_EVENTS:
                raise ValueError('Sự kiện không hợp lệ hoặc đã đủ 512 mục')
            rows.append(row)
        else:
            row = target(rows,args)
            if action=='delete':rows.remove(row)
            elif action=='set_active':
                if type(args.get('active')) is not bool:raise ValueError('active cần true/false')
                row['active']=args['active']
            else:raise ValueError('Thao tác sự kiện không hợp lệ')
        events._backup_events_file(path)
        Lib_System.atomic_write_json(str(path),document,indent=2,_process_lock=False)
        events.load_events(path)
        return result(action,{'id':row['id'],'name':row['name']},'Đã cập nhật sự kiện')


async def execute(name, args, lib):
    require_enabled(name,lib)
    action = args.get('action')
    if name=='_VBot_HASS_Custom':
        if not getattr(lib,'home_assistant_active',False) or not getattr(lib,'hass_custom_commands_active',False):
            raise ValueError('Home Assistant hoặc Custom HASS đang tắt')
        if action not in ('list','run'):
            raise ValueError('Thao tác Custom HASS không hợp lệ')
        import Def_Processing as processing
        path=Path(lib.hass_custom_command_file)
        if not path.is_absolute():path=ROOT/path
        def read_commands():
            with path.open('rb') as handle:raw=handle.read(10*1024*1024+1)
            if len(raw)>10*1024*1024:raise ValueError('Tệp Custom HASS vượt quá 10 MB')
            data=json.loads(raw.decode('utf-8-sig'))
            if not isinstance(data,dict) or not isinstance(data.get('intents'),list):
                raise ValueError('Dữ liệu Custom HASS không hợp lệ')
            rows=[]
            for intent in processing.load_custom_homeassistant_commands(data):
                identity=json.dumps([intent['name'],intent['data_yaml']],sort_keys=True,ensure_ascii=False)
                rows.append(dict(intent,id='hass_'+hashlib.sha256(identity.encode()).hexdigest()[:24]))
            return rows
        rows=await asyncio.to_thread(read_commands)
        if action=='list':
            return result(action,[{'id':row['id'],'name':row['name'],
                                   'active':bool(row.get('active',True)),
                                   'questions':row.get('questions',[])} for row in rows])
        row=target(rows,args)
        if not row.get('active',True):raise ValueError('Tác vụ Custom HASS này đang tắt')
        ok,message=await processing.control_custom_home_assistant_async(copy.deepcopy(row['data_yaml']))
        return result(action,{'id':row['id'],'name':row['name']},message,ok)
    if name=='_VBot_Calendar_Control':
        if not lib.config.get('calendar',{}).get('events',{}).get('active'):
            raise ValueError('Calendar Events đang tắt')
        return await asyncio.to_thread(calendar_change,args)
    if name=='_VBot_Voice_Routines':
        import Voice_Routines as routines
        if not routines.is_enabled():raise ValueError('Kịch bản nhiều bước đang tắt')
        if action=='list':
            data=await asyncio.to_thread(routines.load)
            return result(action,[{k:row[k] for k in ('id','name','enabled')} for row in data['routines']])
        if action=='status':return result(action,routines.snapshot())
        if action=='cancel':return result(action,{'cancelled':routines.cancel()})
        if action!='run':raise ValueError('Thao tác kịch bản không hợp lệ')
        data=await asyncio.to_thread(routines.load)
        row=target(data['routines'],args)
        ok,message=await routines.run_by_id(row['id'],source='xiaozhi_mcp')
        return result(action,{'id':row['id']},message,ok)
    if name=='_VBot_Wake_On_LAN':
        import Wake_On_LAN as wol
        data=await asyncio.to_thread(wol.load)
        if not data['active']:raise ValueError('Wake-on-LAN đang tắt')
        if action=='list':return result(action,[{k:row[k] for k in ('id','name','enabled')} for row in data['devices']])
        if action!='wake':raise ValueError('Thao tác Wake-on-LAN không hợp lệ')
        row=target(data['devices'],args)
        message=await asyncio.to_thread(wol.send,row['id'])
        return result(action,{'id':row['id']},message)
    if name=='_VBot_LAN_Calls':
        import LAN_Calls as calls
        if lib.config.get('lan_calls',{}).get('active') is not True:raise ValueError('Gọi LAN đang tắt')
        state=calls.snapshot()
        if action in ('list','status'):return result(action,state)
        if action=='call':
            row=target(state.get('peers',[]),args)
            state=await asyncio.to_thread(calls.control,'call',row['id'])
        elif action in ('accept','reject','end'):
            state=await asyncio.to_thread(calls.control,action)
        elif action=='mute':
            if type(args.get('muted')) is not bool:raise ValueError('muted cần true/false')
            state=await asyncio.to_thread(calls.control,'mute',muted=args['muted'])
        else:raise ValueError('Thao tác gọi LAN không hợp lệ')
        answer=result(action,state,state.get('message','Đã gửi lệnh'))
        if action in ('call','accept'):answer['_vbot_lan_handoff']=True
        return answer
    if name=='_VBot_Internal_IR':
        import Def_Processing as processing
        if not getattr(lib,'internal_ir_active',False) or not getattr(lib,'internal_ir_tx_active',False):
            raise ValueError('Phát IR nội bộ đang tắt')
        await asyncio.to_thread(processing._reload_internal_ir_commands_if_changed)
        rows=[dict(row,name=key,id=key) for key,row in lib.internal_ir_command_map.items()]
        if action=='list':return result(action,[{'id':row['id'],'name':row['name']} for row in rows])
        if action!='send':raise ValueError('Thao tác IR không hợp lệ')
        row=target(rows,args)
        await processing.send_internal_ir_command_async(row)
        return result(action,{'name':row['name']},'Đã gửi lệnh IR')
    if name=='_VBot_TuyaLocal':
        import Local_Tuya as tuya
        if lib.config.get('local_tuya',{}).get('active') is not True:
            raise ValueError('Tuya Local đang tắt trong cấu hình VBot')
        rows=await asyncio.to_thread(tuya.list_enabled_entries)
        if action=='list_devices':
            return result(action,rows,'Các Entry Tuya Local đang bật')
        if action=='find_device':
            query=' '.join(tuya._normalize(str(args.get('query') or '').strip()))
            if not query:raise ValueError('find_device yêu cầu query')
            matches=[row for row in rows if query in ' '.join(tuya._normalize(
                f"{row['device_name']} {row['entry_name']} {row['device_id']} {row['entry_key']} {row['dps_id']}"))]
            return result(action,matches[:50],f'Tìm thấy {len(matches)} Entry Tuya Local đang bật')
        if action!='control':raise ValueError('Thao tác Tuya Local không hợp lệ')
        device_id=str(args.get('device_id') or '').strip()
        entry_key=str(args.get('entry_key') or '').strip()
        command=str(args.get('command') or '').strip().lower()
        if not device_id or not entry_key:raise ValueError('control yêu cầu device_id và entry_key chính xác')
        if command not in ('turn_on','turn_off'):raise ValueError('Tuya Local chỉ hỗ trợ turn_on hoặc turn_off')
        selected=next((row for row in rows if row['device_id']==device_id and row['entry_key']==entry_key),None)
        if selected is None:raise ValueError('Thiết bị hoặc Entry không được chọn, đã tắt, hoặc không phải DPS Boolean')
        ok,message=await asyncio.to_thread(tuya.execute_entry,device_id,entry_key,command=='turn_on')
        return result(action,{'device_id':device_id,'entry_key':entry_key,'dps_id':selected['dps_id'],
                              'device_name':selected['device_name'],'entry_name':selected['entry_name'],
                              'command':command},message,ok)
    raise ValueError('MCP chưa được hỗ trợ')
