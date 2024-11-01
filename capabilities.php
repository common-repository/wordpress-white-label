<?php
if(!current_user_can('manage_options')){
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
}

// Cannot find a way to extract all capabilities so will use static for the moment:
$caps = array("manage_network","manage_sites","manage_network_users","manage_network_themes","manage_network_options","activate_plugins","add_users","create_users","delete_plugins","delete_themes","delete_users","edit_files","edit_plugins","edit_theme_options","edit_themes","edit_users","export","import","install_plugins","install_themes","list_users","manage_options","promote_users","remove_users","switch_themes","unfiltered_upload","update_core","update_plugins","update_themes","edit_dashboard","moderate_comments","manage_categories","manage_links","unfiltered_html","edit_others_posts","edit_pages","edit_others_pages","edit_published_pages","publish_pages","delete_pages","delete_others_pages","delete_published_pages","delete_others_posts","delete_private_posts","edit_private_posts","read_private_posts","delete_private_pages","edit_private_pages","read_private_pages","edit_published_posts","upload_files","publish_posts","delete_published_posts","edit_posts","delete_posts","read");

global $wp_roles;
$roles = $wp_roles->roles;
$cols = $has_caps = array();
foreach($roles as $role_slug=>$role_data){
	$cols[$role_slug] = $role_data["name"];
	if(count($role_data["capabilities"])>=1){
		foreach($role_data["capabilities"] as $cap_slug=>$trueval){
			if($trueval==1){
				$has_caps[$role_slug][] = $cap_slug;
			}
		}
	}else{
		$has_caps[$role_slug] = array();
	}
}

if(isset($_POST["crc"])){
	if($_POST["crc"]=="capabilities"){
		if( wp_verify_nonce($_POST[$wordpressWhiteLabel->nonceField],'capabilities')){
			unset($has_caps);
			$has_caps = array();
			foreach($cols as $colID=>$col){
				foreach($caps as $cap){
					if(isset($_POST["caps"][$colID][$cap])){
						$wp_roles->add_cap($colID, $cap);
						$has_caps[$colID][] = $cap;
					}else{
						$wp_roles->remove_cap($colID, $cap);
					}
				}
			}
			print $wordpressWhiteLabel->messageInfo("The capability matrix has been updated");
		}
	}
}



?><div class="wrap">
  <div class="icon32" id="icon-tools"><br></div><h2>Capability Matrix</h2>
    <p>White label WordPress with a plugin instead of hacking the WordPress core.</p>
    <p>For more information on Roles and Capabilities please visit <a href="http://codex.wordpress.org/Roles_and_Capabilities" target="_blank">http://codex.wordpress.org/Roles_and_Capabilities</a>.</p>
    <form method="post" action="">
      <input type="hidden" name="crc" value="capabilities" />
      <?php wp_nonce_field('capabilities', $wordpressWhiteLabel->nonceField); ?>


<table cellspacing="0" class="wp-list-table widefat">
  <thead>
    <tr>
      <th style="" class="manage-column column-name" id="name" scope="col">Capability</th>
      <?php foreach($cols as $col){ ?>
	      <th style="" class="manage-column column-description" id="description" scope="col"><?php print $col ?></th>
      <?php } ?>
    </tr>
  </thead>
  <tfoot>
    <tr>
      <th style="" class="manage-column column-name" id="name" scope="col">Capability</th>
      <?php foreach($cols as $col){ ?>
	      <th style="" class="manage-column column-description" id="description" scope="col"><?php print $col ?></th>
      <?php } ?>
    </tr>
  </tfoot>
  <tbody id="the-list">
<?php foreach($caps as $id=>$cap){ ?>
    <tr class="inactive">
      <td class="plugin-title"><strong><?php print $cap ?></strong></td>
      <?php foreach($cols as $colID=>$col){ ?>
      <td class="column-description desc" style="text-align:center;">
      <input type="checkbox" name="caps[<?php print $colID ?>][<?php print $cap ?>]" id="caps<?php print $id ?>"<?php if(in_array($cap, $has_caps[$colID])){ ?> checked="checked"<?php } ?> /></td>
      <?php } ?>
    </tr>
<?php } ?>
  </tbody>
</table>



    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>

  </form>
</div>
