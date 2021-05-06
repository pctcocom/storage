<?php
namespace Pctco\Storage;
use think\facade\Cache;
class Processor{
   private $client;
   /**
   * @access 配置
   * @return
   **/
   function __construct(){
      // Endpoint（地域节点)
      // Bucket 域名
      $config = Cache::store('config')->get(md5('app\admin\controller\Config\storage'));
      $os = $config[$config['app']];
      switch ($config['app']) {
         case 'AliyunOss':
            $this->client = new \Pctco\Storage\Aliyun($os);
            break;
         case 'BaiDuCloudBos':
            $this->client = new \Pctco\Storage\BaiDu($os);
            break;
         case 'TencentCloudCos':
            $this->client = new \Pctco\Storage\Tencent($os);
            break;

         default:
            // code...
            break;
      }

   }
   /**
   * @name upload
   * @describe 上传文件
   * @param string or array $file
   * @return boolean
   **/
   public function upload($file){
      return $this->client->upload($file);
   }
   /**
   * @name delete
   * @describe 删除文件
   * @param string or array $file
   * @return boolean
   **/
   public function delete($file){
      return $this->client->delete($file);
   }
   /**
   * @name exist
   * @describe 判断文件是否存在
   * @param string $file
   * @return boolean
   **/
   public function exist($file){
      return $this->client->exist($file);
   }
}
