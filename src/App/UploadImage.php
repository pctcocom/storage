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
    ** 保存远程链接图片
    ** SaveLinkImage($link,$path,$date = ['y','m','d'],$FileName=true,$curl=false,$isOs = true)
    *? @date 21/12/03 16:25
    *  @param String $link		需要保存图片的链接
    *  @param String $path		保存路径  如 uploads/temp/
    *  @param Array $date    保存路径日期
    *  @param Boolean $FileName	保存的文件名称(默认md5)  true = 自动生成文件名
    *  @param Boolean $curl		获取远程文件所采用的方法
    *  @param Boolean $isOs    是否强制开启或关闭 os
    *! @return Array
    */
   public function SaveLinkImage($link,$path,$date = ['y','m','d'],$FileName=true,$curl=false,$isOs = true){
      $link = trim($link);
      if(empty($link)){
         return [
            'prompt'   =>   'Link does not exist',
            'error' => 1
         ];
      }

      try {
         $ext = false;
         $getimagesize = getimagesize($link);
         $image = preg_replace('/image\//','.',$getimagesize['mime'],1);
         if (!empty($image)) {
            if ($image === $getimagesize['mime']) {
               return [
                  'prompt'   =>   'Link has expired',
                  'error' => 5
               ];
            }else{
               $ext = $image;
            }
         }else{
            /** 
             ** 处理.svg
             *? @date 22/08/02 03:13
             */
            $image = parse_url($link);
            if (empty($image['path'])) {
               $ext = false;
            }else{
               $ext = strrchr($image['path'],'.');
               if ($ext !== '.svg') $ext = false;
            }
            
         }
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
      if($FileName === true){
         if ($ext !== false) {
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
      $regexp = new Regexp($base64);
      $result = $regexp->check('format.img.base64');
      if ($result !== false){
		   // 格式 png
         $ext = $result[2];

         if ($ext === 'svg+xml') $ext = 'svg';

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
      $isImg = new Regexp($image);
      $image = $isImg->RemoveUrlParam();
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
      $ext = strrchr($image,'.');

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
    ** 内容中的图片链接处理
    *? @date 21/12/03 18:03
    *  @param String $is 'save' = 保存内容 , 'save-all' = 全部保存(包括图片附件都会全部下载保存下来) , 'view' = 查看内容 , 'delete' = 删除内容中的图片
    *  @param String $FileFormat  文件格式：文本内容类型 .md or .html
    *  @param String $content  文本内容
    *  @param Number $NewText  $text = new \Pctco\Storage\App\Text(1,'','','','article','article');
    *  @param String $DiskPath  磁盘存储路径 uploads/article/
    *  @param Array $DiskPathDate  磁盘存储路径日期归档 ['y','m']
    *  @param Boolean $ExternalLink 是否开启外链替换成内容
    *! @return 
    */
   public function content($options = []){
      $options = (Object)$options;
      $content = $options->content;
      $regexp = new Regexp($content);
      if ($options->NewText !== false) {
         $NewTextPath = ltrim($options->NewText->path,DIRECTORY_SEPARATOR);
      }
      /** 
       ** 保存内容(save)
       *? @date 21/12/03 18:05、
       *! @return 
       */
      if ($options->is === 'save') {
         /** @name base64 图片 **/
         $src = $regexp->find('html.img.src.base64');
         if ($src !== false) {
            foreach ($src as $base64) {
               if ($options->NewText === false) {
                  $image = $this->SaveBase64ToImage($base64,$options->DiskPath,$options->DiskPathDate);
               }else{
                  $image = $this->SaveBase64ToImage($base64,$NewTextPath,false);
               }
               
               if ($image['error'] == 0) {
                  $path_absolute = $image['path']['absolute'];
                  if ($this->config['os']['use'] == 1) {
                     $path_absolute = str_replace($this->config['os']['domain'],'',$path_absolute);
                  }
                  $content = str_replace($base64, $this->config['os']['var'].DIRECTORY_SEPARATOR.$path_absolute, $content);
               }
            }
         }
         /** @name 对象存储链接图片和外链图片处理 **/
         if ($options->FileFormat === '.md') {
            $src = $regexp->find('markdown.img.link');
         }else{
            $src = $regexp->find('html.img.src.link');
         }
         if ($src !== false) {
            foreach ($src as $link) {
               $url = parse_url($link);
               if (empty($url['host'])) {
                  $url = '';
               }else{
                  $url = $url['scheme'].'://'.$url['host'];
               }

               if (in_array($url,['',$this->config['os']['domain']])) {
                  /** 
                   ** 过滤掉不需下载的图片 给内容替换上 {os}
                   ** 如果 $url 是 空、或者是 $this->config['os']['domain'] 的对象存储域名
                   *? @date 21/12/03 18:14
                   */

                  // 判断 $link 是否包含 {os} 如果不包含则将 {os} 替换上去
                  if (strstr($link,$this->config['os']['var']) === false) {
                     if ($url == '') { // $url == '' 说明图片存储在服务器磁盘里
                        if ($options->NewText === false) {
                           $content = str_replace($link,$this->config['os']['var'].$link,$content);
                        }else{
                           /** 
                            ** temp
                            *? @date 21/12/03 19:24
                            */
                           // 临时存储目录文件
                           $TempLink = app()->getRootPath().'entrance'.$link;
                           // 临时存储目录文件名
                           $LinkName = str_replace('/uploads/temp/','',$link);
                           // 想要上传到对象存储中的图片
                           $imageLinkOS = $NewTextPath.$LinkName;
                           // 想要移动到归档中的文图
                           $LinkArchive = app()->getRootPath().'entrance/'.$NewTextPath;

                           try {
                              $FileTemp = new File($TempLink);
                              $FileArchive = new File($LinkArchive);
                              if ($FileArchive->exists() === false) $FileArchive->mkdirs();
                              if ($FileTemp->move($LinkArchive) === true) {
                                 if ($this->config['os']['use'] == 1) {
                                    $storage = new Processor();
                                    $upload = $storage->upload($imageLinkOS);
                                    $content = str_replace($link,'{os}/'.$imageLinkOS, $content);
                                    if ($upload === true) {
                                       $file3 = new File($LinkArchive.$LinkName);
                                       $file3->delete();
                                    }
                                 }else{
                                    $content = str_replace($link,'{os}/'.$LinkArchive.$LinkName, $content);
                                 }
                              }
                           } catch (\Throwable $th) {
                              //throw $th;
                           }
                        }
                     }else{
                        if ($options->NewText === false) {
                           $DiskPath = $options->DiskPath;
                        }else{
                           $DiskPath = $NewTextPath;
                        }
                        $image = str_replace(
                           $this->config['os']['domain'].'/'.$DiskPath,
                           '',
                           $link
                        );
                        $content = str_replace(
                           $link,
                           $this->config['os']['var'].'/'.$DiskPath.$image,
                           $content
                        );
                     }
                  }
               }else{
                  /** 
                   ** 下载内容中图片的图片并且替换上 {os}
                   *? @date 21/12/03 18:31
                   */
                  if ($options->NewText === false) {
                     $image = $this->SaveLinkImage($link,$options->DiskPath,$options->DiskPathDate);
                  }else{
                     $image = $this->SaveLinkImage($link,$NewTextPath,false);
                  }
                  
                  if ($image['error'] == 0) {
                     $path_absolute = $image['path']['absolute'];
                     
                     if ($this->config['os']['use'] == 1) {
                        $path_absolute = str_replace($this->config['os']['domain'],'',$path_absolute);
                     }
                     $content = str_replace($link, $this->config['os']['var'].$path_absolute, $content);
                  }
                  
               }
            }
         }
         return $content;
      }

      /** 
       ** 全部保存(包括图片附件都会全部下载保存下来)
       *? @date 22/01/17 16:07
       *! @return 
       */
      if ($options->is === 'save-all') {
         if ($options->FileFormat === '.md') {
            $src = $regexp->find('markdown.img.link');
         }else{
            $src = $regexp->find('html.img.src.link');
         }
         if ($src !== false) {
            foreach ($src as $DLink) {
               $link = str_replace($this->config['os']['var'],$this->config['os']['domain'], $DLink);
               if ($options->NewText === false) {
                  $image = $this->SaveLinkImage($link,$options->DiskPath,$options->DiskPathDate);
               }else{
                  $image = $this->SaveLinkImage($link,$NewTextPath,false);
               }
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
       ** 查看内容(view)
       *? @date 21/12/03 18:34
       */
      if ($options->is === 'view') {
         if ($this->config['os']['use'] == 1) {
            // 替换对象存储模式
            $content = str_replace(
               $this->config['os']['var'],
               $this->config['os']['domain'],
               $content,
               $i
            );
         }else{
            // 替换服务器磁盘存储模式
            $content = str_replace(
               $this->config['os']['var'],
               '',
               $content,
               $i
            );
         }
         if ($options->ExternalLink) {
            return $regexp->ReplaceExternalLinks($content);
         }
         
         return $content;
      }

      if ($options->is === 'delete') {
         $arr = [];
         if ($options->FileFormat === '.md') {
            $arr = $regexp->find('markdown.img.link');
         }else{
            $arr = $regexp->find('html.img.src.link');
         }
         if (!empty($arr)) {
            foreach ($arr as $link) {
               $url = parse_url($link);
               if (empty($url['scheme'])) {
                  $url = '';
               }else{
                  $url = $url['scheme'].'://'.$url['host'];
               }
               /** 
                ** 验证 $url 是否是{os}
               */
               if (in_array($url,['',$this->config['os']['var']])) {
                  $image = str_replace($this->config['os']['var'].DIRECTORY_SEPARATOR,'',$link);
                  if ($this->config['os']['use'] == 1) {
                     /** 
                      ** 删除第三方对象存储里的里的图片
                     *? @date 21/12/03 14:57
                     */
                     $storage = new Processor();
                     $storage->delete($image);
                  }else{
                     /** 
                      ** 删除本地图片
                     *? @date 21/12/03 14:57
                     */
                     $file = new File(app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.$image);
                     $file->delete();
                  }
               }
            }
         }
      }
   }
   
   /**
   * @name 抓取绝对图片链接保存图片 （books 导入时使用）
   * @describe 从文本内容中抓取 图片文件 本地图片或远程链接图片 并且保存
   * @param mixed $content html or md 内容
   * @param mixed $type .md or .html
   * @return String
   **/
   public function GrabAbsoluteSave($content,$type,$dir,$date = ['y','m']){
      $regexp = new Regexp($content);

      if ($type === '.md') {
         $src = $regexp->find('markdown.img.link');
      }else{
         $src = $regexp->find('html.img.src.link');
      }
      if ($src !== false) {
         foreach ($src as $DLink) {
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
   /** 
    ** 填充链接
    *? @date 22/01/12 14:39
    *  @param myParam1 Explain the meaning of the parameter...
    *  @param myParam2 Explain the meaning of the parameter...
    *! @return String
    */
   public function FillLink($content,$filllink,$type){
      $regexp = new Regexp($content);

      if ($type === '.md') {
         $src = $regexp->find('markdown.img.link');
      }else{
         $src = $regexp->find('html.img.src.link');
      }
      if ($src !== false) {
         foreach ($src as $DLink) {
            $regexpUrl = new Regexp($DLink);
            if ($regexpUrl->IsUrlType() === 'RelativelyPathUrl') {
               $LinkImage = str_replace($DLink,$filllink.$DLink,$DLink);
               $content = str_replace($DLink, $LinkImage, $content);
            }
         }
      }
      return $content;
   }
}
