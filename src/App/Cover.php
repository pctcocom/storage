<?php
namespace Pctco\Storage\App;
use Naucon\File\File;
use think\facade\Cache;
use Pctco\Coding\Skip32\Skip;
/**
 * 封面处理
 */
class Cover{
   /**
   * @param mixed $id or $uid
   * @param mixed $cover 上传路径 img
   * @param mixed $dir 存储目录文件夹名称
   **/
   function __construct($id,$cover,$dir = 'cover',$skip = 'cover'){
      $this->config = [
         'os'   =>   Cache::store('config')->get(md5('app\admin\controller\Config\storage'))
      ];

      $this->storage = new \Pctco\Storage\Processor();

      $this->id = $id;
      $id = abs(intval($id));
   	$id = sprintf("%09d", $id);
   	$dir1 = substr($id, 0, 3);
   	$dir2 = substr($id, 3, 2);
   	$dir3 = substr($id, 5, 2);

      $this->dirs = $dir;

      $this->FileName = Skip::en($skip,substr($this->id, -2)).'-cover.jpg';

      $this->path = 'uploads'.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$dir1.DIRECTORY_SEPARATOR.$dir2.DIRECTORY_SEPARATOR.$dir3.DIRECTORY_SEPARATOR;
      $this->dir = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.$this->path;

      $UploadImage = new \Pctco\Storage\App\UploadImage();

      $regexp = new \Pctco\Verification\Regexp($cover);
      if ($regexp->check('format.link.img') == 1) {
         $image = $UploadImage->SaveLinkImage($cover,'entrance/uploads/temp/',['y','m'],true,false,false);
      }else{
         $image = $UploadImage->SaveBase64ToImage($cover,'entrance/uploads/temp/',['y','m'],true,false);
      }

      if ($image['error'] == 0) {
         $this->cover = $image['path']['system'];
      }else{
         $this->cover = $cover;
      }
   }
   /**
   * @name path
   * @describe 获取
   * @return string
   **/
   public function path(){
      if ($this->config['os']['use'] == 1) {
         if($this->storage->exist($this->path.$this->FileName)) {
            return $this->config['os']['domain'].DIRECTORY_SEPARATOR.$this->path.$this->FileName;
         } else {
            // return $this->config['os']['domain'].DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$this->dirs.DIRECTORY_SEPARATOR.'cover.jpg';
            return DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$this->dirs.DIRECTORY_SEPARATOR.'cover.png';
         }
      }else{
         if(file_exists($this->dir.$this->FileName)) {
            return $this->path.$this->FileName;
         } else {
            return DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$this->dirs.DIRECTORY_SEPARATOR.'cover.png';
         }
      }
   }
   /**
   * @name save
   * @describe 保存
   * @return string
   **/
   public function save(){
      $file = new File($this->dir);
      if ($file->exists() === false) {
         $file->mkdirs();
      }

      try {
         $image = \think\Image::open($this->cover);
      } catch (\Exception $e) {
         return self::path();
         // return json([
         //    'status' => 'warning',
         //    'prompt' => $e->getMessage()
         // ]);
      }

      $image->thumb(500,705,\think\Image::THUMB_SCALING)->save($this->dir.$this->FileName);


      if ($this->config['os']['use'] == 1) {
         $upload = $this->storage->upload($this->path.$this->FileName);
         $file = new File($this->dir.$this->FileName);
         $file->delete();
      }

      $file = new File($this->cover);
      $file->delete();
      return self::path();

   }
   /**
   * @name delete
   * @describe 删除
   * @return string
   **/
   public function delete(){
      if ($this->config['os']['use'] == 1) {
         return $this->storage->delete($this->path.$this->FileName);
      }
      $fileObject = new File($this->dir.$this->FileName);
      return $fileObject->delete();
   }
}
