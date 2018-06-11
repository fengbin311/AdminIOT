<?php
// +----------------------------------------------------------------------
// | AdminIOT
// +----------------------------------------------------------------------
// | Copyright (c) 2017 https://www.adminiot.com.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed (Without the authorship of the author, the code can not be
// | transmitted two times or used for other business practices)
// +----------------------------------------------------------------------
// | Author: Robert <78320701@qq.com> Date:2017/12
// +--

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Error extends Controller
{
    public function index(){
        $url = $this->request->server('HTTP_REFERER');
        $server = $this->request->server();
        if(isset($server['HTTP_REFERER'])){
            $url = $server['HTTP_REFERER'];
        }
        return $this->redirect($url, [], 302, ['error_message' => '页面不存在!']);
    }

    public function _empty()
    {
        $url = $this->request->server('HTTP_REFERER');
        $server = $this->request->server();
        if(isset($server['HTTP_REFERER'])){
            $url = $server['HTTP_REFERER'];
        }
        return $this->redirect($url, [], 302, ['error_message' => '页面不存在!']);
    }
}