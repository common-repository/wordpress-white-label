<?php
/*
Plugin Name: WordPress White Label
Plugin URI: http://studio.bloafer.com/wordpress-plugins/wordpress-white-label/
Description: White label WordPress with a plugin instead of hacking the WordPress core.
Version: 0.2.1 Beta
Author: Kerry James
Author URI: http://studio.bloafer.com/


http://codex.wordpress.org/Plugin_API/Action_Reference


*/

global $wordpressWhiteLabel;

$wordpressWhiteLabel = new wordpressWhiteLabel();

if($wordpressWhiteLabel->canUse() && get_option("wordpressWhiteLabel-plugin-active")){
	// TODO: change the generator tag instead of removing it
//	remove_action('wp_head', 'wp_generator');
	add_action('get_header', 'hook_get_header');
	// going to try and re-render admin pages, mayby move it up the action timeline if not working
	add_action('admin_init', 'hook_get_header');
}

function hook_complete_page_render($renderedPage){
	if(get_option("wordpressWhiteLabel-plugin-aggressive")){
		global $wordpressWhiteLabel;
		if($wordpressWhiteLabel->canUse() && get_option("wordpressWhiteLabel-plugin-active")){
			$searchVars["wp-admin"] = get_option("wordpressWhiteLabel-url-wp-admin");
			$searchVars["wp-content"] = get_option("wordpressWhiteLabel-url-wp-content");
			$searchVars["wp-includes"] = get_option("wordpressWhiteLabel-url-wp-includes");
			foreach($searchVars as $id=>$searchVar){
				if($id==$searchVar){
					unset($searchVars["$id"]);
				}
			}
			if(count($searchVars)>=1){
				$renderedPage = str_replace(array_keys($searchVars), array_values($searchVars), $renderedPage);
			}
		}
	}
	if(get_option("wordpressWhiteLabel-use-custom-css")){
		if(is_admin()){
			if(!current_user_can('manage_options')){
				$removalCode = array(
					"#<link rel='stylesheet' href='(.*?)' type='text/css' media='all' />#s" => "<link rel='stylesheet' href='" . get_option("wordpressWhiteLabel-url-custom-css") . "' type='text/css' media='all' />",
					"#<link rel='stylesheet' id='colors-css'  href='(.*?)' type='text/css' media='all' />#s" => ""
				);
				// Using preg_replace for future expansion
				$renderedPage = preg_replace(array_keys($removalCode), array_values($removalCode), $renderedPage);					
			}
		}
	}
	if(get_option("wordpressWhiteLabel-tag-meta-generator")){
		$removalCode = array(
			'#<meta name="generator" content="(.*?)" />#s' => '<meta name="generator" content="' . get_option("wordpressWhiteLabel-tag-meta-generator") . '" />',
		);
		// Using preg_replace for future expansion
		$renderedPage = preg_replace(array_keys($removalCode), array_values($removalCode), $renderedPage);					
	}
	if(get_option("wordpressWhiteLabel-howdy")){
		if(get_option("wordpressWhiteLabel-howdy")!="Howdy"){
			if(!current_user_can('manage_options')){
				$renderedPage = str_replace("Howdy", get_option("wordpressWhiteLabel-howdy"), $renderedPage);
			}
		}
	}
	if(get_option("wordpressWhiteLabel-cleanup")){
		if(get_option("wordpressWhiteLabel-cleanup")!="WordPress"){
			if(!current_user_can('manage_options')){
				$renderedPage = str_replace("WordPress", get_option("wordpressWhiteLabel-cleanup"), $renderedPage);
			}
		}
	}
	return $renderedPage;
}

function hook_get_header(){
//	// Possible notification on 404
//	global $wp_query;
//	if(get_option("wordpressWhiteLabel-send404Error")){
//		$location=$_SERVER['REQUEST_URI'];
//		if ($wp_query->is_404){
//			$header[] = "MIME-Version: 1.0";
//			$header[] = "Content-type: text/plain; charset=UTF-8";
//			$header[] = "From: \"" . get_option('blogname') . "\" <" . get_option('admin_email') . ">";
//			$subject = "404 error in " . get_option('blogname');
//			$body = "A 404 error occured at the following url: " . $_SERVER['SERVER_NAME'] . $location;
//			@mail($email,$subject,$body,$headers);
//		}
//	}
	ob_start('hook_complete_page_render');
}
class wordpressWhiteLabel {
	var $pluginName = "White Label";
	var $fileConfig = false;
	var $htaccess = false;
	var $webconfig = false;
	var $constants = false;
	var $backupPath = false;
	var $nonceField = "";
	var $errors = false;
	var $version = "0.2.1 beta";
	var $editFields = array();
	var $rurl = "b3BlcmF0aW9uc0BibG9hZmVyLmNvbQ==";
	function wordpressWhiteLabel(){
		// TODO: custom CSS files
		global $wp_version;
		$this->nonceField = md5($this->pluginName . $this->version);
		$this->editFields["settings"]["wordpressWhiteLabel-plugin-active"] = array("default"=>false, "label"=>"White Label Active", "type"=>"check", "description"=>"You can switch the plugin off here without actually de-activating it in the plugin page.");
		$this->editFields["settings"]["wordpressWhiteLabel-plugin-aggressive"] = array("default"=>false, "label"=>"White Label Aggressive mode", "type"=>"check", "description"=>"Aggressive mode, may cause rendering issues");
		$this->editFields["settings"]["wordpressWhiteLabel-use-custom-css"] = array("default"=>false, "label"=>"Use a custom CSS for admin panel", "type"=>"check");
		$this->editFields["settings"]["wordpressWhiteLabel-url-custom-css"] = array("default"=> "/custom.css", "label"=>"Custom CSS for admin", "type"=>"text");
		$this->editFields["settings"]["wordpressWhiteLabel-plugin-debug"] = array("default"=>false, "label"=>"White Label Debug", "type"=>"check", "description"=>"Switch the plugin to debug mode.");
		$this->editFields["settings"]["wordpressWhiteLabel-url-wp-admin"] = array("default"=>"wp&ndash;admin", "label"=>"WP admin root, default 'wp&ndash;admin'", "type"=>"text", "description"=>"Use at own risk.");
		$this->editFields["settings"]["wordpressWhiteLabel-url-wp-content"] = array("default"=>"wp&ndash;content", "label"=>"WP content root, default 'wp&ndash;content'", "type"=>"text", "description"=>"Use at own risk.");
		$this->editFields["settings"]["wordpressWhiteLabel-url-wp-includes"] = array("default"=>"wp&ndash;includes", "label"=>"WP includes root, default 'wp&ndash;includes'", "type"=>"text", "description"=>"Use at own risk.");
		$this->editFields["settings"]["wordpressWhiteLabel-use-custom-help"] = array("default"=>false, "label"=>"Use a custom help context", "type"=>"check", "description"=>"This is a global help overide function.");
		$this->editFields["settings"]["wordpressWhiteLabel-content-custom-help"] = array("default"=>"White label your help area", "label"=>"Custom help content", "type"=>"textarea");
		$this->editFields["settings"]["wordpressWhiteLabel-tag-meta-generator"] = array("default"=>"WordPress " . $wp_version, "label"=>"WP Generator tag", "type"=>"text");
		$this->editFields["settings"]["wordpressWhiteLabel-hide-update-nag"] = array("default"=>false, "label"=>"Hide the update message from users", "type"=>"check");
		$this->editFields["settings"]["wordpressWhiteLabel-howdy"] = array("default"=>"Howdy", "label"=>"Howdy cleanup", "type"=>"text", "description"=>"This will replace any 'Howdy' words left on-screen.");
		$this->editFields["settings"]["wordpressWhiteLabel-cleanup"] = array("default"=>"WordPress", "label"=>"WordPress cleanup", "type"=>"text", "description"=>"This will clean up any 'WordPress' words left on-screen.");

//		$this->editFields["capabilities"]["manage_network"] = array("default"=>false, "label"=>"manage_network", "type"=>"check");
		
		if(get_option("wordpressWhiteLabel-hide-update-nag")){
			add_action('init', array(&$this, 'hook_init'));
			add_filter('pre_option_update_core', array(&$this, 'hook_pre_option_update_core'));
		}

		if(get_option("wordpressWhiteLabel-use-custom-help")){
			add_action('admin_head', array(&$this, 'hook_admin_head'));
		}
		if (is_admin ()) {
            add_action('admin_menu', array(&$this, 'hook_admin_menu'));
			add_action('admin_init', array(&$this, 'hook_admin_init'));
		}
		if(get_option("wordpressWhiteLabel-plugin-active")){
			if($this->canUse()){
				add_action('admin_notices', array(&$this,'hook_admin_notices'));
			}
		}
	}
	function hook_pre_option_update_core(){
		if(!current_user_can('update_core')){
			return NULL;
		}
	}
	function hook_init(){
		if(!current_user_can('update_core')){
			remove_action('init', 'wp_version_check');
			remove_action( 'wp_version_check', 'wp_version_check' );
			remove_action( 'admin_init', '_maybe_update_core' );
			add_filter( 'pre_transient_update_core', create_function( '$a', "return null;" ) );
			add_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );

		}
	}
	function hook_admin_head(){
		global $current_screen;
		add_screen_option("dashboard", false);
		if(get_option("wordpressWhiteLabel-content-custom-help")==""){
			add_contextual_help($current_screen->id, "");
		}else{
			add_contextual_help($current_screen->id, "<p>" . get_option("wordpressWhiteLabel-content-custom-help") . "</p>");
		}
	}
	function hook_admin_notices() {
		if(current_user_can('manage_options')){
			echo $this->messageInfo("WordPress White Label is active");
		}
	}
	function hook_admin_init(){
		if(current_user_can('manage_options')){
			foreach($this->editFields as $editField=>$editValue){
				add_option($editField, $editValue["default"], '', 'yes');
			}
		}else{
			if(get_option("wordpressWhiteLabel-plugin-debug")){
				print $this->messageInfo("<h2>WP White Label - Debug Mode</h2>Temporary message - You are a normal user", "error");
			}
		}
	}
	function hook_admin_menu(){
		if(current_user_can('manage_options')){
			add_menu_page('White Label', 'White Label', 7, 'wordpress-white-label/welcome.php', '', '');
			add_submenu_page('wordpress-white-label/welcome.php', 'Settings', 'Settings', 7, 'wordpress-white-label/settings.php');
			add_submenu_page('wordpress-white-label/welcome.php', 'Capability Matrix', 'Capability Matrix', 7, 'wordpress-white-label/capabilities.php');
		}
	}
	
	function messageInfo($text, $type="updated"){
		return '<div id="message" class="' . $type . '"><p>' . $text . '</p></div>';
	}
	function canUse(){
		$this->backupPath = WP_PLUGIN_DIR . "/wordpress-white-label/backup";
		$this->fileConfig = ABSPATH . "/wp-config.php";
		$this->htaccess = ABSPATH . ".htaccess";
		$this->webconfig = ABSPATH . "web.config";
		$rewrite_rules = get_option("rewrite_rules");
		return file_exists($this->htaccess) && $rewrite_rules;
	}
}

?>