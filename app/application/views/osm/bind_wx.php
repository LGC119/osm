<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
<link href="<?php echo base_url()?>osm_resources/css/common.css" rel="stylesheet" type="text/css" />
<script src="<?php echo base_url()?>osm_resources/js/jquery.min.js">
</script>
<title>OSM会员微信绑定</title>
    <style type="text/css">
        .f-row {
            width: 80%;
            position: relative;
            /*display: -webkit-box;
            display: box;*/
        }
        .col {
            -webkit-box-flex: 1;
            box-flex: 1;
            margin: 0;
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
    <div class="logo">
        <img src="<?php echo base_url()?>osm_resources/img/logo.png">
    </div>
    <h2>欧诗漫微信绑定</h2>
    <form name="reg" action="do_bind_wx" method="post">
        <div class="f-row">
        <input id="phone_num" type="tel" name="mobile" placeholder="手机号" maxlength="11">
        </div>
        <div class="f-row">
        <button id="getCheckCodeBtn">获取验证码</button>
        <input id="phone_code" type="tel" name="checkcode" placeholder="手机验证码">
        </div>
        <input type="hidden" name="indexcode" value="<?php echo $indexcode;?>">
        <input type="hidden" name="openId" value="<?php echo $openid;?>">
        <div class="f-row">
        <input type="submit" id="submit" name="submit" value="绑定">
        </div>
    </form>
</body>
<script type="text/javascript">
    var getCheckCodeBtn = document.getElementById('getCheckCodeBtn');

    getCheckCodeBtn.onclick = function () {
        //先验证手机号格式是否正确
        var phone = $('#phone_num').val(); 
        var rst = isMobil(phone);
        if(!rst){
            alert("对不起，手机号格式不正确！");
            return false;
        };
        // 按钮禁用
        getCheckCodeBtn.disabled = true;
        var mobile = document.getElementsByName('mobile');
        mobile = mobile[0].value;
        var indexcode = document.getElementsByName('indexcode');
        indexcode = indexcode[0].value;
        var openid = document.getElementsByName('openId');
        openid = openid[0].value;
        ajaxPost(mobile, indexcode, openid);
        return false;
    };

    $("#submit").click(function(){
        var phone = $('#phone_num').val(); 
        var rst = isMobil(phone);

        var phone_code = $("#phone_code").val();
        var phone_code_rst = isMobilCode(phone_code); 

        if(rst && phone_code_rst){
            return true;
        }else{
            alert("对不起，请按要求填写信息！")
            return false;
        }
    });

    // post 请求
    var ajaxPost = function (mobile, indexcode, openid) {
        
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
                if (xmlHttp.responseText > 0) {
                    //成功发送验证码后60秒才能再次点击 获取验证码 按钮
                    var time = 61;
                    getCheckCodeBtn.disabled = true;
                    var timmer = setInterval(function () {
                        time--;
                        if(time < 1){
                            getCheckCodeBtn.innerHTML = "获取验证码";
                            getCheckCodeBtn.disabled = false;
                            clearInterval(timmer);
                        }else{
                            getCheckCodeBtn.innerHTML = "获取验证码(" + time + ")";
                        }
                    },1000);

                    alert('验证码发送成功，请在10分钟内填写并绑定');

                } else {
                    alert('验证码发送失败，请重试');
                }
            }
        }
        xmlHttp.open('POST', 'send_check_code', true);
        xmlHttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xmlHttp.send('mobile=' + mobile + '&source=WX&openId=' + openid + '&indexcode=' + indexcode);
    }

    //手机号验证
    function isMobil(s){
        var patrn=/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])\d{8}$/;
        if (!patrn.exec(s)) return false
        return true
    }
    //手机验证码
    function isMobilCode(s){
        var patrn=/^\d{6}$/;
        if (!patrn.exec(s)) return false
        return true
    }
</script>
</html>
