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

class AdminProfiles extends Model
{
    use SoftDelete;

    protected $name = 'admin_user_profiles';
    protected $autoWriteTimestamp = true;
    
    public function adminUser()
    {
        return $this->belongsTo('AdminUsers','user_id','profile_id')->field('user_id,user_name,nick_name,status');
    }
    
}
