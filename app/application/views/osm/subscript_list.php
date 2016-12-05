<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
<link href="<?php echo base_url()?>osm_resources/css/common.css" rel="stylesheet" type="text/css" />
<script src="<?php echo base_url()?>osm_resources/js/jquery.min.js">
</script>
<title>OSM会员订阅</title>
    <style type="text/css">
        .f-row {
            margin:10px auto;
            width: 80%;
            position: relative;
            /*display: -webkit-box;
            display: box;*/
        }
        button, input#submit {
            margin:10px auto;
            -webkit-appearance: none;
            border: 0;
            background: #DAA520;
            color: #DCDCDC;
            border-radius: 0;
            height: 2.65em;
            display: block;
        }
    </style>
</head>
<body>
    <div id="Layer1" style="position:absolute; width:100%; height:100%; z-index:-1">    
        <img src="<?php echo base_url()?>osm_resources/img/bg.jpg" height="100%" width="100%"/>
    </div>
    <div class="logo">
        <img src="<?php echo base_url()?>osm_resources/img/logo.png">
    </div>
    <h2>欧诗漫会员订阅</h2>
    <form name="reg" action="do_subscription" method="post">
    <?php foreach ($sub as $item):?>
        <div class="f-row">
            <?php if($item['pid'] == 0){?>
            <div>
                <h4><?php echo $item['tag_name']?></h4>
                <?php foreach ($item['tags'] as $val):?>
                    <?php if($val['pid'] == $item['id']){?>
                        <div>
                            <input id="mark<?php echo $val['id']?>" type="checkbox" name="groups[]" 
                            <?php 
                            foreach ($taged as $tag) {
                                if ($tag['tag_id'] == $val['id']) {
                                    echo "checked";
                                }
                            }?> 
                            value="<?php echo $val['tag_name']?>">&nbsp;&nbsp;<label for="mark<?php echo $val['id']?>"><?php echo $val['tag_name']?></label>
                        </div>
                    <?php }?>
                <?php endforeach;?>
            </div>
            <?php }?>
        </div>
    <?php endforeach;?>
    <input type="hidden" name="indexcode" value="<?php echo $indexcode;?>">
    <input type="hidden" name="openId" value="<?php echo $openId;?>">
    <input id="submit" type="submit" value="订阅">
    </form>
</body>
<script>
    $("#submit").click(function () {
        var user_status = $("#status").val();
        var check_length = $("input:checked").length;
        if(user_status == 0 && check_length == 0){
            alert("您没有选择订阅内容！");
            return false;
        }
    });
</script>
</html>
