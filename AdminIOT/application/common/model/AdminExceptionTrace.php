<?php
namespace app\common\model;

use think\Model;

class AdminExceptionTrace extends Model
{
    protected $name = 'admin_exception_trace';
    
    public function syslog(){
        return $this->belongsTo('admin_exception','log_id');
    }

    public function getTraceAttr($value){
        return '<pre>'.$value.'</pre>';
    }
    
}
