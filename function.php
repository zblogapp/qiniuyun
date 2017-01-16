<?php
function qiniuyun_SubMenu($id)
{
    $arySubMenu = array(
        0 => array('七牛账户设置', 'main.php', 'left', false),
        1 => array('水印设置', 'water.php', 'left', false),
        2 => array('缩略图设置', 'thumbnail.php', 'left', false)
    );

    
    foreach($arySubMenu as $k => $v)
    {
        echo '<a href="'. $v[1] . '" ' . ($v[3] ? 'target="_blank"' : '');
        echo '><span class="m-' . $v[2] . ' ' . ($id == $k ? 'm-now' : '');
        echo '">' . $v[0] . '</span></a>';
    }
}


function qiniuyun_display_text($param)
{
    echo TransferHTML($GLOBALS['qiniuyun']->cfg->$param, '[textarea]');
}

function qiniuyun_output_option($value, $param, $text)
{
    echo "<option value=\"$value\" ";
    if ($GLOBALS['qiniuyun']->cfg->$param == $value) {
        echo ' selected';
    }
    echo ">$text</option>";
}

function qiniuyun_test_image($url)
{
    return preg_match("/\.jpe?g|gif|png|svg|bmp|tiff$/i", $url);
}
