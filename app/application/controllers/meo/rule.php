<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Rule extends ME_Controller
{
	private $wx_aid;
	private $wb_aid;
	private $company_id;
	private $staff_id;

	public function __construct()
	{
		parent::__construct();
		$this->wb_aid = $this->session->userdata('wb_aid');
		$this->company_id = $this->session->userdata('company_id');
		$this->staff_id = $this->session->userdata('staff_id');
		$this->load->model('meo/rule_model', 'rule');
		$this->load->model('mex/media_model', 'media');
	}

	public function index()
	{
//        $this->load->library("Wxapi");
//        echo $this->wxapi->re_get_token(31);
	}

	/**
	 * 功能：查询规则并对规则进行处理
	 * 参数：
	 */
	public function select_rule()
	{
		// 规则与关键词
		$aid = $this->wb_aid;
		$rule_keyword_old = $this->rule->select_rule_keyword($aid);
		$rule_keyword = $rule_keyword_old['data'];
		// 规则与素材
		$rule_media = $this->rule->select_rule_media();
		// 规则与标签
		$rule_tag = $this->rule->select_rule_tag();
		$new_rule_tag = array();
		foreach ($rule_tag as $newtagV) {
			$new_rule_tag[$newtagV['ruleid']] = array(
				'id' => $newtagV['tagid'],
				'name' => $newtagV['tagname']
			);
		}
		// 规则与标签组合
		$newTag = array();
		foreach ($new_rule_tag as $newtagK1 => $newtagV1) {
			$tagid = explode(',', $newtagV1['id']);
			$tagname = explode(',', $newtagV1['name']);
			foreach ($tagid as $tagK => $tagV) {
				// 若id为空则不存起来
				if (!$tagV) {
					break;
				}
				$newTag[$newtagK1][$tagK] = array(
					'id' => $tagV,
					'name' => $tagname[$tagK]
				);
			}
		}
//        var_dump($newTag);
//        exit;
		// 规则与素材组合 1对多时，以二维数组方式存储
		foreach ($rule_media as $mediaK => $mediaV) {
			if ($mediaK != 0) {
				if (is_array(current($rule_media[$mediaK - 1]))) {
					$prevRuleId = $rule_media[$mediaK - 1][0]['ruleid'];
				} else {
					$prevRuleId = $rule_media[$mediaK - 1]['ruleid'];
				}
				// 当前的ruleid等于前一个的ruleid
				if ($mediaV['ruleid'] == $prevRuleId) {
					if (is_array(current($rule_media[$mediaK - 1]))) {
						array_push($rule_media[$mediaK - 1], $rule_media[$mediaK]);
						$rule_media[$mediaK] = $rule_media[$mediaK - 1];
					} else {
						$rule_media[$mediaK] = array($rule_media[$mediaK - 1], $rule_media[$mediaK]);
					}
					$rule_media[$mediaK - 1] = '';
					unset($rule_media[$mediaK - 1]);
				}
			}
		}

		// 规则与素材组合 健值对应  rule_id对应内容
		$newMediaData = array();
		foreach ($rule_media as $mediaV2) {
			if (is_array(current($mediaV2))) {
				$newMediaData[$mediaV2[0]['ruleid']] = $mediaV2;
			} else {
				$newMediaData[$mediaV2['ruleid']] = array($mediaV2);
			}

		}

//        $this ->meret($newMediaData,200);exit;

		// 规则与关键词拼接
//        var_dump($newTag);exit;
		$newData = array();
		foreach ($rule_keyword as $keyK => $keyV) {
			$newData[$keyK]['ruleid'] = $keyV['ruleid'];
			$newData[$keyK]['updated_at'] = $keyV['updated_at'];
			$idArr = explode(',', $keyV['keywordid']);
			$nameArr = explode(',', $keyV['keywordname']);
			$newArr = array();
			foreach ($idArr as $idK => $idV) {
				$newArr[$idK] = array(
					'id' => $idV,
					'name' => $nameArr[$idK]
				);
			}
			$newData[$keyK]['keywords'] = $newArr;

			$newData[$keyK]['tags'] = isset($newTag[$keyV['ruleid']]) ? $newTag[$keyV['ruleid']] : '';
			if ((int)$newData[$keyK]['keywords'][0]['id'] <= 0) {
				$newData[$keyK]['keywords'] = '';
			}

			$newData[$keyK]['rulename'] = $keyV['rulename'];
//            $newData[$keyK]['media'] = array();
			$newData[$keyK]['media']['news'] = array();
			$newData[$keyK]['media']['text'] = array();
			$newData[$keyK]['media']['image'] = array();
			$newData[$keyK]['media']['voice'] = array();
			$newData[$keyK]['media']['articles'] = array();
			foreach ($newMediaData[$keyV['ruleid']] as $newmediaV) {
				if ($newmediaV['type'] == 'image')
					array_push($newData[$keyK]['media']['image'], $newmediaV);
				if ($newmediaV['type'] == 'text')
					array_push($newData[$keyK]['media']['text'], $newmediaV);
				if ($newmediaV['type'] == 'news')
					array_push($newData[$keyK]['media']['news'], $newmediaV);
				if ($newmediaV['type'] == 'voice')
					array_push($newData[$keyK]['media']['voice'], $newmediaV);
				if ($newmediaV['type'] == 'articles') {
					array_push($newData[$keyK]['media'][$newmediaV['type']], array(
						'mediaid' => $newmediaV['mediaid'],
						'data' => $this->media->get_news_info($newmediaV['articles'])
					));
				}

//                    array_push($newData[$keyK]['media']['articles'],$newmediaV);

			}
//            $newData[$keyK]['media'] = $newMediaData[$keyV['ruleid']];
		}

		// 以updated_at排序 倒序
		foreach ($newData as $k3 => $v3) {
			$sort1[$k3] = $v3['updated_at'];
		}
		if (count($newData) > 1) {
			array_multisort($sort1, SORT_DESC, $newData);
		}
		// 把分页信息弄进去
//        $newData['page'] = array();
		$newData['page']['page'] = $rule_keyword_old['page'];
		$newData['page']['perpage'] = $rule_keyword_old['perpage'];
		$newData['page']['sum'] = $rule_keyword_old['sum'];
		if ($newData)
			$this->meret($newData, MERET_OK, '读取成功！');
		else
			$this->meret(NULL, MERET_OK, '数据为空！');
	}

	// 添加自动回复规则到数据库
	public function insert_rule()
	{
		// 规则名
		$data['rulename'] = $this->input->post('rulename');
		// 关键词
		$data['keyword'] = $this->input->post('keyword');
		$data1['staff_id'] = $data['staff_id'] = $this->staff_id;
		$data1['company_id'] = $data['company_id'] = $this->company_id;
		$data1['aid'] = $data['aid'] = $this->wb_aid;
		$type = $this->input->post('type');
		switch ($type) {
			case 'text':
				$data2['content'] = $this->input->post('content');
				$data1['created_at'] = date("Y-m-d H:i:s", time());
				$data1['updated_at'] = date("Y-m-d H:i:s", time());
				$data1['type'] = 'text';
				$data['media_id'] = $this->media->insert_media_text($data1, $data2);
				break;
			case 'voice':
				$data['media_id'] = $this->input->post('content');
				break;
			case 'article':
				$data['media_id'] = $this->input->post('content');
				break;
			case 'image':
				$data['media_id'] = $this->input->post('content');
				break;
			default:
				break;
		}

		$status = $this->rule->insert_rule($data);
		if ($status)
			$this->meret(NULL, MERET_OK, '新建成功！');
		else
			$this->meret(NULL, MERET_OTHER, '新建失败！');

	}

	// 创建规则
	public function create_rule()
	{
		$data = $this->input->post();
		$data['aid'] = $this->wb_aid;
		$data['company_id'] = $this->company_id;
		$data['staff_id'] = $this->staff_id;
		$status = $this->rule->create_rule($data);
		if ($status)
			$this->meret(NULL, MERET_OK, '创建成功！');
		else
			$this->meret(NULL, MERET_OTHER, '创建失败！');
//        var_dump($data);
	}

	// 修改规则
	public function update_rule()
	{
		$data = $this->input->post();
		$data['aid'] = $this->wb_aid;
		$data['company_id'] = $this->company_id;
		$data['staff_id'] = $this->staff_id;
		$status = $this->rule->update_rule($data);
		if ($status)
			$this->meret(NULL, MERET_OK, '保存成功！');
		else
			$this->meret(NULL, MERET_OTHER, '保存失败！');
	}

	// 删除规则
	public function delete_rule()
	{
		$ruleid = $this->input->post('id');
		$status = $this->rule->delete_rule($ruleid);
		if ($status)
			$this->meret(NULL, MERET_OK, '删除成功！');
		else
			$this->meret(NULL, MERET_OTHER, '删除失败！');

	}

	/**
	 * 功能：查看其他规则
	 * 说明：在wx_account的二个字段。
	 * 一：关注时回复信息
	 * 二：无关键词匹配时回复信息
	 */
	public function select_other()
	{

		$aid = $this->wb_aid;
		$data = $this->rule->select_other($aid);

		if ($data)
			$this->meret($data, MERET_OK, '读取成功！');
		else
			$this->meret(NULL, MERET_EMPTY, '数据为空！');
	}

	/**
	 * 功能：更改其他规则到wx_account表中
	 */
	public function update_other_rule()
	{
		$aid = $this->wb_aid;
		$subscribedReply = $this->input->post('subscribedReply');
		$noKeywordReply = $this->input->post('noKeywordReply');

        $value = $subscribedReply;
        $value2 = $noKeywordReply;
		// $sendValue2['sType']
		// $value2 = $sendValue2['sValue'][0]
		$status = $this->rule->update_other_rule($aid, $value, $value2);
		if ($status)
			$this->meret(NULL, MERET_OK, '更改成功！');
		else
			$this->meret(NULL, MERET_OTHER, '更改失败！');
	}


}
