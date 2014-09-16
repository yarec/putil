<?php

namespace PUtil;

trait YiiUtils {

    public $order= 'desc';
    public $id= null;
    public $aid= null;
    public $page= null;
    public $offset= null;
    public $pagesize= null;
    public $input = null;
    public $data = null;

    /**
     * public function filters() {
     *     return array(  
     *         'initdata',
     *         'init',  
     *     );  
     * }  
     */
    public function filterInitdata($filterChain) {  
        $baseurl = Yii::app()->getBaseUrl(true);
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

    public function getCountArray($count){
        $ret['cur_page'] = $this->page;
        $ret['total_page'] = ceil($count/$this->pagesize);
        $ret['count'] = $count;
        return $ret;
    }

    public static function error($m){
        \Yii::log($m, 'error');
    }
    public static function info($m){
        \Yii::log($m, 'error');
    }

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
            self::fail($arr, 'code', $err_code);
        }
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
     * ex: self::db()->select('*')
     *               ->from('tbl t')
     *               ->join('tbl t1', 't.id=t1.tid')
     *               ->where($wh)
     *               ->order($order)
     *               ->limit($offset, $limit)
     *               ->group('name, id')
     */
    public static function db($db='db', $sql=''){
        return Yii::app()->$db->createCommand($sql);
    }
    public static function sql($sql='', $db='db'){
        return self::db($db, $sql);
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
