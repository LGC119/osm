 <!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
<link href="<?php echo base_url()?>osm_resources/css/common.css" rel="stylesheet" type="text/css" />
<title>OSM会员注册/绑定</title>
    <style type="text/css">
        .f-row {
            width: 80%;
            position: relative;
            /*display: -webkit-box;
            display: box;*/
        }
        input, select {
            display: block;
            border-radius: 0;
            width: 92%;
            /*-webkit-box-flex: 1;
            box-flex: 1;*/
            height: 2.5em;
            margin: 10px 5px;
            padding-top: 0;
            padding-bottom: 0;
        }        
        button, input#submit {
            -webkit-appearance: none;
            border: 0;
            width: 92%;
            background: #DAA520;
            color: #DCDCDC;
            border-radius: 0;
            height: 2.65em;
            margin: 10px 5px;
            display: block;
            /* -webkit-box-flex: 1;
            box-flex: 1;*/
        }
        /*#checkcode, #getCheckCodeBtn {
            -webkit-box-flex: 1;
            box-flex: 1;
        }
        #birth-label {
            -webkit-box-flex: 1;
            box-flex: 1;
        }*/
        /*select {

            -webkit-box-flex: 1;
            box-flex: 1;
        }
*/
</style>
</head>
<body>
    <div id="Layer1" style="position:absolute; width:100%; height:100%; z-index:-1">    
        <img src="<?php echo base_url()?>osm_resources/img/bg.jpg" height="100%" width="100%"/>
    </div>
    <div style="height:50px"></div>
    <div class="logo">
        <img src="<?php echo base_url()?>osm_resources/img/logo.png">
    </div>
    <h2>欧诗漫会员注册/绑定</h2>
        <div class="f-row">
            <button id="submit" onclick="window.location.href='register?id=<?php echo $id.'&';?>code=<?php echo $code;?>'">还不是会员？马上注册！</button>
        </div>
        <div class="f-row">
            <button id="submit" onclick="window.location.href='bind_wx?id=<?php echo $id.'&';?>code=<?php echo $code;?>'">已是会员，马上绑定！</button>
        </div>
</body>
<script type="text/javascript">
</script>
</html>
