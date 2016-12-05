<!doctype html>
<html lang="en" ng-app>
<head>
	<meta charset="UTF-8">
	<title>
		<?php
			echo isset($display_name) ? $display_name : '';
		?>
	</title>
	<meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
	<script src="../../lib/jquery/jquery.min.js"></script>
</head>
<body>
    <div>
    <?php 
		if( ! empty($info)){
		    echo $info;
		}else{
		    echo '<img src="http://st.map.qq.com/api?size=680*360&center=' . $location_y . ',' . $location_x . '&markers=116.490997,39.913799,red&zoom=16" alt="" width="100%" height="100%">';
		    echo '<div style="font-size:1.2em;">电话:' . $display_tel . '</div>';
		    echo '<div style="font-size:1.2em;">地址:' . $province . ' ' . $city . ' ' . $display_address . '</div>';
		}
	?>
    </div>
</body>
</html>



