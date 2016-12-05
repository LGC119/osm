<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Rule_model extends ME_Model{


    // =======================================================================
    /**
     * 规则添加模块
     */
    // =======================================================================
    // 添加规则
    public function insert_rule($data,$metype){
        // 添加到规则表中
//        $rule_insertid = $this ->insert_rule_data($data,$metype);
//        $data['rule_id'] = $rule_insertid;
//
//        // 添加关键词
//        $this ->insert_keyword($data,$metype);
//
//        // 添加素材与规则对应表
//        return $this ->insert_media_rule($data,$metype);

    }

    // 添加素材与规则对应表
    private function insert_media_rule($data){
        // 微信操作rl_wx_media_rule表  微博操作wb_msg_media_rule
        $tableName = 'wb_msg_media_rule';
        $data1['rule_id'] = $data['rule_id'];
        $data1['media_id'] = $data['media_id'];
        return $this ->db ->insert($tableName,$data1);
    }

    // 查询规则与关键词
    public function select_rule_keyword($wx_aid){
        $sum = $this ->rule_keyword_sum($wx_aid);
        $page = $this->input->get_post('page');
        $perpage = $this->input->get_post('perpage');
        $page = intval($page) > 0 ? intval($page) : 1;
        $perpage = intval($perpage) > 0 ? intval($perpage) : 8;
        $limit = ' LIMIT '.($page - 1) * $perpage.','.$perpage;
        if ($page > ceil($sum / $perpage)){
            $limit = ' LIMIT '.$perpage;
        }

        $rule = 'wb_msg_rule';
        $keyword = 'wb_msg_keyword';
        $sql = 'SELECT rule.id ruleid,rule.name rulename,rule.updated_at,group_concat(keyword.id) keywordid,group_concat(keyword.name) keywordname FROM '.$this ->db ->dbprefix($rule).' AS rule
                    LEFT JOIN '.$this ->db ->dbprefix($keyword).' AS keyword
                        ON rule.id=keyword.rule_id
                    WHERE rule.aid="'.$wx_aid.'"
                    GROUP BY ruleid
                    ORDER BY rule.updated_at DESC'.$limit;
        $data['data'] = $this ->db ->query($sql) ->result_array();
        $data['sum'] = (int)$sum;
        $data['perpage'] = $perpage;
        $data['page'] = $page;
        return $data;
    }
    // 查询规则共多少条
    public function rule_keyword_sum($aid=''){
        $rule = 'wb_msg_rule';
        $where = ' WHERE 1=1 ';
        if($aid){
            $where .=" AND aid='$aid'";
        }

        $sql ='SELECT COUNT(1) sum FROM '.$this ->db ->dbprefix($rule).$where;
        $data = $this ->db ->query($sql) ->result_array();
        return $data[0]['sum'];
    }

    // 查询规则与标签
    public function select_rule_tag(){
        $rule = 'wb_msg_rule';
        $ruletag = 'rl_wb_rule_tag';
        $sql = 'SELECT rule.id ruleid,group_concat(tag.id) tagid,group_concat(tag.tag_name) tagname FROM '.$this ->db ->dbprefix($rule).' AS rule
                    LEFT JOIN '.$this ->db ->dbprefix($ruletag).' AS ruletag
                        ON rule.id=ruletag.rule_id
                    LEFT JOIN '.$this ->db ->dbprefix('tag').' AS tag
                        ON ruletag.tag_id=tag.id
                    GROUP BY ruleid';
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }

    // 查询规则与素材
    public function select_rule_media(){
        $rule = 'wb_msg_rule';
        $mediarule = 'wb_msg_media_rule';
        $sql = 'SELECT rule.id ruleid,media.id mediaid,media.*,mediadata.*
                    FROM '.$this ->db ->dbprefix($rule).' AS rule
                    LEFT JOIN '.$this ->db ->dbprefix($mediarule).' AS mediarule
                        ON rule.id=mediarule.rule_id
                    LEFT JOIN '.$this ->db ->dbprefix('media').' AS media
                        ON mediarule.media_id=media.id
                    LEFT JOIN '.$this ->db ->dbprefix('media_data').' AS mediadata
                        ON media.id=mediadata.mid
                    ORDER BY rule.id DESC';
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }

    // 创建规则
    public function create_rule($data){
        $ruleTab = 'wb_msg_rule';
        $keywordTab = 'wb_msg_keyword';
        $mediaTab =  'wb_msg_media_rule';
        $ruleTag  =  'rl_wb_rule_tag';
        // 添加到规则表中
        $data2['company_id'] = $data1['company_id'] = $data['company_id'];
        $data1['name'] = $data['rulename'];
        $data2['aid'] = $data1['aid'] = $data['aid'];
        $data1['updated_at'] = date('Y-m-d H:i:s',time());
        $this ->db ->insert($ruleTab,$data1);
        $ruleid = $this->db->insert_id();
        // 添加到关键词表中
        $data2['rule_id'] = $ruleid;
        if(isset($data['keywords']) && $data['keywords']){
            foreach($data['keywords'] as $keywordV){
                $data2['name'] = $keywordV['name'];
                $this ->db ->insert($keywordTab,$data2);
            }
        }
        // 标签
        // 规则与标签关系
        if($data['tag']){
            // 重新添加规则标签
            $dataTag['rule_id'] = $ruleid;
            foreach($data['tag'] as $tagV){
                $dataTag['tag_id'] = $tagV;
                $this ->db ->insert($ruleTag,$dataTag);
            }
        }

        // 添加规则与素材关联表中
        $newMedia = array();
        if(isset($data['media']['image']) && count($data['media']['image']) > 0){
            foreach($data['media']['image'] as $imageV){
                $newMedia[] = $imageV['mediaid'] ? $imageV['mediaid'] : $imageV['mid'];
            }
        }
        if(isset($data['media']['news']) && count($data['media']['news']) > 0){
            foreach($data['media']['news'] as $newsV){
                $newMedia[] = $newsV['mediaid'] ? $newsV['mediaid'] : $newsV['mid'];
            }
        }
        if(isset($data['media']['voice']) && count($data['media']['voice']) > 0){
            foreach($data['media']['voice'] as $voiceV){
                $newMedia[] = $voiceV['mediaid'] ? $voiceV['mediaid'] : $voiceV['mid'];
            }
        }
        if(isset($data['media']['articles']) && count($data['media']['articles']) > 0){
            foreach($data['media']['articles'] as $voiceV){
                $newMedia[] = $voiceV['mediaid'] ? $voiceV['mediaid'] : $voiceV['mid'];
            }
        }
        if(isset($data['media']['text']) && count($data['media']['text']) > 0){
            // 文件存到数据库中返回media表的id
            foreach($data['media']['text'] as $textV){
                $dataText['company_id'] = $data['company_id'];
                $dataText['staff_id'] = $data['staff_id'];
                $dataText['content'] = $textV['content'];
                $newMedia[] = $this ->insert_text($dataText);
            }
        }
        $values = '';
        foreach($newMedia as $newV){
            $values.='("'.$ruleid.'","'.$newV.'"),';
        }
        $values = rtrim($values,',');
        if(strlen($values) > 0){
            $sql = 'INSERT INTO '.$this ->db ->dbprefix($mediaTab).'(`rule_id`,`media_id`)
                    VALUES'.$values;
            $status = $this ->db ->query($sql);
        }else{
            $status = true;
        }
        if($status)
            return true;
        else
            return false;
    }

    // 修改规则
    public function update_rule($data){
        if(!$data['ruleid'])
            return false;
        // 规则表
        $rule = 'wb_msg_rule';
        // 关键词表
        $keywordTab = 'wb_msg_keyword';
        // 素材
        $mediarule = 'wb_msg_media_rule';
        // 规则标签关系
        $ruleTag = 'rl_wb_rule_tag';

        // 修改规则名
        if($data['rulename']){
            $data1['name'] = $data['rulename'];
            $data1['aid'] = $data['aid'];
            $this ->db ->update($rule,$data1,array('id'=>$data['ruleid']));
        }

        // 规则与标签关系
        if($data['tag']){
            // 删除规则标签
            $this ->db ->delete($ruleTag,array('rule_id'=>$data['ruleid']));
            // 重新添加规则标签
            $dataTag['rule_id'] = $data['ruleid'];
            foreach($data['tag'] as $tagV){
                $dataTag['tag_id'] = $tagV;
                $this ->db ->insert($ruleTag,$dataTag);
            }
        }

        // 修改关键词
        if($data['keywords']){
            // 删除关键词
            $this ->db ->delete($keywordTab,array('rule_id'=>$data['ruleid']));
            // 重新添加关键词
            $data2['rule_id'] = $data['ruleid'];
            $data2['aid'] = $data['aid'];
            $data2['company_id'] = $data['company_id'];
            foreach($data['keywords'] as $v){
                $data2['name'] = $v['name'];
                $this ->db ->insert($keywordTab,$data2);
            }
        }

        // 修改素材与规则对应关系
        // 1、删除rule与media相关
        $this ->db ->delete($mediarule,array('rule_id'=>$data['ruleid']));
        // 2、添加素材与规则对应关系
        $newMedia = array();
        if(isset($data['media']['image']) && count($data['media']['image']) > 0){
            foreach($data['media']['image'] as $imageV){
                $newMedia[] = $imageV['mediaid'] ? $imageV['mediaid'] : $imageV['mid'];
            }
        }
        if(isset($data['media']['news']) && count($data['media']['news']) > 0){
            foreach($data['media']['news'] as $newsV){
                $newMedia[] = $newsV['mediaid'] ? $newsV['mediaid'] : $newsV['mid'];
            }
        }
        if(isset($data['media']['voice']) && count($data['media']['voice']) > 0){
            foreach($data['media']['voice'] as $voiceV){
                $newMedia[] = $voiceV['mediaid'] ? $voiceV['mediaid'] : $voiceV['mid'];
            }
        }
        if(isset($data['media']['articles']) && count($data['media']['articles']) > 0){
            foreach($data['media']['articles'] as $articlesV){
                $newMedia[] = $articlesV['mediaid'] ? $articlesV['mediaid'] : 0;
            }
        }
        if(isset($data['media']['text']) && count($data['media']['text']) > 0){
            // 文件存到数据库中返回media表的id
            foreach($data['media']['text'] as $textV){
                $dataText['company_id'] = $data['company_id'];
                $dataText['staff_id'] = $data['staff_id'];
                $dataText['content'] = $textV['content'];
                $newMedia[] = $this ->insert_text($dataText);
            }
        }
        $values = '';
        foreach($newMedia as $newV){
            $values.='("'.$data['ruleid'].'","'.$newV.'"),';
        }
        $values = rtrim($values,',');
        if($values){
            $sql = 'INSERT INTO '.$this ->db ->dbprefix($mediarule).'(`rule_id`,`media_id`)
                    VALUES'.$values;
            $status = $this ->db ->query($sql);
        }else{
            $status = true;
        }
        if($status)
            return true;
        else
            return false;
    }

    // 删除规则
    public function delete_rule($ruleid){
        $rule = 'wb_msg_rule';
        $keyword = 'wb_msg_keyword';
        $mediarule = 'wb_msg_media_rule';
        // 微信：【wx_rule】          微博：【wb_msg_rule】
        $status = $this ->db ->delete($rule,array('id'=>$ruleid));
        // 微信：【wx_keyword】       微博：【wb_msg_keyword】
        $this ->db ->delete($keyword,array('rule_id'=>$ruleid));
        // 微信：【rl_wx_media_rule】 微博：【wb_msg_media_rule】
        $this ->db ->delete($mediarule,array('rule_id'=>$ruleid));
        return $status;
    }

    // 添加文本到media表中并返回media表id
    private function insert_text($data){
        $data1['type'] = 'text';
        $data1['staff_id'] = $data['staff_id'];
        $data1['company_id'] = $data['company_id'];
        $data1['created_at'] = date('Y-m-d H:i:s',time());
        $this ->db ->insert('media',$data1);
        $data2['mid'] = $this ->db ->insert_id();
        $data2['content'] = $data['content'];
        $this ->db ->insert('media_data',$data2);
        return $data2['mid'];
    }

    // 查看其他回复规则
    public function select_other($aid){
        $account = 'wb_account';
        $sql = "SELECT subscribed_reply,nokeyword_reply FROM ".$this ->db ->dbprefix($account)."
                WHERE id='$aid'";
        $data = $this ->db ->query($sql)->result_array();
        if($data)
            return $data[0];
        else
            return false;
    }

    /**
     * FUNCTION get_rule_by_content
     * 根据一段文字内容，返回最匹配的一条规则信息
     * @return 关键字ID，规则ID，规则标签ID 
    **/
    public function get_rule_by_content ($content, $wx_aid)
    {
        $keyword = 'wb_msg_keyword';
        $rt = 'rl_wb_rule_tag';
        $rule_info = $this->db->select("wk.id, wk.rule_id, GROUP_CONCAT(rwrt.tag_id SEPARATOR '|') AS tag_ids")
            ->from($keyword.' wk')
            ->join($rt.' rwrt', 'wk.rule_id = rwrt.rule_id', 'left')
            ->where("LOCATE(name, '{$content}')", NULL, FALSE)
            ->where(array('name <>'=>"", 'aid'=>$wx_aid))
            ->group_by('wk.rule_id')
            ->order_by('LENGTH(name)', 'DESC')
            ->get()->row_array();
        return $rule_info;
    }

    /**
     * FUNCTION get_reply_msg 获取回复信息
     *  
     * @return array ('type'=>'xxxx', 'content'=>'cxczx');
    **/
    public function get_reply_msg ($rule_info, $user_info, $account)
    {
        $media_rule = 'wb_msg_media_rule';
        // 获取规则的回复，是否有图文(图文标签是否与用户匹配！)
        $media_ids = $this->db->select("rwmr.media_id, GROUP_CONCAT(tag_id SEPARATOR '|') AS tags", FALSE)
            ->from($media_rule.' rwmr')
            ->join('rl_media_tag rmt', 'rwmr.media_id = rmt.media_id', 'LEFT')
            ->where('rule_id', $rule_info['rule_id'])
            ->group_by('rwmr.media_id')
            ->get()->result_array();
        if ( ! $media_ids)
            return array ('type'=>'noreply'); # 没有规则回复内容

        // 选择发送内容 [根据用户标签]
        $news_ids = $this->get_tag_user_rule($media_ids, $user_info['id'], $account['id']);
        if ($news_ids == FALSE) {
            # 随机选一条回复
            $rand_id = array_rand($media_ids);
            $media_id = $media_ids[$rand_id]['media_id'];
            $content = $this->db->select('md.content, m.id,m.updated_at ,m.type, m.wx_media_id AS media_id, md.title, md.digest, m.filename, md.content_source_url')
                ->from('media m')
                ->join('media_data md', 'm.id = md.mid','left')
                ->where('m.id', $media_id)
                ->get()->row_array();
            if ( ! $content){
                return array ('type'=>'noreply');
            }else if ($content['type'] == 'news'){
                return array ('type'=>'news', 'news'=>array($content));
            }else{
                // 如果是图片或是语音过期，则重新上传
                if (($content['type'] == 'image' || $content['type'] == 'voice') 
                    && ((time()+100) > strtotime($content['updated_at']))) 
                {
                    $this ->load ->library('Wxapi');
                    $urlType = $content['type'] == 'image' ? 'images' : 'voice';
                    $file_path = 'uploads/'.$urlType.'/'.$content['filename'];
                    $wx_data = $this ->wxapi ->wx_upload_file($content['type'],$file_path,$account['id']);
                    if(!isset($wx_data['errcode'])){
                        $upMedia['updated_at'] = date('Y-m-d H:i:s',$wx_data['created_at']+3*3600*24);
                        $content['media_id'] = $upMedia['wx_media_id'] = $wx_data['media_id'];
                        $this ->db ->update('media',$upMedia,array('id'=>$content['id']));
                    }
                }
                return $content;
            }
        } else {
            # 回复带标签的图文消息 <共四条>
            for ($i = 0;$i < count($media_ids) && count($news_ids) < 4;$i++) 
            {
                if ( ! empty($media_ids[$i]['tags']) && ! in_array($media_ids[$i]['media_id'], $news_ids)) 
                {
                    $news_ids[] = $media_ids[$i]['media_id'];
                }
            }

            # 将图文信息从数据库中取出
            $news = $this->db->select('md.content, m.id, m.type, m.wx_media_id AS media_id, md.title, md.digest, m.filename, md.content_source_url')
                ->from('media m')
                ->join('media_data md', 'm.id = md.mid')
                ->where_in('m.id', $news_ids)
                ->get()->result_array();

            return $news ? array ('type'=>'news', 'news'=>$news) : array ('type'=>'noreply');
        }
    }

    # 获取打了标签的用户的自动回复图文
    # 返回最多两条与标签相关联的图文
    public function get_tag_user_rule ($media_ids, $wx_user_id, $wx_aid)
    {
        $joinTable = 'rl_wb_user_tag';
        $userTable = 'wb_user';
		$joinStr = 'rwut.wb_user_id=wu.id';
		$whereArr = array(
			'rwut.wb_aid' =>$wx_aid,
			'wu.id'       =>$wx_user_id
		);

        if ( ! $wx_user_id OR ! $wx_aid)
            return FALSE;

        # 获取用户 wx_user_id 并获取用户的标签, 如果回复的media_id包含用户tag，优先回复
        $wx_user_tags = $this->db->select('tag_id')
            ->from($joinTable.' rwut')
            ->join($userTable.' wu', $joinStr, 'LEFT')
            ->where($whereArr)
            ->order_by('weight', 'DESC')
            ->get()->result_array();
        if ( ! $wx_user_tags) # 用户没有标签
            return FALSE;

        # 如果用户存在标签，根据标签选择回复内容，获取两条标签相关的图文
        $news_ids = array ();
        foreach ($wx_user_tags as $tag) {
            $tag_id = $tag['tag_id'];
            for ($i = 0; $i < count($media_ids) && count($news_ids) < 2; $i++) {
                if ( ! empty($media_ids[$i]['tags']) && strpos('|'.$media_ids[$i]['tags'].'|', '|'.$tag_id.'|') !== FALSE) 
                    $news_ids[] = $media_ids[$i]['media_id'];
            }
        }
        return empty($news_ids) ? FALSE : $news_ids;
    }

    // 更新其他回复规则
    public function update_other_rule($aid,$subscribedReply,$noKeywordReply){
        $account = 'wb_account';
        $sql = "UPDATE ".$this ->db ->dbprefix($account)." SET
                subscribed_reply='$subscribedReply',nokeyword_reply='$noKeywordReply'
                WHERE id='$aid'";
//        echo $this ->db ->last_query();
        return $this ->db ->query($sql);
    }

    // 查询无关键词时的回复
    public function select_other_rule($wx_aid){
        $sql = "SELECT nokeyword_reply FROM ".$this ->db ->dbprefix('wb_account')."
                WHERE id='$wx_aid'";
        $data = $this ->db ->query($sql)->result_array();
        if($data)
            return $data[0]['nokeyword_reply'];
        else
            return false;
    }





} 
