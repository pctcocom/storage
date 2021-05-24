<?php
namespace Pctco\Storage\App;
use think\facade\Config;
use Naucon\File\File;
/**
 *
 */
class UploadImage{
   function __construct(){
      $config = [
         'os'   =>   Config::get('initialize')['client']['domain']['os']
      ];
      $this->config = $config;
   }
   /**
   * @access 保存远程链接图片
   * @param mixed    $link		需要保存图片的链接
   * @param mixed    $path		保存路径  如 uploads/temp/
   * @param mixed    $date    保存路径日期
   * @param mixed    $FileName	保存的文件名称(默认md5)  true = 自动生成文件名
   * @param mixed    $curl		获取远程文件所采用的方法
   * @param mixed    $isOs    是否强制开启或关闭 os
   * @return array
   **/
   public function SaveLinkImage($link,$path,$date = ['y','m','d'],$FileName=true,$curl=false,$isOs = true){
      $link = trim($link);
      if(empty($link)){
         return [
            'prompt'   =>   'Link does not exist',
            'error' => 1
         ];
      }
      if(empty($path)){
         return [
            'prompt'   =>   'Path does not exist',
            'error' => 2
         ];
      }

      $SaveDate = '';
      if (is_array($date)){
         foreach ($date as $v) {
            $SaveDate .= date($v).DIRECTORY_SEPARATOR;
         }
      }else{
         $SaveDate = '';
      }
      $SavePath = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.$path.$SaveDate;

      // 创建文件名
      if($FileName){
         $ext = strrchr($link,'.');
         if(empty(in_array($ext,['.gif','.jpg','.jpeg','.png']))){
            return [
               'prompt'   =>   'Picture suffix is not supported',
               'error' => 3
            ];
         }
         $FileName = md5(time().rand(1,99999999)).$ext;
      }

      //创建保存目录
      if(!file_exists($SavePath) && !mkdir($SavePath,0777,true)){
         return [
            'prompt'   =>   'Create a save directory',
            'error' => 4
         ];
      }

      if($curl){
         // 普通
         $ch = curl_init();
         $timeout = 5;
         curl_setopt($ch,CURLOPT_URL,$link);
         curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
         curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);

         $img = curl_exec($ch);
         curl_close($ch);
      }else{
         ob_start();
         @readfile($link);
         $img = ob_get_contents();
         ob_end_clean();
      }

      $fp2 = @fopen($SavePath.$FileName,'a');
      fwrite($fp2,$img);
      fclose($fp2);
      unset($img,$link);


      $absolute = DIRECTORY_SEPARATOR.$path.$SaveDate.$FileName;
      if ($isOs) {
         if ($this->config['os']['use'] == 1) {
            $storage = new \Pctco\Storage\Processor();
            $upload = $storage->upload($path.$SaveDate.$FileName);
            if ($upload === true) {
               $absolute = $this->config['os']['domain'].$absolute;
               $fileObject = new File($SavePath.$FileName);
               $fileObject->delete();
            }
         }
      }

      return [
         'saveDate'=>$SaveDate,
         'fileName'=>$FileName,
         'path'=>[
            'relative'   =>   $SaveDate.$FileName,
            'system'   =>   $SavePath.$FileName,
            'absolute'   =>   $absolute,
         ],
         'error'=>0
      ];
	}
   /**
   * @access 保存base64数据为图片
   * @param mixed    $base64  base64编码
   * @param mixed    $path    保存路劲  entrance/uploads/temp/
   * @param mixed    $date    保存路径日期
   * @param mixed    $FileName    自动生产文件名
   * @param mixed    $isOs    是否强制开启或关闭 os
   * @return
   **/
	public function SaveBase64ToImage($base64,$path,$date = ['y','m','d'],$FileName = true,$isOs = true){
		//匹配出图片的格式
      if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
		   // 格式 .png
         $ext = $result[2];

         if ($FileName) {
            $FileName = md5(time().rand(1,99999999)).'.'.$ext;
         }else{
            $FileName = $FileName.'.'.$ext;
         }

         $SaveDate = '';
         if (is_array($date)){
            foreach ($date as $v) {
               $SaveDate .= date($v).DIRECTORY_SEPARATOR;
            }
         }else{
            $SaveDate = '';
         }

         $SavePath = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.$path.$SaveDate;

         //创建保存目录
         if(!file_exists($SavePath) && !mkdir($SavePath,0777,true)){
            return [
               'prompt'   =>   'Create a save directory',
               'error' => 3
            ];
         }

         $SavePath = $SavePath.$FileName;
         if (file_put_contents($SavePath,base64_decode(str_replace($result[1], '', $base64)))){


            $absolute = DIRECTORY_SEPARATOR.$path.$SaveDate.$FileName;
            if ($isOs) {
               if ($this->config['os']['use'] == 1) {
                  $storage = new \Pctco\Storage\Processor();
                  $upload = $storage->upload($path.$SaveDate.$FileName);
                  if ($upload === true) {
                     $absolute = $this->config['os']['domain'].$absolute;
                     $fileObject = new File($SavePath);
                     $fileObject->delete();
                  }
               }
            }

            return [
               'date'=>$SaveDate,
               'fileName'=>$FileName,
               'path'=>[
                  'relative'   =>   $SaveDate.$FileName,
                  'system'=>$SavePath,
                  'absolute'=>$absolute,
               ],
               'error' => 0
            ];
         }else{
            return [
               'prompt'   =>   'base64 Conversion failed',
               'error'	=>	2
            ];
         }
      }else{
			return [
            'prompt'   =>   'Link format error',
            'error'	=>	1
         ];
      }
	}
   /**
   * @access 图片 转 base64
   * @param mixed $image 图片文件 本地图片或远程链接图片
   * @return string base64 code
   **/
   public function ImageToBase64($image) {
      $base64 = '';
      $http = preg_match("/^http(s)?:\\/\\/.+/",$image);
      if($http){
          $link = $this->SaveLinkImage($image,'uploads/temp/',['y','m'],true,false,false);
          if ($link['error'] != 0) {
             return [
                'status' => 'warning',
                'prompt' => $link['prompt']
             ];
          }
          $image = $link['path']['system'];
      }

      $info = getimagesize($image);
      $data = fread(fopen($image, 'r'), filesize($image));
      $base64 = 'data:' . $info['mime'] . ';base64,' . chunk_split(base64_encode($data));
      if ($http) {
         $fileObject = new File($image);
         $fileObject->delete();
      }
      return $base64;
   }
}
