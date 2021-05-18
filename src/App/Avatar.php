<?php
namespace Pctco\Storage\App;
use Naucon\File\File;
use think\facade\Cache;
/**
 * 头像处理
 */
class Avatar{
   private $config;
   private $id;  // $id OR $uid
   private $dir; // 目录路径
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

      $this->dir = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR.$dir1.DIRECTORY_SEPARATOR.$dir2.DIRECTORY_SEPARATOR.$dir3.DIRECTORY_SEPARATOR;

      $this->config = [
         'size'   =>   [200,120,48],
         'name'   =>   ['big', 'middle', 'small']
      ];

      $this->size = in_array($size, ['big', 'middle', 'small']) ? $size : 'middle';
      $this->avatar = $avatar;
   }
   /**
   * @name path
   * @describe 获取头像
   * @return string
   **/
   public function path(){
   	$path = substr($this->id, -2)."_avatar_$this->size.jpg";
      if(file_exists($this->dir.$path)) {
      	return DIRECTORY_SEPARATOR.$this->dir.$path;
      } else {
      	return DIRECTORY_SEPARATOR.'entrance'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR.'default_avatar_'.$this->size.'.jpg';
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
