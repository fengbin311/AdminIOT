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

class AdminAuthGroups extends Model
{
    protected $name = 'admin_auth_groups';

    public function authGroupAccess(){
        return $this->hasMany('AdminAuthGroupAccess','group_id','id');
    }
}
