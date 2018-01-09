<?php



/*------------------------------------------------------------------------------



Plugin Name: Gravity forms pre populate add-on



Plugin URI: https://grid7.com



Description: Add-on to Gravity Forms for passing persisted query string variables



Author: Sean Tierney



Version: 0.1



Author URI: https://grid7.com



------------------------------------------------------------------------------*/

if(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != parse_url($_COOKIE['HTTP_REFERER'], PHP_URL_HOST)   ){
    if(isset($_COOKIE['HTTP_REFERER'])){     
        if(parse_url((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_HOST) == parse_url($_COOKIE['HTTP_REFERER'], PHP_URL_HOST)){
            $referer_link  = $_SERVER['HTTP_REFERER'];                     
        }else{
            $referer_link = $_COOKIE['HTTP_REFERER'];  
        }      
    }else{
        $referer_link  = $_SERVER['HTTP_REFERER'];
    }
}else{
    if(empty($_COOKIE['HTTP_REFERER'])){
        $referer_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }else{
        $referer_link = $_SERVER['HTTP_REFERER'];
    }
}

// echo $referer_link;
// print_r($_SERVER);

// get fields names
$gravitypopulate = explode(',', esc_attr(get_option('gravitypopulate_options')));
$gravitypopulate = array_map('trim', $gravitypopulate);


add_action('init', function($arg) use ($gravitypopulate, $referer_link)
{
    if(!is_admin()) save_ref($gravitypopulate, htmlspecialchars($referer_link));
}, 1);

function save_ref($gravitypopulate, $referer_link)
{

	//stores GET varaible in cookies if available
    foreach ($gravitypopulate as $key) {
        
        if (isset($_GET[$key])) {
            
            setcookie($key, htmlspecialchars($_GET[$key], ENT_QUOTES), time() + 99999999, '/', NULL);
            
        }
        
    }

    setcookie('HTTP_REFERER', htmlspecialchars($referer_link, ENT_QUOTES), time() + 99999999, '/', NULL);
	if (isset($_COOKIE['HTTP_REFERER'])) {
		$_POST['input_-2']= htmlspecialchars($referer_link);
	}elseif(isset($referer_link) and $referer_link!=''){
		$_POST['input_-2']=htmlspecialchars($referer_link);
	}


}


foreach ($gravitypopulate as $key) {
    
    add_filter('gform_field_value_' . $key, function($arg) use ($key)
    {

         if (isset($_GET[$key])) {
            
            return htmlspecialchars($_GET[$key], ENT_QUOTES);
        
		}else if (isset($_COOKIE[$key])) {
            
            return htmlspecialchars($_COOKIE[$key], ENT_QUOTES);
            
        } else
            return '';

    }, -999);
    
}





function generate_Populate_admin_page()
{
    
    $msg = '';
    
    if (!empty($_POST) && check_admin_referer('gravitypopulate_options_update', 'gravitypopulate_admin_nonce')) {
        
        update_option('gravitypopulate_options', stripslashes($_POST['inputs']));
		update_option('sakka_actid', stripslashes($_POST['actid']));
        
        $msg = '<div class="updated"><p>Your settings have been<strong>updated</strong></p></div>';
        
    }
    
    
    echo '<div class="wrap">

  <h2>Gravity Populate Configuration</h2>' . $msg . '

  <form action="" method="post" id="inputs">



    <p>Enter inputs Parameter Names separated with , <br/>

      <textarea type="text" id="inputs" name="inputs" style="width:60%;">' . esc_attr(get_option('gravitypopulate_options')) . '</textarea>

    </p>
	<p>Enter <a href="http://www.activecampaign.com/?_r=CFWQFK4C" target="_new">Active Campaign</a> Account ID : 

      <input type="text" id="actid" name="actid" value="'.esc_attr(get_option('sakka_actid')).'" style="width:30%;"/>
    </p>

    <p class="submit">

      <input type="submit" name="submit"

value="Update" />

    </p>

    ' . wp_nonce_field('gravitypopulate_options_update', 'gravitypopulate_admin_nonce') . '

  </form>

</div>';
    
    
    
}



function Gravity_Populate_add_menu_item()
{
    
    add_submenu_page('plugins.php', // Menu page to attach to
        'Gravity Populate Configuration', // page title
        'Gravity Populate', // menu title
        'manage_options', // permissions
        'Gravity-Populate', // page-name (used in the URL)
        'generate_Populate_admin_page' // clicking callback function
        );
    
}

add_action('admin_menu', 'Gravity_Populate_add_menu_item');


function gravity_custom_prepopulate_js()
{
    $js = '<script>jQuery(document).ready(function(){';
	$js .='jQuery("form").append("<input type=\'hidden\' name=\'input_-1\' value=\'"+window.location.href+"\'>");';
    $js .= '});';
    $js .= '</script>';
    echo $js;
    
}

add_filter('wp_head', 'gravity_custom_prepopulate_js');


/* tracking email */

add_filter("gform_save_field_value", "sakka_save_field_value", 10, 4);

function sakka_save_field_value($value, $lead, $field, $form)
{
    if ($field["label"] == 'Email') {
        setcookie('email', htmlspecialchars($value, ENT_QUOTES), time() + 99999999, '/', NULL);
    }
    return $value;
}
add_filter('wp_head', 'sakka_tracking_email');
function sakka_tracking_email()
{
	$sakka_actid = esc_attr(get_option('sakka_actid','0'));
    if (isset($_COOKIE['email']))
        echo '<script type="text/javascript">
	var trackcmp_email = "' . htmlspecialchars($_COOKIE['email']) . '";
	var trackcmp = document.createElement("script");
	trackcmp.async = true;
	trackcmp.type = "text/javascript";
	trackcmp.src = "//trackcmp.net/visit?actid='.$sakka_actid.'&e="+encodeURIComponent(trackcmp_email)+"&r="+encodeURIComponent(document.referrer)+"&u="+encodeURIComponent(window.location.href);
	var trackcmp_s = document.getElementsByTagName("script");
	if (trackcmp_s.length) {
		trackcmp_s[0].parentNode.appendChild(trackcmp);
	} else {
		var trackcmp_h = document.getElementsByTagName("head");
		trackcmp_h.length && trackcmp_h[0].appendChild(trackcmp);
	}
	</script>';
    
}


add_filter('gform_admin_pre_render','sakka_gform_admin_pre_render',1);
function sakka_gform_admin_pre_render($form){
	if($_GET['page']=='gf_edit_forms') return $form;
	array_push($form['fields'],new GF_Field_Hidden(array('label'=>'REQUEST_URI','id'=>-1)));
	array_push($form['fields'],new GF_Field_Hidden(array('label'=>'HTTP_REFERER','id'=>-2)));

	return $form;
}

add_filter('gform_pre_submission_filter','sakka_gform_pre_submission_filter',1);
function sakka_gform_pre_submission_filter($form){
	array_push($form['fields'],new GF_Field_Hidden(array('label'=>'REQUEST_URI','id'=>-1,'size'=>'medium','type'=>'text')));
	array_push($form['fields'],new GF_Field_Hidden(array('label'=>'HTTP_REFERER','id'=>-2,'size'=>'medium','type'=>'text')));
	return $form;
}



/* EOF */