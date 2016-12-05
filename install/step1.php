<?php
define('LIB', 'install_lib');
include('lib.php');
echo file_exists(ROOT.'/resources/install.lock') ? 1 : 0;
