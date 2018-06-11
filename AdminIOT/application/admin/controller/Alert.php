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
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;
use app\common\model\AdminTriggerLog;
use think\Model;
use think\Config;
use app\common\helper\OneNetCloud;

class Alert extends Base
{

    public $index_validate = [
        [
            'keywords|查询关键字',
            'number'
        ],
        [
            'page|页码',
            'number'
        ]
    ]
    ;

    public function _initialize()
    {
        parent::_initialize();
        $this->onenet_cloud = new OneNetCloud();
    }
    
    // 后台用户列表
    public function index()
    {
        $admin_trigger_log = new AdminTriggerLog();
        $admin_trigger_log_count = $admin_trigger_log->count();
        $this->assign([
            'total' => $admin_trigger_log_count
        ]);
        return $this->fetch();
    }
    
    // ajax service
    public function listalert()
    {
        $result = $this->validate($this->param, $this->index_validate);
        if (true !== $result) {
            return $this->do_error($result);
        }
        
        $admin_trigger_log = new AdminTriggerLog();
        
        if (! empty($this->param['keywords'])) {
            $keywords = trim($this->param['keywords']);
            $admin_trigger_log->whereLike('dev_id|trig_id', $keywords);
            $this->assign('keywords', $keywords);
        }
        
        $trigger_log = $admin_trigger_log->order('time desc')->paginate(10, false);
        
        $trigger_log_array = $trigger_log->toArray();
        $lists = $trigger_log_array['data'];
        
        // 批量获取设备在线状态
        $dev_id_lists = array_column($lists, "dev_id");
        $devices_status = $this->onenet_cloud->devices_status($dev_id_lists);
        if (empty($devices_status)) {
            $this->view->engine->layout(false);
            Config::set('app_trace', false);
            return $this->display("<div id=\"load-cloud-data-error\" style=\"text-align: center;color:#f39c12\"><span>网络数据加载失败</span></div>");
        }
        
        foreach ($lists as $key => $event) {
            $lists[$key]["dev_title"] = $devices_status['devices'][$key]['title'];
            $lists[$key]["dev_online"] = $devices_status['devices'][$key]['online'];
        }
        
        $this->assign([
            'lists' => $lists,
            'page' => $trigger_log->render(),
            'total' => $trigger_log->total()
        ]);
        
        $this->view->engine->layout(false);
        Config::set('app_trace', false);
        return $this->fetch();
    }

    public function getList()
    {
        $start_page = 1;
        $page_size = 5;
        
        $lists = array();
        $lists["alerts"] = array();
        
        $result = $this->validate($this->param, $this->index_validate);
        if (true !== $result) {
            $lists["errno"] = 1; // 数据获取失败
            $lists["msg"] = "页码参数错误"; // 数据获取失败
            return json($lists);
        }
        
        $admin_trigger_log = new AdminTriggerLog();
        $trigger_log = $admin_trigger_log->order('time desc')->paginate($page_size, false);
        
        // 只获取一个设备，目的是获取总设备数
        $page = isset($this->param['page']) ? $this->param['page'] : 1;
        $total = $trigger_log->total();
        $lastPage = (int) ceil($total / $page_size);
        if ($page > $lastPage) {
            $lists["errno"] = 1; // 数据获取失败
            $lists["msg"] = "页码参数错误"; // 数据获取失败
            return json($lists);
        }
        
        $trigger_log_array = $trigger_log->toArray();
        $trigger_lists = $trigger_log_array['data'];
        
//         var_dump($trigger_lists);
        
        // 批量获取设备在线状态
        $dev_id_lists = array_column($trigger_lists, "dev_id");
        $devices_status = $this->onenet_cloud->devices_status($dev_id_lists);
//         var_dump($dev_id_lists);
//         var_dump($devices_status);
        if (empty($devices_status)) {
            $lists["errno"] = 1; // 数据获取失败
            $lists["msg"] = "设备云连接失败"; // 数据获取失败
            return json($lists);
        }
        
        foreach ($trigger_lists as $key => $event) {
            $trigger_lists[$key]["dev_title"] = $devices_status['devices'][$key]['title'];
            $trigger_lists[$key]["dev_online"] = $devices_status['devices'][$key]['online'];
        }
        
        $lists["errno"] = 0; // 数据获取成功
        $lists["alerts"] = array_merge($lists["alerts"], $trigger_lists);
        $lists["cur_page"] = $page;
        $lists["has_more"] = $lastPage > $page ? 1 : 0;
        
        return json($lists);
    }
}
 