
var device_command_packet_id = 1;
var device_command_trycnt = 0;

function disable_click()
{
    $("#device-send-comand-button").prop("disabled","disabled");
    $("#device-led-checkbox").prop("disabled",true);
    $("#device-alert-checkbox").prop("disabled",true);
}

function enable_click()
{
    $("#device-send-comand-button").prop("disabled","");
    $("#device-led-checkbox").prop("disabled",false);
    $("#device-alert-checkbox").prop("disabled",false);
}

function send_command(url)
{
    $.getJSON(url)
        .done(function (ret) {
            // console.log("done");
            console.log(ret);

            if ( 1 == ret.errno) {
                alert_pop_up("alert-warning","给设备下发命令失败，将自动重复命令");
                device_command_trycnt--;
                if(device_command_trycnt > 0){
                    setTimeout(function(){
                        console.log("resend command");
                        send_command(url);
                    },100);
                }else
                    enable_click();
            }else{// enable
                $("#device-send-command-dialog").find("#sms-reply").val(ret.data);
                enable_click();
            }
        })
        .fail(function (jqxhr, textStatus, error) {
            var err = textStatus + ", " + error;
            console.log("Request Failed: " + err);
        });
}

$(function () {

    $("#device-send-comand-button").click(function () {
        //
        var url = "/admin/device/sendcommand?" + $("#device-send-command-dialog").find("form").serialize();
        console.log("send commad %s",url);

        disable_click();

        device_command_trycnt =2;
        send_command(url);
    });

    //下发快捷命令操作
    $("#device-led-checkbox").click(function () {

        device_command_packet_id++;
        if (this.checked == true) {
            $("#device-send-command-dialog #sms").val("led:1"+" pid-"+device_command_packet_id);

        } else {
            $("#device-send-command-dialog #sms").val("led:0"+" pid-"+device_command_packet_id);
        }
        //模拟触发发送命令
        $("#device-send-comand-button").trigger('click');
    });

    $("#device-alert-checkbox").click(function () {

        device_command_packet_id++;
        if (this.checked == true) {
            $("#device-send-command-dialog #sms").val("sound:1"+" pid-"+device_command_packet_id);

        } else {
            $("#device-send-command-dialog #sms").val("sound:0"+" pid-"+device_command_packet_id);
        }
        //模拟触发发送命令
        $("#device-send-comand-button").trigger('click');
    });

    // return false;

});

function device_send_command(did) {

    $("#device-send-command-dialog").find("input[name='did']").val(did);
    $("#device-send-command-dialog").modal("show");

}

