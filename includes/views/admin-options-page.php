<div class="wrap sfif-page">
	
	<h2><?php _e('Set All First Images As Featured', 'sfif_domain'); ?></h2>
	
	<div id="alert"></div>
	
	<form method="post" name="sfif" action="<?php bloginfo('wpurl') ?>/wp-admin/admin-ajax.php">
				
				<?php settings_fields( 'sfif_settings_group' ); ?>
				
				<p><?php _e('This plugin will search for all the first images of your <b>published</b> posts or pages and set them as the featured image.', 'sfif_domain'); ?></p>
				
				
					<!-- ** Enable buttons ***************** -->
						
					<h3><?php _e('Options', 'sfif_domain'); ?></h3>
					<h4><?php _e('Please make sure to make a backup of your metapost table before running the plugin.', 'sfif_domain'); ?></h4>		
						
						
						<p>
							
							<label class="description" for="post_type"><?php _e( 'Run For:', 'sfif_domain'); ?></label>
							<select id="post_type" name="sfif_settings[post_type]">
								
								
								<?php 	
									
									$post_types = get_post_types( '' ,'object');
									$exclude = array('attachment', 'revision', 'nav_menu_item');
									$type_labels = array();
									
		  							foreach ( $post_types as $post_type ) {
										
										if( in_array($post_type->name, $exclude)) continue;
										if(in_array($post_type->labels->name, $type_labels)) continue;
										 
										array_push($type_labels, $post_type->labels->name); 
										
										echo '<option value="' . $post_type->name . '" ' . selected($post_type->name, $options['run_for']) . '>';
										echo $post_type->labels->name;
										echo '</option>';
			
									}
								 ?>
								
							</select>
								
							</select>
						</p>
						
						<p>
							<input type="checkbox" id="overwrite" name="sfif_settings[overwrite]" value="1" <?php checked(1, $options['overwrite'] ) ?> />
							<label class="description" for="overwrite"><?php _e( 'Overwrite thumbnails', 'sfif_domain'); ?></label><br/>
							<span class="tip"><?php _e('If enabled the first image found in the post will overwrite the already selected featured image.', 'sfif_domain')?></span>
						</p>
						
						<?php wp_nonce_field('update_featured', 'token') ?>
						<input type="hidden" name="action_update" value="update_featured" / >
					
						<p class="submit">
							<input type="submit" onclick="return false;" class="button-primary" value="<?php _e('Start', 'sfif_domain'); ?>" />
						</p>
				
			</form>	
			
			<div id="activity"></div>
	
</div>