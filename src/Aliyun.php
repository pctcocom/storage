<?php
namespace Pctco\Storage;
use OSS\OssClient;
use OSS\Core\OssException;
use OSS\Core\OssUtil;
class Aliyun{
   private $config;
   private $client;
   /**
   * @name 构造 配置信息
   * @describe __construct
   **/
   function __construct($config){
      $this->config = $config;
      try {
          $this->client = new OssClient($this->config['AccessKeyId'],$this->config['AccessKeySecret'],$this->config['endpoint']);
      } catch (OssException $e) {
          exit($e->getMessage());
      }
   }
   /**
   * @name 单文件上传 AND 批量文件上传
   * @describe uploadFile
   * @param mixed $file 路径/内容
   * 单文件 uploads/sp.mp4
   * 批量文件 [uploads/1.mp4,uploads/2.mp4]
   * @return boolean
   **/
   public function upload($file){
      try {
         if (is_array($file)) {
            foreach ($file as $f) {
               $this->client->uploadFile($this->config['bucket'],$f,app()->getRootPath().'entrance/'.$f);
            }
         }else{
            $this->client->uploadFile($this->config['bucket'],$file,app()->getRootPath().'entrance/'.$file);
         }

         return true;
      } catch (OssException $e) {
         return $e->getMessage();
      }
   }
   /**
   * @name 删除单个文件 AND 删除多个文件
   * @describe deleteObject
   * @param mixed $deleteFile oss路径/内容
   * 单文件 uploads/sp.mp4
   * 批量文件 [uploads/1.mp4,uploads/2.mp4]
   * @return boolean
   **/
   public function delete($file){
      try {
         if (is_array($file)) {
            $this->client->deleteObjects($this->config['bucket'],$file);
         }else{
            $this->client->deleteObject($this->config['bucket'],$file);
         }
         return true;
      } catch (OssException $e) {
         return $e->getMessage();
      }
   }
   /**
   * @name 判断文件是否存在
   * @describe doesObjectExist
   * @param mixed $file oss路径/内容
   * uploads/sp.mp4
   * @return boolean
   **/
   public function exist($file){
      try{
         return $this->client->doesObjectExist($this->config['bucket'],$file);
      } catch(OssException $e) {
         return $e->getMessage();
      }
   }
}
