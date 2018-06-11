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


namespace app\admin\controller;

use app\api\controller\Api;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Parsedown;
use tools\Sysinfo;

class Index extends Base
{
    public function index()
    {
        //默认重定向到账户登入界面
         $this->redirect('/login');
    }

}