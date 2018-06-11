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

class AdminMenus extends Model
{
    //use SoftDelete;
    protected $name = 'admin_menus';
    protected $autoWriteTimestamp = true;

    //关联权限
    public function authRule()
    {
        return $this->hasOne('AuthRules','menu_id','menu_id');
    }
}