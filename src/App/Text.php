<?php
namespace Pctco\Storage\App;
use Naucon\File\File;
use Naucon\File\FileWriter;
use Naucon\File\FileReader;
use think\facade\Cache;
use Pctco\Coding\Skip32\Skip;
use Pctco\File\Markdown;
/**
 * 封面处理
 */
class Text{
   /**
   * @param mixed $id    item(链接id) id
   * @param mixed $iid    index(索引id) id
   * @param mixed $format    文件格式 如: .md
   * @param mixed $content    [true:则复制 $this->index 路径文本内容,自定义文件内容:/test/index.md,self::get:获取id路径下的文件内容,直接文本:'...']
   * @param mixed $dir 存储目录文件夹名称
   * @param mixed $skip 加密解密类型
   **/
   function __construct($id,$iid,$format,$content = true,$dir = 'books',$skip = 'books'){
      $this->config = [
         'os'   =>   Cache::store('config')->get(md5('app\admin\controller\Config\storage'))
      ];

      $this->storage = new \Pctco\Storage\Processor();
      $this->format = $format;
      $this->content = $content;

      $this->id = $id;
      $id = abs(intval($id));
   	$id = sprintf("%09d", $id);
   	$dir1 = substr($id, 0, 3);
   	$dir2 = substr($id, 3, 2);
   	$dir3 = substr($id, 5, 2);

      $this->dirs = $dir;
      $this->root = app()->getRootPath().'entrance';

      $this->path = DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$dir1.DIRECTORY_SEPARATOR.$dir2.DIRECTORY_SEPARATOR.$dir3.DIRECTORY_SEPARATOR.substr($this->id, -2).DIRECTORY_SEPARATOR;

      $this->index = DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$this->dirs.DIRECTORY_SEPARATOR.'index'.$format;

      $this->dir = $this->root.$this->path;
      $this->file = $this->dir.Skip::en($skip,substr($iid, -2)).$format;
   }
   /**
   * @name get
   * @describe 获取
   * @return string
   **/
   public function get(){
      if(file_exists($this->file)) {
         return str_replace($this->root,'',$this->file);
      } else {
         return $this->index;
      }
   }
   /**
   * @name post
   * @describe 保存
   * @return string
   **/
   public function post(){
      $w = new FileWriter($this->file,'w+');
      $w->write(self::GetContent());
      return self::get();
   }
   /**
   * @name delete
   * @describe 删除
   * @param mixed $range 删除范围  all 删除全部
   * @return string
   **/
   public function delete($range = ''){
      // if ($this->config['os']['use'] == 1) {
      //    return $this->storage->delete(str_replace($this->root,'',$this->file));
      // }

      if ($range === 'all') {
         $file = new File($this->dir);
         return $file->deleteAll();
      }


      $file = new File($this->file);
      return $file->delete();

      // 删除全部
      // $sourcePath = __DIR__ . '/tmp/';
      //
      // use Naucon\File\File;
      // $fileObject = new File($sourcePath);
      // $fileObject->deleteAll();


   }
   /**
   * @name get content
   * @describe 获取默认文本内容
   * @param mixed $to  数据转换
   * @return string
   **/
   public function GetContent($to = false){
      if ($this->content === true) {
         $file = new FileReader($this->root.$this->index, 'r', true);
         $content = $file->read();
      }else if(substr(strrchr($this->content, '.'), 0) == $this->format){
         $file = new FileReader($this->root.$this->content, 'r', true);
         $content = $file->read();
      }else if($this->content === 'self::get'){
         $file = new FileReader($this->root.self::get(), 'r', true);
         $content = $file->read();
      }else{
         $content = $this->content;
      }

      if ($to === false) {
         return $content;
      }


      if ($to === 'md-html') {
         $markdown = new Markdown();
         $transform = htmlspecialchars($markdown->text($content));
      }


      return [
         'original'   =>   $content,
         'transform'   =>   $transform
      ];


   }
}
