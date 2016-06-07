<?php
require_once 'BaiduBce.phar';

use BaiduBce\BceClientConfigOptions;
use BaiduBce\Util\Time;
use BaiduBce\Util\MimeTypes;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Services\Bos\BosClient;

define(FILE_TYPE_IMAGE,'bmp|gif|jpg|jpeg|png');
class BosHandler{
  private $client;
  public static $error;
  public static $errno;
  private static $initno=0;
  private $option=['bucketname'=>'','temp_dir'=>'','maxsize'=>4194304,'typeallow'=>null];

  public function __construct($config,$bucketname,$temp_dir) {
    try{
      $this->client = new BosClient($config);
    }catch(Exception $e){
      $this->client=null;
      return null;
    }
    if(isset($config['BosHandler_option']))
      $this->option=$config['BosHandler_option'];

    if(isset($temp_dir))
      $this->option['temp_dir']=$temp_dir;

    if(isset($bucketname))
      $this->option['bucketname']=$bucketname;
  }
  public function set($option){
    if(is_array($option))
      foreach ($option as $key => $value) {
        $this->option[$key]=$value;
      }
  }
  public static function checkTypeAllow($fileName,$typeAllow){
    if(empty($typeAllow)) return true;
    $allow_file = explode("|", strtolower($typeAllow));
    $ext=strtolower(end(explode(".", $fileName)));
    if (!in_array($ext,$allow_file)){
      return false;
    }else{
      return true;
    }
  }
  public function checkSizeAllow($size){
    $maxsize=$this->option['maxsize'];
    if(!is_numeric($maxsize))
      return true;
    if($size>$maxsize){
      return false;
    }else{
      return true;
    }
  }
  private static function setError($errno,$msg=''){
    self::$errno=$errno;
    $errorMsg='';
    switch ($errno){
      case 1:   
        $errorMsg='无效的文件变量';
        break;
      case 2:   
        $errorMsg=$msg;
        break;
      case 3:   
        $errorMsg='未指定bucketname';
        break;
      case 4:   
        $errorMsg='未指定缓冲目录';
        break;
      case 5:   
        $errorMsg='超过后台指定最大文件大小';
        break;
      case 6:   
        $errorMsg='不支持此类型文件上传';
        break;
      case 7:   
        $errorMsg='缓冲目录不存在且创建失败';
        break;
      case 8:   
        $errorMsg='文件不合法或无法移动';
        break;
      case 9:   
        $errorMsg='指定文件不是上传文件';
        break;
      case 10:   
        $errorMsg='BosClient不可用';
        break;
      case 11:   
        $errorMsg='上传到BOS时抛出异常';
        break;
      case 12:   
        $errorMsg='上传到BOS失败';
        break;
      case -1:   
        $errorMsg='警告:缓存文件未删除';
        break;
    }
    self::$error=$errorMsg;
    if($errno>0)
      return self::initError(false);
    else
      return true;
  }
  private static function initError($ret){
    if(self::$initno==0)
      self::setError(0);
    if(isset($ret))
      self::$initno-=1;
    else
      self::$initno+=1;
    return $ret;
  }
  public function ulf($file,$addr,$option){
    self::initError();
    if(!empty($addr)){
      if(preg_match('/^.*?(?=\/)/',$addr,$match)){
        $bucketname=$match[0];
      }
      $folder='';
      if(preg_match('/\/(.*)\//',$addr,$match)){
        $folder=$match[1].'/';
      }
      if(preg_match('/.*\/(.*?)$/',$addr,$match)){
        $name=$match[1];
      }
    }
    if(!isset($file)||!isset($file['error'])) return self::setError(1);
    if($file['error']>0) return self::setError(2,self::fileErrorMsg($file['error']));
    if(empty($bucketname)){
      if(empty($this->option['bucketname']))
        return self::setError(3);
      else
        $bucketname=$this->option['bucketname'];
    }
    if(empty($this->option['temp_dir']))
      return self::setError(4);
    if(!$this->checkSizeAllow($file['size'])) return self::setError(5);
    $typeAllow=$this->option['typeallow'];
    if(!self::checkTypeAllow($file['name'],$typeAllow)) return self::setError(6);
    if(empty($name))
      $name=$file['name'];
    $new_file_name=$this->option['temp_dir'].'/'.$name;
    if(!self::moveUploadedFile($file['tmp_name'],$new_file_name)){
      return self::initError(false);
    }
    chmod($new_file_name,0600);
    $objkey=$folder.$name;
    if(!($ret=$this->upload($bucketname,$objkey,$new_file_name,$option)))
      return self::initError(false);
    if(!unlink($new_file_name))
      self::setError(-1);
    self::initError(true);
    return $ret?$ret:false;
  }
  public static function fileErrorMsg($error){
    $errorMsg='';
    if ($error > 0){
        $errorMsg.= 'File_Error: ';
        switch ($error){
          case 1:   
            $errorMsg.='文件大小超过upload_max_filesize';
            break;
          case 2:   
            $errorMsg.='文件大小超过max_file_size';
            break;
          case 3:   
            $errorMsg.='文件只有部分被上传';
            break;
          case 4:   
            $errorMsg.='没有文件被上传';
            break;
          case 6:   
            $errorMsg.='找不到临时文件夹';
            break;
          case 7:   
            $errorMsg.='文件写入失败';
            break;
        }
    }
    return $errorMsg;
  }
  public static function moveUploadedFile($tmp_name,$fileName){
    self::initError();
    $dir=dirname($fileName);
    if(!is_dir($dir)){
      if(!mkdir($dir)){
        return self::setError(7);
      }
    }
    if (is_uploaded_file($tmp_name)){
      if(!move_uploaded_file($tmp_name, $fileName)){
        return self::setError(8);
      }else{
        return self::initError(true);
      }
    }else{
      return self::setError(9);
    }
  }
  public function upload($bucketname,$objkey,$fileName,$option){
    self::initError();
    if(!$this->client) return self::setError(10);
    try{
      if(isset($option))
        $ret=$this->client->putObjectFromFile($bucketname,$objkey,$fileName,$option);
      else 
        $ret=$this->client->putObjectFromFile($bucketname,$objkey,$fileName);
    }catch(Exception $e){
      return self::setError(11);
    }
    if($ret){
      self::initError(true);
      return $ret;
    }else
      return self::setError(12);
  }
}
?>