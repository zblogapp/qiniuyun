<?php

function Qiniuyun_Encode($str) // URLSafeBase64Encode
{
    $find = array('+', '/');
    $replace = array('-', '_');

    return str_replace($find, $replace, base64_encode($str));
}


function Qiniuyun_Decode($str)
{
    $find = array('-', '_');
    $replace = array('+', '/');

    return base64_decode(str_replace($find, $replace, $str));
}
