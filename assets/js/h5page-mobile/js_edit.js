
$('.changex').each(function(k,v){
    $(this).attr('idx', k);
});

$('.changex').mousemove(function(){
    /* $(this).css('border','2px solid #FF9400') */
    $(this).css('border','1px solid #FF9400');
});

$('.changexxx').click(function(){
    return false;
});

$('.changex').mouseleave(function(){
    $(this).css('border','0px')
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
            parent.$('#myModalLabel').html('改文字');
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'" typex="'+typex+'" class="form-horizontal"><div class="control-group"><label class="control-label" for="title">修改文字：</label><div class="controls"><input id="title" type="text" /></div></div></form>');
            parent.$('#title').val($(this).text())
            break;

        case 'img':
           // parent.$('#window-content').html('');
           // parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"> <button id="choosepic" class="btn">选图</button><button id="picupload" class="btn">上传</button>  </form>');
           // parent.$('#myModalLabel').html('改图');
           //$(window.parent.document).find('#window-content').html('');
           $(window.parent.document).find('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"> <span id="picpath"><center>上传图片支持格式：GIF | JPG | PNG | BMP </br>文件最大不超过2MB<br/>推荐图片长宽比例： 12:5 </center></span></form>');
           $(window.parent.document).find('#myModalLabel').html('改图');
           //$(window.parent.document).find('#fileupload').trigger('click');
            //parent.$('#picupload').css('display','block');
            parent.$('#choosepic').css('display','block');
            break;

        case 'rich':
            //parent.$('#window-content').html('');
            parent.$('#window-content').append('<form id="txtform" idx="'+idx+'"  typex="'+typex+'" class="form-horizontal"></form>');
            parent.$('#myModalLabel').html('编辑内容');
            parent.$('#showedit').css('display','block');
            //alert($(this).html());
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

    parent.$('#modal-window').trigger('click');
    //event.stopPropagation(); 
    
    return false;
});
