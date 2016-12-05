<?php
define('LIB', 'install_lib');
include('lib.php');
//获取post数据
$method = trim($_POST['method']);

if(1 == $method){
    $host = trim($_POST['host']);
    $port = trim($_POST['port']);
    $user = trim($_POST['user']);
    $password = trim($_POST['password']);
    $dbname = trim($_POST['dbname']);
    $prefix = trim($_POST['prefix']);

    $dbcon = testDB($host, $user, $password, $port, $dbname);
    echo $dbcon;
}
if(2 == $method){
    $host = trim($_POST['host']);
    $port = trim($_POST['port']);
    $user = trim($_POST['user']);
    $password = trim($_POST['password']);
    $dbname = trim($_POST['dbname']);
    $prefix = trim($_POST['prefix']);
    $ad_name = trim($_POST['ad_name']);
    $ad_pwd= trim($_POST['ad_pwd']);
    $ad_company_name = trim($_POST['ad_company_name']);

    $link = mysqli_connect($host, $user, $password, 'mysql', $port);
    //$link = mysql_connect($host.':'.$port, $user, $password);
    //var_dump($link);exit;
    $rs1 = importSQL($link, $dbname, $prefix, $ad_name, $ad_pwd, $ad_company_name);
    //var_dump($rs1); exit;
    if($rs1){
        $rs2 = writeConfig($config_file, $database_file, $host, $user, $password, $dbname, $prefix, $port); //如果是0 两个都写入失败 是1则config成功，database失败 2则database成功config失败 3则都成功
    }else{
        $rs2 = 4;
    }

    if(3 == $rs2){
        file_put_contents(ROOT.'/resources/install.lock', '');
        echo 1;
    }
}

