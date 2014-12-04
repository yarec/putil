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
        $uid = Yii::app()->session[$key];
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
        $this->page= isset($_GET['page'])?$_GET['page']:1;
        $this->pagesize= isset($_GET['pagesize'])?$_GET['pagesize']:10;
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
     *  self::get('type', -1);    
     */
    public static function get($key, $default=''){
        if(isset($_GET[$key]) && !empty($_GET[$key])){
            return $_GET[$key];
        } else{
            return $default;
        }
    }

    /**
     *  self::post('type', -1);    
     */
    public static function post($key, $default=''){
        if(isset($_POST[$key]) && !empty($_POST[$key])){
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
            $pathroot=realpath(dirname(__FILE__)."/../../../");
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
     *       self::ret(1);
     *       self::ret(1,'some error message');
     *       self::ret(11,'some error message','errorfield');
     *       self::ret(array('fwef'=>array('eeewf eofwoe fwoe')), 1);
     */
    public static function ret($arr=array(), $code=0, $field=''){
        $a = $arr;
        $c = $code;
        if(is_numeric($arr)){
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

    /**
     * 发送邮件
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

        $mail = new PHPMailer;
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

        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return 0;
        }
    }
}
