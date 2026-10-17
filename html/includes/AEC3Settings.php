<?php
function vbotAEC3Defaults(): array {
    return ['active'=>false, 'library'=>'/home/pi/VBot_AEC3/music_test/libvbot_aec3_music.so',
        'output'=>'linear', 'playback_device'=>'default', 'playback_latency_ms'=>100,
        'capture_delay_ms'=>32, 'render_clock_ppm'=>980, 'capture_gain'=>1.0,
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
        $value[$key]=$item;
    }
    foreach (['output'=>['normal','linear'],'noise_suppression'=>['off','low','moderate','high','very_high']] as $key=>$choices) {
        $item=$post['aec3_'.$key]??$value[$key];
        if (!in_array($item,$choices,true)) throw new InvalidArgumentException('AEC3: '.$key.' không hợp lệ');
        $value[$key]=$item;
    }
    foreach (['capture_gain'=>[0.1,8,false],'wake_gain'=>[0.1,8,false],
        'playback_latency_ms'=>[20,200,true],'capture_delay_ms'=>[0,500,true],
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
