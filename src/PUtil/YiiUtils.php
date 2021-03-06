<?php

namespace PUtil;

trait YiiUtils {

    /**
     * ============================
     * request funcs
     * ============================
     */

    public $order= 'desc';
    public $id= null;
    public $aid= null;
    public $page= null;
    public $offset= null;
    public $pagesize= null;
    public $input = null;
    public $data = null;


//    $this->pubact_list = array('index','login','loginadmin');
// 
//    $u1 = $this->ck_auth($filterChain,'uid',0);
//    if($u1){
//        $filterChain->run();
//    }
//    else{
//        $u2 = $this->ck_auth($filterChain,'adminlogin',0);
//        if($u2){
//            $filterChain->run();
//        }
//        else{
//            self::ret(1,'auth error');
//        }
//    }

    public $uid;
    public $pubact_list = array('index','show');
    public function ck_auth($filterChain,$key='uid',$run=1){
        $uid = \Yii::app()->session[$key];
        if($uid) $this->uid = $uid;

        if($this->uid || in_array($this->aid, $this->pubact_list) ){
            if($run){
                $filterChain->run();
            }
            else{
                return 1;
            }
        }
        else{
            if($run){
                self::ret(1,'auth error');
            }
            else{
                return 0;
            }
        }
    }

    /**
     * public function filters() {
     *     return array(  
     *         'initdata',
     *         'init',  
     *     );  
     * }  
     */
    public function filterInitdata($filterChain) {  
        $baseurl = \Yii::app()->getBaseUrl(true);
        $this->aid= $this->action->id;
        $page = self::req('page');
        $pagesize = self::req('pagesize');
        $this->aid= $this->action->id;
        $this->page= $page?$page:1;
        $this->pagesize= $pagesize?$pagesize:10;
        $this->offset = ($this->page-1)* $this->pagesize;

        if(!empty($_POST)){
            $this->data = $_POST;
        }
        else{
            $this->input = file_get_contents("php://input");
            if($this->input){
                if(strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded')!==false){
                    parse_str($this->input, $this->data);
                }
                else if(strpos($_SERVER['CONTENT_TYPE'], 'application/json')!==false){
                    $this->data = json_decode($this->input, true);
                }
            }
        }
        $this->id = isset($_GET['id'])?$_GET['id']:null;
        if($this->id){
            $this->data['id'] = 0+$this->id;
        }

        $filterChain->run();  
    }

    /**
     * self::param('key');
     */
    public static function param($key){
        return \Yii::app()->params[$key];
    }

    /**
     * self::session('key');
     */
    public static function session($key, $val=''){
        if($val){
            \Yii::app()->session[$key] = $val;
        }
        else{
            return \Yii::app()->session[$key];
        }
    }

    /**
     * add the conf to main.php -> errors
        'errors' => array(
           'OK'        => 0,    # 正确返回
           'AUTH'      => 1,    # 权限错误
           'CODE'      => 2,    # 验证码错误
           'VALID'     => 3,   # 表单验证错误
           'SQL_ERR'   => 3000,
           'PARAM_ERR' => 3001,
        ),
     */
    public static function err($key){
        $errs = self::param('errors');
        return $errs[$key];
    }

    /**
     * add the conf to main.php -> params
     * 'testStatus' => 1,
     */
    public static function tSt(){
        return self::param('testStatus');
    }

    /**
     *  self::req('type', -1);
     */
    public static function req($key, $default=''){
        $item = self::get($key, $default);
        if($item == ''){
            $item = self::post($key, $default);
        }
        return $item;
    }

    /**
     *  self::get('type', -1);    
     */
    public static function get($key, $default=''){
        if(isset($_GET[$key]) && $_GET[$key] != ''){
            return $_GET[$key];
        } else{
            return $default;
        }
    }

    /**
     *  self::post('type', -1);    
     */
    public static function post($key, $default=''){
        if(isset($_POST[$key]) && $_POST[$key] != ''){
            return $_POST[$key];
        } else{
            return $default;
        }
    }

    public function getCountArray($count){
        $ret['cur_page'] = $this->page;
        $ret['total_page'] = ceil($count/$this->pagesize);
        $ret['count'] = $count;
        return $ret;
    }

    /**
     * $imgname = self::saveimg('img_file');
     */
    public static function saveimg($imgname='img'){
        $name = '';
        if(isset($_FILES[$imgname])){
            $pathroot=$_SERVER['DOCUMENT_ROOT'];
            self::info("path: $pathroot");
            $dir = $pathroot.'/imgs/';
            $mode = 0777;
            is_dir($dir) || mkdir($dir, $mode);

            $type=$_FILES[$imgname]["type"];
            if (($type=="image/pjpeg") or ($type=="image/jpeg")){
                $img_type = ".jpg";
            }
            if (($type=="image/gif")){
                $img_type = ".gif";
            }
            if (($type=="image/png")){
                $img_type = ".png";
            }
            if(isset($img_type)){
                $fname = date('Ymdhis').$img_type;
                move_uploaded_file($_FILES[$imgname]["tmp_name"], $dir . $fname);
                $name = $fname;
            }
        }

        return $name;
    }

    /**
     * ============================
     * Logging
     * ============================
     */
    public static function error($m){
        \Yii::log($m, 'error');
    }
    public static function info($m){
        \Yii::log($m, 'error');
    }

    /**
     * ============================
     * Response
     * ============================
     */

	/**
	 * 输出json数据
	 * @param array or string $result
	 */
	public static function sendJSON($result){
        #$t = Yii::app()->common->get_total_millisecond();
		if(!is_string($result)){
			$content = \CJSON::encode($result);
		}else{
			$content = $result;
		}
	
		if (isset($_GET['jsoncallback'])) {
			$content = $_GET['jsoncallback'].'('.$content.')';
		}

        $acao = self::param('acao');
        if($acao){
            header("Access-Control-Allow-Origin: {$acao['acao']}");
            header("Access-Control-Allow-Methods: {$acao['acam']}");
            header("Access-Control-Allow-Headers: {$acao['acah']}");
            header("Access-Control-Max-Age: {$acao['acma']}");
        };
	
		header("Content-type: application/json");
		#header('content-length:'.mb_strlen($content));
		echo $content;
        #$t = Yii::app()->common->outout_time_delta($t, 'sendJSON');
		\Yii::app()->end();
        #die;
	}

    /**
     * arr : array('data'=>$data,
     *             'total_page'=>$total_page,
     *             'cur_page'=>$cur_page,
     *             'count'=>$count)
     */
    public static function succ($arr=array(), $status_name='succ', $succ_val = 1){
        $data=$arr;
        $total_page = 0;
        $cur_page = 1;
        $count = 0;
        $ret =  array($status_name=>$succ_val,'errormsg'=>'','errorfield'=>'');
        if(isset($arr['data'])){
            $data = $arr['data'];
        }
        if(isset($arr['total_page'])){
            $ret['total_page'] = $arr['total_page'];
        }
        if(isset($arr['cur_page'])){
            $ret['cur_page'] = $arr['cur_page'];
        }
        if(isset($arr['count'])){
            $ret['count'] = $arr['count'];
        }
        $ret['data'] = $data;

        self::sendJSON($ret);
    }

    /**
     * arr : array('field1'=>array('msg1', 'msg2'),
     *             'field2'=>array('msg1', 'msg2'))
     */
    public static function fail($arr=array(), $status_name='succ', $err_code = 0){
        $key = $msg = '';
        if(count($arr)>0){
            $keys = array_keys($arr);
            $key = $keys[0];
            $msg = $arr[$key][0];
        }
        $ret =  array($status_name=>$err_code,'errormsg'=>$msg,'errorfield'=>$key);
        self::sendJSON($ret);
    }
    /**
     *  succ: code($data)
     *  fail: code($data, $err_code)
     */
    public static function code($arr=array(), $code=0){
        if(is_string($code)){
            $code = self::err($code);
        }
        if($code==0){
            self::succ($arr, 'code', 0);
        }
        else{
            self::fail($arr, 'code', $code);
        }
    }

    /**
     *  succ: ret($data)
     *  fail: ret($data, $err_code)
     *  more ex: 
     *       self::ret();
     *       self::ret('AUTH');
     *       self::ret(1);
     *       self::ret(1,'some error message');
     *       self::ret(11,'some error message','errorfield');
     *       self::ret(array('fwef'=>array('eeewf eofwoe fwoe')), 1);
     */
    public static function ret($arr=array(), $code=0, $field=''){
        $a = $arr;
        $c = $code;
        if(is_numeric($arr) || is_string($arr)){
            $c = $arr;
            $a = array();
            if(is_array($code)){
                $a = $code;
            }
            else{
                $code = $code===0?'':$code;
                $a = array($field=>array($code));
            }
        }
        self::code($a, $c);
    }


    /**
     * ============================
     * db funcs
     * ============================
     */

    /**
     * ex: self::db()->select('*')
     *               ->from('tbl t')
     *               ->join('tbl t1', 't.id=t1.tid')
     *               ->where($wh)
     *               ->order($order)
     *               ->limit($offset, $limit)
     *               ->group('name, id')
     */
    public static function db($db='db', $sql=''){
        return \Yii::app()->$db->createCommand($sql);
    }
    public static function sql($sql='', $db='db'){
        return self::db($db, $sql);
    }

    /**
     * ============================
     * misc
     * ============================
     */

    /**
     * 使用curl来读取或发送数据
     * @param string $url
     * @param int $connecttime		连接时间
     * @param int $timeout	超时时间
     * @param string $postFields	使用POST方式请求
     * @return
     */
	public static function curl($url,$connecttime=10,$timeout=30,$postFields=''){
	    $ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connecttime);
	    curl_setopt($ch,CURLOPT_HEADER,0);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.8) Gecko/20100202 Firefox/3.5.8 GTB7.0');//IE7
	    curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
	    if($postFields){
	        if(is_array($postFields)){
	            $postFields = http_build_query($postFields);
	        }
	       //指定post数据
	        curl_setopt($ch, CURLOPT_POST, 1);
	        //添加变量
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
	    }
	    $result = curl_exec($ch);
	    if(curl_errno($ch)){
	        \Yii::log(curl_error($ch).'==>'.var_export(curl_getinfo($ch),true),'error','curlContent');
	        return '';
	    }
	    curl_close($ch);
	    return $result;
	}
    
    /**
     * 获取ip
     */
    public static function getIP() { //获取IP
    	if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
    		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    	}else if (!empty($_SERVER["HTTP_CLIENT_IP"])){
    		$ip = $_SERVER["HTTP_CLIENT_IP"];
    	}else if (!empty($_SERVER["REMOTE_ADDR"])){
    		$ip = $_SERVER["REMOTE_ADDR"];
    	}else if ((getenv("HTTP_X_FORWARDED_FOR"))){
    		$ip = getenv("HTTP_X_FORWARDED_FOR");
    	}else if ((getenv("HTTP_CLIENT_IP"))){
    		$ip = getenv("HTTP_CLIENT_IP");
    	}else if ((getenv("REMOTE_ADDR"))){
    		$ip = getenv("REMOTE_ADDR");
    	}else{
    		$ip = "Unknown";
    	}
    	return $ip;
    }

    /**
     * 生成ID唯一主键
     * @param unknown $pre 前缀
     * @return string
     */
    public static function genID($pre) { //生成主键ID
        list($usec, $sec) = explode(" ", microtime());
        $rand = rand(0,100);
        return ($pre.$sec.substr($usec,2,6));
    }

    public static function cguid(){
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        return strtoupper(md5(uniqid(rand(), true)));
    }

    public static function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            $charid = self::cguid();
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }

    public static function randstr($cnt=6){
        return substr( md5(rand()), 0, $cnt);
    }

    public static function randnum($cnt=6){
        $code = '';
        for($i=0; $i<$cnt; $i++){
            $code .= rand(0,9);
        }
        return $code;
    }

    public static function startWith($str, $s){
        return strpos($str, $s) === 0;
    }

    public static function captcha(){
        $builder = new \PCaptcha\CaptchaBuilder;
        $builder->build();
        self::session('phrase', $builder->getPhrase());
        #echo "<img src='{$builder->inline()}' />";
        #echo self::session('phrase');
        header('Content-type: image/jpeg');
        $builder->output();
    }

    /**
     * 发送邮件
     *
     * require: "phpmailer/phpmailer": "dev-master"
     *
     * add the conf to main.php -> params
     *  'email'=>array(
     *      'host' => 'smtp.163.com',
     *      'port' => 25,
     *      'account' => 'rong800test@163.com',
     *      'password' => ''
     *  ),
     *
     */
    public function sendMail($to,$subject,$content,$fromName='',$toName='',$html=true) { 
        $host = 'localhost';
        $port = 25;
        $account = '';
        $password = '';

        $email_conf = \Yii::app()->params['email'];
        if($email_conf){
            $host = $email_conf['host'];
            $port= $email_conf['port'];
            $account= $email_conf['account'];
            $password= $email_conf['password'];
        }

        $mail = new \PHPMailer;
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $account;
        $mail->Password = $password;
        $mail->Port = 25;        

        $mail->From = $account;
        $mail->FromName = $fromName;
        $mail->addAddress($to, $toName);

        $mail->WordWrap = 50;
        $mail->isHTML($html);

        $mail->Subject = $subject;
        $mail->Body    = $content;
        $mail->AltBody = $content;

        $mail->CharSet = "utf-8";

        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return 0;
        }
    }

    /**
     * 计算两个坐标之间的距离(米)
     * @param float $fP1Lat 起点(纬度)
     * @param float $fP1Lon 起点(经度)
     * @param float $fP2Lat 终点(纬度)
     * @param float $fP2Lon 终点(经度)
     * @return int
     */
    function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon){
        $fEARTH_RADIUS = 6378137;
        //角度换算成弧度
        $fRadLon1 = deg2rad($fP1Lon);
        $fRadLon2 = deg2rad($fP2Lon);
        $fRadLat1 = deg2rad($fP1Lat);
        $fRadLat2 = deg2rad($fP2Lat);
        //计算经纬度的差值
        $fD1 = abs($fRadLat1 - $fRadLat2);
        $fD2 = abs($fRadLon1 - $fRadLon2);
        //距离计算
        $fP = pow(sin($fD1/2), 2) +
            cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);
        return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
    }


    function rad($d) {
        define('PI',M_PI);
        define('EARTH_RADIUS',6371.3);
        return $d*PI/180.0;
    }
    /// <summary>
    /// 根据距离计算某点角度偏差
    /// </summary>
    /// <param name="lat">纬度</param>
    /// <param name="distance">距离（千米）</param>
    /// <param name="latDeviation">纬度偏差</param>
    /// <param name="lngDeviation">经度偏差</param>
    function getMaxDeviation($lat, $distance) {
        $radLat1 = $this->rad($lat);
        //另一种根据纬度计算地球半径的写法，因为极地半径和赤道半径有偏差。
        //EARTH_RADIUS = 6356.9088 + 20.9212 * (90.0 - lat) / 90.0;
        $latRatio = 180 / (PI * EARTH_RADIUS); //经线上1公里对应纬度偏差
        $lngRatio = $latRatio / cos($radLat1);//纬线上1公里对应经度偏差
        $latDeviation = $distance * $latRatio;
        $lngDeviation = $distance * $lngRatio;
        return array($latDeviation, $lngDeviation);
    }

    function getDates($start, $end){
        $dates = array($start);
        while(end($dates) < $end){
            $dates[] = date('Y-m-d', strtotime(end($dates).' +1 day'));
        }
        return $dates;
    }
}
