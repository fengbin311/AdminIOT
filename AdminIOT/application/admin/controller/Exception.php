<?php

namespace app\admin\controller;

use app\common\model\AdminLogs;
use app\common\model\AdminUsers;
use app\common\model\AdminException;
use think\Log;

class Exception extends Base
{

    //系统日志列表
    public function index(){
        $adminException = new AdminException();
        $page_param = ['query' => []];
        if (isset($this->get['keywords']) && !empty($this->get['keywords'])) {
            $page_param['query']['keywords'] = $this->get['keywords'];
            $keywords = "%" . $this->get['keywords'] . "%";
            $adminException->whereLike('message', $keywords);
            $this->assign('keywords', $this->get['keywords']);
        }
        $lists = $adminException->with('adminExceptionTrace')
                                ->order('log_id desc')
                                ->paginate(10, false, $page_param);
        $this->assign([
            'lists'    => $lists,
            'page'     => $lists->render(),
            'total'    => $lists->total()
        ]);

        return $this->fetch();
    }
    
   
    public function del()
    {
   
        $adminException = AdminException::get($this->id);
        if (!$adminException) {
            return $this->do_error('log不存在！');
        }
    
        if ($adminException->delete()) {
            if ($adminException->adminExceptionTrace->delete()) {
                return $this->do_success();
            }
            return $this->do_error('log删除失败');
        }
        return $this->do_error('log删除失败');
    }
    
    
    public function getTrace()
    {
        $adminException = AdminException::get($this->id);
        $trace = $adminException->adminExceptionTrace->trace;
        return json($trace);
    }

}