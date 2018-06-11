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

use app\common\model\AdminLogs;
use app\common\model\AdminMenus;
use app\common\model\AdminUsers;
use app\common\model\Syslogs;
use app\common\model\AdminTriggerLog;
use think\Log;
use onenetapi\OneNetApi;
use app\common\helper\OneNetCloud;
use think\Config;

class Stat extends Base
{
    
    // 统计概览
    public function index()
    {
        $admin_users = new AdminUsers();
        $admin_user_count = $admin_users->count();
        $admin_logs = new AdminLogs();
        $admin_log_count = $admin_logs->count();
        $admin_menus = new AdminMenus();
        $admin_menu_count = $admin_menus->count();
        $admin_trigger_log = new AdminTriggerLog();
        $admin_trigger_log_count = $admin_trigger_log->count();
        
        $onenet_cloud = new OneNetCloud();
        
        // 统计当前在线/离线设备总数
        $device_total_count = $onenet_cloud->get_device_total_count();
        $device_online_count = $onenet_cloud->get_device_online_count();
        $device_offline_count = $onenet_cloud->get_device_offline_count();
        
        $this->assign([
            'adminuser_count' => $admin_user_count,
            'admin_log_count' => $admin_log_count,
            'admin_menu_count' => $admin_menu_count,
            'device_total_count' => $device_total_count,
            'device_online_count' => $device_online_count,
            'device_offline_count' => $device_offline_count,
            'admin_trigger_log_count' => $admin_trigger_log_count
        ]);
        
        if (isset($this->param['app'])) {
            $this->view->engine->layout(false);
            Config::set('app_trace', false);
            return $this->fetch("indexm");
        } else
            return $this->fetch();
    }


    //以下为APP获取的信息
    public function getStat()
    {
        $lists = array();

        $admin_trigger_log = new AdminTriggerLog();
        $admin_trigger_log_count = $admin_trigger_log->count();

        $onenet_cloud = new OneNetCloud();
        // 统计当前在线/离线设备总数
        $device_total_count = $onenet_cloud->get_device_total_count();
        $device_online_count = $onenet_cloud->get_device_online_count();
        $device_offline_count = $onenet_cloud->get_device_offline_count();


        //此处获取某个设备的数据点信息，用来给APP显示电压、电流、SOC、温度
        //1.先获取某个设备的数据流,此处获取第一个设备的数据流
//        $device_ds_app =  $onenet_cloud->get_device_ds();
//        $voltage = $device_ds_app->

        $lists["errno"] = 0; // 数据获取成功

//        $lists["device_total_count"] = $device_total_count;
//        $lists["device_online_count"] = $device_online_count;
//        $lists["device_offline_count"] = $device_offline_count;
//        $lists["trigger_log_count"] = $admin_trigger_log_count;

        $lists["device_total_count"] = 100;
        $lists["device_online_count"] = 55;
        $lists["device_offline_count"] = 45;
        $lists["trigger_log_count"] = 27;

        return json($lists);
    }

    public function mapdata()
    {
        
        // 每次最多返回设备地图数目
        $max_ajax_devices_per = 10;
        
        $start_page = 1;
        $page_size = 100;
        $key_word = NULL;
        $tag = NULL;
        $is_online = NULL;
        $is_private = NULL;
        $device_ids = NULL;
        
        $page = isset($this->param['cur_page']) ? $this->param['cur_page'] : 1;
        if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
            $start_page = $page; // 当前请求分页页面
        }
        
        $max_ajax_pages_per = (int) ceil($max_ajax_devices_per / $page_size);
        $end_page = 0;
        
        $devicemapdata = array();
        $devicemapdata["devices"] = array();
        
        $cur_page = $start_page;
        
        $onenet_cloud = new OneNetCloud();
        
        // 只获取一个设备，目的是获取总设备数
        $device_total_count = $onenet_cloud->get_device_total_count();
        $lastPage = (int) ceil($device_total_count / $page_size);
        $pagenum = ($lastPage - $start_page) + 1;
        
        // 云端设备总数如果大于客户端请求设备数时，需要分页获取。
        $loopcnt = (int) $pagenum > $max_ajax_pages_per ? $max_ajax_pages_per : $pagenum;
        
        do {
            
            $device_list = $onenet_cloud->device_list($cur_page, $page_size, $key_word, $tag, $is_online, $is_private, $device_ids);
            if (! empty($device_list)) {
                $devicemapdata["devices"] = array_merge($devicemapdata["devices"], $device_list["devices"]);
            }
            
            $cur_page ++;
            $loopcnt --;
        } while ($loopcnt > 0);
        
        if (! empty($device_list)) {
            $devicemapdata["total_count"] = $device_list['total_count'];
            $devicemapdata["per_page"] = $device_list['per_page'];
            $devicemapdata["page"] = $device_list['page'];
        }
        
        // FIXME FOR TETS
        $devicemapdata["has_more"] = ($lastPage - $start_page) + 1 > $max_ajax_pages_per ? 1 : 0;
        
        return json($devicemapdata);
    }
}