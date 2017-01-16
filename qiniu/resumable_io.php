<?php

require_once "http.php";
require_once "auth_digest.php";

// ----------------------------------------------------------
// class Qiniuyun_Rio_PutExtra

class Qiniuyun_Rio_PutExtra
{
    public $Bucket = null;        // 必选（未来会没有这个字段）。
    public $Params = null;
    public $MimeType = null;
    public $ChunkSize = 0;        // 可选。每次上传的Chunk大小
    public $TryTimes = 3;        // 可选。尝试次数
    public $Progresses = null;    // 可选。上传进度：[]BlkputRet
    public $Notify = null;        // 进度通知：func(blkIdx int, blkSize int, ret *BlkputRet)
    public $NotifyErr = null;    // 错误通知：func(blkIdx int, blkSize int, err error)

    public function __construct($bucket = null) {
        $this->Bucket = $bucket;
    }
}

// ----------------------------------------------------------
// func Qiniuyun_Rio_BlockCount

define('QINIU_RIO_BLOCK_BITS', 22);
define('QINIU_RIO_BLOCK_SIZE', 1 << QINIU_RIO_BLOCK_BITS); // 4M

function Qiniuyun_Rio_BlockCount($fsize) // => $blockCnt
{
    return ($fsize + (QINIU_RIO_BLOCK_SIZE - 1)) >> QINIU_RIO_BLOCK_BITS;
}

// ----------------------------------------------------------
// internal func Qiniuyun_Rio_Mkblock/Mkfile

function Qiniuyun_Rio_Mkblock($self, $host, $reader, $size) // => ($blkputRet, $err)
{
    if (is_resource($reader)) {
        $body = fread($reader, $size);
        if ($body === false) {
            $err = new Qiniuyun_Error(0, 'fread failed');

            return array(null, $err);
        }
    } else {
        list($body, $err) = $reader->Read($size);
        if ($err !== null) {
            return array(null, $err);
        }
    }
    if (strlen($body) != $size) {
        $err = new Qiniuyun_Error(0, 'fread failed: unexpected eof');

        return array(null, $err);
    }

    $url = $host . '/mkblk/' . $size;

    return Qiniuyun_Client_CallWithForm($self, $url, $body, 'application/octet-stream');
}


function Qiniuyun_Rio_Mkfile($self, $host, $key, $fsize, $extra) // => ($putRet, $err)
{
    $url = $host . '/mkfile/' . $fsize;
    if ($key !== null) {
        $url .= '/key/' . Qiniuyun_Encode($key);
    }
    if (!empty($extra->MimeType)) {
        $url .= '/mimeType/' . Qiniuyun_Encode($extra->MimeType);
    }

    if (!empty($extra->Params)) {
        foreach ($extra->Params as $k => $v) {
            $url .= "/" . $k . "/" . Qiniuyun_Encode($v);
        }
    }

    $ctxs = array();
    foreach ($extra->Progresses as $prog) {
        $ctxs [] = $prog['ctx'];
    }
    $body = implode(',', $ctxs);

    return Qiniuyun_Client_CallWithForm($self, $url, $body, 'application/octet-stream');
}

// ----------------------------------------------------------
// class Qiniuyun_Rio_UploadClient

class Qiniuyun_Rio_UploadClient
{
    public $uptoken;

    public function __construct($uptoken)
    {
        $this->uptoken = $uptoken;
    }

    public function RoundTrip($req) // => ($resp, $error)
    {
        $token = $this->uptoken;
        $req->Header['Authorization'] = "UpToken $token";

        return Qiniuyun_Client_do($req);
    }
}

// ----------------------------------------------------------
// class Qiniuyun_Rio_Put/PutFile

function Qiniuyun_Rio_Put($upToken, $key, $body, $fsize, $putExtra) // => ($putRet, $err)
{
    global $QINIU_UP_HOST;

    $self = new Qiniuyun_Rio_UploadClient($upToken);

    $progresses = array();
    $uploaded = 0;
    while ($uploaded < $fsize) {
        $tried = 0;
        $tryTimes = ($putExtra->TryTimes > 0) ? $putExtra->TryTimes : 1;
        $blkputRet = null;
        $err = null;
        if ($fsize < $uploaded + QINIU_RIO_BLOCK_SIZE) {
            $bsize = $fsize - $uploaded;
        } else {
            $bsize = QINIU_RIO_BLOCK_SIZE;
        }
        while ($tried < $tryTimes) {
            list($blkputRet, $err) = Qiniuyun_Rio_Mkblock($self, $QINIU_UP_HOST, $body, $bsize);
            if ($err === null) {
                break;
            }
            $tried += 1;
            continue;
        }
        if ($err !== null) {
            return array(null, $err);
        }
        if ($blkputRet === null) {
            $err = new Qiniuyun_Error(0, "rio: uploaded without ret");

            return array(null, $err);
        }
        $uploaded += $bsize;
        $progresses [] = $blkputRet;
    }

    $putExtra->Progresses = $progresses;

    return Qiniuyun_Rio_Mkfile($self, $QINIU_UP_HOST, $key, $fsize, $putExtra);
}

function Qiniuyun_Rio_PutFile($upToken, $key, $localFile, $putExtra) // => ($putRet, $err)
{
    $fp = fopen($localFile, 'rb');
    if ($fp === false) {
        $err = new Qiniuyun_Error(0, 'fopen failed');

        return array(null, $err);
    }

    $fi = fstat($fp);
    $result = Qiniuyun_Rio_Put($upToken, $key, $fp, $fi['size'], $putExtra);
    fclose($fp);

    return $result;
}

// ----------------------------------------------------------
