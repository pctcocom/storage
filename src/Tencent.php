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
   * @name 字符串上传
   * @describe putObject
   * @param mixed $file 文本路径
   * @param mixed $content 字符串内容
   * @return boolean
   **/
   public function put($file,$content){
      try {
         $this->client->putObject([
            'Bucket'   =>   $this->config['bucket'],
            'Key'   =>   $file,
            'Body'   =>   $content
         ]);
         return true;
      } catch (\Exception $e) {
         return "$e\n";
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
         // $data =
         // $this->client->getObject([
         //    'Bucket'   =>   $this->config['bucket'],
         //    'Key'   =>   $file
         // ])->toArray();
         // $body = $data['Body'];

         return file_get_contents('https://'.$this->config['bucket'].'.cos.'.$this->config['region'].'.myqcloud.com/'.$file);
      } catch (\Exception $e) {
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
         $data =
         $this->client->listObjects([
            'Bucket' => $this->config['bucket'],
            'Delimiter' => '',
            'EncodingType' => 'url',
            'Marker' => '',
            'Prefix' => $dir,
            'MaxKeys' => 1000,
         ])
         ->toArray();

         if (!empty($data['Contents'])) {
            return $data['Contents'];
         }
         return false;
      } catch (\Exception $e) {
         return false;
      }
   }
   /**
   * @name 文件拷贝
   * @describe copyObject https://help.aliyun.com/document_detail/88514.html
   * @param mixed $file 文本路径
   * @return boolean
   **/
   public function copy($FromFile,$ToFile){
      try {
         return $this->client->copyObject([
            'Bucket'   =>   $this->config['bucket'],
            'Key'   =>   $FromFile,
            'CopySource'   =>   $this->config['bucket'].'.cos.'.$this->config['region'].'.myqcloud.com/'.$ToFile
         ]);
      } catch (\Exception $e) {
         return "$e\n";
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

         if (substr($file, -1) === '/') { // 删除文件夹中所有文件包括文件夹
            $file = $this->list($file);
            if ($file !== false) {
               $file = array_reverse(array_column($file,'Key'));
            }
         }

         if (is_array($file)) { // 批量删除
            foreach ($file as $v) {
               $this->client->deleteObject([
                  'Bucket' => $this->config['bucket'],
                  'Key' => urldecode($v)
               ]);
            }
         }else{ // 单文件删除
            if ($file !== false) {
               $this->client->deleteObject([
                  'Bucket' => $this->config['bucket'],
                  'Key' => $file
               ]);
            }
            
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
      try {
         return $this->client->headObject(array(
            'Bucket' => $this->config['bucket'],
            'Key' => $file
         ));
         return true;
      } catch (\Exception $e) {
         return false;
      }
   }
}
