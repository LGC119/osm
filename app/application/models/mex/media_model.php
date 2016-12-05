<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 * 功能：
 * 1、 上传多媒体文件 insert_media
 * 2、 上传图文信息
 */

class Media_model extends ME_Model{
    public function __construct(){
        parent::__construct();
    }

    // 添加普通数据到media表中
    public function insert_media($data,$mediaid=''){
        // 若存在则更新
        if($mediaid)
            $this ->db ->update('media',$data,array('id'=>$mediaid));
        else
            $this ->db ->insert('media',$data);
        return $this ->db ->insert_id();
    }

    //
    public function post_voice($desc,$title,$media_id){
        $data['description'] = $desc;
        $data['title'] = $title;
        $data['mid'] = $media_id;
        return $this ->db ->insert('media_data',$data);
    }

    // 添加media与标签
    public function rl_media_tag($data){
        // 若存在media_id则更新
        $this ->db ->insert('rl_media_tag',$data);
    }
    // 删除标签
    public function delete_media_tag($mediaid){
        $this ->db ->delete('rl_media_tag',array('media_id'=>$mediaid));
    }

    // 添加普通数据到media_data表中
    public function insert_media_data($data,$mediaid=''){
        if($mediaid)
            $status = $this ->db ->update('media_data',$data,array('mid'=>$mediaid));
        else
            $status = $this ->db ->insert('media_data',$data);
        return $status;
    }

    // 添加文本到media 与media_data表中
    public function insert_media_text($data1,$data2){
        $this ->db ->insert('media',$data1);
        $media_id = $this ->db ->insert_id();
        $data2['mid'] = $media_id;
        $this ->db ->insert('media_data',$data2);
        return $media_id;
    }

    // 通过articles字段id查询该图文的子图文信息
    public function get_news_info($articles){
        if(is_array($articles)){
            $artStr = implode(',',$articles);
        }else{
            $artStr = $articles;
        }
        $sql = 'SELECT media.id,media.updated_at,media.filename,media_data.thumb_media_id,media_data.author,media_data.title,media_data.content_source_url,media_data.content,media_data.digest,media_data.show_cover_pic FROM '.$this ->db ->dbprefix('media').' AS media
                    LEFT JOIN '.$this ->db ->dbprefix('media_data').' AS media_data
                        ON media.id=media_data.mid
                    WHERE media.id in ('.$artStr.')';
        $data = $this ->db ->query($sql) ->result_array();
        $newData = array();
        $articleArr = explode(',',$artStr);
        foreach($articleArr as $v){
            foreach($data as $v1){
                if($v == $v1['id']){
                    $newData[] = $v1;
                }
            }
        }
        return $newData;
    }

    /**
     * 功能：获取图片信息  语音信息  图文信息
     */
    public function get_media_data($type){
        $types = array('image', 'news', 'voice','articles');
        if ( ! in_array($type, $types))
            $type = 'all';
        $title = $this->input->get_post('title');
        $tag = $this->input->get_post('tag');

        $sum = $this ->get_media_sum($type,$title,$tag);
        $limit = '';
        $page = $this->input->get_post('page');
        $perpage = $this->input->get_post('perpage');

        $page = intval($page) > 0 ? intval($page) : 1;
        $perpage = intval($perpage) > 0 ? intval($perpage) : 6;
        $limit[0] = ($page - 1) * $perpage;
        $limit[1] = $perpage;
        if ($page > ceil($sum / $perpage)){
            $limit[2] = $perpage;
        }

        $this->db->select('m.id mediaid, m.wx_media_id,m.articles articles, m.created_at, m.updated_at, m.filename, m.aid, m.type, md.*')
            ->from('media m')
            ->join('media_data md', 'm.id = md.mid', 'left')
            ->join('rl_media_tag rmt','m.id=rmt.media_id','left')
            ->where('is_deleted', 0)
            ->group_by('m.id')
            ->order_by('created_at','desc');

        if ($type == 'all') 
            $this->db->where_in('m.type', $types);
        else 
            $this->db->where('m.type', $type);

        if($tag){
            $this->db->where('rmt.tag_id', $tag);
        }
        if($title){
            $this->db->like('md.title', $title);
        }

        if(isset($limit[2])){
            $this ->db ->limit($limit[2]);
        }else{
            $this ->db ->limit($limit[1],$limit[0]);
        }
        $data['data'] = $this->db->get()->result_array();
        $data['sum'] = (int)$sum;
        $data['perpage'] = $perpage;
        $data['page'] = $page;
        return $data;
    }

    // 查询总条数
    public  function get_media_sum($type,$title='',$tag=''){
        if($type=='all'){
            $where = " WHERE 1=1 ";
        }else{
            $where = " WHERE m.type='$type' ";
        }
        if($title){
            $where .= " AND md.title like '%$title%'";
        }
        if($tag){
            $where .= " AND rmt.tag_id='$tag'";
        }
        $sql = "SELECT count(1) sum FROM (SELECT COUNT(1) sum1 FROM ".$this ->db ->dbprefix('media')." m
                    LEFT JOIN ".$this->db->dbprefix('media_data')." md
                        ON m.id=md.mid
                    LEFT JOIN ".$this->db->dbprefix('rl_media_tag')." rmt
                        ON m.id=rmt.media_id
                    ".$where." AND m.is_deleted=0 GROUP BY m.id)s2";

        $data = $this ->db ->query($sql) ->result_array();
//        echo $this->db->last_query();
        return $data[0]['sum'];
    }

    // 通过mediaid获取wx_media_id
    public function get_wx_media_id($id){
        $sql = 'SELECT wx_media_id FROM '.$this ->db ->dbprefix('media').'
                    WHERE id="'.$id.'" LIMIT 1';
        $data = $this ->db ->query($sql) ->result_array();
        return isset($data[0]['wx_media_id']) ? $data[0]['wx_media_id'] : FALSE;
    }
    // 通过mediaid获取filename  type
    public function get_media_filename($id){
        $sql = 'SELECT filename,type FROM '.$this ->db ->dbprefix('media').'
                    WHERE id="'.$id.'" LIMIT 1';
        $data = $this ->db ->query($sql) ->result_array();
        return isset($data[0]) ? $data[0] : FALSE;
    }

    // 通过media_id获取 media内容与media_data的内容
    public function get_media_all($id){
        $sql = 'SELECT m.*,md.title,md.author,md.description,md.content,md.thumb_media_id,md.content_source_url,md.digest
                    FROM '.$this ->db ->dbprefix('media').' AS m
                    LEFT JOIN '.$this ->db ->dbprefix('media_data').' AS md
                        ON m.id = md.mid
                    WHERE m.id="'.$id.'"';
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }

    // media对应tag
    public function media_to_tag(){
        $sql = 'SELECT mediatag.media_id,group_concat(mediatag.tag_id) tag_id,group_concat(tag.tag_name) tag_name FROM '.$this ->db ->dbprefix('rl_media_tag').' AS mediatag
                    LEFT JOIN '.$this ->db ->dbprefix('tag').' AS tag
                        ON mediatag.tag_id=tag.id
                    GROUP BY mediatag.media_id';
        return $this ->db ->query($sql) ->result_array();
    }

    // 添加到h5页中
    public function insert_h5($data){
        $this->db->insert('h5_page',$data);
        return $this->db->insert_id();
    }
    // 添加h5与标签关联
    public function rl_h5_tag($data){
        $this->db->insert('rl_h5_page_tag',$data);
    }

    // 添加拼接图文的数据
    public function spell($data){
        $this->db->insert('media',$data);
        return $this->db->insert_id();
    }

    // 拼接数据
    public function update_articles_media($media_id,$wx_media_id){
        return $this->db->update('media',array('wx_media_id'=>$wx_media_id),array('id'=>$media_id));
    }



} 