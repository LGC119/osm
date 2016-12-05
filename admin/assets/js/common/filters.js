'use strict';

define(['me'], function (me) {
    me.filter('checkmark', function () {
        return function (input) {
            return input ? '\u2713' : '\u2718';
        };
    })

    // 计算还差几天到期
    .filter('expiresIn', function () {
        return function (input) {
            if(parseInt(input) == 2133273600000){
                return '永久';
            }
            var input = parseInt(input) + 3600 * 24 * 1000 - 1 ;
//            console.log(input)
            var dtTimestamp = Date.parse(new Date());
            var remainingDays = Math.ceil((input - dtTimestamp) / 1000 / 3600 / 24);
            if (remainingDays > 0) {
                return remainingDays + '天';
            } else {
                return '已过期';
            }
        };
    })

    // 素材库类型
    .filter('mediaType', function () {
        return function (input) {
            var output;
            switch (input) {
                case 'news':
                    output = '图文';
                    break;
                case 'text':
                    output = '文字';
                    break;
                case 'image':
                    output = '图片';
                    break;
                case 'voice':
                    output = '语音';
                    break;
                default:
                    output = '未知';
                    break;
            }
            return output;
        }
    })

    // 群发发送状态
    .filter('groupSendStatus', function () {
        return function (input) {
            var output;
            switch (input) {
                case '0':
                    output = '待发送';
                    break;
                case '1':
                    output = '已发送';
                    break;
                default:
                    output = '未知';
            }
            return output;
        }
    })
});

