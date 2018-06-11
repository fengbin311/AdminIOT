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
use app\common\model\AdminUsers;
use app\common\model\AuthGroups;
use app\common\model\AdminDevice;
use app\common\helper\OneNetCloud;
use think\Log;
use think\Config;
use think\Model;
use think\Session;
use app\common\model\AdminCmdlog;
use Monolog\Logger;
use Exception;
use PHPExcel_IOFactory;
use PHPExcel;

class Device extends Base
{

    public $validate = [
        [
            'keywords|查询关键字',
            'chsDash'
        ],
        [
            'page|页码',
            'number'
        ],
        [
            'did|设备ID',
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
        $page_size = $this->web_data['list_rows'];
        
        $keyword = NULL;
        $tag = NULL;
        $is_online = NULL;
        $is_private = NULL;
        $device_ids = NULL;
        
        if (isset($this->param['type']) && $this->param['type'] == 'excel') {
            return $this->export();
        }
        
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
        
        // 獲取查询關鍵字
        if (isset($this->param['keywords']) && ! empty($this->param['keywords'])) {
            $keyword = trim($this->param['keywords']);
            $this->assign('keywords', $keyword);
            
            if ($keyword == "在线") {
                $is_online = true;
                $keyword = null;
            }
            
            if ($keyword == "离线") {
                $is_online = false;
                $keyword = null;
            }
        }
        
        $device_list = $this->onenet_cloud->device_list($cur_page, $page_size, $keyword, $tag, $is_online, $is_private, $device_ids);
        
        if (empty($device_list)) {
            return $this->do_error("数据获取失败，请重新刷新网页试一下");
        } else {
            
            $device_total_count = $device_list['total_count'];
            $devicelist = $device_list["devices"];
            
            // FIXME add device status field. only for ui display.
            foreach ($devicelist as $key => $d) {
                if (isset($d['online'])) {
                    if ($d['online']) {
                        $devicelist[$key]["status"] = "在线";
                    } else {
                        $devicelist[$key]["status"] = "离线";
                    }
                } else { // FIX when search online keyword , one net api don't return online field.
                    if ($keyword == "在线")
                        $devicelist[$key]["status"] = "在线";
                    if ($keyword == "离线")
                        $devicelist[$key]["status"] = "离线";
                }
            }
            
            // 分页处理显示
            $page = $this->paginate($page_size, $device_total_count, false);
            $this->assign([
                'devicelist' => $devicelist,
                'page' => $page,
                'total' => $device_total_count
            ]);
            return $this->fetch();
        }
    }
    
    // 增加
    public function add()
    {
        $validate = [
            [
                'device_name|设备名称',
                'chsDash|token'
            ],
            [
                'device_auth|鉴权信息',
                'chsDash'
            ]
        ];
        
        if ($this->request->isPost()) {
            // 其他类型统一验证
            $result = $this->validate($this->param, $validate);
            if (true !== $result) {
                return $this->do_error($result);
            }
            
            $device = array();
            $device['title'] = $this->param['device_name'];
            $device['auth_info'] = $this->param['device_auth'];
            $ret = $this->onenet_cloud->device_add($device);
            if (empty($ret)) {
                return $this->do_error("添加设备失败");
            } else {
                return $this->do_success("添加设备成功");
            }
        } // end post
        
        return $this->fetch();
    }
    
    // 修改
    public function edit()
    {
        $validate = [
            [
                'device_name|设备名称',
                'chsDash|token'
            ],
            [
                'device_auth|鉴权信息',
                'chsDash'
            ]
        ];
        
        if ($this->request->isPost()) {
            
            $result = $this->validate($this->param, $validate);
            if (true !== $result) {
                return $this->do_error($result);
            }
            
            $device = array();
            $device['title'] = $this->param['device_name'];
            
            if ($this->param['device_auth'] !== $this->param['device_auth_info_origin'])
                $device['auth_info'] = $this->param['device_auth'];
            
            $device['desc'] = $this->param['device_desc'];
            $device['private'] = true;
            $device_id = $this->param['device_id'];
            $ret = $this->onenet_cloud->device_edit($device_id, $device);
            if (empty($ret)) {
                halt($this->onenet_cloud->raw_response());
                return $this->do_error("修改设备失败");
            } else {
                return $this->do_success("修改设备成功");
            }
        } else {
            $id = $this->id;
            $device = $this->onenet_cloud->device($id);
            if (empty($device)) {
                return $this->do_error();
            } else {
                $this->assign([
                    'device' => $device,
                    'device_id' => $id
                ]);
                return $this->fetch();
            }
        }
    }
    
    // 删除
    public function del()
    {
        $device_id = $this->id;
        $ret = $this->onenet_cloud->device_delete($device_id);
        if (empty($ret)) {
            return $this->do_error("删除设备失败");
        } else {
            return $this->do_success();
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
        $lists["devices"] = array();
        
        $page = isset($this->param['page']) ? $this->param['page'] : 1;
        if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
            $start_page = $page; // 当前请求分页页面
        }
        
        // 只获取一个设备，目的是获取总设备数
        $device_total_count = $this->onenet_cloud->get_device_total_count();
        $lastPage = (int) ceil($device_total_count / $page_size);
        if ($page > $lastPage) {
            $lists["errno"] = 1; // 数据获取失败
            $lists["msg"] = "页码参数错误"; // 数据获取失败
            return json($lists);
        }
        
        $device_list = $this->onenet_cloud->device_list($start_page, $page_size, $keyword, $tag, $is_online, $is_private, $device_ids);
        
        if (empty($device_list)) {
            $lists["errno"] = 1; // 数据获取失败
            $lists["msg"] = "设备云连接失败"; // 数据获取失败
        } else {
            
            $device_total_count = $device_list['total_count'];
            $devicelist = $device_list["devices"];
            
            // FIXME add device status field. only for ui display.
            foreach ($devicelist as $key => $d) {
                if (isset($d['online'])) {
                    if ($d['online']) {
                        $devicelist[$key]["status"] = "在线";
                    } else {
                        $devicelist[$key]["status"] = "离线";
                    }
                } else { // FIX when search online keyword , one net api don't return online field.
                    if ($keyword == "在线")
                        $devicelist[$key]["status"] = "在线";
                    if ($keyword == "离线")
                        $devicelist[$key]["status"] = "离线";
                }
            }
            
            $lists["errno"] = 0; // 数据获取成功
            $lists["devices"] = array_merge($lists["devices"], $device_list["devices"]);
            $lists["cur_page"] = $start_page;
            $lists["has_more"] = $lastPage > $start_page ? 1 : 0;
        }

        
        return json($lists);
    }

//以下为手机端数据调用
    public function getdatapoint_app()
    {
        $lists = array();
//        $dsid = array();
        $did = '31976358';
        $dsid = 'voltage';
//        $start_time = '2018-06-09T08:00:35';
//        $end_time = '2018-06-11T20:00:35';
        $start_time = NULL;
        $end_time = NULL;
        $limit = 1;
        $cursor = NULL;
        $ret = $this->onenet_cloud->datapoint_get($did, $dsid, $start_time, $end_time, $limit, $cursor);
        if (empty($ret)) {
            $lists["errno"] = 1;
            $lists["errmsg"] = "电压获取失败";
            return json($lists);
        } else {
            $datapoints = $ret['datastreams'][0]['datapoints'];
            $lists["errno"] = 0; // 数据获取成功
            $lists["errmsg"] = "电压获取成功";
            $lists["voltage"] = $datapoints;
//            console.log($ret);
//            return json($lists);
        }
        $dsid = 'current';
        $ret = $this->onenet_cloud->datapoint_get($did, $dsid, $start_time, $end_time, $limit, $cursor);
        if (empty($ret)) {
            $lists["errno1"] = 1;
            $lists["errmsg1"] = "电流获取失败";
            return json($lists);
        } else {
            $datapoints = $ret['datastreams'][0]['datapoints'];
            $lists["errno1"] = 0; // 数据获取成功
            $lists["errmsg1"] = "电流获取成功";
            $lists["current"] = $datapoints;
//            return json($lists);
        }
        $dsid = 'temperature';
        $ret = $this->onenet_cloud->datapoint_get($did, $dsid, $start_time, $end_time, $limit, $cursor);
        if (empty($ret)) {
            $lists["errno2"] = 1;
            $lists["errmsg2"] = "温度获取失败";
            return json($lists);
        } else {
            $datapoints = $ret['datastreams'][0]['datapoints'];
            $lists["errno2"] = 0; // 数据获取成功
            $lists["errmsg2"] = "温度获取成功";
            $lists["temperature"] = $datapoints;
//            return json($lists);
        }
        $dsid = 'SOC';
        $ret = $this->onenet_cloud->datapoint_get($did, $dsid, $start_time, $end_time, $limit, $cursor);
        if (empty($ret)) {
            $lists["errno3"] = 1;
            $lists["errmsg3"] = "SOC获取失败";
            return json($lists);
        } else {
            $datapoints = $ret['datastreams'][0]['datapoints'];
            $lists["errno3"] = 0; // 数据获取成功
            $lists["errmsg3"] = "SOC获取成功";
            $lists["SOC"] = $datapoints;
            return json($lists);
        }
    }




    public function data()
    {
        if (filter_var($this->param['did'], FILTER_VALIDATE_INT) == false) {
            return $this->do_error();
        }
        
        $did = $this->param['did'];
        
        // 获取设备信息
        $device = $this->onenet_cloud->device($did);
        if (empty($device)) {
            return $this->do_error("网络获取数据失败");
        }
        
        // 获取该设备下所有数据流信息
        $datastream = $this->onenet_cloud->datastream_of_dev($did);
        if (empty($datastream)) {
            return $this->do_error("网络获取数据失败");
        }
        
        // 转化数组为json字符串，便于前端显示
        foreach ($datastream as $k => $v) {
            if (isset($v['current_value']) && is_array($v['current_value']))
                $datastream[$k]['current_value'] = json_encode($v['current_value']);
        }

        $this->assign([
            'device' => $device,
            'datastream' => $datastream,
            'total' => count($datastream)
        ]);
        
        return $this->fetch();
    }
    
    // 查询某个设备下某个数据流的历史数据点
    public function getdatapoint()
    {
        $validate = [
            [
                'did|设备ID',
                'number'
            ],
            [
                'dsid|数据流ID',
                'chsDash'
            ],
            [
                'start_time|起始时间',
                'date'
            ],
            [
                'end_time|结束时间',
                'date'
            ],
            [
                'cursor|位标',
                'alphaDash'
            ]
        ];
        
        $result = $this->validate($this->param, $validate);
        if (true !== $result) {
            return json([
                "errno" => 1,
                "errmsg" => $result
            ]);
        }
        
        $did = $this->param['did'];
        $dsid = $this->param['dsid'];
        $start_time = $this->param['start_time'];
        $end_time = $this->param['end_time'];
        $limit = 20;//查询的个数
        $cursor = NULL;
        
        if (strtotime($end_time) < strtotime($start_time)) {
            return json([
                "errno" => 1,
                "errmsg" => "查询结束时间不能小于起始时间"
            ]);
        }
        
        if (isset($this->param['cursor']))
            $cursor = $this->param['cursor'];
        
        $ret = $this->onenet_cloud->datapoint_get($did, $dsid, $start_time, $end_time, $limit, $cursor);
        if (empty($ret)) {
            return json([
                "errno" => 1,
                "errmsg" => "数据获取失败"
            ]);
        } else {
            
            // 去掉数据点日期毫秒部分
            $datapoints = $ret['datastreams'][0]['datapoints'];
            foreach ($datapoints as $key => $dp) {
                $datapoints[$key]['at'] = substr($dp['at'], 0, 19);
            }
            
            $ret['datastreams'][0]['datapoints'] = $datapoints;
            return json([
                "errno" => 0,
                "errmsg" => "数据获取成功",
                "data" => $ret
            ]);
        }
    }

    private function responceCommand($success, $data)
    {
        if ($success)
            return json([
                'errno' => 0,
                'data' => $data
            ]);
        else
            return json([
                'errno' => 1,
                'data' => $data
            ]);
    }

    public function sendcommand()
    {
        $validate = [
            [
                'did|设备ID',
                'number'
            ]
        ];
        // [
        // 'sms|发送命令','chsDash'
        // ]
        
        $result = $this->validate($this->param, $validate);
        if (true !== $result) {
            return self::responceCommand(false, $result);
        }
        
        if (! isset($this->param['did']) || ! isset($this->param['sms']))
            return self::responceCommand(false, "device id or cmd need");
        
        $did = $this->param['did'];
        $sms = $this->param['sms'];
        
        // send command to onlien device
        $sndtime = date("Y-m-d H:i:s");
        $ret = $this->onenet_cloud->cmd_send($did, 0, 0, $sms);
        if (empty($ret))
            Log::record($this->onenet_cloud->raw_response(), 'error');
        return self::responceCommand(false, $this->onenet_cloud->error());
        
        $cmd_uuid = $ret['cmd_uuid'];
        $cnt = 10;
        
        $cmdlog = new AdminCmdlog();
        do {
            usleep(200000); // 200ms
            $ret = $this->onenet_cloud->get_dev_cmd_status($cmd_uuid);
            if (! empty($ret)) {
                if (in_array($ret['status'], [
                    0,
                    3,
                    5,
                    6
                ])) {
                    // save cmd log
                    $cmdlog->save([
                        'did' => $did,
                        'sndtime' => $sndtime,
                        'cmd' => $sms,
                        'status' => $ret['status']
                    ]);
                    return self::responceCommand(false, $ret['desc']);
                }
                if ($ret['status'] == 4) { // Command Response Received
                    
                    $rcvtime = date("Y-m-d H:i:s");
                    $resp = $this->onenet_cloud->get_dev_cmd_resp($cmd_uuid);
                    // save cmd log
                    $cmdlog->save([
                        'did' => $did,
                        'sndtime' => $sndtime,
                        'cmd' => $sms,
                        'status' => $ret['status'],
                        'resp' => $resp,
                        'rcvtime' => $rcvtime
                    ]);
                    
                    return self::responceCommand(true, $resp);
                }
            }
            $cnt --;
        } while ($cnt > 0);
        
        // save cmd log
        $cmdlog->save([
            'did' => $did,
            'sndtime' => $sndtime,
            'cmd' => $sms,
            'status' => $ret['status']
        ]);
        return self::responceCommand(false, $ret['desc']);
    }

    public function cmdhistory()
    {
        $page_size = 20;
        $validate = [
            [
                'keywords|查询关键字',
                'number'
            ],
            [
                'page|页码',
                'number'
            ],
            [
                'did|设备ID',
                'number'
            ]
        ];
        
        $result = $this->validate($this->param, $validate);
        if (true !== $result) {
            return $this->do_error($result);
        }
        
        if (isset($this->param['did'])) {
            $did = $this->param['did'];
            Session::set('device_id', $did);
        } else {
            $did = Session::get('device_id');
        }
        
        $cmdlog = new AdminCmdlog();
        // 獲取關鍵字
        if (isset($this->param['keywords']) && ! empty($this->param['keywords'])) {
            $keyword = $this->param['keywords'];
            $this->assign('keywords', $keyword);
            $cmdlog->whereLike('did', $keyword);
        }
        
        $lists = $cmdlog->order('sndtime desc')->paginate($page_size, false);
        $this->assign([
            'lists' => $lists,
            'page' => $lists->render(),
            'total' => $lists->total()
        ]);
        
        return $this->fetch();
    }

    public function export()
    {
        $cur_page = 1;
        $page_size = 20;
        $keyword = NULL;
        $tag = NULL;
        $is_online = NULL;
        $is_private = NULL;
        $device_ids = NULL;
        $device_list = $this->onenet_cloud->device_list($cur_page, $page_size, $keyword, $tag, $is_online, $is_private, $device_ids);
        if (empty($device_list)) {
            // TBD
        } else {
            $devicelist = $device_list["devices"];
            $data = array();
            
            // FIXME add device status field. only for ui display.
            foreach ($devicelist as $key => $d) {
                $data[$key] = array(
                    $devicelist[$key]["id"],
                    $devicelist[$key]["title"],
                    $devicelist[$key]["online"],
                    $devicelist[$key]["create_time"]
                );
            }
            $header = [
                '设备ID',
                '设备名称',
                '设备状态',
                '设备创建时间'
            ];
            $this->exportExcel($header, $data, '设备表', '2007');
        }
    }

    function exportExcel($head, $body, $name = null, $version = '2007')
    {
        try {
            
            // 输出 Excel 文件头
            $name = empty($name) ? date('Y-m-d-H-i-s') : $name;
            
            $objPHPExcel = new PHPExcel();
            $sheetPHPExcel = $objPHPExcel->setActiveSheetIndex(0);
            $char_index = range("A", "Z");
            
            // Excel 表格头
            foreach ($head as $key => $val) {
                $sheetPHPExcel->setCellValue("{$char_index[$key]}1", $val);
            }
            // Excel body 部分
            foreach ($body as $key => $val) {
                $row = $key + 2;
                $col = 0;
                foreach ($val as $k => $v) {
                    $sheetPHPExcel->setCellValue("{$char_index[$col]}{$row}", $v);
                    $col ++;
                }
            }
            // 版本差异信息
            $version_opt = [
                '2007' => [
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'ext' => '.xlsx',
                    'write_type' => 'Excel2007'
                ],
                '2003' => [
                    'mime' => 'application/vnd.ms-excel',
                    'ext' => '.xls',
                    'write_type' => 'Excel5'
                ],
                'pdf' => [
                    'mime' => 'application/pdf',
                    'ext' => '.pdf',
                    'write_type' => 'PDF'
                ],
                'ods' => [
                    'mime' => 'application/vnd.oasis.opendocument.spreadsheet',
                    'ext' => '.ods',
                    'write_type' => 'OpenDocument'
                ]
            ];
            
            header('Content-Type: ' . $version_opt[$version]['mime']);
            header('Content-Disposition: attachment;filename="' . $name . $version_opt[$version]['ext'] . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');
            
            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $version_opt[$version]['write_type']);
            $objWriter->save('php://output');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
} 