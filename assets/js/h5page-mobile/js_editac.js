
$('.changex').each(function(k,v){
    $(this).attr('idx', k);
    $(this).css('border','1px solid #FF9400');
});

$('.changex').mousemove(function(){
    /* $(this).css('border','2px solid #FF9400') */
    $(this).css('border','2px solid #FF9400');
});

$('.changex').mouseleave(function(){
    $(this).css('border','1px');
});

$('.changex[typex=ads]').css('height','30px').css('width','100%').css('background-color', '#FF9400');
$('.changex[typex=ads]').html('<center><b style="color:red;">点击以添加广告位</b></center>');
$('.changex[typex=rich]').html('<p> <strong>Rolex</strong></p><p> 劳力士创始人为汉斯.威尔斯多夫，1908年他在瑞士将劳力士注册为商标。</p><p> <strong>Vacheron Constantin</strong></p><p> 始创于1775年的江诗丹顿已有250年历史，是世界上历史最悠久、延续时间最长的名表之一。</p><p> <strong>IWC</strong></p><p> 创立于1868年的万国表有“机械表专家”之称。</p><strong>Cartier</strong> <p> 卡地亚拥有150多年历史，是法国珠宝金银首饰的制造名家。</p>');
$('.changex[typex=img]').attr('src', '../assets/img/h5page/h5_tpl.png');
$('.changexxx').click(function(){
    return false;
});



$('.changex').on('click',function(){
    parent.$('#showedit').css('display','none');
    parent.$('#picupload').css('display','none');
    parent.$('#choosepic').css('display','none');
    parent.$('#addselect').css('display','none');

    var idx = $(this).attr('idx');
    var typex = $(this).attr('typex');
    var me = $(this);
    parent.$('#window-content').html('');
    $(window.parent.document).find('#window-content').append('<input id="txtform2" idx="'+idx+'"  typex="'+typex+'"  type="hidden" >');

    switch(typex){
        case 'text':
            parent.$('#myModalLabel').html('编辑标题');
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'" typex="'+typex+'" class="form-horizontal"><div class="control-group"><label class="control-label" for="title">标题：</label><div class="controls"><input id="title" type="text" /></div></div></form>');
            parent.$('#title').val($(this).text())
            break;

        case 'img':
           $(window.parent.document).find('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"> <span id="picpath"><center>上传图片支持格式：GIF | JPG | PNG | BMP </br>文件最大不超过2MB<br/>推荐图片长宽比例： 12:5 </center></span></form>');
           $(window.parent.document).find('#myModalLabel').html('上传头图');
            parent.$('#choosepic').css('display','block');
            break;

        case 'rich':
            //parent.$('#window-content').html('');
            
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"></form>');
            parent.$('#myModalLabel').html('编辑内容');
            parent.$('#showedit').css('display','block');
            parent.editVal($(this).html());

            break;

        case 'tip':
            parent.$('#myModalLabel').html('改文字');
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"><div class="control-group"><label class="control-label" for="title">修改文字：</label><div class="controls"><input id="title" type="text" /></div></div></form>');
            parent.$('#title').val($(this).attr('placeholder'));
            break;

        case 'value':
            parent.$('#myModalLabel').html('改文字');
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"><div class="control-group"><label class="control-label" for="title">修改文字：</label><div class="controls"><input id="title" type="text" /></div></div></form>');
            parent.$('#title').val($(this).val())
            break;

        case "radio":
            parent.$('#myModalLabel').html('改数量');
            //parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"><div class="control-group"><label class="control-label" for="title">修改选项数量：</label><div class="controls"><input id="title" type="text" /></div></div></form>');
            //parent.$('#title').val($(this).val())
            break;

        case "select":
            parent.$('#myModalLabel').html('改数量');
            parent.$('#optionlist').empty();
            me.children().each(function(k,v){
                parent.$('#optionlist').append('<span class="oneselection"><input class="optiontxt" value="'+$(this).val()+'" type="text" /> &nbsp; <span class="label label-important removeme">&nbsp;-&nbsp;</span><br/></span>');
            });
            parent.$('#addselect').css('display','block');
            break;

        case "option":
            parent.$('#myModalLabel').html('改选项');
            var htmls ='';
            $('#selectmenu1 > option').each(function(k,v){
                var opt = {};
                opt += $(this).val();
                htmls += '<br/><input type="text" id="options'+k+'" value="'+$(this).text()+'"/>'
            });
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"><div class="control-group"><label class="control-label" for="title">修改选项列表：</label><div class="controls"> <div id="options">'+htmls+'</div>  </div></div></form>');
            break;
        case "check":
            parent.$('#myModalLabel').html('改数量');
            parent.$('#optionlist').empty();
            $(this).find('input').each(function(k,v){
                parent.$('#optionlist').append('<span class="oneselection"><input class="optiontxt" value="'+$(this).attr('checkname')+'" type="text" /> &nbsp; <span class="label label-important removeme">&nbsp;-&nbsp;</span><br/></span>');
            });
            parent.$('#addselect').css('display','block');
            break;
    };  //End Switch

    if (typex == 'ads') {
        parent.$('#modal-selectads').trigger('click');
    } else {
        // parent.$('#modal-window').trigger('click');
        parent.$('#modal-container-window').modal();
    }
    //event.stopPropagation(); 
    return false;
});
