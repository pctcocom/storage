<?php
namespace Pctco\Storage;
class Aliyun{
   private $config;
   /**
   * @access 配置
   * @return
   **/
   function __construct(){
      $this->config = [
         'accessKeyId'   =>   'LTAI4FjgS1Vmxywzbk8YLu87',
         'accessKeySecret'   =>   'oKdJ5Ypj8qIyuMu0Vg3HYtQlUCsVPn',
         'cycle'   =>   300,
         'length'   =>   6,
         'sign'   =>   '大鱼测试',
         'regionId'   =>   'cn-hangzhou',
         '01'   =>   'SMS_16686167', // 身份验证验证码
         '02'   =>   'SMS_16686166', // 短信测试
         '03'   =>   'SMS_16686165', // 登录确认验证码
         '04'   =>   'SMS_16686164', // 登录异常验证码
         '05'   =>   'SMS_16686163', // 用户注册验证码
         '06'   =>   'SMS_16686162', // 活动确认验证码
         '07'   =>   'SMS_16686161', // 修改密码验证码
         '08'   =>   'SMS_16686160', // 信息变更验证码
      ];
   }
   public function test($itac,$phone,$template,$product = ''){

   }
}
