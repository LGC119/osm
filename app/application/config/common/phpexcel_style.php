<?php 

/* 导出报表的样式配置文件 */
/* 如需部分自定义，请使用 array_replace_recursive 或其他方式替换样式的值 */

/* 01. 蓝色主题 [包含标题, 表头, 单元格样式] */
$config['excel_theme_blue'] = array(
	/* 白底深蓝文字的大标题 */
	'header_style_arr' => array(
		'alignment' => array(
			'horizontal'	=> 'center',
			'vertical'		=> 'center',
			'wrap'			=> FALSE,
			'color'			=> array(
								'argb' => 'FF16365C'
							)
		),
		'font' => array(
			'size'	=> 16,
			'name'	=> '华文中宋',
			'bold'	=> TRUE,
			'color'	=> array(
						'argb' => 'FF16365C'
					)
		)
	),
	/* 居右深蓝文字表头 */
	'th_style_arr' => array(
		'alignment' => array(
			'horizontal'	=> 'right',
			'vertical'		=> 'center',
			'wrap'			=> TRUE
		),
		'font' => array(
			'size'	=> 10,
			'bold'	=> TRUE,
			'color'	=> array(
						'argb' => 'FF16365C'
					)
		),
		'fill' => array(
			'type'			=> 'solid',
			'startcolor'	=> array(
								'rgb' => '87CEEB'
							),
		),
		'borders' => array(
			'allborders' => array(
				'style' => 'thin',
				'color' => array(
					'rgb' => '666666'
				)
			)
		)
	),
	/* 居右的单元格数据 */
	'td_style_arr' => array(
		'alignment' => array(
			'horizontal'	=> 'right',
			'vertical'		=> 'center',
			'wrap'			=> FALSE
		),
		'borders' => array(
			'allborders' => array(
				'style' => 'thin',
				'color' => array(
					'rgb' => '999999'
				)
			)
		)
	)
);