<?php
namespace Pctco\Storage;
include 'phar://'.app()->getRootPath().'vendor/pctco/baidu-cloud-sdk/src/BaiduBce.phar/vendor/autoload.php';
use BaiduBce\BceClientConfigOptions;
use BaiduBce\Util\MimeTypes;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Services\Bos\BosClient;
class BaiDu{
   private $config;
   private $client;
   /**
   * @name 构造 配置信息
   * @describe __construct
   **/
   function __construct($config){
      $this->config = $config;
      try {
         $this->client = new BosClient($this->config);
      } catch (\BaiduBce\Exception\BceBaseException $e) {
         exit($e->getStatusCode());
      }
   }
   /**
   * @name 单文件上传
   * @describe putObjectFromFile
   * @param mixed $file 路径/内容
   * 单文件 uploads/sp.mp4
   * @return boolean
   **/
   public function upload($file){
      try {
         if (is_array($file)) {
            foreach ($file as $v) {
               $this->client->putObjectFromFile($this->config['credentials']['bucket'],$v,app()->getRootPath().'entrance/'.$v);
            }
         }else{
            $this->client->putObjectFromFile($this->config['credentials']['bucket'],$file,app()->getRootPath().'entrance/'.$file);
         }
         return true;
      } catch (\BaiduBce\Exception\BceBaseException $e) {
         return $e->getStatusCode();
      }
   }
   /**
   * @name 删除单个文件
   * @describe deleteObject
   * @param mixed $file 路径/内容
   * 单文件 uploads/sp.mp4
   * @return boolean
   **/
   public function delete($file){
      try {
         if (is_array($file)) {
            foreach ($file as $v) {
               $this->client->deleteObject($this->config['credentials']['bucket'],$v);
            }
         }else{
            $this->client->deleteObject($this->config['credentials']['bucket'],$file);
         }
         return true;
      } catch (\BaiduBce\Exception\BceBaseException $e) {
         return $e->getStatusCode();
      }
   }
   /**
   * @name 判断文件是否存在
   * @describe getObjectMetadata
   * @param mixed $file 路径/内容
   * 单文件 uploads/sp.mp4
   * @return boolean
   **/
   public function exist($file){
      try {
         $this->client->getObjectMetadata($this->config['credentials']['bucket'],$file);
         return true;
      } catch (\BaiduBce\Exception\BceBaseException $e) {
         return $e->getStatusCode();
      }
   }
}
