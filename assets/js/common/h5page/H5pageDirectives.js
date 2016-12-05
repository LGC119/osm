'use strict';
define(['me'], function (me) {
	// 模板切换的包围DOM指令，作用是为模板切换按钮部分提供一个公用的空间存放所有切换按钮的scope
	me.directive('tplswitcherswrap', function () {
		return {
			restrict: 'EA',
			replace: true,
			transclude: true,
			template: '<div ng-transclude></div>',
			controller: function () {
				var tplSwitchers = [];
				this.gotChosen = function (chosen) {
					angular.forEach(tplSwitchers, function (tplSwitcher) {
						if (chosen != tplSwitcher) {
							tplSwitcher.showMe = true;
						}
					});
				};
				this.addTplSwitcher = function (tplSwitcher) {
					tplSwitchers.push(tplSwitcher);
					if (tplSwitchers.length === 1) {
						tplSwitcher.showMe = false;
					}
				}
			}
		};
	});

	// 模板选择按钮的指令
	me.directive('tplswitchers', function () {
		return {
			restrict: 'EA',
			require: '^?tplswitcherswrap',	// ^表示同时在父级节点中寻找指定的 controller，?表示如果指定的 controller 不存在，则忽略错误
			link: function (scope, element, attrs, tplswitcherswrapController) {
				scope.showMe = true;
				tplswitcherswrapController.addTplSwitcher(scope);
				scope.switchTpl = function (tplId) {
					scope.showMe = false;
					tplswitcherswrapController.gotChosen(scope);
					scope.iframe.tpl = tplId;
					// 为何此处不用$apply?如果直接写jquery的on绑定的话，与ng-click不同，不属于angular作用域内。
				}
			}
		};
	});
});
