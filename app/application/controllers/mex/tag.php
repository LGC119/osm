<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Tag extends ME_Controller
{
    private $wx_aid;
    public function __construct(){
        parent::__construct();
        $this ->wx_aid = 1;
        $this ->load ->model('mex/tag_model','tag');
    }
    // 获取粉丝标签
    public function get_user_tag(){
        $data['topNum'] = $this ->input ->get('topNum');
        $data['group_id'] = $this ->input ->get('group_id');
        $data['openid'] = $this ->input ->get('openid');
        $datalist = $this ->tag ->get_user_tag($data);
        if($datalist)
            $this ->meret($datalist,200,'读取成功！');
        else
            $this ->meret('',204,'数据为空！');

    }
    // 粉丝标记标签
    public function user_mark(){
        $data['wx_aid'] = $this ->wx_aid;
        $data['wx_user_id'] = $this ->input ->post('wx_user_id');
        $data['tag_id'] = $this ->input ->post('tag_id');
        $status = $this ->tag ->user_mark($data);
        if($status)
            $this ->meret('',200,'更新成功！');
        else
            $this ->meret('',508,'更新失败');
    }
    // 粉丝取消标记标签
    public function user_unmark(){
        $wx_user_id = $this ->input ->post('wx_user_id');
        $status = $this ->user ->user_unmark($wx_user_id);
        if($status)
            $this ->meret('',200,'取消成功！');
        else
            $this ->meret('',508,'取消失败！');
    }

}

