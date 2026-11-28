<?php
function vbotAudioBoardPreset(string $imageFile = '/os_image_created.txt'): ?array {
    $image = @file_get_contents($imageFile, false, null, 0, 4096);
    if ($image === false || trim($image) === '') return null;
    $i2s = stripos($image, 'i2s') !== false;
    return [
        'board'=>$i2s ? 'i2s' : 'wm8960',
        'label'=>$i2s ? 'VBot AIO I2S' : 'VBot AIO WM8960',
        'fields'=>[
            'audio_noise_suppression'=>'low',
            'audio_high_pass_filter'=>true,
            'audio_agc_active'=>true,
            'audio_agc_limiter'=>true,
            'audio_agc_target_level_dbfs'=>6,
            'audio_agc_compression_gain_db'=>9,
            'aec3_output'=>'linear',
            'aec3_capture_gain'=>1,
            'aec3_wake_gain'=>1,
            'aec3_playback_latency_ms'=>100,
            'aec3_capture_delay_ms'=>$i2s ? -16 : 8,
            'aec3_render_clock_ppm'=>0,
        ],
    ];
}

function vbotAEC3Defaults(): array {
    return ['active'=>false, 'library'=>'/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so',
        'output'=>'linear', 'playback_device'=>'default', 'playback_latency_ms'=>100,
        'capture_delay_ms'=>0, 'render_clock_ppm'=>0, 'capture_gain'=>1.0,
        'wake_gain'=>1.0, 'noise_suppression'=>'off', 'bypass_legacy_filters'=>true,
        'external_sources'=>false];
}
function vbotAEC3Settings(array $post, array $old): array {
    $value=array_replace(vbotAEC3Defaults(),$old);
    if (!isset($post['aec3_settings_present'])) return $value;
    $value['active']=isset($post['aec3_active']);
    $value['bypass_legacy_filters']=isset($post['aec3_bypass_legacy_filters']);
    foreach (['library','playback_device'] as $key) {
        $item=trim((string)($post['aec3_'.$key]??$value[$key]));
        if ($item==='' || strpos($item,"\0")!==false) throw new InvalidArgumentException('AEC3: '.$key.' không hợp lệ');
        if ($key==='library' && preg_match('~(?:^|/)music_test/libvbot_aec3_music\.so$~i', $item)) {
            $item='/home/pi/VBot_AEC3/webrtc-2.1/libvbot_aec3_music_2_1.so';
        }
        $value[$key]=$item;
    }
    foreach (['output'=>['normal','linear'],'noise_suppression'=>['off','low','moderate','high','very_high']] as $key=>$choices) {
        $item=$post['aec3_'.$key]??$value[$key];
        if (!in_array($item,$choices,true)) throw new InvalidArgumentException('AEC3: '.$key.' không hợp lệ');
        $value[$key]=$item;
    }
    foreach (['capture_gain'=>[0.1,8,false],'wake_gain'=>[0.1,8,false],
        'playback_latency_ms'=>[20,200,true],'capture_delay_ms'=>[-100,500,true],
        'render_clock_ppm'=>[-3000,3000,true]] as $key=>$limits) {
        $item=filter_var($post['aec3_'.$key]??$value[$key],FILTER_VALIDATE_FLOAT);
        if ($item===false || !is_finite($item) || $item<$limits[0] || $item>$limits[1] || ($limits[2] && floor($item)!=$item))
            throw new InvalidArgumentException('AEC3: '.$key.' ngoài phạm vi');
        $value[$key]=$limits[2]?(int)$item:(float)$item;
    }
    return $value;
}

function vbotAudioProcessingDefaults(): array {
    return ['active'=>true, 'noise_suppression'=>'low', 'high_pass_filter'=>true,
        'agc'=>['active'=>true, 'target_level_dbfs'=>6, 'compression_gain_db'=>9, 'limiter'=>true],
        'aec3'=>vbotAEC3Defaults()];
}

function vbotAudioProcessingSettings(array $post, array $smart): array {
    $defaults=vbotAudioProcessingDefaults();
    $value=array_replace($defaults,$smart['audio_processing']??[]);
    $value['agc']=array_replace($defaults['agc'],$value['agc']??[]);
    $value['aec3']=array_replace(vbotAEC3Defaults(),$smart['audio_processing']['aec3']??($smart['aec3']??[]));
    if (isset($post['audio_processing_present'])) {
        $value['active']=isset($post['audio_processing_active']);
        $value['high_pass_filter']=isset($post['audio_high_pass_filter']);
        $value['agc']['active']=isset($post['audio_agc_active']);
        $value['agc']['limiter']=isset($post['audio_agc_limiter']);
        $level=$post['audio_noise_suppression']??$value['noise_suppression'];
        if (!in_array($level,['off','low','moderate','high','very_high'],true))
            throw new InvalidArgumentException('WebRTC: noise suppression không hợp lệ');
        $value['noise_suppression']=$level;
        foreach (['target_level_dbfs'=>31,'compression_gain_db'=>90] as $key=>$max) {
            $item=filter_var($post['audio_agc_'.$key]??$value['agc'][$key],FILTER_VALIDATE_INT);
            if ($item===false || $item<0 || $item>$max)
                throw new InvalidArgumentException('WebRTC AGC: '.$key.' ngoài phạm vi');
            $value['agc'][$key]=$item;
        }
        // AEC and post-processing share a library but use separate APM instances.
        $post['aec3_noise_suppression']='off';
        $value['aec3']=vbotAEC3Settings($post,$value['aec3']);
    }
    unset($value['aec3']['noise_suppression'],$value['aec3']['bypass_legacy_filters']);
    return $value;
}

function vbotALSAMixerDefaults(): array {
    return [
        'active'=>true,
        'persist_state'=>true,
        'i2s'=>['mic_capture'=>150, 'speaker'=>247],
        'wm8960'=>[
            'capture'=>35,
            'input_boost'=>2,
            'adc_hpf'=>true,
            'alc_off'=>true,
            'speaker'=>123,
            'playback'=>255,
        ],
    ];
}

function vbotALSAMixerPreset(string $imageFile = '/os_image_created.txt'): array {
    $image = @file_get_contents($imageFile, false, null, 0, 4096);
    if ($image !== false && trim($image) !== '') {
        $i2s = stripos($image, 'i2s') !== false;
        $board = $i2s ? 'i2s' : 'wm8960';
        $label = $i2s ? 'VBot AIO I2S' : 'VBot AIO WM8960';
    } else {
        $cards = $imageFile === '/os_image_created.txt'
            ? (string)@file_get_contents('/proc/asound/cards', false, null, 0, 4096)
            : '';
        if ($cards !== '' && stripos($cards, 'sndrpigooglevoi') !== false) {
            $board = 'i2s';
            $label = 'VBot AIO I2S';
        } elseif ($cards !== '' && stripos($cards, 'wm8960soundcard') !== false) {
            $board = 'wm8960';
            $label = 'VBot AIO WM8960';
        } else {
            $board = 'wm8960';
            $label = 'VBot AIO I2S / WM8960';
        }
    }
    return [
        'board'=>$board,
        'label'=>$label,
        'fields'=>[
            'alsa_mixer_persist_state'=>true,
            'alsa_i2s_mic_capture'=>150,
            'alsa_i2s_speaker'=>247,
            'alsa_wm8960_capture'=>35,
            'alsa_wm8960_input_boost'=>2,
            'alsa_wm8960_adc_hpf'=>true,
            'alsa_wm8960_alc_off'=>true,
            'alsa_wm8960_speaker'=>123,
            'alsa_wm8960_playback'=>255,
        ],
    ];
}

function vbotALSAMixerSettings(array $post, array $smart): array {
    $defaults=vbotALSAMixerDefaults();
    $old=is_array($smart['alsa_mixer']??null)?$smart['alsa_mixer']:[];
    $value=[
        'active'=>is_bool($old['active']??null)?$old['active']:$defaults['active'],
        'persist_state'=>is_bool($old['persist_state']??null)?$old['persist_state']:$defaults['persist_state'],
        'i2s'=>array_replace($defaults['i2s'],is_array($old['i2s']??null)?$old['i2s']:[]),
        'wm8960'=>array_replace($defaults['wm8960'],is_array($old['wm8960']??null)?$old['wm8960']:[]),
    ];
    if (!isset($post['alsa_mixer_present'])) return $value;
    $value['active']=isset($post['alsa_mixer_active']);
    $value['persist_state']=isset($post['alsa_mixer_persist_state']);
    foreach ([
        ['i2s','mic_capture','alsa_i2s_mic_capture',0,255,'ALSA I2S VBot_DAC_Microphone phải từ 0 đến 255'],
        ['i2s','speaker','alsa_i2s_speaker',0,255,'ALSA I2S VBot_DAC_Speaker phải từ 0 đến 255'],
        ['wm8960','capture','alsa_wm8960_capture',0,63,'ALSA WM8960 Capture phải từ 0 đến 63'],
        ['wm8960','input_boost','alsa_wm8960_input_boost',0,3,'ALSA WM8960 Input Boost phải từ 0 đến 3'],
        ['wm8960','speaker','alsa_wm8960_speaker',0,127,'ALSA WM8960 Speaker phải từ 0 đến 127'],
        ['wm8960','playback','alsa_wm8960_playback',0,255,'ALSA WM8960 Playback phải từ 0 đến 255'],
    ] as [$group,$key,$postKey,$min,$max,$errMsg]) {
        $raw=$post[$postKey]??$value[$group][$key];
        $item=filter_var($raw,FILTER_VALIDATE_INT);
        if ($item===false || $item<$min || $item>$max) throw new InvalidArgumentException($errMsg);
        $value[$group][$key]=(int)$item;
    }
    $value['wm8960']['adc_hpf']=isset($post['alsa_wm8960_adc_hpf']);
    $value['wm8960']['alc_off']=isset($post['alsa_wm8960_alc_off']);
    return $value;
}
