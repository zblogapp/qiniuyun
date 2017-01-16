<?php

require_once "utils.php";
require_once "conf.php";

// ----------------------------------------------------------

class Qiniuyun_Mac {

    public $AccessKey;
    public $SecretKey;

    public function __construct($accessKey, $secretKey)
    {
        $this->AccessKey = $accessKey;
        $this->SecretKey = $secretKey;
    }

    public function Sign($data) // => $token
    {
        $sign = hash_hmac('sha1', $data, $this->SecretKey, true);

        return $this->AccessKey . ':' . Qiniuyun_Encode($sign);
    }

    public function SignWithData($data) // => $token
    {
        $data = Qiniuyun_Encode($data);

        return $this->Sign($data) . ':' . $data;
    }

    public function SignRequest($req, $incbody) // => ($token, $error)
    {
        $url = $req->URL;
        $url = parse_url($url['path']);
        $data = '';
        if (isset($url['path'])) {
            $data = $url['path'];
        }
        if (isset($url['query'])) {
            $data .= '?' . $url['query'];
        }
        $data .= "\n";

        if ($incbody) {
            $data .= $req->Body;
        }

        return $this->Sign($data);
    }

    public function VerifyCallback($auth, $url, $body) // ==> bool
    {
        $url = parse_url($url);
        $data = '';
        if (isset($url['path'])) {
            $data = $url['path'];
        }
        if (isset($url['query'])) {
            $data .= '?' . $url['query'];
        }
        $data .= "\n";

        $data .= $body;
        $token = 'QBox ' . $this->Sign($data);

        return $auth === $token;
    }
}

function Qiniuyun_SetKeys($accessKey, $secretKey)
{
    global $QINIUYUN_ACCESS_KEY;
    global $QINIUYUN_SECRET_KEY;

    $QINIUYUN_ACCESS_KEY = $accessKey;
    $QINIUYUN_SECRET_KEY = $secretKey;
}

function Qiniuyun_RequireMac($mac) // => $mac
{
    if (isset($mac)) {
        return $mac;
    }

    global $QINIUYUN_ACCESS_KEY;
    global $QINIUYUN_SECRET_KEY;

    return new Qiniuyun_Mac($QINIUYUN_ACCESS_KEY, $QINIUYUN_SECRET_KEY);
}

function Qiniuyun_Sign($mac, $data) // => $token
{
    return Qiniuyun_RequireMac($mac)->Sign($data);
}

function Qiniuyun_SignWithData($mac, $data) // => $token
{
    return Qiniuyun_RequireMac($mac)->SignWithData($data);
}

// ----------------------------------------------------------
