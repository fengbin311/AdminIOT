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

use think\Db;
use app\common\model\AdminTrigger;
use app\common\model\AdminUsers;
use app\common\model\AuthGroups;
use think\Log;
use app\common\helper\OneNetCloud;
use think\Config;
use think\Session;
use think\Validate;

class Trigger extends Base
{

    public $validate = [
        [
            'keywords|查询关键字',
            'chsDash'
        ],
        [
            'page|页码',
            'number'
        ]
    ];

    protected $onenet_cloud;

    public function _initialize()
    {
        parent::_initialize();
        $this->onenet_cloud = new OneNetCloud();
    }
    
    // 后台用户列表
    public function index()
    {
        $cur_page = 1;
        $page_size = 10;
        $keyword = NULL;
        $tag = NULL;
        $is_online = NULL;
        $is_private = NULL;
        $device_ids = NULL;
        
        $result = $this->validate($this->param, $this->validate);
        if (true !== $result) {
            return $this->do_error($result);
        }
        
        // 获取当前分页页面
        $varPage = isset(Config::get('paginate')['var_page']) ? Config::get('paginate')['var_page'] : 'page';
        $page = isset($this->param[$varPage]) ? $this->param[$varPage] : 1;
        if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
            $cur_page = $page; // 当前请求分页页面
        }
        
        // 獲取關鍵字
        if (isset($this->param['keywords']) && ! empty($this->param['keywords'])) {
            $keyword = $this->param['keywords'];
            $this->assign('keywords', $keyword);
        }
        $device_total_count = $this->onenet_cloud->get_device_total_count();
        $trigger_list = $this->onenet_cloud->trigger_list($cur_page, $page_size, $keyword);
//         var_dump($trigger_list);
        if (empty($trigger_list)) {
            $this->do_error("数据获取失败，请重新刷新网页试一下");
        } else {
            
            $total_count = $trigger_list['total_count'];
            $triggerlist = $trigger_list["triggers"];
            
            $page = $this->paginate($page_size, $total_count, false);
            $this->assign([
                'lists' => $triggerlist,
                'page' => $page,
                'total' => $total_count,
                'device_total_count' => $device_total_count
            ]);
            
            return $this->fetch();
        }
    }
    
    
    public function getList()
    {
        $start_page = 1;
        $page_size = 5;
    
        $keyword = NULL;
        $tag = NULL;
        $is_online = NULL;
        $is_private = NULL;
        $device_ids = NULL;
    
    
        $lists = array();
        $lists["triggers"] = array();
    
        $page = isset($this->param['page']) ? $this->param['page'] : 1;
        if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
            $start_page = $page; // 当前请求分页页面
        }
    
        // 只获取一个设备，目的是获取总设备数
        $trigger_total_count = $this->onenet_cloud->get_trigger_total_count();
        $lastPage = (int) ceil($trigger_total_count / $page_size);
        if($page > $lastPage){
            $lists["errno"] = 1; //数据获取失败
            $lists["msg"] = "页码参数错误"; //数据获取失败
            return json($lists);
        }
    
        $trigger_list = $this->onenet_cloud->trigger_list($start_page, $page_size, $keyword);
        if (empty($trigger_list)) {
            $lists["errno"] = 1; //数据获取失败
            $lists["msg"] = "设备云连接失败"; //数据获取失败
        } else {
            
            $lists["errno"] = 0; //数据获取成功
            $lists["triggers"] = array_merge($lists["triggers"], $trigger_list["triggers"]);
            $lists["cur_page"] =  $start_page;
            $lists["has_more"] =  $lastPage > $start_page ? 1 : 0;
        }
    
        return json($lists);
    }
    
   
    
    // 增加
    public function add()
    {
        $url = $this->do_url . 'add';
        $validate = [
            [
                'trigger_title|触发器名称',
                'chsDash|token'
            ],
            [
                'trigger_ds_id|数据流名称',
                'chsDash'
            ],
            [
                'trigger_type|触发类型',
                'length:1,6'
            ]
        ];
        
        if ($this->request->isPost()) {
            
            // 验证 触发器阀值有效性
            if (isset($this->param['trigger_threshold'])) {
                
                if (filter_var($this->param['trigger_threshold'], FILTER_VALIDATE_INT) == false) {
                    if (filter_var($this->param['trigger_threshold'], FILTER_VALIDATE_FLOAT) == false)
                        return $this->do_error("触发数据只能是整数或是浮点数");
                    else
                        $threshold = (float) $this->param['trigger_threshold'];
                } else
                    $threshold = (int) $this->param['trigger_threshold'];
            }
            
            // 其他类型统一验证
            $result = $this->validate($this->param, $validate);
            if (true !== $result) {
                return $this->do_error($result);
            }
            
            $trigger = array();
            $trigger['title'] = $this->param['trigger_title'];
            $trigger['ds_id'] = $this->param['trigger_ds_id'];
            $trigger_device_range_all = (int) $this->param['trigger_device_range_all'];
            if (! $trigger_device_range_all) {
                $dummy_did =  $this->onenet_cloud->get_device_dummy_did();
                $trigger['dev_ids'] = array($dummy_did);
            }
            
            // 默认安全过滤规则里面包含strip_tags ，所以单独获取 触发类型变量
            $type = $this->request->param("trigger_type", "", "trim");
            $trigger['type'] = $type;
 
            
            if (isset($threshold))
                $trigger['threshold'] = $threshold;
            
            $trigger['url'] = Config::get("trigger_event_url");
            
            $ret = $this->onenet_cloud->trigger_add($trigger);
            if (empty($ret)) {
                return $this->do_error("触发器保存失败");
            } else {
                return $this->do_success("添加触发器成功");
            }
        } // end post
        
        $datastreams = $this->onenet_cloud->get_device_ds();
        $dslist = array_column($datastreams, 'id');
        
        $this->assign([
            'dslist' => $dslist
        ]);
        return $this->fetch();
    }
    
    // 修改
    public function edit()
    {
        $validate = [
            [
                'trigger_title|触发器名称',
                'chsDash|token'
            ],
            [
                'trigger_ds_id|数据流名称',
                'chsDash'
            ],
            [
                'trigger_type|触发类型',
                'length:1,6'
            ]
        ];
        
        if ($this->request->isPost()) {
            
            if (isset($this->param['trigger_threshold'])) {
                if (filter_var($this->param['trigger_threshold'], FILTER_VALIDATE_INT) == false) {
                    if (filter_var($this->param['trigger_threshold'], FILTER_VALIDATE_FLOAT) == false)
                        return $this->do_error("触发数据只能是整数或是浮点数");
                    else
                        $threshold = (float) $this->param['trigger_threshold'];
                } else
                    $threshold = (int) $this->param['trigger_threshold'];
            }
            
            $result = $this->validate($this->param, $validate);
            if (true !== $result) {
                return $this->do_error($result);
            }
            
            //first, get this trigger info
            $trigger_id = $this->id;
            $trigger = $this->onenet_cloud->trigger($trigger_id);
            if (empty($trigger)) {
                return $this->do_error();
            } else {
                
                // start update trigger
                $trigger['title'] = $this->param['trigger_title'];
                $trigger['ds_id'] = $this->param['trigger_ds_id'];
                // 默认安全过滤规则里面包含strip_tags ，所以单独获取 触发类型变量
                $type = $this->request->param("trigger_type", "", "trim");
                $trigger['type'] = $type;
                
                if( $type != "change"){
                    $trigger['threshold'] = $threshold;
                }
                
                // post new trigger to clound
                $ret = $this->onenet_cloud->trigger_edit($trigger_id, $trigger);
                if (empty($ret))
                    return $this->do_error("触发器修改更新失败");
                else
                    return $this->do_success();
            }
        } else {
            $id = $this->id;
            $trigger = $this->onenet_cloud->trigger($id);
            if (empty($trigger)) {
                return $this->do_error();
            } else {
                
                // get all the datastreams
                $datastreams = $this->onenet_cloud->get_device_ds();
                $dslist = array_column($datastreams, 'id');
                
                $this->assign([
                    'dslist' => $dslist
                ]);
                
                $this->assign([
                    'trigger' => $trigger
                ]);
                
                return $this->fetch();
            }
        }
    }
    
    // 删除
    public function del()
    {
        $trigger_id = $this->id;
        
        // 批量删除
        if (is_array($trigger_id)) {
            foreach ($trigger_id as $id) {
                $ret = $this->onenet_cloud->trigger_delete($id);
            }
        } else // 单个删除
            $ret = $this->onenet_cloud->trigger_delete($trigger_id);
        
        if (empty($ret)) {
            return $this->do_error("删除触发器失败");
        } else {
            return $this->do_success();
        }
    }

    public function rel()
    {
        // 获取trigger id ex 66765
        // 获取该id trigger info
        $cur_page = 1;
        $page_size = 10;
        $keyword = NULL;
        $tag = NULL;
        $is_online = NULL;
        $is_private = NULL;
        $device_ids = NULL;
        $device_relation_all = 1; // 1 已关联所有设备， 0 已关联部分设备
        $device_total_count = 0;
        
        $listnum = 0;
        
        $devicelist = array();
        
        $result = $this->validate($this->param, $this->validate);
        if (true !== $result) {
            return $this->do_error($result);
        }
        
        if (isset($this->param['tid'])) {
            
            if (filter_var($this->param['tid'], FILTER_VALIDATE_INT) == false)
                return $this->do_error();
            
            $tid = $this->param['tid'];
            Session::set('trigger_id', $tid);
        } else {
            $tid = Session::get('trigger_id');
        }
        
        $device_total_count = $this->onenet_cloud->get_device_total_count();
        
        $trigger = $this->onenet_cloud->trigger($tid);
        if (empty($trigger)) {
            return $this->do_error();
        } else {
            // 查询trigger 设备绑定信息，如果有，获取绑定设备列表
            if (isset($trigger['dev_ids'])) {
                // 获取当前分页页面
                $varPage = isset(Config::get('paginate')['var_page']) ? Config::get('paginate')['var_page'] : 'page';
                $page = isset($this->param[$varPage]) ? $this->param[$varPage] : 1;
                if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
                    $cur_page = $page; // 当前请求分页页面
                }
                
                // $key = "";
                // 獲取關鍵字
                if (isset($this->param['keywords']) && ! empty($this->param['keywords'])) {
                    $keyword = $this->param['keywords'];
                    $this->assign('keywords', $keyword);
                }
                
                $related_device_total_count = count($trigger['dev_ids']);
                $listnum = $related_device_total_count;
                if ($related_device_total_count > 0) {
                    
                    // 获取起始列表起点
                    $start = ($cur_page - 1) * $page_size;
                    // 从数组里面截取某段数组
                    $dev_ids = array_slice($trigger['dev_ids'], $start, $page_size);
                    // var_dump($dev_ids);
                    
                    // 遍历关联设备列表，获取设备详细信息
                    foreach ($dev_ids as $dev_id) {
                        // 根据设备ID，获取设备信息。
                        $device = $this->onenet_cloud->device($dev_id);
                        if (empty($device)) {
                            // var_dump($dev_id,"error");
                            // return $this->do_error("数据获取失败，请重新刷新网页试一下");
                        } else {
                            $devicelist[] = $device;
                        }
                    }
                }
            } else {
                
                // 获取当前分页页面
                $varPage = isset(Config::get('paginate')['var_page']) ? Config::get('paginate')['var_page'] : 'page';
                $page = isset($this->param[$varPage]) ? $this->param[$varPage] : 1;
                if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
                    $cur_page = $page; // 当前请求分页页面
                }
                
                // $key = "";
                // 獲取關鍵字
                if (isset($this->param['keywords']) && ! empty($this->param['keywords'])) {
                    $keyword = $this->param['keywords'];
                    $this->assign('keywords', $keyword);
                }
                
                $onenet_cloud = new OneNetCloud();
                $device_list = $onenet_cloud->device_list($cur_page, $page_size, $keyword, $tag, $is_online, $is_private, $device_ids);
                if (empty($device_list)) {
                    return $this->do_error("数据获取失败，请重新刷新网页试一下");
                } else {
                    $related_device_total_count = $device_total_count;
                    $listnum = $device_list["total_count"];
                    $devicelist = $device_list["devices"];
                }
            }
            
            $page = $this->paginate($page_size, $listnum, false);
            $this->assign([
                'lists' => $devicelist,
                'page' => $page,
                'total' => $device_total_count,
                'related_total' => $related_device_total_count,
                // 'key' => $key,
                'trigger_id' => $tid,
                'trigger' => $trigger
            ]);
            
            return $this->fetch();
        }
    }

    public function relnew()
    {
        $tid = 0;
        
        $result = $this->validate($this->param, $this->validate);
        if (true !== $result) {
            return $this->do_error($result);
        }
        
        if (isset($this->param['tid'])) {
            if (filter_var($this->param['tid'], FILTER_VALIDATE_INT) == false)
                return $this->do_error();
            
            $tid = $this->param['tid'];
            Session::set('trigger_id', $tid);
        } else {
            $tid = Session::get('trigger_id');
        }
        
        if (isset($this->param['action']))
            $action = $this->param['action'];
        
        $trigger = $this->onenet_cloud->trigger($tid);
        if (empty($trigger)) {
            return $this->do_error();
        } else {
            // 查询trigger 设备绑定信息，如果有，获取绑定设备列表
            $cur_page = 1;
            $page_size = 20;
            $keyword = NULL;
            $tag = NULL;
            $is_online = NULL;
            $is_private = NULL;
            $device_ids = NULL;
            
            // 获取当前分页页面
            $varPage = isset(Config::get('paginate')['var_page']) ? Config::get('paginate')['var_page'] : 'page';
            $page = isset($this->param[$varPage]) ? $this->param[$varPage] : 1;
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
                $cur_page = $page; // 当前请求分页页面
            }
            
            // $key = "";
            // 獲取關鍵字
            if (isset($this->param['keywords']) && ! empty($this->param['keywords'])) {
                $keyword = trim($this->param['keywords']);
                $this->assign('keywords', $keyword);
            }
            
            $device_total_count = $this->onenet_cloud->get_device_total_count();
            
            $device_list = $this->onenet_cloud->device_list($cur_page, $page_size, $keyword, $tag, $is_online, $is_private, $device_ids);
            if (empty($device_list)) {
                return $this->do_error("数据获取失败，请重新刷新网页试一下");
            } else {
                
                $listnum = $device_list['total_count'];
                if (isset($trigger['dev_ids']))
                    $related_device_total_count = count($trigger['dev_ids']);
                else { // 默认关联全部设备
                       // 初次添加设备触发器时。默认关联设备总数为0
                    if (isset($action) && $action == "add")
                        $related_device_total_count = 0;
                    else
                        $related_device_total_count = $device_total_count;
                }
                $devicelist = $device_list["devices"];
                
                foreach ($devicelist as $key => $device) {
                    
                    if (isset($trigger['dev_ids'])) {
                        if (in_array($device['id'], $trigger['dev_ids'])) {
                            $devicelist[$key]["status"] = "已关联";
                        } else {
                            $devicelist[$key]["status"] = "未关联";
                        }
                    } else { // 默认关联全部设备
                        if (isset($action) && $action == "add")
                            $devicelist[$key]["status"] = "未关联";
                        else
                            $devicelist[$key]["status"] = "已关联";
                    }
                }
                
                $page = $this->paginate($page_size, $listnum, false);
                $this->assign([
                    'lists' => $devicelist,
                    'page' => $page,
                    'total' => $device_total_count,
                    'related_total' => $related_device_total_count,
                    // 'key' => $key,
                    'tid' => $tid,
                    'trigger' => $trigger
                ]);
                return $this->fetch();
            }
        }
    }

    public function reladd()
    {
        $url = $this->do_url . 'relnew';
        $tid = $this->param['tid'];
        
        $trigger = $this->onenet_cloud->trigger($tid);
        if (empty($trigger)) {
            return $this->do_error("添加设备关联失败", $url, [
                'tid' => $tid
            ]);
        } else {
            
            if (! isset($trigger['dev_ids']))
                $trigger['dev_ids'] = array();
                
                // 批量添加关联？
            if (is_array($this->param['did'])) {
                $trigger['dev_ids'] = array_merge($trigger['dev_ids'], $this->param['did']);
                $ret = $this->onenet_cloud->trigger_edit($tid, $trigger);
                if (empty($ret)) {
                    return $this->do_error("批量添加设备关联失败", $url, [
                        'tid' => $tid
                    ]);
                } else {
                    return $this->do_success("批量添加关联成功", $url, [
                        'tid' => $tid
                    ]);
                }
            } else { // 单个添加关联
                $did = array(
                    $this->param['did']
                );
                
                $trigger['dev_ids'] = array_merge($trigger['dev_ids'], $did);
                $ret = $this->onenet_cloud->trigger_edit($tid, $trigger);
                var_dump($this->onenet_cloud->raw_response());
                if (empty($ret)) {
                    // return $this->do_error("添加设备关联失败", $url, [
                    // 'tid' => $tid
                    // ]);
                } else {
                    return $this->do_success("添加关联成功", $url, [
                        'tid' => $tid
                    ]);
                }
            }
        }
    }

    public function reldel()
    {
        $url = $this->do_url . 'rel';
        $tid = $this->param['tid'];
        
        $trigger = $this->onenet_cloud->trigger($tid);
        if (empty($trigger)) {
            return $this->do_error("删除设备关联失败", $url, [
                'tid' => $tid
            ]);
        } else {
            // 批量删除关联？
            if (is_array($this->param['did'])) {
                $did = array();
                $did = $this->param['did'];
                
                $result = array_udiff($trigger['dev_ids'], $did, function ($a, $b) {
                    if ($a === $b) {
                        return 0;
                    }
                    return ($a > $b) ? 1 : - 1;
                });
                unset($trigger['dev_ids']);
                $trigger['dev_ids'] = array();
                $trigger['dev_ids'] = array_merge($trigger['dev_ids'], $result);
                $ret = $this->onenet_cloud->trigger_edit($tid, $trigger);
                if (empty($ret)) {
                    return $this->do_error("批量删除设备关联失败", $url, [
                        'tid' => $tid
                    ]);
                } else {
                    return $this->do_success("批量删除关联成功", $url, [
                        'tid' => $tid
                    ]);
                }
            } else { // 单个删除关联
                $did = $this->param['did'];
                $key = array_search($did, $trigger['dev_ids']);
                array_splice($trigger['dev_ids'], $key, 1);
                $ret = $this->onenet_cloud->trigger_edit($tid, $trigger);
                if (empty($ret)) {
                    return $this->do_error("删除设备关联失败", $url, [
                        'tid' => $tid
                    ]);
                } else {
                    return $this->do_success("删除关联成功", $url, [
                        'tid' => $tid
                    ]);
                }
            }
            return;
        }
    }
}