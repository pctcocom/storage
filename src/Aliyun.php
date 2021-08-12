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
   * @name 字符串上传
   * @describe putObject
   * @param mixed $file 文本路径
   * @param mixed $content 字符串内容
   * @return boolean
   **/
   public function put($file,$content){
      try {
         $this->client->putObject($this->config['bucket'],$file,$content);
         return true;
      } catch (OssException $e) {
         return $e->getMessage();
      }
   }
   /**
   * @name 字符串读取
   * @describe getObject
   * @param mixed $file 文本路径
   * @return boolean
   **/
   public function get($file){
      try {
         return $this->client->getObject($this->config['bucket'],$file);
      } catch (OssException $e) {
         // return $e->getMessage();
         return false;
      }
   }
   /**
   * @name 列举文件
   * @describe listObjects
   * @param mixed $dir 文件夹路径 dir/
   * @return boolean
   **/
   public function list($dir){
      try {
         $info =
         $this->client->listObjects($this->config['bucket'],[
            'delimiter' => '/',
            'prefix' => $dir,
            'max-keys' => 1000,
            'marker' => '',
         ]);

         $objectList = $info->getObjectList(); // object list

         if (!empty($objectList)) {
            return $objectList;
         }
         return false;
      } catch (\Exception $e) {
         return false;
      }
   }
   /**
   * @name 文件拷贝
   * @describe copyObject https://help.aliyun.com/document_detail/88514.html
   * @param mixed $FromFile 文本路径(从)
   * @param mixed $ToFile 文本路径(到)
   * @return boolean
   **/
   public function copy($FromFile,$ToFile){
      try {
         return $this->client->copyObject($this->config['bucket'],$FromFile,$this->config['bucket'],$ToFile);
      } catch (OssException $e) {
         return $e->getMessage();
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
         if (substr($file, -1) === '/') { // 删除文件夹中所有文件包括文件夹
            $file = $this->list($file);
            if ($file !== false) {
               $files = [];
               foreach ($file as $v) {
                  $files[] = $v->getKey();
               }
               $file = $files;
            }
         }

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
