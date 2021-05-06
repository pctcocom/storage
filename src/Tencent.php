<?php
namespace Pctco\Storage;
class Tencent{
   private $config;
   private $client;
   /**
   * @name 构造 配置信息
   * @describe __construct
   **/
   function __construct($config){
      $this->config = $config;
      try {
         $this->client = new \Qcloud\Cos\Client(array_diff_key($this->config,['bucket'=>'xy']));
      } catch (\Exception $e) {
         exit('error');
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
               $path = app()->getRootPath().'entrance/'.$v;
               $files = fopen($path,'rb');
               if ($files) {
                  $this->client->Upload(
                     $bucket = $this->config['bucket'],
                     $key = $v,
                     $body = $files
                  );
               }
            }
         }else{
            $path = app()->getRootPath().'entrance/'.$file;
            $files = fopen($path,'rb');
            if ($files) {
               $this->client->Upload(
                  $bucket = $this->config['bucket'],
                  $key = $file,
                  $body = $files
               );
            }
         }

         return true;
      } catch (\Exception $e) {
         echo "$e\n";
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
               $this->client->deleteObject([
                  'Bucket' => $this->config['bucket'],
                  'Key' => $v
               ]);
            }
         }else{
            $this->client->deleteObject([
               'Bucket' => $this->config['bucket'],
               'Key' => $file
            ]);
         }

         return true;
      } catch (\Exception $e) {
         echo "$e\n";
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

   }
}
