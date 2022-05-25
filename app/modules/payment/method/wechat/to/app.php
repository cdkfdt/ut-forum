<?php
ini_set('date.timezone','Asia/Shanghai');
require_once dirname(dirname(__FILE__)).'/lib/WxPay.Config.php';
class AppPay {
    protected $appid;
    protected $mch_id;
    protected $key;
    protected $openid;
    protected $out_trade_no;
    protected $body;
    protected $total_fee;
        function __construct($appid, $openid, $mch_id, $key,$out_trade_no,$body,$total_fee,$notify_url) {
			$this->appid = $appid;
			$this->openid = $openid;
			$this->mch_id = $mch_id;
			$this->key = $key;
			$this->out_trade_no = $out_trade_no;
			$this->body = $body;
			$this->total_fee = $total_fee;
			$this->notify_url = $notify_url;
    }
    public function pay() {
        //ͳһ�µ��ӿ�
        $return = $this->weixinapp();
        return $return;
    }
    //ͳһ�µ��ӿ�
    private function unifiedorder() {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = array(
            'appid' => $this->appid, //APPID
            'mch_id' => $this->mch_id, //�̻���
            'nonce_str' => $this->createNoncestr(),
            'body' => $this->body,
            'out_trade_no'=> $this->out_trade_no,
            'total_fee' => $this->total_fee,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => $this->notify_url,
            'openid' => $this->openid,
            'trade_type' => 'JSAPI'//��������
        );
        //ͳһ�µ�ǩ��
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $return = $this->xmlToArray($this->postXmlCurl($xmlData, $url, 60));
        return $return;
    }
    private static function postXmlCurl($xml, $url, $second = 30) {
        $ch = curl_init();
        //���ó�ʱ
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post �ύ��ʽ
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);
        //���� curl
        $data = curl_exec($ch);
        //���ؽ��
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WxPayException("curl ����������:$error");
        }
    }       
    //����ת���� xml
    private function arrayToXml($arr) {
        $xml = "";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "";
        return $xml;
    }
    //xml ת��������
    private function xmlToArray($xml) {
        //��ֹ�����ⲿ xml ʵ�� 
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }
    private function weixinapp() {
        //ͳһ�µ��ӿ�
        $unifiedorder = $this->unifiedorder();
//        print_r($unifiedorder);
        $parameters = array(
            'appId' => $this->appid, //С���� ID
            'timeStamp' => '' . time() . '', //ʱ���
            'nonceStr' => $this->createNoncestr(), //�����
            'package' => 'prepay_id=' . $unifiedorder['prepay_id'], //���ݰ�
            'signType' => 'MD5'//ǩ����ʽ
        );
        //ǩ��
        $parameters['paySign'] = $this->getSign($parameters);
        return $parameters;
    }
    private function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) { $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1); } 
		return $str; 
		} 
    private function getSign($Obj) {
		foreach ($Obj as $k => $v) {
        $Parameters[$k] = $v;
        }
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String . "&key=" . $this->key;
        $String = md5($String);
        $result_ = strtoupper($String);
        return $result_;
    }
    private function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
$appid=$_POST['appid'];
$openid=$_POST['openid'];
$fee=$_POST['fee'];
$posnum = $_POST['posnum'];
$apppay = new AppPay($appid,$openid,WxPayConfig::MCHID,WxPayConfig::KEY,$posnum,"Goods Payment",$fee,WxPayConfig::NOTIFY_URL);
$return=$apppay->pay();
echo json_encode($return);