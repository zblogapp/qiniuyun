<?php
require_once '../../../zb_system/function/c_system_base.php';
require_once '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('qiniuyun')) {$zbp->ShowError(48);die();}
init_qiniuyun();
$blogtitle = '七牛云存储';

switch (GetVars('action', 'POST')) {
  case 'upload':
    $upload_water = ($qiniuyun->water_enable && $qiniuyun->water_overwrite);
    $file_name = 'upload_test_file_name' . mt_rand(1000, 9999) . '_' . mt_rand(0, 10000) . '.png';
    $cloud_path = $qiniuyun->cloudpath . $file_name;
    echo json_encode(array(
      'ret' => $qiniuyun->upload($cloud_path, dirname(__FILE__) . '/water.png', $upload_water),
      'err' => $qiniuyun->lastError,
      'name' => $file_name
    ));
    exit;
  break;
  case 'delete':
    echo json_encode(array(
      'ret' => $qiniuyun->delete(GetVars('key', 'POST')),
      'err' => $qiniuyun->lastError
    ));
    exit;
  break;
}

require $blogpath . 'zb_system/admin/admin_header.php';
?>
<link rel="stylesheet" type="text/css" href="qiniu-style.css"/>
<?php
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu">
    <?php Qiniuyun_SubMenu(3);?>
  </div>
  <div id="divMain2">
    <div id="test-data">
      5秒后开始验证设置...
    </div>
  </div>
</div>
<script>
(function () {
  function appendData(data) {
    $("#test-data").append("<br/>").append(data.toString());
  }

  try {
      $.post('test.php', {
        action: 'upload'
      }).success(function (ret) {
        var data = JSON.parse(ret);
        console.log(data)
        if (data.ret == null) {
          appendData('上传失败：')
          appendData(ret);
          return;
        }
        appendData('上传成功。');
        $.post('test.php', {
          action: 'delete',
          key: data.ret.key
        }).success(function (ret) {
          var data = JSON.parse(ret);
          console.log(data)
          if (data.err !== null) {
            appendData('删除失败：')
            appendData(ret);
            return;
          } else {
            appendData('删除成功。')
            appendData('配置验证通过。')
          }
        })
      }).error(appendData)
  } catch (e) {
    appendData(e.message)
  }
})()

</script>
<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
