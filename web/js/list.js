function docancel(safe_id) {

    if(confirm('你确定要取消该申请么？')){
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {},
            url: "/site/cancel?id="+safe_id,
            success: function () {
                window.location.reload();
            }
        });
    }
}


