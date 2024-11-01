<?php
if(!current_user_can('manage_options')){
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
}
?><div class="wrap">
  <div class="icon32" id="icon-tools"><br></div><h2><?php print $wordpressWhiteLabel->pluginName ?> version <?php print $wordpressWhiteLabel->version ?></h2>
    <p>White label WordPress with a plugin instead of hacking the WordPress core.</p>
<?php print $wordpressWhiteLabel->messageInfo("Be warned this is a beta and must only be used if you do not mind destroying your WordPress installation.", "error"); ?>
</div>