<?php if ( ! defined('LIB')) exit('No direct script access allowed');
session_start();
define('ROOT', dirname(__DIR__));
$GLOBALS['install_finished'] = 0;
/**
 * 检测系统环境
 */
function getSysInfo(){
    $info = array();

    $info['root'] = ROOT; //项目根目录
    $info['operate'] = php_uname('s'); //操作系统
    $info['php'] = PHP_VERSION; //php版本

    //$info['mysql_ext'] = function_exists('mysql_connect'); //是否支持mysql扩展
    //$info['mysqli_ext'] = function_exists('mysqli_connect'); //是否支持mysqli扩展
    //if($info['mysql_ext'] || $info['mysqli_ext']){
    //    $info['mysql'] = TRUE;
    //}else{
    //    $info['mysql'] = FALSE;
    //}

    $info['mysqli_ext'] = function_exists('mysqli_connect'); //是否支持mysqli扩展
    $info['safe'] = ini_get('safe_mode')?"开启":"关闭";
    $info['timezone'] = !empty(ini_get('date.timezone')) ? ini_get('date.timezone') : '未设置 请设置时区';

    $info['config'] = is_writable(ROOT.'/app/application/config/'); //检查config目录是否可写，自成config.php与database.php文件
    $info['cache'] = is_writable(ROOT.'/app/application/cache/'); //缓存目录是否可写
    $info['logs'] = is_writable(ROOT.'/app/application/logs/'); //日志目录是否可写
    $info['uploads'] = is_writable(ROOT.'/uploads/'); //上传目录是否可写
    $info['resources'] = is_writable(ROOT.'/resources'); //资源目录是否可写

    //判断是否可以执行下一步了
    if(strcmp($info['php'], '5.4.0') > 0 && $info['mysqli_ext'] && $info['config'] && $info['cache'] && $info['cache'] && $info['logs'] && $info['uploads'] && $info['resources'] && $info['timezone'] != '未设置 请设置时区'){
        $info['rs'] = TRUE;
    }else{
        $info['rs'] = FALSE;
    }
    
    if($info['mysqli_ext']){
        $info['mysql'] = "支持";
    }else{
        $info['mysql'] = "不支持";
    }

    return json_encode($info);
}


/**
 * 检测能否连接数据库及数据库是否存在
 *
 * @return 0 未连接成功 1 连接成功，数据库不存在 2 连接成功，数据库存在
 */
function testDB($host, $user, $passwd, $port='3306', $db = ''){
    if(function_exists('mysqli_connect')){
        $link = @mysqli_connect($host, $user, $passwd, 'mysql', $port);
    }else{
        $link = FALSE;
    }

    if($link && $db != ''){
        $exists_db = @mysqli_select_db($link, $db);
    }

    return $link ? 1+$exists_db : 0;
}



/**
 * 将sql中的库名，和表名分别替换掉
 * 并将管理员账号和公司名插入数据库中
 */
function importSQL($link, $dbname, $table_pre, $ad_name, $ad_pwd, $company){
    $table_pre = rtrim($table_pre, '_').'_';
    $search = array('/EXISTS `masengine`/', '/USE `masengine`/', '/EXISTS `me_/', '/INTO `me_/', '/COMPANY_NAME/', '/COMPANY_CREATE_TIME/', '/AD_NAME/', '/AD_LOGIN_NAME/', '/AD_PWD/', '/AD_CREATE_TIME/');
    $replace = array("EXISTS `$dbname`", "USE `$dbname`", "EXISTS `$table_pre", "INTO `$table_pre", "$company", date('Y-m-d H:i:s'), ucfirst($ad_name), "$ad_name", md5($ad_pwd), date('Y-m-d H:i:s'));
    $str_in = file_get_contents('./structure.sql').file_get_contents('./insert.sql');
    $str_out = preg_replace($search, $replace, $str_in);
    $arr_out = explode(';', $str_out);
    array_pop($arr_out);
    //var_dump($arr_out);exit;
    foreach($arr_out as $v){
        if(!mysqli_query($link, $v))
            return FALSE;
    }
    return TRUE;
}


/**
 * 写配置文件
 */
function writeConfig($config_file, $database_file, $host, $user, $pwd, $dbname, $prefix, $port){
    //$rs1 = file_put_contents(ROOT.'/app/application/config/config.php', $config_file) ? 1 : 0;
    $rs1 = 1; //配置文件已经有了 如果对文件没有写权限会提示安装失败

    $prefix = rtrim($prefix, '_').'_';
    $search = array('/HOST/', '/USER/', '/PWD/', '/DBNAME/', '/PREFIX/', '/PORT/');
    $replace = array("$host", "$user", "$pwd", "$dbname", "$prefix", "$port");
    $str_out = preg_replace($search, $replace, $database_file);
    $rs2 = file_put_contents(ROOT.'/app/application/config/database.php', $str_out) ? 2 : 0;
    return $rs1 + $rs2; //如果是0 两个都写入失败 是1则config成功，database失败 2则database成功config失败 3则都成功
}



/**
 * 定义config.php文件的变量
 * 定义database.php文件的变量
 * 定义structure.sql变量
 * 定义insert.sql变量
 */
$config_file = <<<'EOC'
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your CodeIgniter root. Typically this will be your base URL,
| WITH a trailing slash:
|
|	http://example.com/
|
| If this is not set then CodeIgniter will guess the protocol, domain and
| path to your installation.
|
*/
$config['base_url']	= '';

/*
|--------------------------------------------------------------------------
| Index File
|--------------------------------------------------------------------------
|
| Typically this will be your index.php file, unless you've renamed it to
| something else. If you are using mod_rewrite to remove the page set this
| variable so that it is blank.
|
*/
$config['index_page'] = 'index.php';

/*
|--------------------------------------------------------------------------
| URI PROTOCOL
|--------------------------------------------------------------------------
|
| This item determines which server global should be used to retrieve the
| URI string.  The default setting of 'AUTO' works for most servers.
| If your links do not seem to work, try one of the other delicious flavors:
|
| 'AUTO'			Default - auto detects
| 'PATH_INFO'		Uses the PATH_INFO
| 'QUERY_STRING'	Uses the QUERY_STRING
| 'REQUEST_URI'		Uses the REQUEST_URI
| 'ORIG_PATH_INFO'	Uses the ORIG_PATH_INFO
|
*/
$config['uri_protocol']	= 'AUTO';

/*
|--------------------------------------------------------------------------
| URL suffix
|--------------------------------------------------------------------------
|
| This option allows you to add a suffix to all URLs generated by CodeIgniter.
| For more information please see the user guide:
|
| http://codeigniter.com/user_guide/general/urls.html
*/

$config['url_suffix'] = '';

/*
|--------------------------------------------------------------------------
| Default Language
|--------------------------------------------------------------------------
|
| This determines which set of language files should be used. Make sure
| there is an available translation if you intend to use something other
| than english.
|
*/
$config['language']	= 'english';

/*
|--------------------------------------------------------------------------
| Default Character Set
|--------------------------------------------------------------------------
|
| This determines which character set is used by default in various methods
| that require a character set to be provided.
|
*/
$config['charset'] = 'UTF-8';

/*
|--------------------------------------------------------------------------
| Enable/Disable System Hooks
|--------------------------------------------------------------------------
|
| If you would like to use the 'hooks' feature you must enable it by
| setting this variable to TRUE (boolean).  See the user guide for details.
|
*/
$config['enable_hooks'] = FALSE;


/*
|--------------------------------------------------------------------------
| Class Extension Prefix
|--------------------------------------------------------------------------
|
| This item allows you to set the filename/classname prefix when extending
| native libraries.  For more information please see the user guide:
|
| http://codeigniter.com/user_guide/general/core_classes.html
| http://codeigniter.com/user_guide/general/creating_libraries.html
|
*/
$config['subclass_prefix'] = 'ME_';


/*
|--------------------------------------------------------------------------
| Allowed URL Characters
|--------------------------------------------------------------------------
|
| This lets you specify with a regular expression which characters are permitted
| within your URLs.  When someone tries to submit a URL with disallowed
| characters they will get a warning message.
|
| As a security measure you are STRONGLY encouraged to restrict URLs to
| as few characters as possible.  By default only these are allowed: a-z 0-9~%.:_-
|
| Leave blank to allow all characters -- but only if you are insane.
|
| DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
|
*/
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';


/*
|--------------------------------------------------------------------------
| Enable Query Strings
|--------------------------------------------------------------------------
|
| By default CodeIgniter uses search-engine friendly segment based URLs:
| example.com/who/what/where/
|
| By default CodeIgniter enables access to the $_GET array.  If for some
| reason you would like to disable it, set 'allow_get_array' to FALSE.
|
| You can optionally enable standard query string based URLs:
| example.com?who=me&what=something&where=here
|
| Options are: TRUE or FALSE (boolean)
|
| The other items let you set the query string 'words' that will
| invoke your controllers and its functions:
| example.com/index.php?c=controller&m=function
|
| Please note that some of the helpers won't work as expected when
| this feature is enabled, since CodeIgniter is designed primarily to
| use segment based URLs.
|
*/
$config['allow_get_array']		= TRUE;
$config['enable_query_strings'] = TRUE;
$config['controller_trigger']	= 'c';
$config['function_trigger']		= 'm';
$config['directory_trigger']	= 'd'; // experimental not currently in use

/*
|--------------------------------------------------------------------------
| Error Logging Threshold
|--------------------------------------------------------------------------
|
| If you have enabled error logging, you can set an error threshold to
| determine what gets logged. Threshold options are:
| You can enable error logging by setting a threshold over zero. The
| threshold determines what gets logged. Threshold options are:
|
|	0 = Disables logging, Error logging TURNED OFF
|	1 = Error Messages (including PHP errors)
|	2 = Debug Messages
|	3 = Informational Messages
|	4 = All Messages
|
| For a live site you'll usually only enable Errors (1) to be logged otherwise
| your log files will fill up very fast.
|
*/
$config['log_threshold'] = 0;

/*
|--------------------------------------------------------------------------
| Error Logging Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| application/logs/ folder. Use a full server path with trailing slash.
|
*/
$config['log_path'] = '';

/*
|--------------------------------------------------------------------------
| Date Format for Logs
|--------------------------------------------------------------------------
|
| Each item that is logged has an associated date. You can use PHP date
| codes to set your own date formatting
|
*/
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------
| Cache Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| system/cache/ folder.  Use a full server path with trailing slash.
|
*/
$config['cache_path'] = '';

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
|
| If you use the Encryption class or the Session class you
| MUST set an encryption key.  See the user guide for info.
|
*/
$config['encryption_key'] = 'me3';

/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
|
| 'sess_cookie_name'		= the name you want for the cookie
| 'sess_expiration'			= the number of SECONDS you want the session to last.
|   by default sessions last 7200 seconds (two hours).  Set to zero for no expiration.
| 'sess_expire_on_close'	= Whether to cause the session to expire automatically
|   when the browser window is closed
| 'sess_encrypt_cookie'		= Whether to encrypt the cookie
| 'sess_use_database'		= Whether to save the session data to a database
| 'sess_table_name'			= The name of the session database table
| 'sess_match_ip'			= Whether to match the user's IP address when reading the session data
| 'sess_match_useragent'	= Whether to match the User Agent when reading the session data
| 'sess_time_to_update'		= how many seconds between CI refreshing Session Information
|
*/
$config['sess_cookie_name']		= 'ci_session';
$config['sess_expiration']		= 7200;
$config['sess_expire_on_close']	= FALSE;
$config['sess_encrypt_cookie']	= FALSE;
$config['sess_use_database']	= FALSE;
$config['sess_table_name']		= 'ci_sessions';
$config['sess_match_ip']		= FALSE;
$config['sess_match_useragent']	= TRUE;
$config['sess_time_to_update']	= 300;

/*
|--------------------------------------------------------------------------
| Cookie Related Variables
|--------------------------------------------------------------------------
|
| 'cookie_prefix' = Set a prefix if you need to avoid collisions
| 'cookie_domain' = Set to .your-domain.com for site-wide cookies
| 'cookie_path'   =  Typically will be a forward slash
| 'cookie_secure' =  Cookies will only be set if a secure HTTPS connection exists.
|
*/
$config['cookie_prefix']	= "";
$config['cookie_domain']	= "";
$config['cookie_path']		= "/";
$config['cookie_secure']	= FALSE;

/*
|--------------------------------------------------------------------------
| Global XSS Filtering
|--------------------------------------------------------------------------
|
| Determines whether the XSS filter is always active when GET, POST or
| COOKIE data is encountered
|
*/
$config['global_xss_filtering'] = FALSE;

/*
|--------------------------------------------------------------------------
| Cross Site Request Forgery
|--------------------------------------------------------------------------
| Enables a CSRF cookie token to be set. When set to TRUE, token will be
| checked on a submitted form. If you are accepting user data, it is strongly
| recommended CSRF protection be enabled.
|
| 'csrf_token_name' = The token name
| 'csrf_cookie_name' = The cookie name
| 'csrf_expire' = The number in seconds the token should expire.
*/
$config['csrf_protection'] = FALSE;
$config['csrf_token_name'] = 'csrf_test_name';
$config['csrf_cookie_name'] = 'csrf_cookie_name';
$config['csrf_expire'] = 7200;

/*
|--------------------------------------------------------------------------
| Output Compression
|--------------------------------------------------------------------------
|
| Enables Gzip output compression for faster page loads.  When enabled,
| the output class will test whether your server supports Gzip.
| Even if it does, however, not all browsers support compression
| so enable only if you are reasonably sure your visitors can handle it.
|
| VERY IMPORTANT:  If you are getting a blank page when compression is enabled it
| means you are prematurely outputting something to your browser. It could
| even be a line of whitespace at the end of one of your scripts.  For
| compression to work, nothing can be sent before the output buffer is called
| by the output class.  Do not 'echo' any values with compression enabled.
|
*/
$config['compress_output'] = FALSE;

/*
|--------------------------------------------------------------------------
| Master Time Reference
|--------------------------------------------------------------------------
|
| Options are 'local' or 'gmt'.  This pref tells the system whether to use
| your server's local time as the master 'now' reference, or convert it to
| GMT.  See the 'date helper' page of the user guide for information
| regarding date handling.
|
*/
$config['time_reference'] = 'local';


/*
|--------------------------------------------------------------------------
| Rewrite PHP Short Tags
|--------------------------------------------------------------------------
|
| If your PHP installation does not have short tag support enabled CI
| can rewrite the tags on-the-fly, enabling you to utilize that syntax
| in your view files.  Options are TRUE or FALSE (boolean)
|
*/
$config['rewrite_short_tags'] = FALSE;


/*
|--------------------------------------------------------------------------
| Reverse Proxy IPs
|--------------------------------------------------------------------------
|
| If your server is behind a reverse proxy, you must whitelist the proxy IP
| addresses from which CodeIgniter should trust the HTTP_X_FORWARDED_FOR
| header in order to properly identify the visitor's IP address.
| Comma-delimited, e.g. '10.0.1.200,10.0.1.201'
|
*/
$config['proxy_ips'] = '';


/* End of file config.php */
/* Location: ./application/config/config.php */
EOC;

$database_file = <<<'EOD'
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default_master';
$active_record = TRUE;

$_master_slave_relation = array(
    'default_master' => array('default_slave'),
);

$db['default_master']['hostname'] = 'HOST';
$db['default_master']['username'] = 'USER';
$db['default_master']['password'] = 'PWD';
$db['default_master']['database'] = 'DBNAME';
$db['default_master']['dbdriver'] = 'mysqli';
$db['default_master']['dbprefix'] = 'PREFIX';
$db['default_master']['pconnect'] = false;
$db['default_master']['db_debug'] = TRUE;
$db['default_master']['cache_on'] = FALSE;
$db['default_master']['cachedir'] = '';
$db['default_master']['char_set'] = 'utf8';
$db['default_master']['dbcollat'] = 'utf8_general_ci';
$db['default_master']['swap_pre'] = '';
$db['default_master']['autoinit'] = FALSE;
$db['default_master']['stricton'] = FALSE;
$db['default_master']['port'] = 'PORT';


$db['default_slave']['hostname'] = 'HOST';
$db['default_slave']['username'] = 'USER';
$db['default_slave']['password'] = 'PWD';
$db['default_slave']['database'] = 'DBNAME';
$db['default_slave']['dbdriver'] = 'mysqli';
$db['default_slave']['dbprefix'] = 'PREFIX';
$db['default_slave']['pconnect'] = false;
$db['default_slave']['db_debug'] = TRUE;
$db['default_slave']['cache_on'] = FALSE;
$db['default_slave']['cachedir'] = '';
$db['default_slave']['char_set'] = 'utf8';
$db['default_slave']['dbcollat'] = 'utf8_general_ci';
$db['default_slave']['swap_pre'] = '';
$db['default_slave']['autoinit'] = FALSE;
$db['default_slave']['stricton'] = FALSE;
$db['default_slave']['port'] = 'PORT';

/* End of file database.php */
/* Location: ./application/config/database.php */
EOD;
