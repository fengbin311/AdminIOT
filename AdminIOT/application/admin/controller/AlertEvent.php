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
use think\Controller;
use think\Response;
use think\Log;

class AlertEvent extends Controller
{

    public $index_validate = [
        [
            'keywords|查询关键字',
            'chsDash'
        ],
        [
            'page|页码',
            'number'
        ]
    ];

    //从oneNET云平台接收告警事件并保存到本地数据库
    public function index()
    {
        $error = [
            'errno' => - 1,
            'error' => 'fail'
        ];
        
        if (! $this->request->isPost())
            return json($error);
        
        $content = $this->request->getContent();
        Log::record($content);
        $alert_event = @json_decode($content, TRUE);
        
        if (empty($alert_event))
            return json($error);
        
        if (! isset($alert_event['trigger']))
            return json($error);
        
        $alertlist = array();
        $trigger = $alert_event['trigger'];
        
        foreach ($alert_event['current_data'] as $k => $trigger_source) {
            $alertlist[$k] = array(
                'trig_id' => $trigger['id'],
                'type' => $trigger['type'],
                'dev_id' => $trigger_source['dev_id'],
                'ds_id' => $trigger_source['ds_id'],
                'time' => $trigger_source['at'],
                'value' => $trigger_source['value']
            );
            
            if (isset($trigger['threshold']))
                $alertlist[$k]['threshold'] = $trigger['threshold'];
        }
        
        $admin_trigger_log = new AdminTriggerLog();
        $admin_trigger_log->saveAll($alertlist);
        
        return json([
            'errno' => 0,
            'error' => 'succ'
        ]);
    }
}
 