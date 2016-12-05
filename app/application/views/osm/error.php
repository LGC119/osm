<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
<link href="<?php echo base_url()?>osm_resources/css/common.css" rel="stylesheet" type="text/css" />
    <title>错误</title>
</head>
<body>
    <div id="Layer1" style="position:absolute; width:100%; height:100%; z-index:-1">    
        <img src="<?php echo base_url()?>osm_resources/img/bg.jpg" height="100%" width="100%"/>
    </div>
<?php echo $msg?>
<p><a href="javascript:;" onclick="history.go(-1)">返回</a></p>
</body>
</html>
