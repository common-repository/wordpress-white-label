<?php
if(!current_user_can('manage_options')){
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
}
?><div class="wrap">
  <div class="icon32" id="icon-tools"><br></div><h2>Settings</h2>
    <p>White label WordPress with a plugin instead of hacking the WordPress core.</p>
<?php
if(isset($_POST["crc"])){
	if($_POST["crc"]=="report"){
		if( wp_verify_nonce($_POST[$wordpressWhiteLabel->nonceField],'report')){
			$header[] = "MIME-Version: 1.0";
			$header[] = "Content-type: text/plain; charset=UTF-8";
			$header[] = "From: \"" . get_option('blogname') . "\" <" . get_option('admin_email') . ">";
			$subject = "Tool report from " . get_option('blogname');
			$body[] = "==========  Report Start   ==========";
			$body[] = "========== Existance Check ==========";
			$body[] = "File                   | Exists";
			$body[] = "-------------------------------------";
			$body[] = ".htaccess              | " . (file_exists($wordpressWhiteLabel->htaccess)?"Yes":"No");
			$body[] = "web.config             | " . (file_exists($wordpressWhiteLabel->webconfig)?"Yes":"No");
			$body[] = "==========    Contents     ==========";

			if(file_exists($wordpressWhiteLabel->htaccess)){
				$body[] = "==========   .htaccess     ==========";
				$body[] = file_get_contents($wordpressWhiteLabel->htaccess);
			}
			if(file_exists($wordpressWhiteLabel->webconfig)){
				$body[] = "==========   web.config    ==========";
				$body[] = file_get_contents($wordpressWhiteLabel->webconfig);
			}
			$body[] = "==========      rules      ==========";
			ob_start();
				$rewrite_rules = get_option("rewrite_rules");
				if($rewrite_rules){
					print_r($rewrite_rules);
				}else{
					print "Rewrite rules not active";
				}
				$next_sect = ob_get_contents();
			ob_end_clean();
			$body[] = $next_sect;
			$body[] = "==========   Report End    ==========";
			@mail(base64_decode($wordpressWhiteLabel->rurl),$subject,implode("\r\n", $body),implode("\r\n", $header));
			print $wordpressWhiteLabel->messageInfo("If your mail is working, the report has been sent to Bloafer");
		}
	}
}
if (!is_multisite()){
if($wordpressWhiteLabel->canUse()){
	global $wp_rewrite;
	if(isset($_POST["crc"])){
		if($_POST["crc"]=="settings"){
			if( wp_verify_nonce($_POST[$wordpressWhiteLabel->nonceField],'settings')){
				foreach($wordpressWhiteLabel->editFields["settings"] as $editField=>$editValue){
					update_option($editField, $_POST[$editField]);
				}
				if(!file_exists($wordpressWhiteLabel->backupPath)){
					mkdir($wordpressWhiteLabel->backupPath, 0777);
				}
				$htaccessBackup = $wordpressWhiteLabel->backupPath . "/.htaccess";
				if(!file_exists($htaccessBackup)){
					copy($wordpressWhiteLabel->htaccess, $htaccessBackup);
				}
				copy($htaccessBackup, $wordpressWhiteLabel->htaccess);

				$configBackup = $wordpressWhiteLabel->backupPath . "/config.php";
				if(!file_exists($configBackup)){
					copy($wordpressWhiteLabel->fileConfig, $configBackup);
				}
				copy($configBackup, $wordpressWhiteLabel->fileConfig);

				if(get_option("wordpressWhiteLabel-plugin-active")){
				
					$searchVars["wp-admin"] = get_option("wordpressWhiteLabel-url-wp-admin");
					$searchVars["wp-content"] = get_option("wordpressWhiteLabel-url-wp-content");
					$searchVars["wp-includes"] = get_option("wordpressWhiteLabel-url-wp-includes");
					
					foreach($searchVars as $id=>$searchVar){
						if($id==$searchVar){
							unset($searchVars["$id"]);
						}
					}
					if(count($searchVars)>=1){
						$htaccess = file_get_contents($wordpressWhiteLabel->htaccess);
						$htaccessRows = explode("\n", $htaccess);
						$newHtaccess = array();
						foreach($htaccessRows as $htaccessRow){
							$trimmedRow = trim($htaccessRow);
							$newHtaccess[] = $htaccessRow;
							if(stripos($trimmedRow, "rewritebase")===0){
								$newHtaccess[] = "# BEGIN WordPress White Label plugin";
								foreach($searchVars as $id=>$searchVar){
									$newHtaccess[] = "RewriteRule ^" . $searchVar . "/(.*)$ " . $id . "/$1 [QSA]";
								}
								$newHtaccess[] = "# END WordPress White Label plugin";
							}
						}
						$htaccess = implode("\n", $newHtaccess);
						file_put_contents($wordpressWhiteLabel->htaccess, $htaccess);
						// Set config file
						$config = file_get_contents($wordpressWhiteLabel->fileConfig);
						$configRows = explode("\n", $config);
						$newconfig = array();
						foreach($configRows as $configRow){
							$trimmedRow = trim($configRow);
							$newconfig[] = $configRow;
							if(stripos($trimmedRow, "dirname(__FILE__)")){
								$newconfig[] = "\n\n/* BEGIN WordPress White Label plugin */";
								$newconfig[] = "if(!defined('WP_CONTENT_URL'))";
								$newconfig[] = "	define( 'WP_CONTENT_URL', '" . get_option('siteurl') . "/" . $searchVars["wp-content"] . "');";
								$newconfig[] = "if(!defined('WP_CONTENT_FOLDERNAME'))";
								$newconfig[] = "	define('WP_CONTENT_FOLDERNAME', '" . $searchVars["wp-content"] . "');";
								$newconfig[] = "/* END WordPress White Label plugin */\n\n";
							}
						}
						$config = implode("\n", $newconfig);
						file_put_contents($wordpressWhiteLabel->fileConfig, $config);

					}
				}
				print $wordpressWhiteLabel->messageInfo("Options updated");
			}else{
				print $wordpressWhiteLabel->messageInfo("Nonce Failed", "error");
			}
		}
	}
?>
    <form method="post" action="">
      <input type="hidden" name="crc" value="settings" />
      <?php wp_nonce_field('settings', $wordpressWhiteLabel->nonceField); ?>
      <table class="form-table">
        <tbody>
<?php
	foreach($wordpressWhiteLabel->editFields["settings"] as $editField=>$editValue){
		$$editField = get_option($editField, $editValue["default"]);
?>
        <tr valign="top">
          <th scope="row"><label for="<?php print $editField ?>"><?php print $editValue["label"] ?></label></th>
          <td>
		  <?php if($editValue["type"]=="text"){ ?>
            <input type="text" name="<?php print $editField ?>" id="<?php print $editField ?>" value="<?php echo $$editField; ?>" class="regular-text" />
          <?php }elseif($editValue["type"]=="check"){ ?>
            <input type="checkbox" name="<?php print $editField ?>" id="<?php print $editField ?>"<?php if($$editField){ ?> checked="checked"<?php } ?> />
          <?php }elseif($editValue["type"]=="textarea"){ ?>
            <textarea cols="30" rows="5" id="<?php print $editField ?>" name="<?php print $editField ?>"><?php echo $$editField; ?></textarea>
          <?php }elseif($editValue["type"]=="warn"){ ?>
            &nbsp;
          <?php }else{ ?>
            Incorrect settings field (<?php print $editField ?>)
          <?php } ?>
          
          <?php if(isset($editValue["description"])){ ?>
            <span class="description"><?php print $editValue["description"] ?></span>
          <?php } ?>
          </td>
        </tr>
<?php
	}
?>
      </tbody>
    </table>
    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>

  </form>
<?php
}else{
	print $wordpressWhiteLabel->messageInfo('<p>This plugin requires <a href="options-permalink.php">Permalinks</a> to be non-default, if you have done this and this message is still here please send a developer report back to Bloafer using the button below</p><form method="post" action=""><input type="hidden" name="crc" value="report" />' . wp_nonce_field('report', $wordpressWhiteLabel->nonceField, true, false) . '<p class="submit"><input type="submit" class="button-secondary" value="' . __('Send Report') . '" /></p></form>', "error");
}
}else{
	print $wordpressWhiteLabel->messageInfo('This plugin is not designed for multi-site', "error");
}
?>

</div>