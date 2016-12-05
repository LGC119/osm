<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 统计分析 - 标签
*/
class Stats_tag extends ME_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->model('mex/stats_model', 'stats');
        $tag_info = $this->stats->get_tag_info();

        if ($tag_info)
            $this->meret($tag_info);
        else
            $this->meret(NULL, MERET_EMPTY, '没有标签统计数据！');

        return ;

        $data = array(
            'tag_info' => $tag_info
            );

        $this->meret($data);
        return;
    }

}

/* End of file stats_tag.php */
/* Location: ./application/controllers/mex/stats_tag.php */
