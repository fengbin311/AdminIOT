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

class AdminLogs extends Model
{
    protected $name = 'admin_logs';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;



    public function getLogIpAttr($value)
    {
        return long2ip($value);
    }

    public function getLogTypeAttr($value)
    {
        $logtype=[0=>'NONE',1=>'GET',2=>'POST',3=>'PUT',4=>'DELETE'];
        return $logtype[$value];
    }

    //和后台用户关联
    public function adminUser()
    {
        return $this->belongsTo('AdminUsers','user_id')->field('user_id,user_name,nick_name');
    }


    public function adminLogData()
    {
        return $this->hasOne('AdminLogsDatas','log_id','id')->field('data_id,log_id,data');
    }
    
}
