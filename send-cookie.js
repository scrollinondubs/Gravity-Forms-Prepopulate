var url_current = window.location.href;
if(url_current.includes("?") == 1){
    jQuery.ajax({
        type: "POST",
        dataType: "text",
        url: '/wp-admin/admin-ajax.php',
        data: {
            url_current : url_current,
            action : 'variable_urlcurrent_save_cookie'
        },
        success: function(data){
            console.log('Send url success');
        }
    });
}