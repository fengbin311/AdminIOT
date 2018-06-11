<?php

namespace app\common\model;

use think\Model;

class AdminException extends Model
{
    protected $name = 'admin_exception';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    
    public function adminExceptionTrace()
    {
        return $this->hasOne('admin_exception_trace','log_id')->field('trace_id,log_id,trace');
    }
    
}
