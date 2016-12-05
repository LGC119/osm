<?php
define('LIB', 'install_lib');
include('lib.php');
if(isset($_SESSION['step'])){
	echo $_SESSION['step'];
      unset($_SESSION['step']);
}else{
	echo 0;
}
    
if(isset($_POST['step']))
	$step = trim($_POST['step']);

if(!empty($step)){
    $_SESSION['step'] = $step;
}
