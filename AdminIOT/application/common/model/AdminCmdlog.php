<?php
// +----------------------------------------------------------------------
// | AdminIOT 
// +----------------------------------------------------------------------
// | Copyright (c) 2017 https://www.adminiot.com.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed (Without the authorship of the author, the code can not be
// | transmitted two times or used for other business practices)
// +----------------------------------------------------------------------
// | Author: Robert <78320701@qq.com>  Date:2017/12
// +----------------------------------------------------------------------


namespace app\common\model;

use think\Model;

class AdminCmdlog extends Model
{
    protected $name = 'admin_cmdlog';

    public function getStatusAttr($value)
    {
        $status=[0 => "设备不在线",

                  1 => "命令已创建",

                  2 => "命令已发往设备",

                  3 => "命令发往设备失败",
        
                  4 => "设备正常响应",
        
                  5 => "命令执行超时",
        
                  6 => "设备响应消息过长"];
        
        return $status[$value];
    }
    
    
}
