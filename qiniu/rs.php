<?php

require_once "http.php";

// ----------------------------------------------------------
// class QINIUYUN_RS_GetPolicy

class QINIUYUN_RS_GetPolicy
{
    public $Expires;

    public function MakeRequest($baseUrl, $mac) // => $privateUrl
    {
        $deadline = $this->Expires;
        if ($deadline == 0) {
            $deadline = 3600;
        }
        $deadline += time();

        $pos = strpos($baseUrl, '?');
        if ($pos !== false) {
            $baseUrl .= '&e=';
        } else {
            $baseUrl .= '?e=';
        }
        $baseUrl .= $deadline;

        $token = Qiniuyun_Sign($mac, $baseUrl);

        return "$baseUrl&token=$token";
    }
}

function QINIUYUN_RS_MakeBaseUrl($domain, $key) // => $baseUrl
{
    $keyEsc = str_replace("%2F", "/", rawurlencode($key));
    if (preg_match('/http|https/si', $domain)) {
        return "$domain/$keyEsc";
    } else {
        return "http://$domain/$keyEsc";
    }
}

// --------------------------------------------------------------------------------
// class QINIUYUN_RS_PutPolicy

class QINIUYUN_RS_PutPolicy
{
    public $Scope;                  //必填
    public $Expires;                //默认为3600s
    public $CallbackUrl;
    public $CallbackBody;
    public $ReturnUrl;
    public $ReturnBody;
    public $AsyncOps;
    public $EndUser;
    public $InsertOnly;             //若非0，则任何情况下无法覆盖上传
    public $DetectMime;             //若非0，则服务端根据内容自动确定MimeType
    public $FsizeLimit;
    public $SaveKey;
    public $PersistentOps;
    public $PersistentPipeline;
    public $PersistentNotifyUrl;
    public $FopTimeout;
    public $MimeLimit;

    public function __construct($scope)
    {
        $this->Scope = $scope;
    }

    public function Token($mac) // => $token
    {
        $deadline = $this->Expires;
        if ($deadline == 0) {
            $deadline = 3600;
        }
        $deadline += time();

        $policy = array('scope' => $this->Scope, 'deadline' => $deadline);
        if (!empty($this->CallbackUrl)) {
            $policy['callbackUrl'] = $this->CallbackUrl;
        }
        if (!empty($this->CallbackBody)) {
            $policy['callbackBody'] = $this->CallbackBody;
        }
        if (!empty($this->ReturnUrl)) {
            $policy['returnUrl'] = $this->ReturnUrl;
        }
        if (!empty($this->ReturnBody)) {
            $policy['returnBody'] = $this->ReturnBody;
        }
        if (!empty($this->AsyncOps)) {
            $policy['asyncOps'] = $this->AsyncOps;
        }
        if (!empty($this->EndUser)) {
            $policy['endUser'] = $this->EndUser;
        }
        if (isset($this->InsertOnly)) {
            $policy['insertOnly'] = $this->InsertOnly;
        }
        if (!empty($this->DetectMime)) {
            $policy['detectMime'] = $this->DetectMime;
        }
        if (!empty($this->FsizeLimit)) {
            $policy['fsizeLimit'] = $this->FsizeLimit;
        }
        if (!empty($this->SaveKey)) {
            $policy['saveKey'] = $this->SaveKey;
        }
        if (!empty($this->PersistentOps)) {
            $policy['persistentOps'] = $this->PersistentOps;
        }
        if (!empty($this->PersistentPipeline)) {
            $policy['persistentPipeline'] = $this->PersistentPipeline;
        }
        if (!empty($this->PersistentNotifyUrl)) {
            $policy['persistentNotifyUrl'] = $this->PersistentNotifyUrl;
        }
        if (!empty($this->FopTimeout)) {
            $policy['fopTimeout'] = $this->FopTimeout;
        }
        if (!empty($this->MimeLimit)) {
            $policy['mimeLimit'] = $this->MimeLimit;
        }

        $b = json_encode($policy);

        return Qiniuyun_SignWithData($mac, $b);
    }
}

// ----------------------------------------------------------
// class QINIUYUN_RS_EntryPath

class QINIUYUN_RS_EntryPath
{
    public $bucket;
    public $key;

    public function __construct($bucket, $key)
    {
        $this->bucket = $bucket;
        $this->key = $key;
    }
}

// ----------------------------------------------------------
// class QINIUYUN_RS_EntryPathPair

class QINIUYUN_RS_EntryPathPair
{
    public $src;
    public $dest;

    public function __construct($src, $dest)
    {
        $this->src = $src;
        $this->dest = $dest;
    }
}

// ----------------------------------------------------------

function QINIUYUN_RS_URIStat($bucket, $key)
{
    return '/stat/' . Qiniuyun_Encode("$bucket:$key");
}

function QINIUYUN_RS_URIDelete($bucket, $key)
{
    return '/delete/' . Qiniuyun_Encode("$bucket:$key");
}

function QINIUYUN_RS_URICopy($bucketSrc, $keySrc, $bucketDest, $keyDest)
{
    return '/copy/' . Qiniuyun_Encode("$bucketSrc:$keySrc") . '/' . Qiniuyun_Encode("$bucketDest:$keyDest");
}

function QINIUYUN_RS_URIMove($bucketSrc, $keySrc, $bucketDest, $keyDest)
{
    return '/move/' . Qiniuyun_Encode("$bucketSrc:$keySrc") . '/' . Qiniuyun_Encode("$bucketDest:$keyDest");
}

// ----------------------------------------------------------

function QINIUYUN_RS_Stat($self, $bucket, $key) // => ($statRet, $error)
{
    global $QINIUYUN_RS_HOST;
    $uri = QINIUYUN_RS_URIStat($bucket, $key);

    return Qiniuyun_Client_Call($self, $QINIUYUN_RS_HOST . $uri);
}

function QINIUYUN_RS_Delete($self, $bucket, $key) // => $error
{
    global $QINIUYUN_RS_HOST;
    $uri = QINIUYUN_RS_URIDelete($bucket, $key);

    return Qiniuyun_Client_CallNoRet($self, $QINIUYUN_RS_HOST . $uri);
}

function QINIUYUN_RS_Move($self, $bucketSrc, $keySrc, $bucketDest, $keyDest) // => $error
{
    global $QINIUYUN_RS_HOST;
    $uri = QINIUYUN_RS_URIMove($bucketSrc, $keySrc, $bucketDest, $keyDest);

    return Qiniuyun_Client_CallNoRet($self, $QINIUYUN_RS_HOST . $uri);
}

function QINIUYUN_RS_Copy($self, $bucketSrc, $keySrc, $bucketDest, $keyDest) // => $error
{
    global $QINIUYUN_RS_HOST;
    $uri = QINIUYUN_RS_URICopy($bucketSrc, $keySrc, $bucketDest, $keyDest);

    return Qiniuyun_Client_CallNoRet($self, $QINIUYUN_RS_HOST . $uri);
}

// ----------------------------------------------------------
// batch

function QINIUYUN_RS_Batch($self, $ops) // => ($data, $error)
{
    global $QINIUYUN_RS_HOST;
    $url = $QINIUYUN_RS_HOST . '/batch';
    $params = 'op=' . implode('&op=', $ops);

    return Qiniuyun_Client_CallWithForm($self, $url, $params);
}

function QINIUYUN_RS_BatchStat($self, $entryPaths)
{
    $params = array();
    foreach ($entryPaths as $entryPath) {
        $params[] = QINIUYUN_RS_URIStat($entryPath->bucket, $entryPath->key);
    }

    return QINIUYUN_RS_Batch($self, $params);
}

function QINIUYUN_RS_BatchDelete($self, $entryPaths)
{
    $params = array();
    foreach ($entryPaths as $entryPath) {
        $params[] = QINIUYUN_RS_URIDelete($entryPath->bucket, $entryPath->key);
    }

    return QINIUYUN_RS_Batch($self, $params);
}

function QINIUYUN_RS_BatchMove($self, $entryPairs)
{
    $params = array();
    foreach ($entryPairs as $entryPair) {
        $src = $entryPair->src;
        $dest = $entryPair->dest;
        $params[] = QINIUYUN_RS_URIMove($src->bucket, $src->key, $dest->bucket, $dest->key);
    }

    return QINIUYUN_RS_Batch($self, $params);
}

function QINIUYUN_RS_BatchCopy($self, $entryPairs)
{
    $params = array();
    foreach ($entryPairs as $entryPair) {
        $src = $entryPair->src;
        $dest = $entryPair->dest;
        $params[] = QINIUYUN_RS_URICopy($src->bucket, $src->key, $dest->bucket, $dest->key);
    }

    return QINIUYUN_RS_Batch($self, $params);
}

// ----------------------------------------------------------
// fetch
function QINIUYUN_RS_Fetch($self, $url, $bucket, $key)
{

    global $QINIUYUN_IOVIP_HOST;
    $path = '/fetch/' . Qiniuyun_Encode($url) . '/to/' . Qiniuyun_Encode("$bucket:$key");

    return Qiniuyun_Client_CallNoRet($self, $QINIUYUN_IOVIP_HOST . $path);
}

// ----------------------------------------------------------
