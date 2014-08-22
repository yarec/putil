<?php

namespace PUtil;

trait YiiUtils {
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
    public static function error($m){
        \Yii::log($m, 'error');
    }
    public static function info($m){
        \Yii::log($m, 'error');
    }
    public static function succ($arr=[], $status_name='succ', $succ_val = 1){
        $ret =  array($status_name=>$succ_val,'errormsg'=>'','errorfield'=>'', 'data'=>$arr);
        self::sendJSON($ret);
    }
    public static function fail($arr=[], $status_name='succ', $err_code = 0){
        $key = $msg = '';
        if(count($arr)>0){
            $keys = array_keys($arr);
            $key = $keys[0];
            $msg = $arr[$key][0];
        }
        $ret =  array($status_name=>$err_code,'errormsg'=>$msg,'errorfield'=>$key);
        self::sendJSON($ret);
    }
    public static function code($arr=[], $code=0){
        if($code==0){
            self::succ($arr, 'code', 0);
        }
        else{
            self::fail($arr, 'code', $err_code);
        }
    }
}

trait HelloWorld {
    public function sayHello() {
        echo "Hello World! </br>\n";
    }
}

class TheWorldIsNotEnough {
    use HelloWorld;
    public function say() {
        $this->sayHello();
        echo 'Hello Universe!';
    }
}


class Person {
    var $job = "person";
    function show_job() {
        echo "Hi, I work as a {$this->job}.";
    }
}

class Bartender {
    var $job = "bartender";
    function show_job() {
        echo "BARTENDER: ";
        Person::show_job();
    }
}

class Bartender1 {
    var $job = "bartender1";
    #mixin( "Person" );
}

/*
$b = new Bartender;
mixin( $b, "Person" );
*/
