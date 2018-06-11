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
use traits\model\SoftDelete;

class AdminUsers extends Model
{
    use SoftDelete;

    protected $name = 'admin_users';
    protected $autoWriteTimestamp = true;

    
    
    public function adminLogs()
    {
        return $this->hasMany('AdminLogs','user_id','user_id')->field('title,log_type,log_ip,create_time');
    }

    /**
     * 关联 用户关联角色表
     * @return \think\model\relation\HasMany
     */
    public function adminRoles()
    {
        return $this->hasMany('AdminAuthGroupAccess','uid','user_id')->with('authGroup');
    }

    public function profile()
    {
        return $this->hasOne('AdminProfiles','user_id','user_id');
    }

    public function getStatusAttr($value)
    {
        $status = ['0'=>'冻结','1'=>'正常'];
        return $status[$value];
    }
    
}
