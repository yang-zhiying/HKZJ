<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * 二手车交易平台
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:48
 */



class Usedcar extends CI_Controller {
    private $id =  '';//二手车交易平台 用户id

    public function __construct()
    {
        parent::__construct();
        /*$this->session->set_userdata('used_car_user_id', 1);*/
        $this->id = $this->session->userdata('used_car_user_id');
        if(empty($this->id)){
            header('location:'.site_url('login/used_car_login'));
        }
        $this->load->model('usedcar_model');
    }

    //二手车交易首页
    public function index($type = '', $data_type = ''){
        $type = urldecode($type);
        if($type == '最好车源'){
            $order_str = 'create_time DESC';
        }elseif($type == '降价急售'){

            $order_str = 'whole_price ASC';
        }else{
            $order_str = 'read DESC';
        }

        $data['sale'] = $this->usedcar_model->get_sale_list(array(), 0, 10, $order_str);

        if($data_type == 'json'){
            get_json(200, '获取成功', $data['sale']);
        }else{
            $data['active'] = '首页';
            $this->load->view('usedcar/index.html', $data);
        }
    }

    //买车
    public function buy($offset = 0, $key = '其他', $value = '其他', $data_type = 'html'){
        $key = urldecode($key);
        $value = urldecode($value);
        $data['active'] = '买车';
        $order_str = 'create_time DESC';
        $array = array();
        if($key == '排序'){
            switch ($value){
                case '最新上架':
                    $order_str = 'create_time DESC';
                    break;
                case '价格最低':
                    $order_str = 'whole_price ASC';
                    break;
                case '价格最高':
                    $order_str = 'whole_price DESC';
                    break;
                case '降价急售':
                    $order_str = 'whole_price ASC';
                    break;
            }
        }elseif ($key == '车型'){
            $array = array('car_type'=>$value);
        }elseif ($key == '价格'){
            $price = explode('到', $value);
            $array = array('whole_price <=' => (float)$price[1], 'whole_price >=' => (float)$price[0]);
        }elseif ($key == '排放'){
                $array = array('parameter0' => $value);
        }
        else{
            $array = array();
            $order_str = 'create_time DESC';
        }
        $data['car'] = $this->usedcar_model->get_sale_list($array, $offset, 10, $order_str);

        $data['key'] = $key;
        $data['value'] = $value;
        if($data_type == 'json'){
            get_json(200, '加载成功', $data['car']);
        }else{
            $this->load->view('usedcar/buycar.html', $data);
        }

    }
    //车辆搜索
    public function search($offset = 0, $type = 'html'){
        $data['active'] = '买车';
        if($type == 'html'){ //正常请求
            $keywords = $this->input->post('keywords');
        }else{
            $keywords = urldecode($type);
        }


        $data['car'] = $this->usedcar_model->get_search_list($keywords, $offset, 10);

        if($type == 'html'){
            $data['keywords'] = $keywords; //将关键字传给前端
            $data['is_search'] = '搜索'; //标识为搜索页
            $this->load->view('usedcar/buycar.html', $data);
        }else{
            get_json(200,'加载成功！', $data['car']);
        }

    }
    //卖二手车
    public function sale(){
        $user = $this->usedcar_model->get_user_info(array('id'=>$this->id));
        $data['active'] = '卖车';
        //如果用户信息不完善 不能发布卖车信息 跳转完善个人信息页
        if(!isset($user[0]) || empty($user[0]['address'])){
            $data['info'] = 'empty';

            $this->load->view('usedcar/sale.html', $data);
            return;
        }

        if(empty($this->input->post())){
            $this->load->view('usedcar/sale.html', $data);
        }else{
            $authcode = $this->input->post('authcode');
            if($authcode  != $this->session->userdata('usedcar_authcode')){
                get_json(400, '验证码输入错误');
                return;
            }
            $data = array(
                'user_id' => $this->id, //用户id
                'img_arr_str'=>$this->input->post('img_arr_str'), //照片地址用 -$- 分割
                'brand'=>$this->input->post('brand'), //车辆品牌
                'license_img'=>$this->input->post('license_img'), //行驶证照片地址
                'car_type'=>$this->input->post('car_type'), //车型
                'address'=>$this->input->post('address'),   //车辆所在地
                'guakao'=>$this->input->post('guakao'), //是否提供挂靠 0=>不提供  1=>提供
                'guohu'=>$this->input->post('guohu'),   //是否提供过户 0=>提供 1=不提供
                'bxlc'=>$this->input->post('bxlc'), //表显里程
                'whole_price'=>$this->input->post('whole_price'),   //全款价格
                'pay_type'=>$this->input->post('pay_type'), //付款方式 0=>全款购车  1=>分期付款
                'down_payment'=>$this->input->post('down_payment'), //首付金额，分期付款方式才有
                'xszdjrq'=>$this->input->post('xszdjrq'), //行驶证登记日期
                'jqxgqsj'=>$this->input->post('jqxgqsj'), //交强险过期时间

                'parameter0'=>$this->input->post('parameter0'),
                'parameter1'=>$this->input->post('parameter1'),
                'parameter2'=>$this->input->post('parameter2'),
                'parameter3'=>$this->input->post('parameter3'),
                'parameter4'=>$this->input->post('parameter4'),
                'parameter5'=>$this->input->post('parameter5'),
                'parameter6'=>$this->input->post('parameter6'),
                'parameter7'=>$this->input->post('parameter7'),

                'title'=>$this->input->post('title'),   //标题
                'postscript'=>$this->input->post('postscript'), //买家附言
                'create_time'=>time()   //卖车信息发布时间
            );
            if($this->db->insert('used-car_sale', $data)){
                get_json(200, '发布成功！');
            }else{
                get_json(400, '发布失败，请重试');
            }
        }
    }

    //个人页 我的
    public function person(){
        $data['active'] = '我的';
        $data['user'] = $this->usedcar_model->get_user_info(array('id'=>$this->id))[0];
        $this->load->view('usedcar/mine.html', $data);
    }

    //个人资料
    public function personal_data(){
        $data['active'] = '我的';
        if(empty($this->input->post('address'))){
            $data['user'] = $this->usedcar_model->get_user_info(array('id'=>$this->id))[0];
            $this->load->view('usedcar/edit-data.html', $data);
        }else{
            $data = array(
                'address'=>$this->input->post('address'),
                'realname'=>$this->input->post('realname'),
                'phone'=>$this->input->post('phone'),
                'wechat'=>$this->input->post('wechat'),
            );
            if($this->db->update('used-car_user', $data, array('id'=>$this->id))){
                get_json(200, '信息修改成功');
            }else{
                get_json(200, '信息修改失败，请稍后重试');
            }
        }
    }

    //我的车辆
    public function my_car($type = '在售', $offset = 0, $action = 'see'){
        if($action == 'see'){
            $data['type'] = urldecode($type);
            $this->load->view('usedcar/mycar.html', $data);
        }else{
            $type = urldecode($type);

            if($type == '在售'){
                $where_arr = array('status'=>1);

            }else if($type == '已售'){
                $where_arr = array('status'=>2);

            }else{
                $where_arr = array('status'=>0);
            }
            $data = $this->usedcar_model->get_sale_list($where_arr, $offset);
            get_json(200, '加载成功', $data);
        }
    }

    //我的收藏
    public function my_collect($offset = 0, $type = 'see'){
        $data['active'] = '我的';  //底部导航栏高亮
        $user = $this->usedcar_model->get_user_info(array('id'=>$this->id))[0];
        if(empty($user['collect'])){
            $data['collect'] = array();
        }else{
            $where_in = explode('-$-', $user['collect']);
            $data['collect'] = $this->usedcar_model->get_collect_list($where_in, $offset, 10);
        }

        if($type == 'get'){

        }else{
            $this->load->view('usedcar/collision.html', $data);
        }
    }

    //删除收藏
    public function delete_collect($id){
        if(!is_numeric($id)){
            get_json(400, '数据异常');
            return;
        }
        $user = $this->usedcar_model->get_user_info(array('id'=>$this->id))[0];
        $collect_arr = explode('-$-', $user['collect']);
        if(!in_array($id, $collect_arr)){ //要删除的id未在收藏里
            get_json(400,'您为收藏该信息');
        }
        $collect_arr = array_values(array_diff($collect_arr, [$id]));//删除数组中的$id 并重置数组索引
        $collect_arr_str = implode('-$-',$collect_arr);
        if($this->db->update('used-car_user', array('collect'=>$collect_arr_str), array('id'=>$this->id))){
            get_json(200, '删除成功!');
        }else{
            get_json(400, '删除失败!');
        }
    }

    //查看卖车信息
    public function see($id){
        $info = $this->usedcar_model->get_sale_list(array('id'=>$id), 0, 1);
        if(empty($info)){
            $data['str'] = '该卖车信息已被删除！';
            $this->load->view('usedcar/not_found.html', $data);
            return;
        }

        $data['sale'] = $info[0];   //卖车信息

        $data['parameter'] = $this->usedcar_model->get_format_parameter($data); //获取车辆参数数组

        $data['img_arr'] = explode('-$-', $data['sale']['img_arr_str']); //获取车辆图片数组


        $data['user'] = $this->usedcar_model->get_user_info(array('id'=>$data['sale']['user_id']))[0];  //获取卖车用户信息

        $see_user = $this->usedcar_model->get_user_info(array('id'=>$this->id))[0]; //获取浏览者信息

        $data['collect'] = in_array($id, explode('-$-', $see_user['collect']))?true:false; //判断该篇文章

        $this->db->update('used-car_sale', array('read'=>($data['sale']['read']+1)));//访问量加一

        $data["id"] = $id;

        $this->load->view('usedcar/car-detail.html', $data);

    }
    public function see_json($id){ //根据车辆id返回json数据
    $info = $this->usedcar_model->get_sale_list(array('id'=>$id), 0, 1);
    if(empty($info)){
        $data['str'] = '该卖车信息已被删除！';
        $this->load->view('usedcar/not_found.html', $data);
        return;
    }

    $data['sale'] = $info[0];   //卖车信息


    $data["id"] = $id;

    return $data["sale"];

}

    //收藏卖车信息
    public function collect($id){
        $collect = $this->usedcar_model->get_user_info(array('id'=>$this->id))[0]['collect'];
        if(empty($collect)){
            $str = $id;
        }else{
            $str = $collect.'-$-'.$id;
        }

        if($this->db->update('used-car_user', array('collect'=>$str), array('id'=>$this->id))){
            get_json(200,'收藏成功！');
        }else{
            get_json(400,'收藏失败！');
        }
    }

    //举报卖车信息
    public function accuse($id){
        $accuse = $this->usedcar_model->get_sale_list(array('id'=>$id), 0, 1);
        if(empty($accuse)){
            $data['str'] = '该卖车信息已被删除！';
            $this->load->view('usedcar/not_found.html', $data);
            return;
        }

        if(empty($this->input->post('reason'))){
            $data['sale'] = $accuse[0];
            $this->load->view('usedcar/report.html', $data);
        }else{
            $data = array(
                'reason'=>$this->input->post('reason'),
                'content'=>$this->input->post('content'),
                'phone'=>$this->input->post('phone'),
                'create_time'=>time(),
                'sale_id'=>$id,
                'user_id'=>$this->id
            );
            if($this->db->insert('used-car_accuse', $data)){
                get_json(200, '举报成功！');
            }else{
                get_json(400, '举报失败！');
            }
        }
    }

    //选择经纪人或者商家认证页面以及经纪人和商家认证页面
    public function renzheng($type = 'select'){
        if($type == 'personal'){    //个人认证页
            $this->load->view('usedcar/apply-for-personal.html');
        }else if($type == 'company'){   //商家认证页
            $this->load->view('usedcar/apply-for-company.html');
        }else{  //选择认证方式
            $this->load->view('usedcar/apply-for.html');
        }
    }

    //执行认证操作 提交表单处理后存放数据库
    public function apply($type){
        $type = urldecode($type);
        $authcode = $this->input->post('authcode');
        if($authcode != $this->session->userdata('usedcar_authcode')){
            get_json(400, '验证码错误!');
            return;
        }

        $data = array(
            'realname'  =>  $this->input->post('realname'),     //真实姓名
            'address'   =>  $this->input->post('address'),      //所在地址
            'ID_card'   =>  $this->input->post('ID_card'),      //身份证号
            'phone'     =>  $this->input->post('phone'),        //卖车手机号
            'we_chat'   =>  $this->input->post('we_chat'),      //微信号
            'img0'      =>  $this->input->post('img0'),         //身份证正面
            'img1'      =>  $this->input->post('img1'),         //身份证背面
            'img2'      =>  $this->input->post('img2'),         //手持身份证
            'type'      =>  $type, //认证类型
        );
        //商家额外信息
        if($type == '商家'){
            $data['img3'] = $this->input->post('img3'); //营业执照
            $data['merchant_type'] = $this->input->post('merchant_type'); //商家类型
            $data['company_name'] = $this->input->post('company_name'); //公司名称
            $data['indate'] = $this->input->post('indate'); //有效期
            $data['registration_number'] = $this->input->post('registration_number'); //注册号
            $data['company_address'] = $this->input->post('company_address'); //公司地址
        }

        if($this->db->insert('used-car_apply', $data)){
            get_json(200, '提交成功!');
        }else{
            get_json(200, '提交失败!');
        }
    }

    //图片上传
    public function uploadImage(){
        $base64_image_content = $this->input->post('image', false);
        //echo $base64_image_content;
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = strtolower($result[2]);
            $allowed_type = array('jpg', 'jpeg', 'png', 'bmp');
            if (!in_array($type, $allowed_type)) {
                get_json(400, '只允许上传jpg, jpeg, png, bmp格式的图片哟！');
            }
            $new_file = "uploads/usedcarImg/".$this->id. "/" . date('Ymd', time()) . "/";
            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777, true);
            }
            $new_file = $new_file . md5(time() . mt_rand(1000, 9999)) . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                //echo $new_file;
                get_json(200, '图片上传成功', array('path' => '/' . $new_file));
                return;
            } else {
                get_json(400, '图片上传失败，请检查您的网络！');return;
            }
        }else{
            get_json(400, '图片上传失败');return;
        }
    }

    //删除文件
    public function delete_image(){
        $image_url = $this->input->post('image_url', false);
        $pattern = '/^uploads\/usedcarImg\/\d+\/\d+\/\w+\./';
        if(!preg_match($pattern, $image_url)){
            get_json(400, '您要删除的文件不存在');
            return;
        }
        if(!file_exists($image_url)){
            get_json(200, '该文件未上传成功'); //返回200 让前端清除掉未上传成功的文件
            return;
        }

        if(@unlink($image_url)){
            get_json(200, '删除成功！');
        }else{
            get_json(400,'删除失败！');
        }
    }

    public function authcode(){
        $this->load->library('myclass');
        $this->myclass->authcode('usedcar_authcode');
    }
    public function record(){  //历史记录

        $this->load->view("usedcar/record.html");
        $this->load->view("usedcar/footer.html",array("active"=>""));
    }
    public function record_process_data(){  //历史记录
        $data = $this->input->post("data");
        foreach ($data as $v){
            $result[]=$this->see_json($v);
        }
        echo json_encode($result);
    }

}