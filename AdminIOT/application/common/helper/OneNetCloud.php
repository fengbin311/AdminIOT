<?php



namespace app\common\helper;
use onenetapi\OneNetApi;

class OneNetCloud extends OneNetApi{
     
    // add onenetapi clound connnect test
//    protected $_apikey = 'i5juFLYfjsBRdCw6OsrYjw3wTNI=';//IOT apikey
    protected $_apikey = 'c0SmB2jwY86w3d49leX=WtB4=xM=';//BMS apikey
    protected $_apiurl = 'https://api.heclouds.com';
    
    
    public function __construct()
    {
        parent::__construct($this->_apikey,$this->_apiurl);
    }
    
    //获取设备总数
    public function get_device_total_count()
    {
         $device_list = $this->device_list(1, 1);
         if (! empty($device_list))
            return  $device_list['total_count'];
         else 
            return false;
    }
    //获取触发器总数
    public function get_trigger_total_count()
    {
        $trigger_list = $this->trigger_list(1, 1);;
        if (! empty($trigger_list))
            return  $trigger_list['total_count'];
        else
            return false;
    }
    //获取在线设备总数
    public function get_device_online_count()
    {
        $cur_page = 1;
        $page_size = 1;
        $key_word = NULL;
        $tag = NULL;
        $is_online = true;
        $is_private = NULL;
        $device_ids = NULL;
        
      
       $device_list = $this->device_list($cur_page, $page_size, $key_word, $tag, $is_online, $is_private, $device_ids);
       if (! empty($device_list))
            return  $device_list['total_count'];
         else 
            return false;
    }
    
    //获取离线设备总数
    public function get_device_offline_count()
    {
    
        $cur_page = 1;
        $page_size = 1;
        $key_word = NULL;
        $tag = NULL;
        $is_online = false;
        $is_private = NULL;
        $device_ids = NULL;
        
       $device_list = $this->device_list($cur_page, $page_size, $key_word, $tag, $is_online, $is_private, $device_ids);
       if (! empty($device_list))
            return  $device_list['total_count'];
         else 
            return false;
    }
      
    public function get_device_ds()
    {
        //先随便获取一个设备，当前是获取第一个设备， 由于采用one net 数据流模板定义数据流， 系统中所有设备都能用模板定义的数据流。 因此获取一个设备数据流信息，就可以获取到模板定义全部数据流信息
        $dev = $this->device_list(1, 1);
        if (! empty($dev)){
            return  $ds=$this->datastream_of_dev($dev['devices'][0]['id']);
        } else
            return false;
    }
    
    
    public function get_device_dummy_did()
    {
        //先随便获取一个设备，当前是获取第一个设备， 由于采用one net 数据流模板定义数据流， 系统中所有设备都能用模板定义的数据流。 因此获取一个设备数据流信息，就可以获取到模板定义全部数据流信息
        $dev = $this->device_list(1, 1,"dummy");
        if (! empty($dev)){
            return  $dev['devices'][0]['id'];
        } else
            return false;
    }
}