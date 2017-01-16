<?php

require_once "rs.php";
require_once "io.php";
require_once "resumable_io.php";

function QINIUYUN_RS_Put($self, $bucket, $key, $body, $putExtra) // => ($putRet, $err)
{
    $putPolicy = new QINIUYUN_RS_PutPolicy("$bucket:$key");
    $upToken = $putPolicy->Token($self->Mac);

    return Qiniuyun_Put($upToken, $key, $body, $putExtra);
}

function QINIUYUN_RS_PutFile($self, $bucket, $key, $localFile, $putExtra) // => ($putRet, $err)
{
    $putPolicy = new QINIUYUN_RS_PutPolicy("$bucket:$key");
    $upToken = $putPolicy->Token($self->Mac);

    return Qiniuyun_PutFile($upToken, $key, $localFile, $putExtra);
}

function QINIUYUN_RS_Rput($self, $bucket, $key, $body, $fsize, $putExtra) // => ($putRet, $err)
{
    $putPolicy = new QINIUYUN_RS_PutPolicy("$bucket:$key");
    $upToken = $putPolicy->Token($self->Mac);
    if ($putExtra == null) {
        $putExtra = new Qiniuyun_Rio_PutExtra($bucket);
    } else {
        $putExtra->Bucket = $bucket;
    }

    return Qiniuyun_Rio_Put($upToken, $key, $body, $fsize, $putExtra);
}

function QINIUYUN_RS_RputFile($self, $bucket, $key, $localFile, $putExtra) // => ($putRet, $err)
{
    $putPolicy = new QINIUYUN_RS_PutPolicy("$bucket:$key");
    $upToken = $putPolicy->Token($self->Mac);
    if ($putExtra == null) {
        $putExtra = new Qiniuyun_Rio_PutExtra($bucket);
    } else {
        $putExtra->Bucket = $bucket;
    }

    return Qiniuyun_Rio_PutFile($upToken, $key, $localFile, $putExtra);
}
