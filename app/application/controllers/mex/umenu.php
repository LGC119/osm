<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Umenu extends ME_Controller
{
    private $wx_aid;
    private $company_id;
    private $staff_id;

    public function __construct(){
        parent::__construct();
        $this ->wx_aid = $this ->session ->userdata('wx_aid');
        $this ->company_id = $this ->session ->userdata('company_id');
        $this ->staff_id = $this ->session ->userdata('staff_id');
        $this ->load ->model('mex/umenu_model','umenu');
        $this ->load ->model('mex/media_model','media');
    }
	public function index(){
//        echo "<pre>";
//        print_r($_SESSION);
	}


    // 获取菜单
    public function select_umenu(){
        // 从微信那获取菜单
        $data = $this ->wxapi ->wx_select_menu();
//        var_dump($data);
        // 菜单key对应名称
        $menu = $this ->menu_key_level();
        $newData = array();
        if(!isset($data['menu']['button'])){
            // 菜单为空
            $this ->meret(NULL,MERET_OK,'获取菜单成功！');
            return FALSE;
        }
        $i = 0;
        foreach($data['menu']['button'] as $v){
            if(isset($v['type']) && $v['type'] == 'click'){
                $newData[$i]['key'] = $v['key'];
                $newData[$i]['name'] = $v['name'];
                $newData[$i]['pro_name'] = isset($menu[$v['key']]) ? $menu[$v['key']] : '';
                $i ++;
            }else if(isset($v['sub_button']) && count($v['sub_button'] > 0)){
                foreach($v['sub_button'] as $v1){
                    if(isset($v1['type']) && $v1['type'] == 'click'){
                        $newData[$i]['key'] = $v1['key'];
                        $newData[$i]['name'] = $v1['name'];
                        $newData[$i]['pro_name'] = isset($menu[$v1['key']]) ? $menu[$v1['key']] : '';
                        $i ++;
                    }
                }
                continue;
            }
        }
        // click的菜单名
        $data['menuKey'] = $newData;
        $data = $this ->_u_json_encode($data);
//        $data = '{"menu":{"button":[{"name":"菜单一","sub_button":[{"type":"click","name":"MENU1_1","key":"menu1_1","sub_button":[]},{"type":"click","name":"MENU1_2","key":"menu1_2","sub_button":[]},{"type":"click","name":"MENU1_3","key":"menu1_3","sub_button":[]},{"type":"click","name":"MENU1_4","key":"menu1_4","sub_button":[]},{"type":"click","name":"MENU1_5","key":"menu1_5","sub_button":[]}]},{"name":"MENU2","sub_button":[{"type":"click","name":"MENU2_1","key":"menu2_1","sub_button":[]},{"type":"click","name":"MENU2_2","key":"menu2_2","sub_button":[]},{"type":"click","name":"MENU2_3","key":"menu2_3","sub_button":[]},{"type":"click","name":"MENU2_4","key":"menu2_4","sub_button":[]},{"type":"click","name":"MENU2_5","key":"menu2_5","sub_button":[]}]},{"name":"MENU3","sub_button":[{"type":"click","name":"MENU3_1","key":"menu3_1","sub_button":[]},{"type":"click","name":"MENU3_2","key":"menu3_2","sub_button":[]},{"type":"click","name":"MENU3_3","key":"menu3_3","sub_button":[]},{"type":"click","name":"MENU3_4","key":"menu3_4","sub_button":[]},{"type":"click","name":"MENU3_5","key":"menu3_5","sub_button":[]}]}]}}';
        $this ->meret($data,MERET_OK,'获取菜单成功！');
    }

    // 创建菜单
    public function create_umenu(){
        $wx_aid = $this->wx_aid;
        $menuJson = $this ->input ->post('menu');
        foreach($menuJson['button'] as $menuK=>$menuV){
//            var_dump($menuV);
            if(!isset($menuV['name']) || empty($menuV['name'])){
                $menuJson['button'][$menuK] = '';
                unset($menuJson['button'][$menuK]);
            }
        }

        // 二级菜单名字为空的去掉
        foreach($menuJson['button'] as $buttonK=>$buttonV){
            if(isset($buttonV['sub_button'])){
                foreach($buttonV['sub_button'] as $subK=>$subV){
                    $status = trim($subV['name']);
                    if(empty($status)){
                        $menuJson['button'][$buttonK]['sub_button'][$subK] = '';
                        unset($menuJson['button'][$buttonK]['sub_button'][$subK]);
                    }
                }
                // values值重新分配
                $menuJson['button'][$buttonK]['sub_button'] = array_values($menuJson['button'][$buttonK]['sub_button']);

            }
        }

        //保存菜单类型是链接的信息
        foreach($menuJson['button'] as $val){
            foreach($val['sub_button'] as $menu_val){
                if($menu_val['type'] == 'view'){
                    $data['name'] = $menu_val['name'];
                    $url = $menu_val['url'];
                    $sql = "SELECT id FROM ".$this->db->dbprefix('wx_umenu')."
                        WHERE `view_url`='$url' AND wx_aid='$wx_aid'";
                    $rst = $this->db->query($sql)->result_array();
                    if(!$rst){
                        $data1['wx_aid'] = $wx_aid;
                        $data1['name'] = $menu_val['name'];
                        $data1['view_url'] = $menu_val['url'];
                        $data1['type'] = $menu_val['type'];
                        $this->db->insert('wx_umenu',$data1);
                    }else{
                        $this->db->update('wx_umenu',$data,array('wx_aid'=>$wx_aid,'view_url'=>$url));
                    }
                }
            }
        }
        //echo '<pre>';
        //print_r($menuJson['button']);
        //exit;
        $menuJson = $this ->_u_json_encode($menuJson);
        $data = $this ->umenu ->create_umenu($menuJson);
        if(isset($data['errcode'])){
            $this ->meret($data,MERET_OTHER,'微信创建菜单有问题了！');
            exit;
        }
        if($data)
            $this ->meret($data,MERET_OK,'创建成功！');
        else
            $this ->meret(NULL,MERET_OTHER,'创建失败！');

        exit;
    }

    // 查询Key对应回复内容
    public function get_key_content(){
        // key对应的Media id
        $keymedia = $this ->umenu ->get_key_medias($this ->wx_aid);
        $newkeymedia = array();
        if($keymedia){
            foreach($keymedia as $keymediaV){
                $newkeymedia[$keymediaV['key']] = $keymediaV['medias'];
            }
        }
        $mergeArr = array();
        foreach($newkeymedia as $k1 => $v1){
            if(strpos($v1,',')){
                $newkeymedia[$k1] =  explode(',',$v1);
                $mergeArr = array_merge($mergeArr,$newkeymedia[$k1]);
            }else{
                if($v1){
                    $newkeymedia[$k1] = (array)$v1;
                    $mergeArr = array_merge($mergeArr,(array)$v1);
                }
            }
        }

        // 去重得到要查询的媒体库id
        $mergeArr = array_unique($mergeArr);
        $mergeStr = implode(',',$mergeArr);
        $media = $this ->umenu ->get_media($mergeStr);

        // 添加个filepath进去
        if($media){
            foreach($media as $k5 =>$v5){
                if($media[$k5]['mediatype'] == 'voice')
                    $media[$k5]['filepath'] = 'uploads/voice/'.$v5['filename'];
                else
                    $media[$k5]['filepath'] = 'uploads/images/'.$v5['filename'];
            }
        }
        // 对查询到的媒体信息mediaid对应内容

        $newMedia = array();
        if($media){
            foreach($media as $v2){
                $newMedia[$v2['mediaid']] = $v2;
            }
        }

        // 拼接菜单对应事件
        $endData = array();
        if($newkeymedia){
            foreach($newkeymedia as $k3=>$v3){
                if(is_array($v3)){
                    foreach($v3 as $v4){
                        $endData[$k3] = isset($endData[$k3]) ? $endData[$k3] : array();
                        $endData[$k3]['news'] = isset($endData[$k3]['news']) ? $endData[$k3]['news'] : array();
                        $endData[$k3]['voice'] = isset($endData[$k3]['voice']) ? $endData[$k3]['voice'] : array();
                        $endData[$k3]['image'] = isset($endData[$k3]['image']) ? $endData[$k3]['image'] : array();
                        $endData[$k3]['text'] = isset($endData[$k3]['text']) ? $endData[$k3]['text'] : array();
                        $endData[$k3]['articles'] = isset($endData[$k3]['articles']) ? $endData[$k3]['articles'] : array();

                        $endData[$k3]['news'] = (array)$endData[$k3]['news'];
                        $endData[$k3]['voice'] = (array)$endData[$k3]['voice'];
                        $endData[$k3]['image'] = (array)$endData[$k3]['image'];
                        $endData[$k3]['text'] = (array)$endData[$k3]['text'];
                        $endData[$k3]['articles'] = (array)$endData[$k3]['articles'];
                        if($newMedia[$v4]['mediatype'] == 'articles'){
                            // 通过mediaid获取 articles的子图文
                            $mediaids = $this ->media ->get_media_all($newMedia[$v4]['mediaid']);
                            // 通过子图文的id,获取所有信息
                            $articlesData = $this ->media ->get_news_info($mediaids[0]['articles']);
                            $array4 = array(
                                'mediaid'=>$newMedia[$v4]['mediaid'],
                                'data'=>$articlesData
                            );
                        }else{
                            $array4 = (array)$newMedia[$v4];
                        }
                        array_push($endData[$k3][$newMedia[$v4]['mediatype']],$array4);
                    }
                }
            }
        }
        $this ->meret($endData,MERET_OK,'读取成功');
    }

    // 保存规则
    public function save_rule(){
        $data = $this ->input ->post("obj");
        $key = $this ->input ->post("menuKey");
        $menu_name = $this ->input ->post("menu_name");
        $medias = '';
        // 内容先进media库，返回media id
        if(isset($data['text']) && count($data['text'])>0){
            foreach($data['text'] as $textV){
                $data1['type'] = 'text';
                $data1['staff_id'] = $this ->staff_id;
                $data1['company_id'] = $this ->company_id;
                $data1['created_at'] = date('Y-m-d H:i:s',time());
                $data2['content'] = $textV[0];
                $mediaid = $this ->media ->insert_media_text($data1,$data2);
                $medias .= $mediaid.',';
            }
        }
        if(isset($data['image']) && count($data['image'])>0){
            foreach($data['image'] as $imageV){
                $medias .= $imageV[0].',';
            }
        }
        if(isset($data['news']) && count($data['news'])>0){
            foreach($data['news'] as $newsV){
                $medias .= $newsV[0].',';
            }
        }
        if(isset($data['voice']) && count($data['voice'])>0){
            foreach($data['voice'] as $voiceV){
                $medias .= $voiceV[0].',';
            }
        }
        if(isset($data['articles']) && count($data['articles'])>0){
            foreach($data['articles'] as $voiceV){
                $medias .= $voiceV[0].',';
            }
        }
        $medias = rtrim($medias,',');

        // 更改菜单事件
        $status = $this ->umenu ->save_rule($key,$medias,$this ->wx_aid,$menu_name);
//        return $status;
        if($status)
            $this ->meret(NULL,MERET_OK,'保存成功！');
        else
            $this ->meret(NULL,MERET_OTHER,'保存失败！');



    }

    // 删除规则
    public function delete_rule(){
        $menuKey = $this ->input ->post("menuKey");
        $status = $this ->umenu ->delete_rule($menuKey,$this ->wx_aid);
        if($status)
            $this ->meret(NULL,MERET_OK,'删除成功！');
        else
            $this ->meret(NULL,MERET_OTHER,'删除失败！');
    }

    // 处理中文字符
    public function _u_json_encode($arr){
        if(phpversion() >='5.4.0'){
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }else{
            $code = json_encode($arr);
            return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);
        }

    }

//    菜单key对应菜单级别名称
    private function menu_key_level(){
        return array(
          'menu1' =>'菜单1',
          'menu1_1' =>'菜单1_1',
          'menu1_2' =>'菜单1_2',
          'menu1_3' =>'菜单1_3',
          'menu1_4' =>'菜单1_4',
          'menu1_5' =>'菜单1_5',
          'menu2' =>'菜单2',
          'menu2_1' =>'菜单2_1',
          'menu2_2' =>'菜单2_2',
          'menu2_3' =>'菜单2_3',
          'menu2_4' =>'菜单2_4',
          'menu2_5' =>'菜单2_5',
          'menu3' =>'菜单3',
          'menu3_1' =>'菜单3_1',
          'menu3_2' =>'菜单3_2',
          'menu3_3' =>'菜单3_3',
          'menu3_4' =>'菜单3_4',
          'menu3_5' =>'菜单3_5',
        );
    }
	
}

