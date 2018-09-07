<?php
defined('BASEPATH') or die('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 18:47
 */
class Zhiyun
{
    private $CI;
    private $apiUrl = "https://zhiyunopenapi.95155.com/apis/";//接口地址
    private $apiUser = '704adeb1-b360-4cac-9e37-cac416b6a366'; //API账号
    private $apiPwd = '2BPi136g473a4o104aAL7F24aNe6jS'; //API密码
    private $clientId = 'abce276f-32ea-4eb9-a48b-0454031a562c'; //API客户端ID
    private $des_key = 'CTFOTRV1';//DES加密解密算法的KEY
    private $iv = 'CTFOTRV1'; //偏移向量

    public function __construct()
    {
        $this->CI = &get_instance();
        /*if($this->apiUrl == null){ //初始化数据
            $config = $this->CI->db->get_where('zhiyun_config', array('id'=>1))->result_array()[0];
            $this->apiUrl = $config['apiUrl'];
            $this->apiUser = $config['apiUser'];
            $this->apiPwd = $config['apiPwd'];
            $this->clientId = $config['clientId'];
            $this->des_key = $config['des_key'];
            $this->iv = $config['iv'];
            $this->token =$config['token'];
        }*/
    }

    //登录开放平台
    public function get_token()
    {

        $p           = "user=" . $this->apiUser . "&pwd=" . $this->apiPwd;

        $p           = $this->des_cbc_encrypt($p, $this->des_key, $this->iv); //加密

        $content_url = $this->apiUrl . "/login/" . $p . "?client_id=" . $this->clientId;

        $result      = $this->https_curl($content_url); //请求登录API
        $result_json = $this->des_cbc_decrypt($result, $this->des_key, $this->iv); //解密获取token
        $result_arr = json_decode($result_json, true);

        if($result_arr['status'] == 1001){ //验证令牌是否获取成功
            $this->CI->db->update('zhiyun_config', array('token'=>$result_arr['token']), array('id'=>1));
            return $result_arr['token'];
        }else{
            return false;
        }

    }

    //获取车辆位置
    public function get_location($car_number)
    {
        $token = $this->CI->db->select('token')->get_where('zhiyun_config', array('id'=>1))->result_array()[0]['token']; //获取token

        $p = "token=".$token."&vclN=".$car_number."&timeNearby=24"; //拼接数据
        $p = $this->des_cbc_encrypt($p, $this->des_key, $this->iv); //加密

        $url =  $this->apiUrl."/vLastLocationV3/".$p."?client_id=".$this->clientId; //拼接uri

        $result = $this->https_curl($url); //请求API，获取信息

        $result_json = $this->des_cbc_decrypt($result, $this->des_key, $this->iv); //解密数据

        $result_arr = json_decode($result_json, true);

        // 业务逻辑
        if($result_arr['status'] == 1016){ //令牌失效
            if($this->get_token()){ //获取令牌 更新数据库信息 成功后继续请求获取位置
                $this->get_location($car_number);
            }else{ //否则返回错误结果
                return false;
            }
        }elseif($result_arr['status'] == 1001){ //数据获取成功
            return $result_arr['result']; //返回位置信息
        }elseif($result_arr['status'] == 1006){
            return '无结果';
        }else{
            return false;
        }
    }

    private function https_curl($url)
    {
        $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.jxhkzj.com');
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSLKEY, 'certificate/monitor.pem');

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }



    /*
     * des-cbc加密
     * @param string  $data 要被加密的数据
     * @param string  $key 加密使用的key
     * @param string  $iv 初始向量
     */
    private function des_cbc_encrypt($data, $key, $iv){
        $size = 8; //填充块的大小
        $data = $this->pkcs5_pad($data, $size);

        $data = openssl_encrypt ($data, 'des-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);

        $data = bin2hex($data); //对加密后的密文进行16进制转换
        return $data;
    }

    /*
     * des-cbc解密
     * @param string  $data 加密数据
     * @param string  $key 加密使用的key
     * @param string  $iv 初始向量
     */
    private function des_cbc_decrypt($data, $key, $iv){

        $data = hex2bin($data);
        $decrypted_data =  openssl_decrypt ($data, 'des-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);

        $decrypted_data = $this->pkcs5_unpad($decrypted_data); //对解密后的明文进行去掉字符填充
        return rtrim($decrypted_data); //返回去掉空格之后的数据
    }

    /*
     * 对明文进行给定块大小的字符填充
    */
    private function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /*
     * 对解密后的已字符填充的明文进行去掉填充字符
    */
    private function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        return substr($text, 0, -1 * $pad);
    }

}