'use strict';

define(['me'], function (me) {
	me.controller('WeiboCrontabController', ['$scope', '$sce', '$http', 'Weibo', 'Tag', '$modal', function ($scope, $sce, $http, Weibo, Tag, $modal) {

        // 定时编辑对象
        $scope.edit = {};

		/*获取定时微博*/
        $scope.crontabList = {
            data: {}
        };
        $scope.list = {
            empty: '载入中...'
        }
        $scope.filterData = {};
        $scope.is_sent = [{
            id:-1,
            is_sent:'全部定时微博'
        },{
            id:0,
            is_sent:'定时成功待发布'
        },{
            id:1,
            is_sent:'定时成功已发布'
        },{
            id:2,
            is_sent:'定时失败'
        }];
        
        $scope.singleModel = 1;
        $scope.filterData.is_sent = -1;
        $scope.button={
            button1:true,
            button2:false
        }

        $scope.getCrontabList = function () {
            $scope.filterData.start = '';
            if (typeof $scope.start_date == 'object' && $scope.start_date != null) 
                $scope.filterData.start = format_date ($scope.start_date);

            $scope.filterData.end = '';
            if (typeof $scope.end_date == 'object' && $scope.end_date != null) 
                $scope.filterData.end = format_date ($scope.end_date);


            $scope.filterData.keyword = '';
            if ($scope.keyword != undefined && $scope.keyword.trim() != '') 
                $scope.filterData.keyword = $scope.keyword.trim();

            $scope.filterData.current_page = $scope.crontabList.current_page || 1;
            $scope.filterData.items_per_page = $scope.crontabList.items_per_page || 10;
            $scope.filterData.singleModel = $scope.singleModel;
            $scope.getPending = true;
            $http.post(
                _c.appPath + 'meo/weibo_crontab/get_crontab_list', 
                $scope.filterData
            ).success(function (res) {
                if (res.code == 200) {
                    for (var i in res.data.crontabs) {
                        var wb = res.data.crontabs[i];
                        if (wb.pic_path != undefined && wb.pic_path != '') 
                            wb.pic_path = wb.pic_path.substr(3);
                        if (wb.wb_info != undefined) {
                            wb.wb_info.text = $sce.trustAsHtml(wb.wb_info.text);
                            wb.wb_info.source = $sce.trustAsHtml(wb.wb_info.source);
                        }
                    }
                    if (res.data.crontabs.length == 0) {
                        $scope.crontabList = {};
                        $scope.list.empty = '暂无定时微博记录';
                    }
                    $scope.crontabList = res.data;
                }else {
                    $scope.crontabList = {};
                    $scope.list.empty = '暂无定时微博记录';
                }
                $scope.getPending = false;
            }).error(function (res) {
                $scope.list.empty = '无法获取数据！';
                $scope.getPending = false;
            });
        }
        $scope.getCrontabList();

        /* 格式化时间字符串 */
        var format_date = function (o) 
        {
            if (typeof o != 'object' || o == null)
                return false;
            var y = o.getFullYear();
            var m = ((o.getMonth() + 1) < 10) ? '0' + (o.getMonth() + 1) : (o.getMonth() + 1);
            var d = (o.getDate() < 10) ? '0' + o.getDate() : o.getDate();
            return y + '-' + m + '-' + d;
        }

        /* 时间选择控件 */
        $scope.today = function() {
            $scope.dt = new Date();
        };
        $scope.today();

        $scope.clear = function () {
            $scope.dt = null;
        };

        // Disable weekend selection
        $scope.disabled = function(date, mode) {
            return false;
        };

        $scope.toggleMin = function() {
            $scope.minDate = $scope.minDate ? null : new Date();
        };
        $scope.toggleMin();

        $scope.openDatepicker = function($event, target) {
            $event.preventDefault();
            $event.stopPropagation();

            $scope[target] = true;
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.initDate = new Date('2016-15-20');
        /* 时间选择控件 */
        // 立即发送

        // 编辑定时
        $scope.editCrontab = function (item) {
            var modalInstance = $modal.open({
                templateUrl: 'edit-crontab-modal',
                controller: editModalInstanceCtrl,
                size: 'lg',
                resolve: {
                    item: function () {
                        return item;
                    },
                    edit: function () {
                        return $scope.edit;
                    },
                    getCrontabList: function () {
                        return $scope.getCrontabList;
                    }
                }
            });
        }

        //选择图片方式
        $scope.addType = '';
        $scope.toggleType = function () {
            // 隐藏所有添加图片的方式
            for (var i in $scope.imgUp) {
                $scope.imgUp[i] = false;
            }
            $scope.imgUp[$scope.addType] = true;
        }

        var editModalInstanceCtrl = ['$scope', '$modalInstance', 'item', 'edit', 'getCrontabList', function ($scope, $modalInstance, item, edit, getCrontabList) {
            $scope.edit = edit;
            // 设置编辑项的数据
            $scope.edit.dt = new Date(item.send_at * 1000);
            $scope.edit.text = item.text;
            $scope.edit.sid = item.sid;
            $scope.edit.pic_path = item.pic_path;
            $scope.minDate = new Date();

            // 设置时间调整步进
            $scope.hstep = 1;
            $scope.mstep = 5;
            // 是否为12小时制
            $scope.ismeridian = false;

            $scope.ok = function () {
                var dt = $scope.edit.dt;
                var send_at = dt.getFullYear() + '-' 
                    + (dt.getMonth() + 1) + '-' 
                    + dt.getDate() + ' ' 
                    + dt.getHours() + ':'
                    + dt.getMinutes() + ':'
                    + dt.getSeconds(); 
                $http.post(
                    _c.appPath + 'meo/weibo_crontab/edit_crontab', {
                        id: item.id,
                        text: $scope.edit.text,
                        pic_path: $scope.edit.pic_path,
                        send_at: send_at
                    }
                ).success(function (data) {
                    if (data.code == 200) {
                        $.gritter.add({ 
                            title : '成功', 
                            text : '修改成功', 
                            time : 2000, 
                            class_name : 'gritter-success gritter-center'
                        });
                        getCrontabList();
                        $modalInstance.close();
                    } else {
                        $.gritter.add({ 
                            title : '错误', 
                            text : data.message, 
                            time : 2000, 
                            class_name : 'gritter-warning gritter-center'
                        });
                    }
                }).error(function (data) {
                    $.gritter.add({ 
                        title : '错误', 
                        text : '修改失败', 
                        time : 2000, 
                        class_name : 'gritter-danger gritter-center'
                    });
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };

            $scope.wordsRemain = 140;

            /* 获取微博字符长度 */
            var getLength = (function() {
                var byteLength = function(b) {
                    if (typeof b == "undefined") 
                        return 0
                    var a = b.match(/[^\x00-\x80]/g);
                    return (b.length + (!a ? 0 : a.length))
                };

                return function(q, g) {
                    g = g || {};
                    g.max = g.max || 140;
                    g.min = g.min || 41;
                    g.surl = g.surl || 20;
                    var p = $.trim(q).length;
                    if (p > 0) {
                        var j = g.min,
                        s = g.max,
                        b = g.surl,
                        n = q;
                        var r = q.match(/(http|https):\/\/[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)+([-A-Z0-9a-z\$\.\+\!\_\*\(\)\/\,\:;@&=\?~#%]*)*/gi) || [];
                        var h = 0;
                        for (var m = 0, p = r.length; m < p; m++) {
                            var o = byteLength(r[m]);
                            if (/^(http:\/\/t.cn)/.test(r[m])) {
                                continue ;
                            } else {
                                if (/^(http:\/\/)+(weibo.com|weibo.cn)/.test(r[m])) {
                                    h += o <= j ? o: (o <= s ? b: (o - s + b))
                                } else {
                                    h += o <= s ? b: (o - s + b)
                                }
                            }
                            n = n.replace(r[m], "")
                        }
                        return Math.ceil((h + byteLength(n)) / 2)
                    }
                    else 
                        return 0
                }
            })();

            /* 更新微博内容剩余字数长度 */
            $scope.updateLength = function () 
            {
                var length = getLength($scope.edit.text);
                $scope.wordsRemain = 140 - length;
            }
            $scope.updateLength();

        }];



        // 删除定时
        $scope.deleteCrontab = function (id) {
            var modalInstance = $modal.open({
                templateUrl: 'delete-crontab-modal',
                controller: delModalInstanceCtrl,
                size: 'sm',
                resolve: {
                    id: function () {
                        return id;
                    },
                    getCrontabList: function () {
                        return $scope.getCrontabList;
                    }
                }
            });
        }
        var delModalInstanceCtrl = ['$scope', '$modalInstance', 'id', 'getCrontabList', function ($scope, $modalInstance, id, getCrontabList) {
            $scope.ok = function () {
                // 删除
                $http.post(_c.appPath + 'meo/weibo_crontab/delete_crontab', {
                    'id': id
                }).success(function (res) {
                    if (res.code == 200) {
                        $.gritter.add({ 
                            title : '成功', 
                            text : '删除定时微博成功', 
                            time : 2000, 
                            class_name : 'gritter-success gritter-center'
                        });
                        getCrontabList();
                        $modalInstance.close();
                    } else {
                        $.gritter.add({ 
                            title : '错误', 
                            text : data.message, 
                            time : 2000, 
                            class_name : 'gritter-warning gritter-center'
                        });
                    }
                }).error(function (res) {
                    $.gritter.add({ 
                        title : '错误', 
                        text : '删除失败', 
                        time : 2000, 
                        class_name : 'gritter-danger gritter-center'
                    });
                });
            };
            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        }];

        $scope.listByRegular = function(){
            $scope.singleModel = 0;
            $scope.getCrontabList();
        }
        $scope.listByGoofy = function(){
            $scope.singleModel = 1;
            $scope.getCrontabList();
        }
	}]);
});