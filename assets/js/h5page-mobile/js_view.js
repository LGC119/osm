$('input[type="submit"]').click(function(){
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1);
        $.ajax({
            type     : "POST",
            url      :  './index.php/h5page/wxh5_ext/handle_submit' + '?' + hashes,
            datatype : 'json',
            data     : $('#submitform').serialize(),
            success  : function(info){
                if (info == 'success'){
                    if ($('#hiddeninfos').val() == 'weibo'){
                        window.location='./auth';
                    } else {
                        alert('成功提交');
                        $('#submitform').html('<br/><p><center><strong style="border:1px solid yellow"><b>提交成功</b></strong></center></p>');
                        //window.open('about:blank','_self'); window.close();
                    }
                }else if (info = 'resubmit'){
                    alert('重复提交');
                    $('#submitform').html('<br/><p><center><strong style="border:1px solid red"><b>重复提交</b></strong></center></p>');
                } else {
                    alert('提交失败，稍后再试。');
                };
            },
            error: function(){
                alert('error' + thispath);
            },
        });
        return false;
});

    $(function(){
        $.ajax({
        	type     : "POST",
        	url      : 'index.php/h5page/wxh5_ext/gethtml',
        	datatype : 'json',
        	data     : {'html' : 'ok'},
        	//timeout  : 2000,
        	success  : function(rval) {
                eval('var rst=' + rval);
                $('.changex').each(function(k,v){
                    $(this).attr('idx', k);
                    var idx = k;
                    var typex = $(this).attr('typex');
                    if (typeof(typex) == 'string'){
                               $('.changex[idx="'+idx+'"]').attr('name', ''+k);
                       switch(typex){
                           case "text":
                               $('.changex[idx="'+idx+'"]').text(rst[k]);
                               break;
                           case "img":
                               $('.changex[idx="'+idx+'"]').attr('src', '../'+rst[k]);
                               break;
                           case "rich":
                               $('.changex[idx="'+idx+'"]').html(rst[k]);
                               break;
                           case "value":
                               $('.changex[idx="'+idx+'"]').val(rst[k]);
                               break;
                           case "tip":
                               $('.changex[idx="'+idx+'"]').attr('placeholder', rst[k]);
                               //$('.changex[idx="'+idx+'"]').attr('name', rst[k]);
                               $('.changex[idx="'+idx+'"]').attr('name', ''+k);
                               break;
                           case "radio":
                               break;
                           case "select":
                               //$('#mobile-frame').contents().find('.changex[idx="'+idx+'"] .optionx').each(function(x,y){
                                var html = '';
                                $.each(rst[k],function(x,y){
                                    if(y!=''){
                                        $(this).val(y[x]);
                                        html += '<option value="'+y+'"  class="optionx" >'+y+'</option>'
                                    }
                                });
                                $('.changex[idx="'+k+'"]').empty();
                                $('.changex[idx="'+k+'"]').append(html);
                                //$('.changex[idx="'+k+'"] select').empty();
                                //$('.changex[idx="'+k+'"] select').append(html);
                               break;
                           case "option":
                               var none = 'option:none';
                               break;
                           case "check":
                                var html = '';
                                $.each(rst[k],function(x,y){
                                    if(y!=''){
                                        $(this).val(y[x]);
                                        html += '<input data-role="none" name="checkbox' + x + '" checkname="' + y + '" type="checkbox">' + y + ' <br/>';
                                    }
                                });
                                $('.changex[idx="'+k+'"]').empty();
                                $('.changex[idx="'+k+'"]').append(html);

                       };  //End Switch
                    };
                });
            },  //End success function

            error: function(a,info,c){
                alert('出错了。。。');
            }
        });

        //记录点击次数
//        $('body').on('click', 'a', function() {
//            var clickurl = $(this).attr('href');
//            var titleurl = $(this).text();
//            //console.log(clickurl);
//            $.ajax({
//            	type     : "POST",
//            	url      : './clicklogger',
//            	datatype : 'json',
//            	data     : {'click' : clickurl,'title':titleurl},
//            	success  : function(rst) {
//                    //window.location=clickurl;
//                },
//            });
//            return false;
//        });
    });
