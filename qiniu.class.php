<?php
class QINIUYUN {
    private $data = array();
    private $cfg = null;
    private $is_init = false;

    public $lastError = null;

    public function __construct() {
    }

    public function __set($name, $value) {
        global $zbp;
        if ($name == 'cfg') {
            $this->cfg = $value;
        } elseif (!is_null($this->cfg->$name)) {
            $this->cfg->$name = $value;
        } elseif (in_array($name, $this->data)) {
            $this->data[$name] = $value;
        } else {
            return false;
        }

        if ($name == 'access_token' || $name == 'secret_key') {
            Qiniuyun_SetKeys($this->access_token, $this->secret_key);
        }

        return true;
    }

    public function __get($name) {
        if ($name == 'cfg') {
            return $this->cfg;
        } elseif ($name == 'domain') {
            return $this->get_domain();
        } elseif (!is_null($this->cfg->$name)) {
            return $this->cfg->$name;
        } elseif (in_array($name, $this->data)) {
            return $this->data[$name];
        } else {
            return '';
        }

    }

    public function initialize() {
        if ($this->is_init) {
            return;
        }

        global $zbp;
        $this->cfg = $zbp->Config('qiniuyun');

        if ($this->cfg->version == '1.0') {
            $this->init_1_1_config();
        } elseif ($this->cfg->version == '1.1') {
            // do nothing
        } else {
            $this->init_config();
        }

        Qiniuyun_SetKeys($this->access_token, $this->secret_key);
        $this->is_init = true;

        return true;
    }

    public function init_1_1_config() {
        $this->cfg->version = '1.1';
        $this->cfg->upload_domain = 'http://upload.qiniu.com';

        return $this->save_config();
    }

    public function init_config() {
        $this->cfg->access_token = '';
        $this->cfg->secret_key = '';
        $this->cfg->bucket = '';
        $this->cfg->domain = '';
        $this->cfg->cloudpath = '';
        $this->cfg->water_enable = false;
        $this->cfg->water_overwrite = false;
        $this->cfg->water_dissolve = '100';
        $this->cfg->water_gravity = 'SouthEast';
        $this->cfg->water_dx = '10';
        $this->cfg->water_dy = '10';
        $this->cfg->thumbnail_quality = '85';
        $this->cfg->thumbnail_longedge = '300';
        $this->cfg->thumbnail_shortedge = '300';
        $this->cfg->thumbnail_cut = false;
        $this->cfg->upload_domain = 'http://upload.qiniu.com';

        $this->cfg->version = '1.1';

        return $this->save_config();

    }

    public function save_config() {
        return $GLOBALS['zbp']->SaveConfig('qiniuyun');
    }

    private function get_domain() {
        return ($this->cfg->domain == '' ? $this->bucket . '.qiniudn.com' : $this->cfg->domain);
    }

    public function get_url($key, $water = false) {
        $return = Qiniuyun_RS_MakeBaseUrl($this->domain, $key);
        if ($water && Qiniuyun_test_image($return)) {
            $return = $this->get_waterimage_url($return, QINIUYUN_WATER_URL, $this->water_dissolve, $this->water_gravity, $this->water_dx, $this->water_dy);
        }

        return $return;
    }

    public function delete($key) {
        $client = new Qiniuyun_MacHttpClient(null);

        return QINIUYUN_RS_Delete($client, $this->bucket, $key);
    }

    public function upload($filepath_cloud, $filepath_local, $watermark = false) {
        $GLOBALS['QINIUYUN_UP_HOST'] = $this->cfg->upload_domain;
        $upload_token = $this->get_upload_token();
        $putExtra = new Qiniuyun_PutExtra();
        $putExtra->Crc32 = 1;
        list($ret, $err) = Qiniuyun_PutFile($upload_token, $filepath_cloud, $filepath_local, $putExtra);
        if ($watermark && $err == null) {
            $url = $this->get_url($ret['key'], true);
            if (!qiniuyun_test_image($url)) {
                return $ret;
            }

            $local_url = $this->download_waterimage($url);

            $this->delete($ret['key']);
            $return = $this->upload($filepath_cloud, $local_url, false);

            unlink($local_url);

            return $return;
        }
        $this->lastError = $err;

        return $ret;
    }

    private function get_upload_token() {
        $putPolicy = new QINIUYUN_RS_PutPolicy($this->bucket);

        return $putPolicy->Token(null);
    }

    private function download_waterimage($url_cloud) {
        global $zbp;
        $local_file = $zbp->usersdir . 'upload/zbp.qiniutmp.water.' . time();
        $ajax = Network::Create();
        if (!$ajax) {
            throw new Exception('主机没有开启网络功能');
        }

        $ajax->open('GET', $url_cloud);
        $ajax->send();
        file_put_contents($local_file, $ajax->responseText);

        return $local_file;
    }

    private function get_waterimage_url($url_cloud, $image_local, $dissolve = 100, $gravity = 'SouthEast', $dx = 10, $dy = 10) {
        $param = array(
            "watermark", "1",
            "image", str_replace(array('+', '/'), array('-', '_'), base64_encode($image_local)),
            "dissolve", $dissolve,
            "gravity", $gravity,
            "dx", $dx,
            "dy", $dy,
        );

        return $url_cloud . '?' . implode($param, '/');
    }

    public function get_thumbnail_url($url_cloud, $quality = 85, $longedge = 300, $shortedge = 300, $cut = false) {
        $param = array(
            "imageView",
            ($cut ? 5 : 4), //mode
            "w", $longedge,
            "h", $shortedge,
            "q", $quality,
        );

        return $url_cloud . '?' . implode($param, '/');
    }
}
