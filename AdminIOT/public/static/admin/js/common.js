
/* Chinese initialisation for the jQuery UI date picker plugin. */
/* Written by Cloudream (cloudream@gmail.com). */
(function( factory ) {
    if ( typeof define === "function" && define.amd ) {

        // AMD. Register as an anonymous module.
        define([ "../datepicker" ], factory );
    } else {

        // Browser globals
        factory( jQuery.datepicker );
    }
}(function( datepicker ) {

    datepicker.regional['zh-CN'] = {
        closeText: '关闭',
        prevText: '&#x3C;上月',
        nextText: '下月&#x3E;',
        currentText: '今天',
        monthNames: ['一月','二月','三月','四月','五月','六月',
            '七月','八月','九月','十月','十一月','十二月'],
        monthNamesShort: ['一月','二月','三月','四月','五月','六月',
            '七月','八月','九月','十月','十一月','十二月'],
        dayNames: ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'],
        dayNamesShort: ['周日','周一','周二','周三','周四','周五','周六'],
        dayNamesMin: ['日','一','二','三','四','五','六'],
        weekHeader: '周',
        dateFormat: 'yy-mm-dd',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: true,
        yearSuffix: '年'};
    datepicker.setDefaults(datepicker.regional['zh-CN']);

    return datepicker.regional['zh-CN'];

}));


// Validation errors messages for Parsley
// Load this after Parsley
Parsley.addMessages('zh-cn', {
    defaultMessage: "不正确的值",
    type: {
        email:        "请输入一个有效的电子邮箱地址",
        url:          "请输入一个有效的链接",
        number:       "请输入正确的数字",
        integer:      "请输入正确的整数",
        digits:       "请输入正确的号码",
        alphanum:     "请输入字母或数字"
    },
    notblank:       "请输入值",
    required:       "必填项",
    pattern:        "格式不正确",
    min:            "输入值请大于或等于 %s",
    max:            "输入值请小于或等于 %s",
    range:          "输入值应该在 %s 到 %s 之间",
    minlength:      "请输入至少 %s 个字符",
    maxlength:      "请输入至多 %s 个字符",
    length:         "字符长度应该在 %s 到 %s 之间",
    mincheck:       "请至少选择 %s 个选项",
    maxcheck:       "请选择不超过 %s 个选项",
    check:          "请选择 %s 到 %s 个选项",
    equalto:        "输入值不同"
});

Parsley.setLocale('zh-cn');

function alert_pop_up(alert_class,msg) {
    var alert_common = $("#alert-common");
    alert_common.addClass(alert_class);
    alert_common.html(msg);
    alert_common.fadeIn(500);
    alert_common.fadeOut(2500);
}

function clear_form(){
    var url_all =  window.location.href;
    var arr = url_all.split('?');
    var url = arr[0];
    location.replace(url);
}

//for all the page .分页处理相关
function change_page_rows(obj) {

    $.cookie.raw = true;
    $.cookie('page_list_rows', obj.value, { expires: 30, path: '/'});
    // console.log("page row list change"+ obj.value);
    window.location.reload();
}
