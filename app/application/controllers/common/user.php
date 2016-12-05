<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends ME_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    // display users info
    // pass type&current_page
    public function index()
    {
        $params = $this->input->get(NULL, TRUE);
        extract($params);

        if ('weibo' == $type)
        {
            $aid = $this->session->userdata('wb_aid');
        }
        else if ('weixin' == $type)
        {
            $aid = $this->session->userdata('wx_aid');
        }
        else
        {
            $this->meret(NULL, MERET_BADREQUEST, 'wrong type');
            return;
        }

        $current_page = isset($params['current_page']) ? intval($params['current_page']) : 1;
        $items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
        $offset = ($current_page - 1) * $items_per_page;

        $this->load->model('common/user_model', 'user');
        $rs = $this->user->get_users($type, $aid, $items_per_page, $offset);
        $this->meret($rs, MERET_OK);
        return;
    }

    // display user's info
    public function show()
    {
        $params = $this->input->get(NULL, TRUE);
        extract($params);

        $this->load->model('common/user_model', 'user');
        $rs = $this->user->show_user($type, $id);
        $this->meret($rs, MERET_OK);
        return;
    }

    // pass tablename&data[param1]&data[param2]&id&type
    public function edit()
    {
        $params = $this->input->post(NULL, TRUE);
        extract($params);

        $data = ! empty($params['data']) ? $params['data'] : '';

        if ('weibo' != $type && 'weixin' != $type)
        {
            $this->meret(NULL, MERET_BADREQUEST, 'wrong type');
            return;
        }
        if (! empty($data))
        {
            $this->load->model('common/user_model', 'user');

            $data = $this->user->edit_user($type, $id, '', $data);
            $status = $data ? MERET_OK : MERET_DBERR;
            $this->meret($data, $status);
            return;
        }
        else
        {
            $this->meret(NULL, MERET_EMPTY);
            return;
        }
        
    }

    // 用户对应标签
    public function tagid_to_name(){
        $this->load->model('common/user_model', 'user');
        $data = $this->user->tagid_to_name();
        $newData = array();
        foreach($data as $v){
            if(strpos($v['tag_name'],',')){
                $newV = explode(',',$v['tag_name']);
            }else{
                $newV = (array)$v['tag_name'];
            }
            $newData[$v['wx_user_id']] = $newV;
        }
        $this->meret($newData,MERET_OK);
    }

    // 用户对应组
    public function user_to_group(){
        $this->load->model('common/user_model', 'user');
        $data = $this->user->user_to_group();
        $newData = array();
        foreach($data as $k=>$v){
            if(strpos($v['gname'],',')){
                $newData[$v['wx_user_id']] = explode(',',$v['gname']);
            }else{
                $newData[$v['wx_user_id']] = (array)$v['gname'];
            }
        }
        $this->meret($newData,MERET_OK);
    }

    /* 给用户手动打标签 */
    public function edit_user_tag () 
    {
        $source = $this->input->get_post('source') == 'wx' ? 'wx' : 'wb';

        $user_id = intval($this->input->get_post('user_id'));
        $tags = $this->input->get_post('tags');

	    $account = array('company_id'=>$this->cid);
        if($source == 'wx'){
	        $account['id'] = (int)$this->session->userdata('wx_aid');
        }else{
	        $account['id'] = (int)$this->session->userdata('wb_aid');
        }
        if ( ! $account['id'] > 0) {
            $this->meret(NULL, MERET_BADREQUEST, '请选定账号再进行操作！');
            return ;
        }

        $this->load->model('common/tag_model', 'tag');
        $res = $this->tag->tag_user($tags, $user_id, 'manual', $account, $source);

        if (is_string($res))
            $this->meret(NULL, MERET_BADREQUEST, $res);
        else 
            $this->meret($res);

        return ;
    }

}
