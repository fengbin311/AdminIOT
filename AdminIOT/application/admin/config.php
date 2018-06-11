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

use \think\Request;
$root = Request::instance()->root();
if(strstr($root, '/public/'))
    $basename = '/public';
else 
    $basename = '/';
  
return [
    // 模板参数替换
    'view_replace_str' => [
        '__ROOT__'   => $basename,
        '__STATIC__' => $basename.'static/admin',
        '__AVATAR__' => $basename.'uploads/admin/avatar/'
    ],

    'template'                   => [

        'layout_on'       => true,
        'layout_name'     => 'template/layout',
        'layout_item'     => '[__REPLACE__]',

        // 模板引擎类型 支持 php think 支持扩展
        'type'            => 'Think',
        // 模板路径
        'view_path'       => '',
        // 模板后缀
        'view_suffix'     => '.html',
        // 预先加载的标签库
        'taglib_pre_load' => '',
        // 默认主题
        'default_theme'   => '',
    ],
  

    //后台用户头像相关设置
    'admin_avatar'               => [
        'upload_path' => ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'avatar' . DS,
    ],

    
];
