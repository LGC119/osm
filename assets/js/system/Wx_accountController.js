'use strict';

define(['me'], function (me) {
	me.controller('Wx_accountController', ['$scope', '$http','$modal', 'Account', function ($scope, $http,$modal, Account){
		$scope.wx_empty = '载入中...';
		$scope.wx_accounts = [];
        $scope.wx_account = '';
        $scope.wx_token = '';
        $scope.wx_url = '';
        $scope.account_id = 0;
        $scope.imgData = {};
        $scope.imgData.imgname = '';
        $scope.imgData.imgsrc = '';

            // 获取所有绑定账号
        function getAllAccounts(){
			// 获取所有账号
			$scope.accounts = Account.query(function(res){
				if (res.code == 200) {
					if (res.data.wx_accounts.length > 0)
						$scope.wx_accounts = res.data.wx_accounts;
					else
						$scope.wx_empty = '没有绑定微信账号';
				} else {
					$scope.wx_empty = res.message;
				}
			});
        }
        getAllAccounts();

		$scope.initModal = function ()
		{
			$('#add_new1').modal({
                show:true,
                backdrop:"static"
            });
		}


        // 微信绑定第一步
        $scope.wx_bind = function(){
            $scope.wx_url = '';
            var weixin = {};
            if($scope.wx_account.length <=0 || $scope.wx_token.length <=0){
                $.gritter.add({
                    title: '帐号与token为填项！不可为空！',
                    time:'1000',
                    class_name:'gritter-error'
                });
                return;
            }
            weixin.wx_account = $scope.wx_account;
            weixin.wx_token = $scope.wx_token;

            $http.post(
                _c.appPath+'system/account/wx_bind',
                {
                        weixin:weixin
                }
            ).success(function(data){
                if(data.code == 200){
                    $scope.wx_url = data.data.url;
                    $scope.account_id = data.data.id;
                    $('#add_new2').modal({
                        show:true,
                        backdrop:"static"
                    });
                }else{
                    $.gritter.add({
                        title: '绑定失败!',
                        time:'1000',
                        class_name:'gritter-error'
                    });
                }
                    $('#add_new1').modal("hide");
            }).error(function(){

                });
        }

        // 微信绑定第二步
        $scope.wx_bind2 = function(){
            var weixin2 = {};
            weixin2.appid = $scope.wx_appid;
            weixin2.appsecret = $scope.wx_appsecret;
            weixin2.id = $scope.account_id;
            $http.post(
                _c.appPath+'system/account/wx_bind2',
                {
                    weixin2:weixin2
                }
            ).success(function(data){
                    if(data.code == 200){
                        // 绑定成功，则向后台请求添加数据
                        $.gritter.add({
                            title: '绑定成功!',
                            time:'500',
                            class_name:'gritter-success'
                        });
                        $http.post(
                            _c.appPath+'mex/user/insert_user_all',
                            {
                            }
                        ).success(function(data){
                        }).error(function(){
                        });
                        $http.post(
                            _c.appPath+'mex/send/insert_send_num',
                            {
                            }
                        ).success(function(data){
                        }).error(function(){
                        });
                    }else{
                        $.gritter.add({
                            title: '绑定失败!',
                            time:'1000',
                            class_name:'gritter-error'
                        });
                    }
                    getAllAccounts();
                    $('#add_new2').modal("hide");
            }).error(function(){

            });
        }

        // 验证微信
        $scope.wx_check = function(id){
            $scope.account_id = id;
            $http.post(
                _c.appPath+'system/account/get_account_find',
                {
                    id:id
                }
            ).success(function(data){
//                    console.log(data);
                    if(data.code == 200){
                        $scope.wx_url = data.data.url;
                        $scope.wx_appid = data.data.appid;
                        $scope.wx_appsecret = data.data.secret;
                    }else{
                        $scope.wx_appid = '';
                        $scope.wx_appsecret = '';
                    }
                }).error(function(){

                });
            $("#add_new2").modal("show");

        }

        // 解除绑定
        $scope.wx_unbind = function(id){
            var modalInstance = $modal.open({
                templateUrl: 'delete-modal',
                controller: delModalInstanceCtrl,
                size: 'sm',
                resolve: {
                    id: function () {
                        return id;
                    }
                }
            });
        }
        var delModalInstanceCtrl = ['$scope', '$modalInstance', 'id', function ($scope, $modalInstance,id) {
            $scope.ok = function () {
                $modalInstance.close();
                $http.post(
                    _c.appPath+'system/account/wx_unbind',
                    {
                        id:id
                    }
                ).success(function(data){
//                    console.log(data);
//                    return;
                        if(data.code == 200){
                            $.gritter.add({
                                title: '解绑成功!',
                                time:'500',
                                class_name:'gritter-success'
                            });
                        }else{
                            $.gritter.add({
                                title: '解绑失败!',
                                time:'1000',
                                class_name:'gritter-error'
                            });
                        }
                        getAllAccounts();
                    }).error(function(){

                    });
            };
            $scope.cancel = function () {
                $modalInstance.close();
//                $modalInstance.dismiss('cancel');
            };
        }];

        // 修改
        $scope.wx_edit = function(id,nickname,picurl){
            $scope.wx_aid = id;
            $scope.wx_nickname = nickname;
            if(!picurl){
                picurl = 'default.jpg';
            }
            $scope.imgData.imgsrc = 'uploads/images/'+picurl;
            $scope.imgData.imgname = picurl;
            $('#edit_new').one('shown.bs.modal', function (e) {
                $("#uploadImage").uploadify({
                    'width'         : 110,
                    'height'        : 30,
                    'queueID'       : 'some_file_queue',
                    'buttonClass'   : 'btn',
                    'swf'           : 'assets/lib/uploadify/uploadify.swf',
                    'uploader'      : _c.appPath+'mex/media/upload_image_local',
                    'auto'          : true,//关闭自动上传
                    'removeTimeout' : 0,//文件队列上传完成1秒后删除
                    'removeCompleted':true,
                    'method'   : 'post',//方法，服务端可以用$_POST数组获取数据
                    'buttonText' : '上传图片',//设置按钮文本
                    'multi'    : false,//允许同时上传多张图片
                    'uploadLimit' : 20,//一次最多只允许上传张图片
                    'fileTypeDesc' : 'Image Files',//只允许上传图像
                    'fileTypeExts' : '*.gif; *.jpg; *.png;*.jpeg;',//限制允许上传的图片后缀
                    'fileSizeLimit' : 800,//限制上传的图片不得超过800KB
                    'formData':{
                    },
                    'onUploadSuccess' : function(file, data, response){ //每次成功上传后执行的回调函数，从服务端返回数据到前端
//                   console.log(data)
                        var data = jQuery.parseJSON(data);
                        if(data.code == '200'){
                            $.gritter.add({
                                title: '上传图片成功!',
                                time:'500',
                                class_name:'gritter-success'
                            });
                            $scope.$apply(function () {
                                $scope.imgData.imgsrc = 'uploads/images/'+data['data']['filename'];
                                $scope.imgData.imgname = data['data']['filename'];
                            });
                        }else{
                            $.gritter.add({
                                title: '上传图片失败!',
                                time:'1000',
                                class_name:'gritter-error'
                            });
                        }
                    }
                });
            })
            $("#edit_new").modal("show");
        }

        $scope.edit_ok = function(){
            $scope.editAccountData = {};
            $scope.editAccountData.wx_nickname = $scope.wx_nickname;
            $scope.editAccountData.imgname = $scope.imgData.imgname;
            $scope.editAccountData.wx_aid = $scope.wx_aid;
            $http.post(
                _c.appPath+'system/account/update_account',
                {
                    data:$scope.editAccountData
                }
            ).success(function(data){
                    if(data.code == 200){
                        $.gritter.add({
                            title: '修改成功!',
                            time:'500',
                            class_name:'gritter-success'
                        });
                    }else{
                        $.gritter.add({
                            title: '修改失败!',
                            time:'1000',
                            class_name:'gritter-error'
                        });
                    }
                    $("#edit_new").modal("hide");
                }).error(function(){

                });
            getAllAccounts();
        }

	}]);
});

/* 绑定成微博成功返回 */
function success()
{
	alert('绑定成功！');
	window.location.reload(); /* 刷新绑定数据，刷新页面 */
}

/* 绑定微博失败 */
function error()
{
	alert('绑定失败！');
}















