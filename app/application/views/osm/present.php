<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
    <link href="<?php echo base_url()?>osm_resources/css/common.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url()?>osm_resources/js/jquery.min.js"></script>
    <!-- <link href="../signin/css/style1.css" rel="stylesheet" type="text/css" /> -->
    <style type="text/css">
    .f-row {
            width: 80%;
            position: relative;
            /*display: -webkit-box;
            display: box;*/
        }
     .f-rows{
     	width: 100%;
		/*border: #DDE5DE solid 1px;*/
     }
     p{
     	 display:none;
     }
     ul{
     	padding:0px;
     }
     .GoodsID{
     	display:none;
     }
     button{
        background: #DAA520;
        color: #DCDCDC;
     	height:2.65em;
     	color:white;
     	 font-weight:bold;
     }
    </style>
    <title>OSM礼品兑换</title>
</head>
<body>
    <div id="Layer1" style="position:absolute; width:100%; height:100%; z-index:-1">    
        <img src="<?php echo base_url()?>osm_resources/img/bg.jpg" height="100%" width="100%"/>
    </div>
	<div class="f-row">
		<ul>
			<p class="MemberID"></p>
		    <?php if (isset($not_time)): 
		    	 echo $not_time; 
		    endif ?>
		    <br>
		    <br>
			<?php foreach ($present as $val):?>
			<div class="f-row">
				<li>
					<div class="f-rows">
						<ul>
					 		<li class="GoodsID" alt="<?php echo $val['GoodsID'];?>">
					 			<div class="f-rows">
					 				<?php echo $val['GoodsID'];?>
					 			</div>
					 		</li>
					 		<li class="picturepath" alt="<?php if (isset($val['picturepath'])) {
					 			echo $val['picturepath'];
					 		}?>">
					 			<div class="f-rows">
					 				<img src="<?php if (isset($val['picturepath'])) {
					 			echo $val['picturepath'];
					 		}?>">
					 			</div>
					 		</li>
					 		<li class="Points" name="<?php echo $val['GoodsName'];?>" alt="<?php echo $val['Points'];?>">
					 			<div class="f-rows">
					 				<?php echo $val['GoodsName'];?>
						 			<div class="f-rows" style="color:#FF4201">　
						 				积分:<?php echo $val['Points'];?>
						 			</div>
					 			</div>
					 		</li>
					 		<li>
					 			<div class="f-row">兑换数量：
	            					<input id="checkcode" type="tel" name="checkcode" placeholder="兑换数量" <?php if (isset($not_time)):
					 					echo 'disabled';
					 				endif ?> >
					 				<button alt="" repeat="repeat" <?php if (isset($not_time)):
					 					echo 'disabled';
					 				endif ?> >点击兑换</button>
	            				</div>
					 		</li>
					 	</ul>
					</div>
				</li>
			</div>
			<?php endforeach?>	
		</ul>
	</div>
</body>
<script>
	var MemberID; //会员id
	var GoodsID;//商品id
	var num;//商品数量
	var Points;//积分
	var GoodsName;//物品名称

	//点击事件：对写入input标签中的内容进行判断，防止重复提交，ajax提交需要兑换的信息
	$('button').click(function(){
		var goods_num = $(this).prev().val();
		var mask = isnum(goods_num);
		if(!mask){
			alert('请输入正确的数量');
			return;
		}
		if(goods_num >100){
			alert('最大兑换数量为100');
			return;
		}
		if($("button").attr("repeat") == ""){
			alert("您已经提交过了，请耐心等待！")
		}else{
			MemberID = $('.MemberID').text();
			GoodsID = $(this).parents("ul").children().eq(0).attr('alt');
			Points = $(this).parents("ul").children().eq(2).attr('alt');
			GoodsName = $(this).parents("ul").children().eq(2).attr('name');
			num = $(this).prev().val();
			if(num == ''){
				alert('请填写您要兑换的数量');  
			}else{
				$("button").attr("repeat",""); 
				$.post("points_exchange",{MemberID:MemberID,GoodsName:GoodsName,GoodsID:GoodsID,Points:Points,num:num},function(result){
					if (!result) {
						document.location = 'error';
					} else {
						document.location = 'success';
					}
					$("button").attr("repeat","repeat");
				},"json");
			}
		}
	});

	//对兑换数量进行匹配，是数字通过不是数字返回false
	function isnum(s){
        var patrn=/^\d*$/;
        if (!patrn.exec(s))	return false
        return true
	}
</script>
</html>