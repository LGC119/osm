<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: liurongqing
 * Date: 14-5-23
 * Time: 下午4:03
 */

class Event_model extends ME_Model
{

    public function __construct(){
        parent::__construct();
//        $this ->load ->library('Wxapi');
//        $this ->load ->model('mex/media_model','media');
    }

//    创建活动
    public function create_event($data,$data2,$data3){
        // 添加到event表中
        $this ->db ->insert('event',$data);
        $insert_id = $this ->db ->insert_id();
        $group['event_id'] = $tag['event_id'] = $data2['event_id'] = $insert_id;

        // 添加到活动与标签关联表中【rl_event_tag】
        if(strpos($data3['tag_id'],',')){
            $tagArr = explode(',',$data3['tag_id']);
            foreach($tagArr as $tagV){
                $tag['tag_id'] = $tagV;
                $this ->db ->insert('rl_event_tag',$tag);
            }
        }else{
            $tag['tag_id'] = $data3['tag_id'];
            $this ->db ->insert('rl_event_tag',$tag);
        }

        // 添加到活动与组关联表中【rl_event_wx_group】
        if(strpos($data3['group_id'],',')){
            $groupArr = explode(',',$data3['group_id']);
            foreach($groupArr as $groupV){
                $group['group_id'] = $groupV;
                $this ->db ->insert('rl_event_wx_group',$group);
            }
        }else{
            $group['group_id'] = $data3['group_id'];
            $this ->db ->insert('rl_event_wx_group',$group);
        }
        // 添加到event_wx_info表中
        return $this ->db ->insert('event_wx_info',$data2);

    }

//    活动列表
    public function select_event($data2){
        $wx_aid = $data2['wx_aid'];
        $where = ' ';
        if($data2['name']){
            $name = $data2['name'];
            $where .= " AND LOCATE('$name',event_title)";
        }
        if($data2['tag_id']){
            $tagsql = 'SELECT event_id FROM '.$this ->db ->dbprefix('rl_event_tag').'
                        WHERE tag_id="'.$data2['tag_id'].'"';
            $tagData = $this ->db ->query($tagsql) ->result_array();
            $tagStr = '';
            foreach($tagData as $tagV){
                $tagStr .= $tagV['event_id'].',';
            }
            $tagStr = rtrim($tagStr,',');
            $where .= ' AND id in ('.$tagStr.')';
        }
        if($data2['type']){
            $type = $data2['type'];
            $where .= " AND type='$type'";
        }
        if($data2['trade']){
            $trade = $data2['trade'];
            $where .= " AND trade='$trade'";
        }
        if($data2['status']){
            $status = $data2['status'];
            $where .= " AND status='$status'";
        }
        if($data2['group_id']){
            $groupsql = 'SELECT event_id FROM '.$this ->db ->dbprefix('rl_event_wx_group').'
                        WHERE group_id="'.$data2['group_id'].'"';
            $groupData = $this ->db ->query($groupsql) ->result_array();
            $groupStr = '';
            foreach($groupData as $grpupV){
                $groupStr .= $grpupV['event_id'].',';
            }
            $groupStr = rtrim($groupStr,',');
            $where .= ' AND id in ('.$groupStr.')';
        }
        $sql = 'SELECT event_info.start_time send_time,event.title,event.detail,event.created_at,event.start_time,event.end_time,event.status,event.type,event.trade
                    FROM '.$this ->db ->dbprefix('event').' AS event
                    LEFT JOIN '.$this ->db ->dbprefix('event_wx_info').' AS event_info
                    WHERE aid="'.$wx_aid.'"'.$where;
        $data = $this ->db ->query($sql) ->result_array();
        return $data;

    }

//    活动详情
    public function select_event_info($id){
        $sql = 'SELECT event.*,event_info.start_time send_time FROM '.$this ->db ->dbprefix('event').' AS event
                    LEFT JOIN '.$this ->db ->dbprefix('event_wx_info').' AS event_info
                    WHERE id="'.$id.'"';
        $data = $this ->db ->query($sql) ->result_array();
        return $data;
    }
} 