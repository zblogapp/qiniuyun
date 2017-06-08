<?php
include_once dirname(__FILE__) . "/qiniu/rs.php";
include_once dirname(__FILE__) . "/qiniu/io.php";
include_once dirname(__FILE__) . "/qiniu.class.php";
include_once dirname(__FILE__) . "/function.php";
//define('QINIUYUN_WATER_URL', 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/logo_white_fe6da1ec.png');
define('QINIUYUN_WATER_URL', $bloghost . 'zb_users/plugin/qiniuyun/water.png');
$qiniuyun = new QINIUYUN();

RegisterPlugin("qiniuyun", "ActivePlugin_qiniuyun");
function init_qiniuyun() {
    global $qiniuyun;
    $qiniuyun->initialize();
}

function ActivePlugin_qiniuyun() {
    Add_Filter_Plugin('Filter_Plugin_Upload_Url', 'qiniuyun_upload_url');
    Add_Filter_Plugin('Filter_Plugin_Upload_SaveFile', 'qiniuyun_upload_savefile');
    Add_Filter_Plugin('Filter_Plugin_Upload_DelFile', 'qiniuyun_upload_delfile');
    Add_Filter_Plugin('Filter_Plugin_Upload_SaveBase64File', 'qiniuyun_upload_savefile');
}

function qiniuyun_upload_url(&$upload) {
    init_qiniuyun();
    global $zbp;global $qiniuyun;
    $file = $zbp->GetUploadByID($upload->ID);

    $is_url_water = ($qiniuyun->water_enable && !$qiniuyun->water_overwrite);
    $url = $qiniuyun->get_url($file->Metas->qiniuyun_key, $is_url_water);

    if (!$qiniuyun->water_enable) {
        if ($qiniuyun->image_style != '') {
            $url .= '-' . $qiniuyun->image_style;
        }
    }

    return $url;
}

function qiniuyun_upload_savefile($tmp, &$upload) {
    init_qiniuyun();
    global $zbp;global $qiniuyun;
    $file_path = $zbp->usersdir . 'upload/zbp.qiniutmp.' . time() . '.' . mt_rand(0, 9999);
    $file_name = date("Ymd", time()) . mt_rand(1000, 9999) . '_' . mt_rand(0, 10000) . '.' . GetFileExt($upload->SourceName);
    $upload_water = ($qiniuyun->water_enable && $qiniuyun->water_overwrite);

    if (is_file($tmp)) {
        @move_uploaded_file($tmp, $file_path);
    }
    //先上传到本地
    else {
        @file_put_contents($file_path, base64_decode($tmp));
    }

    $upload->Name = $file_name;

    $cloud_path = $qiniuyun->cloudpath . date("Y/m/", time()) . $file_name; //构造云文件名

    $ret = $qiniuyun->upload($cloud_path, $file_path, $upload_water);

    $upload->Metas->qiniuyun_key = $ret['key'];

    unlink($file_path);
    $GLOBALS['Filter_Plugin_Upload_SaveFile']['qiniuyun_upload_savefile'] = PLUGIN_EXITSIGNAL_RETURN;
    $GLOBALS['Filter_Plugin_Upload_SaveBase64File']['qiniuyun_upload_savefile'] = PLUGIN_EXITSIGNAL_RETURN;

    return true;
}

function qiniuyun_upload_savebase64file($str64, &$upload) {
    $s = base64_decode($str64);

}

function qiniuyun_upload_delfile(&$upload) {
    init_qiniuyun();
    global $zbp;
    global $qiniuyun;
    $qiniuyun->delete($upload->Metas->qiniuyun_key);
    $GLOBALS['Filter_Plugin_Upload_DelFile']['qiniuyun_upload_delfile'] = PLUGIN_EXITSIGNAL_RETURN;

    return true;
}

function qiniuyun_thumbnail_url($content) {
    init_qiniuyun();global $qiniuyun;
    //基本逻辑：
    //1. 有七牛，调七牛
    //2. 没七牛，调首图
    //3. 都没图，返空值
    $url = '';
    if (preg_match('/http|https/si', $qiniuyun->domain)) {
        $url = $qiniuyun->domain;
    } else {
        $url = 'http://' . $qiniuyun->domain;
    }
    $pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/i";
    $qiniuyun_pattern = "/<img.*?src=[\'|\"](" . str_replace('/', '\/', preg_quote($url)) . ".*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/i";
    $match_qiniu = null;
    $match = null;

    preg_match_all($qiniuyun_pattern, $content, $match_qiniu);
    preg_match_all($pattern, $content, $match);

    if (isset($match_qiniu[1][0]) > 0) {
        return $qiniuyun->get_thumbnail_url($match_qiniu[1][0], $qiniuyun->thumbnail_quality, $qiniuyun->thumbnail_longedge, $qiniuyun->thumbnail_shortedge, $qiniuyun->thumbnail_cut);
    } elseif (isset($match[1][0]) > 0) {
        return $match[1][0];
    } else {
        return '';
    }

}

function InstallPlugin_qiniuyun() {
}

function UninstallPlugin_qiniuyun() {
}
