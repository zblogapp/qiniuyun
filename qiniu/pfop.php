<?php
require_once 'auth_digest.php';

// --------------------------------------------------------------------------------
// class Qiniuyun_Pfop

class Qiniuyun_Pfop {

    public $Bucket;
    public $Key;
    public $Fops;
    public $NotifyURL;
    public $Force;
    public $Pipeline;

    public function MakeRequest($self)
    {

        global $QINIUYUN_API_HOST;

        $ReqParams = array(
            'bucket' => $this->Bucket,
            'key' => $this->Key,
            'fops' => $this->Fops,
            'notifyURL' => $this->NotifyURL,
            'force' => $this->Force,
            'pipeline' => $this->Pipeline
        );

        $url = $QINIUYUN_API_HOST . '/pfop/';

        return Qiniuyun_Client_CallWithForm($self, $url, $ReqParams);
    }

}

function Qiniuyun_PfopStatus($client, $id)
{
    global $QINIUYUN_API_HOST;

    $url = $QINIUYUN_API_HOST . '/status/get/prefop?';
    $params = array('id' => $id);

    return Qiniuyun_Client_CallWithForm($client, $url, $params);
}
