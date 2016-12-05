<?php 

/**
+++ +++ [记录状态] +++
**/
define('UNTOUCHED',		 0);	# [主状态] - 未操作
define('CATEGORIZED',	 1);	# [主状态] - 已分类
define('SUBMITED',		 2);	# [主状态] - 已处理（有可能为定时的，未发送）
define('REPLIED',		 3);	# [主状态] - 已发送（完全处理完毕的）
define('IGNORED',		 4);	# [主状态] - 已忽略
define('SUSPENDING',	 5);	# [主状态] - 挂起中

define('UNASSINGED',	 0);	# [分配状态] - 未分配
define('ASSINGED',		 1);	# [分配状态] - 已分配
define('REASSINGED',	 2);	# [分配状态] - 重分配

define('AUDITING',		 0);	# [审核状态] - 正在审核
define('PASSED',		 1);	# [审核状态] - 通过回复
define('REBUTED',		 2);	# [审核状态] - 驳回回复
/* +++ [记录状态 end] +++ */

/**
+++ +++ [操作代码] +++
**/
define('CATEGORIZE',	 0);	# [0].分类
define('ASSIGN',		 1);	# [1].分配
define('SUBMIT',		 2);	# [2].提交处理
define('REPLY',			 3);	# [3].发送
define('PASS',			 4);	# [4].审核通过
define('REBUT',			 5);	# [5].驳回
define('REASSIGN',		 6);	# [6].重分配
define('RECATEGORIZE',	 7);	# [7].修改分类
define('TASK',			 8);	# [8].定时发送
define('IGNORE',		 9);	# [9].忽略

define('PIN',			50);	# [50].置顶
define('UNPIN',			51);	# [51].取消置顶
define('SUSPEND',		90);	# [90].挂起
define('UNIGNORE',		91);	# [91].取消忽略
define('UNSUSPEND',		92);	# [92].取消挂起
/* +++ [操作代码 end] +++ */

/**
+++ 操作的记录状态变更设置 +++
**/
$config['op_changes'] = array(
	/* 分类操作 */
	0 => array(
		'operation_status' => CATEGORIZED
	),
	/* 分配操作 */
	1 => array(
		'assign_status' => ASSINGED
	),
	/* 提交处理操作 */
	2 => array(
		'operation_status' => SUBMITED
	),
	/* 发送回复操作 */
	3 => array(
		'operation_status' => REPLIED
	),
	4 => array(),
	/* 驳回操作 */
	5 => array(
		'operation_status' => CATEGORIZED
	),
	6 => array(),
	/* 修改分类操作 */
	7 => array(
		'operation_status' => CATEGORIZED
	),
	8 => array(),
	/* 忽略操作 */
	9 => array(
		'operation_status' => IGNORED
	),
	/* 挂起操作 */
	90 => array(
		'operation_status' => SUSPENDING
	),
	/* 取消忽略操作 */
	91 => array(
		'operation_status' => UNTOUCHED
	),
);