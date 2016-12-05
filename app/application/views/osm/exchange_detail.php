<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
    <link href="<?php echo base_url()?>osm_resources/css/common.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url()?>osm_resources/js/jquery.min.js"></script>
    <script src="<?php echo base_url()?>osm_resources/js/jquery/jquery_ui.min.js"></script>
    <link rel="stylesheet" href="<?php echo base_url()?>osm_resources/js/jquery/jquery_ui.min.css">
    <!-- <link href="../signin/css/style1.css" rel="stylesheet" type="text/css" /> -->
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
    <title>OSM兑礼明细</title>
</head>
<body>
    <div id="Layer1" style="position:absolute; width:100%; height:100%; z-index:-1">    
        <img src="<?php echo base_url()?>osm_resources/img/bg.jpg" height="100%" width="100%"/>
    </div>
    <div class="logo">
        <img src="<?php echo base_url()?>osm_resources/img/logo.png">
    </div>
    <h2>欧诗漫兑礼明细</h2>
    <form name="reg" action="exchange_detail" method="get">
        <div class="f-row">
            <div class="label">
                 开始时间：
            </div>
            <input class="datepicker" id="startdate" type="text" readonly="readonly" name="startdate" placeholder="开始时间" style="display:inline-block" value=<?php echo $startdate; ?> >
        </div>
        <div class="f-row">
            <div class="label">
                 结束时间：
            </div>
            <input class="datepicker" id="enddate" type="text" readonly="readonly" name="enddate" placeholder="结束时间" style="display:inline-block;"value=<?php echo $enddate; ?> >
        </div>
        <div class="f-row">
            <input id="submit" type="submit" name="submit" value="查 询">
        </div>
    </form>
    <?php if ($point): ?>
        <?php echo '当前积分：'.$point; ?>
    <?php endif ?>
    <table border="1" style="border-collapse:collapse;">
        <tr>
            <th>序号</th>
            <th>日期</th>
            <th>预约兑换礼品</th>
            <th>扣减积分</th>
            <th>是否已领取</th>
        </tr>
        <?php if (isset($error)) {
            echo $error;
        } else {?>
            <?php foreach ($records as $key => $record):?>
                <tr>
                    <td><?php echo $key+1 ?></td>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($record['createtime'])) ?></td>
                    <td><?php echo $record['materielname'] ?></td>
                    <td><?php echo $record['point'] ?></td>
                    <td><?php echo $record['Status'] ?></td>
                </tr>
            <?php endforeach;
        }?>  
    </table>
</body>
<script type="text/javascript">
    $(function() {
        $( ".datepicker" ).datepicker({//添加日期选择功能
            changeYear:1,//设置允许通过下拉框列表选取年份。
            changeMonth:1,//设置允许通过下拉框列表选取月份。
            numberOfMonths:1,//显示几个月
            showButtonPanel:true,//是否显示按钮面板
            dateFormat: 'yy-mm-dd',//日期格式
            clearText:"清除",//清除日期的按钮名称
            closeText:"关闭",//关闭选择框的按钮名称
            // yearSuffix: '年', //年的后缀
            showMonthAfterYear:true,//是否把月放在年的后面
            defaultDate:'now',//默认日期
            monthNamesShort: ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'],
            dayNames: ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'],
            dayNamesShort: ['周日','周一','周二','周三','周四','周五','周六'],
            dayNamesMin: ['日','一','二','三','四','五','六'],
            currentText: '今天',
            });
    });
    var getCheckCodeBtn = document.getElementById('getCheckCodeBtn');
    var submitBtn = document.getElementsByName('submit');
    submitBtn = submitBtn[0];

    
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
                    alert('验证码发送成功，请在10分钟内填写并注册。');
                } else {
                    alert('验证码发送失败');
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