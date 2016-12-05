var groupsfn = doT.template($('#groupstpl').html());
var groupSetFn = doT.template($('#group-set-tpl').html());
var groupsListFn = doT.template($('#groups-list').html());
var catesfn = doT.template($('#cates-filter-tpl').html());
var usersfn = doT.template($('#userstpl').html());
var pagingfn = doT.template($('#pagingtpl').html());
// 获取用户
function getUsers(type) {
	var offset = ( typeof(offset) != 'undefined' ) ? offset : '';

	$.getJSON(
		_c.baseUrl+'getdata/e_wxuser_h5ext.json?method=get_users' + '&type=' + type,
		function(data) {
			showUsers(data, type);
		}
	);
}
// 模板
function showUsers(data, type) {
	
	ZENG.msgbox.hide();
	if (data.code == '200') {
		var users = usersfn(data.data.users);
		$('.users-list').html(users);
		var paging = pagingfn(data.paging);
		$('.paging').html(paging);
		//分组选择模板
		if ( typeof(data.data.groups) !== 'undefined' ) {
			var groups = groupsfn(data.data.groups);
			var groupSet = groupSetFn(data.data.groups);
			$('.groups-box select').html(groups);
			$('.groups-set select').html(groupSet);
			
			var groupsList = groupsListFn(data.data.groups);
			$('#manage-group-box .modal-body').html(groupsList);
		}

		// 标签筛选模板
		if ( typeof(data.data.cates) !== 'undefined' ) {
			var cates = catesfn(data.data.cates.data);
			if (!$('.cates-filter').length) {
				$('.more-filter').prepend(cates);
			}
			
		}
		// 绑定分页
		$('.paging div:not(.ui-state-disabled)').userFilters({
			'url': _c.baseUrl + 'getdata/e_wxuser_h5ext.json?method=get_users',
			'method': 'showUsers',
			'type': type
		});
	} else {
		$('.users-list').html(data.msg);
	}
}

// 绑定范围滑动条事件
var consumingCb = function() {
	var value = consuming.getValue();
	$('.consuming-min').text(value[0].toFixed(1));
	$('.consuming-max').text(value[1].toFixed(1));
};
var consuming = $('.slider').slider().on("slide", consumingCb).data('slider');

//点击沟通记录

//发送信息
//新建分组
$('#manage-group-box').on(
	'click',
	'.new-group',
	function() {
		$('<tr class="new-group-row"><td><input type="text" id="new-group-name"></td><td class="group-opt"><button class="btn btn-primary new-group-confirm">确定</button>&nbsp;<button class="btn new-group-cancel">取消</button></td></tr>').appendTo('#manage-group-box table');
	}
);
$('#manage-group-box').on(
	'click',
	'.new-group-confirm',
	function() {
		ZENG.msgbox.show('载入中...', 6, 10000000);
		$(this).attr('disabled','disabled');
		var gname = $('#manage-group-box #new-group-name').val();
		$.post(
			_c.baseUrl+'getdata/e_wxuser.json?method=new_group',
			{
				'gname': gname
			},
			function(data) {
				var d = JSON.parse(data);
				if (d.code == '200')
				{
					ZENG.msgbox.show(d.msg, 4, 2000);

					$('.new-group-row').remove();
					$('<tr group-id="' + d.id + '"><td class="group-name">' + gname + '</td><td class="group-opt"><button class="btn edit">修改</button>&nbsp;<button class="btn delete">删除</button></td></tr>').appendTo('#manage-group-box table');

					$('.groups-box select, .groups-set select').append('<option value="' + d.id + '">' + gname + '</option>');
				}
				else
				{
					ZENG.msgbox.show(d.msg, 5, 2000);
				}
			}
		);
	}
);

$('#manage-group-box').on(
	'click',
	'.new-group-cancel',
	function() {
		$('.new-group-row').remove();
	}
);

//全选
$('.users-list').on(
	'click',
	'.liHead input',
	function() {
		if( $(this).attr('checked') == 'checked' ) {
			$('li.user').find('input').attr('checked', 'checked');
		} else {
			$('li.user').find('input').removeAttr('checked');
		}
		
	}
);

//将选中用户放入分组
$('.group').on(
	'click',
	'.putIntoGroupAll',
	function() {
		ZENG.msgbox.show('操作中...', 6, 10000000);
		var userIdsObj = $('.user').find('input:checked');
		var userIds = [];
		userIdsObj.each(function() {
			userIds.push( $(this).val() );
		});
		var gid = $('.groups-set select option:selected').attr('filter');
		$.post(
			_c.baseUrl + 'getdata/e_wxuser.json?method=into_group',
			{
				'user_ids': userIds,
				'gid': gid
			},
			function(data) {
				var d = JSON.parse(data);
				if(d.code == '200') {
					ZENG.msgbox.show(d.msg, 4, 2000);
					$('.main').load(currentPage);
				} else {
					ZENG.msgbox.show(d.msg, 5, 2000);
				}
			}
		);
	}
);


// 地区选择插件			
$('#city_select_province_city1').ajax_city_select({
	'province':'province21',
	'city':'city21'
});

// 展开更多筛选
$('.group').on('click', '#show-more', function() {
	$('.more-filter').slideDown();
	$(this).attr('id', 'hide-more').text('收起更多选项');
});
$('.group').on('click', '#hide-more', function() {
	$('.more-filter').slideUp();
	$(this).attr('id', 'show-more').text('展开更多选项');
});

// 筛选
$('.submit-filter').userFilters({
	'url': _c.baseUrl + 'getdata/e_wxuser.json?method=get_users',
	'method': 'showUsers',
	'type': $('.submit-filter').attr('type')
});

// 分组管理
$('.group').on(
	'click',
	'.manage-group',
	function() {
		$('.new-group-row').remove();
		$('#manage-group-box').modal();
	}
);

// 修改分组
$('#manage-group-box').on(
	'click',
	'.edit',
	function() {
		var render = $(this);
		var currentGroup = render.closest('tr');
		var groupId = currentGroup.attr('group-id');
		var groupNameRow = currentGroup.children('.group-name');
		var groupOptRow = currentGroup.children('.group-opt');
		var groupName =  groupNameRow.text();

		groupNameRow.html('<input type="text" id="edit-group-name" value="' + groupName + '" original-value="' + groupName + '">');
		groupOptRow.html('<button class="btn btn-primary edit-group-confirm">确定</button>&nbsp;<button class="btn edit-group-cancel">取消</button>');
	}
);
// 确认分组名称修改
$('#manage-group-box').on(
	'click',
	'.edit-group-confirm',
	function() {
		ZENG.msgbox.show('处理中...', 6, 10000000);
		var render = $(this);
		var currentGroup = render.closest('tr');
		var groupId = currentGroup.attr('group-id');
		var groupNameRow = currentGroup.children('.group-name');
		var groupOptRow = currentGroup.children('.group-opt');
		var groupNameInput = currentGroup.find('#edit-group-name');
		var groupName = groupNameInput.val();
		var originalName = groupNameInput.attr('original-value');

		render.attr('disabled', 'disabled');

		$.post(
			_c.baseUrl+'getdata/e_wxuser.json?method=edit_group',
			{
				'gname': groupName,
				'gid': groupId
			},
			function(data) {
				if (data.code == 200)
				{
					ZENG.msgbox.show(data.msg, 4, 2000);
					groupNameRow.html(groupName);
				}
				else
				{
					ZENG.msgbox.show(data.msg, 5, 2000);
					groupNameRow.html(originalName);
				}
				groupOptRow.html('<button class="btn edit">修改</button>&nbsp;<button class="btn delete">删除</button>');
			},
			'json'
		);
	}
);

// 取消修改组名
$('#manage-group-box').on(
	'click',
	'.edit-group-cancel',
	function() {
		var currentGroup = $(this).closest('tr');
		var groupNameRow = currentGroup.children('.group-name');
		var groupOptRow = currentGroup.children('.group-opt');
		var groupNameInput = currentGroup.find('#edit-group-name');
		var originalName = groupNameInput.attr('original-value');

		groupNameRow.html(originalName);
		groupOptRow.html('<button class="btn edit">修改</button>&nbsp;<button class="btn delete">删除</button>');
	}
);

// 删除组
$('#manage-group-box').on(
	'click',
	'.delete',
	function() {
		var render = $(this);
		var currentGroup = render.closest('tr');
		var groupId = currentGroup.attr('group-id');
		var groupNameRow = currentGroup.children('.group-name');
		var groupOptRow = currentGroup.children('.group-opt');
		var groupName = groupNameRow.text();
		var message = '确定删除分组：' + groupName  + ' ?';

		groupNameRow.html(message);
		groupOptRow.html('<button class="btn btn-primary" id="group-delete-confirm">确定</button>&nbsp;<button class="btn" id="group-delete-cancel" group-name="' + groupName + '">取消</button>');

	}
);
$('#manage-group-box').on(
	'click',
	'#group-delete-confirm',
	function() {
		ZENG.msgbox.show('处理中...', 6, 10000000);
		var render = $(this);
		var currentGroup = render.closest('tr');
		var groupId = currentGroup.attr('group-id');

		render.attr('disabled', 'disabled');

		$.post(
			_c.baseUrl+'getdata/e_wxuser.json?method=delete_group',
			{
				'gid': groupId
			},
			function(data) {
				if (data.code == 200)
				{
					currentGroup.remove();
					ZENG.msgbox.show(data.msg, 4, 2000);
				}
				else
				{
					ZENG.msgbox.show(data.msg, 5, 2000);
				}
				
			},
			'json'
		);
	}
);
$('#manage-group-box').on(
	'click',
	'#group-delete-cancel',
	function() {
		var render = $(this);
		var currentGroup = render.closest('tr');
		var groupId = currentGroup.attr('group-id');
		var groupNameRow = currentGroup.children('.group-name');
		var groupOptRow = currentGroup.children('.group-opt');
		var groupName = render.attr('group-name');
		groupNameRow.html(groupName);
		groupOptRow.html('<button class="btn edit">修改</button>&nbsp;<button class="btn delete">删除</button>');
	}
);

// 展开标签
$('.users-list').on(
	'click',
	'.show-cates',
	function() {
		// 展开标签div
		var render = $(this);
		render.parents('.user').find('.cates-info').slideToggle();
		// 更改按钮
		// render.text('收起标签').attr('class', 'hide-cates');
		if (render.text() == '展开标签')
		{
			render.text('收起标签');
		}
		else
		{
			render.text('展开标签');
		}
		
		return false;
	}
);
