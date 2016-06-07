<?php
require_once 'BaiduBce.phar';
require_once 'BosHandler.php';
require_once 'SampleConf.php';

use BaiduBce\BceClientConfigOptions;
use BaiduBce\Util\Time;
use BaiduBce\Util\MimeTypes;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Services\Bos\BosClient;

header("Content-Type:text/html; charset=utf-8");
global $BOS_CONFIG;
$uploadbos=new BosHandler($BOS_CONFIG);
$option=['bucketname'=>'yourbucketname','temp_dir'=>'a writable dir','maxsize'=>null,'typeallow'=>null];
/*you can set option with $_GET*/
if($_GET['bucketname']){
  $option['bucketname']=$_GET['bucketname'];
  echo $_GET['bucketname'].'<br>';
}
if($_GET['temp_dir']){
  $option['temp_dir']=$_GET['temp_dir'];
  echo $_GET['temp_dir'].'<br>';
}
if($_GET['maxsize']){
  $option['maxsize']=$_GET['maxsize'];
  echo $_GET['maxsize'].'<br>';
}
if($_GET['typeallow']){
  $option['typeallow']=$_GET['typeallow'];
  echo $_GET['typeallow'].'<br>';
}
$addr='';
if($_GET['addr']){
  $addr=$_GET['addr'];
  echo $_GET['addr'].'<br>';
}

$uploadbos->set($option);
$uploadbos->ulf($_FILES['file'],$addr);
/*echo error number and msg*/
echo BosHandler::$errno."<br>";
echo BosHandler::$error."<br>";

?>
<form enctype="multipart/form-data"  method=post>
<input type="hidden" name="MAX_FILE_SIZE" value="202400">
Upload this file: <input name="file" type="file">
<input type="submit" value="Send File">
</form>