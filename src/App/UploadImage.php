<?php
namespace Pctco\Storage\App;
use think\facade\Config;
use think\facade\Cache;
use Naucon\File\File;
use Pctco\Verification\Regexp;
use Pctco\Storage\Processor;
/**
 *
 */
class UploadImage{
   function __construct(){
      $config = [
         'os'   =>   Cache::store('config')->get(md5('app\admin\controller\Config\storage'))
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

      try {
         file_get_contents($link);
      } catch (\Exception $e) {
         return [
            'prompt'   =>   'Link has expired',
            'error' => 5
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
         $isImg = new Regexp($link);
         if ($isImg->check('format.link.img')) {
            $ext = strrchr($isImg->RemoveUrlParam(),'.');
            $FileName = md5(time().rand(1,99999999)).$ext;
         }else{
            return [
               'prompt'   =>   'Picture suffix is not supported',
               'error' => 3
            ];
         }
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
            $storage = new Processor();
            $upload = $storage->upload($path.$SaveDate.$FileName);
            if ($upload === true) {
               $absolute = $this->config['os']['domain'].$absolute;
               $file = new File($SavePath.$FileName);
               $file->delete();
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
                  $storage = new Processor();
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

   /**
   * @name 抓取保存图片
   * @describe 从文本内容中抓取 图片文件 本地图片或远程链接图片  或 替换 对象存储链接
   * @param mixed $content html or md 内容
   * @param mixed $type .md or .html
   * @param mixed $dir uploads/books/
   * @param mixed $date ['y','m']  or ''
   * @return String
   **/
   public function GrabSave($content,$type,$dir,$date = ['y','m']){
      $regexp = new Regexp($content);

      /**
      * @name base64 图片
      **/
      $BArr = $regexp->find('html.img.src.base64');
      if ($BArr !== false) {
         foreach ($BArr as $base64) {
            $image = $this->SaveBase64ToImage($base64,$dir,$date);
            if ($image['error'] == 0) {
               $path_absolute = $image['path']['absolute'];
               if ($this->config['os']['use'] == 1) {
                  $path_absolute = str_replace($this->config['os']['domain'],'',$path_absolute);
               }
               $content = str_replace($base64, $this->config['os']['var'].DIRECTORY_SEPARATOR.$path_absolute, $content);
            }
         }
      }
      /**
      * @name image 外链图片
      **/
      if ($type === '.md') {
         $LArr = $regexp->find('markdown.img.link');
      }else{
         $LArr = $regexp->find('html.img.src.link');
      }
      if ($LArr !== false) {
         foreach ($LArr as $DLink) {
            $DLinkDoname = parse_url($DLink);
            if (empty($DLinkDoname['scheme'])) {
               $DLinkDoname = '';
            }else{
               $DLinkDoname = $DLinkDoname['scheme'].'://'.$DLinkDoname['host'];
            }

            // 过滤掉不需下载的图片
            if (empty(in_array(
               $DLinkDoname,[
                  '',
                  $this->config['os']['var'],
                  $this->config['os']['domain']
               ]
            ))) {
               $image =
               $this->SaveLinkImage($DLink,$dir,$date);
               if ($image['error'] == 0) {
                  $path_absolute = $image['path']['absolute'];
                  if ($this->config['os']['use'] == 1) {
                     $path_absolute = str_replace($this->config['os']['domain'],'',$path_absolute);
                  }
                  $content = str_replace($DLink, $this->config['os']['var'].$path_absolute, $content);
               }
            }else{
               // 编辑内容时 需要重新加上{os}
               if (strstr($DLink,$this->config['os']['var']) === false) {
                  if ($DLinkDoname == '') {
                     $content = str_replace($DLink,$this->config['os']['var'].$DLink,$content);
                  }else{
                     $image = str_replace($this->config['os']['domain'].'/'.$dir,'',$DLink);
                     $content = str_replace($DLink,$this->config['os']['var'].'/'.$dir.$image,$content);
                  }
               }
            }
         }
      }
      return $content;
   }
   /**
   * @name 抓取绝对图片链接保存图片
   * @describe 从文本内容中抓取 图片文件 本地图片或远程链接图片 并且保存
   * @param mixed $content html or md 内容
   * @param mixed $type .md or .html
   * @return String
   **/
   public function GrabAbsoluteSave($content,$type,$dir,$date = ['y','m']){
      $regexp = new Regexp($content);

      if ($type === '.md') {
         $LArr = $regexp->find('markdown.img.link');
      }else{
         $LArr = $regexp->find('html.img.src.link');
      }
      if ($LArr !== false) {
         foreach ($LArr as $DLink) {
            $LinkImage = str_replace($this->config['os']['var'],$this->config['os']['domain'], $DLink);
            $image =
            $this->SaveLinkImage($LinkImage,$dir,$date);
            if ($image['error'] == 0) {
               $path_absolute = $image['path']['absolute'];
               if ($this->config['os']['use'] == 1) {
                  $path_absolute = str_replace($this->config['os']['domain'],'',$path_absolute);
               }
               $content = str_replace($DLink, $this->config['os']['var'].$path_absolute, $content);
            }
         }
      }
      return $content;
   }
   /**
   * @name 抓取删除图片
   * @describe 从文本内容中抓取 图片文件 并且删除
   * @param mixed $content html or md 内容
   * @param mixed $type .md or .html
   * @return String
   **/
   public function GrabDel($content,$type){
      $regexp = new Regexp($content);
      $arr = [];
      if ($type === '.md') {
         $arr = $regexp->find('markdown.img.link');
      }else{
         $arr = $regexp->find('html.img.src.link');
      }
      if (!empty($arr)) {
         foreach ($arr as $link) {
            $DLinkDoname = parse_url($link);
            if (empty($DLinkDoname['scheme'])) {
               $DLinkDoname = '';
            }else{
               $DLinkDoname = $DLinkDoname['scheme'].'://'.$DLinkDoname['host'];
            }
            if (in_array(
               $DLinkDoname,[
                  '',
                  $this->config['os']['var']
               ]
            )) {
               $image = str_replace($this->config['os']['var'].DIRECTORY_SEPARATOR,'',$link);
               if ($this->config['os']['use'] != 1) {
                  $file = new File(app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.$image);
                  $file->delete();
               }
               if ($this->config['os']['use'] == 1) {
                  $storage = new Processor();
                  $storage->delete($image);
               }
            }
         }
      }
   }
   /**
   * @name replace path
   * @describe 替换对象存储链接内容的图片链接域名 ()
   * @param string $content 内容
   * @return string
   **/
   public function repla($content,$type = '.md'){
      if ($type !== '.md') {
         $content = htmlspecialchars_decode($content);
      }
      if ($this->config['os']['use'] == 1) {
         $content = str_replace($this->config['os']['var'],$this->config['os']['domain'],$content,$i);
      }else{
         $content = str_replace($this->config['os']['var'],'',$content,$i);
      }
      return $content;
   }
   /**
   * @name Repla Link Os
   * @describe 替换图片链接 {os}
   * @param string $link 图片链接
   * @param string $to true = link=>{os}, false = {os}=>link
   * @return string
   **/
   public function ReplaLinkOs($link,$to){
      if ($this->config['os']['use'] == 1) {
         if ($to === true) {
            $link = str_replace($this->config['os']['domain'],$this->config['os']['var'],$link,$i);
         }else{
            $link = str_replace($this->config['os']['var'],$this->config['os']['domain'],$link,$i);
         }

      }else{
         if ($to === true) {
            $link = str_replace('',$this->config['os']['var'],$link,$i);
         }else{
            $link = str_replace($this->config['os']['var'],'',$link,$i);
         }
      }
      return $link;
   }
}
