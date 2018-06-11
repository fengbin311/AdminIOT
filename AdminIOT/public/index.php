<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------



    // if( !stristr($_SERVER['REMOTE_ADDR'], "223.74") &&  // client
    //     !stristr($_SERVER['REMOTE_ADDR'], "183.230") && //oneNet api server
    //     !stristr($_SERVER['REMOTE_ADDR'], "127.0") ) // localhost dev
    //      die('access denied');


// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
