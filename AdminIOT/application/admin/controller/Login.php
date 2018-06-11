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

use app\common\model\AdminUsers;
use think\Config;
use think\Controller;
use app\admin\auth\Auth;
use think\Db;
use think\Session;
use tools\GeetestLib;

class Login extends Controller
{

    protected $param;

    public function __construct()
    {
        parent::__construct();
        $this->param = $this->request->param();
    }
    
    // 登录
    public function login()
    {
        if ($this->request->isPost()) {
            
            // 验证提交参数是否合法
            $validate = [
                [
                    'user_name|帐号',
                    'require|max:25|token'
                ],
                [
                    'password|密码',
                    'require'
                ],
                [
                    'captcha|验证码',
                    'require|captcha'
                ]
            ];
            
            $result = $this->validate($this->param, $validate);
            if (true !== $result) {
                return $this->do_error($result);
            }
            
            $data = [
                'user_name' => $this->param['user_name'],
                'password' => md5($this->param['password'])
            ];
            $admin_users = new AdminUsers();
            $admin_user = $admin_users->get($data);
            if ($admin_user) {
                
                if ($admin_user->getData('status') != 1) {
                    return $this->do_error('账户被冻结');
                }
                
                if ($this->param['is_remember'] == 1) {
                    Auth::login($admin_user['user_id'], $admin_user['user_name'], true);
                } else {
                    Auth::login($admin_user['user_id'], $admin_user['user_name'], false);
                }
                
                //保存登入日志
                $auth = new Auth();
                $this->request->param('password', '');
                $auth->createLog('登录', 2);
                $redirect_uri = isset($this->param['uri']) ? $this->param['uri'] : 'admin/stat/index';
                return $this->do_success('登录成功', $redirect_uri);
            }
            return $this->do_error('账户或密码错误');
        }
        
        $bg_all = range(1, 5);
        $bg = array_rand($bg_all, 1);
        
        $this->assign([
            'title' => "登录",
            'bg_num' => $bg_all[$bg]
        ]);
        
        return $this->fetch('login/login');
    }
    
    // 退出
    public function logout()
    {
        $auth = new Auth();
        $auth->createLog('退出', 2);
        $auth->logout();
        $this->redirect('login/login');
    }

    
    // 登录
    
    public function mGetToken()
    {
        $name = '__token__';
        $type = 'md5'; 
        $token = $this->request->token($name, $type);
        $ret['__token__'] = $token;
        return json($ret);
    }
    
    public function mLogin()
    {
        $ret=[];
        
        if ($this->request->isPost()) {
    
            // 验证提交参数是否合法
            $validate = [
                [
                    'user_name|帐号',
                    'require|max:25|token'
                ],
                [
                    'password|密码',
                    'require'
                ],
            ];
    
            $result = $this->validate($this->param, $validate);
            if (true !== $result) {
                $ret['errno'] =1;
                $ret['msg'] = $result;
                return json($ret);
            }
    
            $data = [
                'user_name' => $this->param['user_name'],
                'password' => md5($this->param['password'])
            ];
            $admin_users = new AdminUsers();
            $admin_user = $admin_users->get($data);
            if ($admin_user) {
    
                Auth::login($admin_user['user_id'], $admin_user['user_name'], false);
                
                //保存登入日志
                $auth = new Auth();
                $this->request->param('password', '');
                $auth->createLog('登录', 2);
                $ret['errno'] = 0;
                $ret['msg'] = '登入成功';
                return json($ret);
            }
            
            $ret['errno'] = 1;
            $ret['msg'] = '账户或密码错误';
            return json($ret);
        }
        
        $ret['errno'] = 1;
        $ret['msg'] = '请post方式提交数据';
        return json($ret);
        
    }
    
    /**
     * 登录成功
     * 
     * @param string $msg            
     * @param null $url            
     * @param string $data            
     */
    protected function do_success($msg = '', $url = null, $data = '')
    {
        if ($url == null) {
            $url = url($this->do_url . 'index');
        }
        
        if ($msg == '') {
            $msg = '操作成功！';
        }
        
        return $this->redirect($url, $data, 302, [
            'success_message' => $msg
        ]);
    }

    /**
     * 登录错误
     * 
     * @param string $msg            
     * @param null $url            
     * @param string $data            
     */
    protected function do_error($msg = '', $url = null, $data = '')
    {
        if ($url == null) {
            $url = $this->request->server('HTTP_REFERER');
        }
        
        if ($msg == '') {
            $msg = '操作失败！';
        }
        return $this->redirect($url, $data, 302, [
            'error_message' => $msg
        ]);
    }
}