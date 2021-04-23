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

// This will update the hidden inputs' values if page was served from cache and unable to access via PHP $_COOKIE
(function ($) {

    $(function() {
        const wrappers = document.querySelectorAll('[data-gravity-populate]');
        wrappers.forEach(wrapper => {
            const fieldName = wrapper.dataset.gravityPopulate;
            const input = wrapper.querySelector('input');
            const cookieValue = getCookie(`STYXKEY_${fieldName}`);
            // If input exists, currently has no value, and cookie with value is set - then update the input's value;
            if (input && !input.value && null !== cookieValue) {
                input.value = cookieValue;
            }
        });
    });

    function getCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for(var i = 0; i <ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') {
            c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
          }
        }
        return null;
    }

})(jQuery)