<?php
namespace Pctco\Storage\App;
use Naucon\File\File;
use app\model\User;
/**
 * 头像处理
 */
class Avatar{
   private $config;
   private $id;  // $id OR $uid
   private $dir; // 目录路径
   private $path; // 目录路径
   private $size; // 想要操作的大小 'big', 'middle', 'small'
   private $avatar; // avatar img

   /**
   * @param mixed $id or $uid
   * @param mixed $size 大小 ['big', 'middle', 'small']
   * @param mixed $avatar 头像路径
   **/
   function __construct($id,$size,$avatar){
      $this->id = $id;
      $id = abs(intval($id));
   	$id = sprintf("%09d", $id);
   	$dir1 = substr($id, 0, 3);
   	$dir2 = substr($id, 3, 2);
   	$dir3 = substr($id, 5, 2);


      $this->path = DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR.$dir1.DIRECTORY_SEPARATOR.$dir2.DIRECTORY_SEPARATOR.$dir3.DIRECTORY_SEPARATOR;
      $this->dir = app()->getRootPath().'entrance'.$this->path;

      $this->config = [
         'size'   =>   [200,120,48],
         'name'   =>   ['big', 'middle', 'small']
      ];


      if ($size === 'all') {
         $this->size = ['big', 'middle', 'small'];
      }else{
         $this->size = in_array($size, ['big', 'middle', 'small']) ? $size : 'middle';
      }


      $UploadImage = new \Pctco\Storage\App\UploadImage();
      $image = $UploadImage->SaveBase64ToImage($avatar,'uploads/temp/',['y','m'],true,false);
      if ($image['error'] == 0) {
         $this->avatar = $image['path']['system'];
      }else{
         $this->avatar = $avatar;
      }
   }
   /**
   * @name path
   * @describe 获取头像
   * @return string
   **/
   public function path(){
      if (is_array($this->size)) {
         $group = [];
         foreach ($this->size as $v) {
            $path = substr($this->id, -2)."_avatar_$v.jpg";
            if(file_exists($this->dir.$path)) {
            	$group[$v] = $this->path.$path;
            } else {
            	$group[$v] =  DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR.'default_avatar_'.$v.'.jpg';
            }
         }
         return $group;
      }else{
         $path = substr($this->id, -2)."_avatar_$this->size.jpg";
         if(file_exists($this->dir.$path)) {
         	return $this->path.$path;
         } else {
         	return DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR.'default_avatar_'.$this->size.'.jpg';
         }
      }
   }
   /**
   * @name save
   * @describe 保存头像
   * @return string
   **/
   public function save(){
      $fileObject = new File($this->dir);
      if ($fileObject->exists() === false) {
         $fileObject->mkdirs();
      }

      try {
         $image = \think\Image::open($this->avatar);
      } catch (\Exception $e) {
         return json([
            'status' => 'warning',
            'prompt' => $e->getMessage()
         ]);
      }

      foreach ($this->config['size'] as $i => $size) {
         $img = substr($this->id, -2).'_avatar_'.$this->config['name'][$i].'.jpg';
         $image->thumb($size,$size,\think\Image::THUMB_SCALING)->save($this->dir.$img);
      }
      $fileObject = new File($this->avatar);
      $fileObject->delete();

      try {
         $User = new User();
         return $User->UpdateUserSession([
            'utime'   =>   time()
         ]);

      } catch (\Exception $e) {

      }
      return self::path();
   }
   /**
   * @name delete
   * @describe 删除头像
   * @return string
   **/
   public function delete(){
      foreach ($this->config['name'] as $i) {
         $img = $this->dir.substr($this->id, -2)."_avatar_$i.jpg";
         $fileObject = new File($img);
         $fileObject->delete();
      }
   }
}
