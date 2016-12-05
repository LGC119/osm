<?php 

/* 常量定义 */
/* 0未分类，1未处理，2审核中，3已处理，4重分配，5驳回 */
define('UNCATEGORIZED', 0);
define('UNDEALT', 1);
define('AUDITING', 2);
define('DEALT', 3);
define('REALLOCATE', 4);
define('REFUSED', 5);

/* 99忽略，88该微博已删除，98定时微博 */
define('SUSPENDING', 98);
define('IDNORED', 99);
define('DELETED', 88);

$config['op_statuses'] = array(
	// '' => '',
);