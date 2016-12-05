<!DOCTYPE html>
<html>
<head>
    <title></title>
    <style>
        *{
            margin: 0;
            padding: 0;
        }
        #container{
            margin-left: auto;
            margin-right: auto;
            margin-top: 50px;
            width: 800px;
            height: 600px;
            border: 1px solid #cccccc;
        }
        .but{
            width: 80px;
            height: 30px;
        }
        .p_10{
            padding: 10px;
        }
        .m_10{
            margin: 10px;
        }
        .t_r{
            text-align: right;
        }
        .t_l{
            text-align: left;
        }
        .t_i_10{
            text-indent: 10px;
        }
        table{
            width: 500px;
            height: 150px;
        }
        .input{
            width: 200px;
            height: 28px;
            border: 1px solid #cccccc;
        }
    </style>
</head>
<body>
    <div id="container">
        <button class="but m_10">创建规则</button>

        <div class="m_10">
            <form method="post" action="http://w.com/me3/app/index.php/mex/receive_message/insert_rule" target="hide_frame">
            <table>
                <tr>
                    <td class="t_r">规则名:</td>
                    <td class="t_i_10">
                        <input type="text" name="name" class="input"/>
                    </td>
                </tr>
                <tr>
                    <td class="t_r">关键词:</td>
                    <td class="t_i_10">
                        <input type="text" name="keyword" class="input"/>
                    </td>
                </tr>
                <tr>
                    <td class="t_r">回复消息:</td>
                    <td class="t_i_10">
<!--                        <input type="text" name="message" class="input"/>-->
                        <input type="radio" name="type" checked/> 文字
                        <input type="radio" name="type"/> 语音
                        <input type="radio" name="type"/> 图文
                        <input type="radio" name="type"/> 图片
                    </td>
                </tr>
                <tr>
                    <td>上传文件</td>
                    <td>

                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="t_l t_i_10">
                        <button class="but">确认</button>
                    </td>
                </tr>
            </table>
            </form>
        </div>
        <div class="m_10">
            <iframe width="500" height="200" name="hide_frame" style="" frameborder="0"></iframe>
        </div>
    </div>
</body>
</html>