<?php
// start engine to know permissions and status
$engine = new lcso_engine('js', array(), '', $GLOBALS['lcso_basedir'], $GLOBALS['lcso_baseurl']);


?>
<div class="wrap">  
    <h2>LC Scripts Optimizer - <?php  _e('Settings', 'lcso_ml') ?></h2> 
    
    <div id="lcso_donate">
    	<?php _e('Enjoying the plugin and want to show some appreciation?', 'lcso_ml') ?><br/>
        <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K2YXYQDYRVGWL" target="_blank"><?php _e('Offer me a coffee or two!', 'lcso_ml') ?></a>
    </div> 

	<?php
	$prefix = 'lcso_';
	$metas = array(
		'enable',
		
		'not_for_css',
		'not_for_js',
		'not_for_html',
		
		'only_filter_css',
		'only_filter_js',
			
		'no_css_import', 
		'no_css_min', 
		
		'js_ignore',
		'js_filter',
		
		'css_ignore',
		'css_filter',
		'inl_js_ignore',
        
        'ignored_js',
        'ignored_css',
	);
	
	
	// HANDLE DATA
	if(isset($_POST['lcso_settings_submit'])) { 
		if (!isset($_POST['lcso_noncename']) || !wp_verify_nonce($_POST['lcso_noncename'], 'lcso')) {die('<p>Cheating?</p>');};
	
		foreach($metas as $meta) {
			$meta = $prefix.$meta;
			
			$val = (isset($_POST[$meta])) ? $_POST[$meta] : false;
			if(is_string($val)) {$val = stripslashes(trim($val));}
			
			if(empty($val)) {
				delete_option($meta);	
			} else {
				update_option($meta, $val, false); 
			}
		}
		
		echo '<div class="updated"><p><strong>'. __('Settings saved', 'lcso_ml') .'!</strong></p></div>';
	}
	
	
	// fields data retrieving
	$fdata = array();
    
    // cache vals with a single DB call (WP 6.4>)
    if(function_exists('get_options')) {
        get_options(array_merge($metas, array('lcso_queued_scripts', 'lcso_queued_styles')));
    } 
        
	foreach($metas as $meta) {
		$meta = $prefix.$meta;
		$fdata[$meta] = (isset($_POST[$meta])) ? $_POST[$meta] : get_option($meta, '');
	}
	?>


  	<form name="lcso_global" method="post" class="form-wrap lcso_form" id="lcso_settings_form" action="<?php echo esc_attr(str_replace( '%7E', '~', $_SERVER['REQUEST_URI'])); ?>" style="margin-top: 12px;">
   
        <div>
        	<table class="widefat lcso_table">
              <thead><tr><td colspan="3"><?php _e('Initialization', 'lcso_ml') ?></td></tr></thead>
              <tbody>
              	<tr>
                   <td class="lcso_label_td"><?php _e("Enable optimization?", 'lcso_ml'); ?></td>
                   <td class="lcso_field_td">
                      <select name="lcso_enable" autocomplete="off">
                      	<option value=""><?php _e("No", 'lcso_ml'); ?></option>
                        <option value="dev_mode" <?php selected($fdata['lcso_enable'], 'dev_mode') ?>><?php _e("Dev mode (enabled for visitors)", 'lcso_ml'); ?></option>
                        <option value="test_mode" <?php selected($fdata['lcso_enable'], 'test_mode') ?>><?php _e("Testing mode (enabled for WP admins)", 'lcso_ml'); ?></option>
                        <option value="1" <?php selected($fdata['lcso_enable'], 1) ?>><?php _e("Yes", 'lcso_ml'); ?></option>
                      </select>
                   </td>
                   <td><span class="info"><?php _e('Turn on the magic!', 'lcso_ml') ?></span></td>
                 </tr>

                 <tr>
                   <td class="lcso_label_td"><?php _e("System status", 'lcso_ml'); ?></td>
                   <td colspan="2">
						<?php
						if($engine->can_write() && $engine->cache_folder_check()) {
							echo '
								<p>
									<strong style="color: #3e791a;">'. __("Everything's ok!", 'lcso_ml') .'</strong>
									<input type="button" class="button-secondary lcso_clean_cache" value="'. __('Clean cache', 'lcso_ml') .'" style="left: 31px; position: relative; top: -4px;" />
								</p> 
								<p>Static files are correctly saved in this folder: <em>'. $engine->basedir .'/'. $engine->cache_folder .'</em></p>
							';	
						}
						else {
							echo '
								<p><strong style="color: #dd3d36;">'. __("Permission problems!", 'lcso_ml') .'</strong></p>
								<p>Your server has creation restritions and plugin can\'t create this folder: <em>'. $engine->basedir .'/'. $engine->cache_folder .'</em><br/>
								Site\'s loading will be improved, but on high traffic sites this might use many server resources</p>
							';
						}
						
						?>
                   </td>
                 </tr>
              </tbody>
            </table>


            <table class="widefat lcso_table">
              <thead><tr><td colspan="3"><?php _e('Scripts Management', 'lcso_ml') ?></td></tr></thead>
              <tbody>
                <tr>
                 <td class="lcso_label_td"><?php _e("Avoid processing Javascript?", 'lcso_ml'); ?></td>
                 <td class="lcso_field_td">
                    <?php $checked = ($fdata['lcso_not_for_js']) ? 'checked="checked"' : ''; ?>
                    <input type="checkbox" name="lcso_not_for_js" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                 </td>
                 <td><span class="info"><?php _e("If checked, won't manage Javascript files", 'lcso_ml') ?></span></td>
               </tr>
               <tr>
                 <td class="lcso_label_td"><?php _e("Avoid processing CSS?", 'lcso_ml'); ?></td>
                 <td class="lcso_field_td">
                    <?php $checked = ($fdata['lcso_not_for_css']) ? 'checked="checked"' : ''; ?>
                    <input type="checkbox" name="lcso_not_for_css" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                 </td>
                 <td><span class="info"><?php _e("If checked, won't manage CSS files", 'lcso_ml') ?></span></td>
				</tr>
                <tr>
                 <td class="lcso_label_td"><?php _e("Avoid processing HTML?", 'lcso_ml'); ?></td>
                 <td class="lcso_field_td">
                    <?php $checked = ($fdata['lcso_not_for_html']) ? 'checked="checked"' : ''; ?>
                    <input type="checkbox" name="lcso_not_for_html" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                 </td>
                 <td><span class="info"><?php _e("If checked, won't minify HTML code", 'lcso_ml') ?></span></td>
				</tr>
                
                <tr><td colspan="3"></td></tr>
                
                <tr>
                   <td class="lcso_label_td"><?php _e("Do NOT concat CSS?", 'lcso_ml'); ?></td>
                   <td class="lcso_field_td">
                      <?php $checked = ($fdata['lcso_only_filter_css']) ? 'checked="checked"' : ''; ?>
                      <input type="checkbox" name="lcso_only_filter_css" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                   </td>
                   <td><span class="info"><?php _e('Check to just use selective CSS inclusion and disable concatenation', 'lcso_ml') ?></span></td>
                </tr>
                <tr>
                   <td class="lcso_label_td"><?php _e("Do NOT concat Javascript?", 'lcso_ml'); ?></td>
                   <td class="lcso_field_td">
                      <?php $checked = ($fdata['lcso_only_filter_js']) ? 'checked="checked"' : ''; ?>
                      <input type="checkbox" name="lcso_only_filter_js" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                   </td>
                   <td><span class="info"><?php _e('Check to just use selective javascript inclusion and disable concatenation', 'lcso_ml') ?></span></td>
                </tr>
                
                <tr><td colspan="3"></td></tr>

              	<tr>
                   <td class="lcso_label_td"><?php _e("Avoid processing @import in CSS files?", 'lcso_ml'); ?></td>
                   <td class="lcso_field_td">
                      <?php $checked = ($fdata['lcso_no_css_import']) ? 'checked="checked"' : ''; ?>
                      <input type="checkbox" name="lcso_no_css_import" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                   </td>
                   <td><span class="info"><?php _e('Check only noticing CSS issues', 'lcso_ml') ?></span></td>
                 </tr>
                 <tr>
                   <td class="lcso_label_td"><?php _e("Avoid minifying CSS scripts?", 'lcso_ml'); ?></td>
                   <td class="lcso_field_td">
                      <?php $checked = ($fdata['lcso_no_css_min']) ? 'checked="checked"' : ''; ?>
                      <input type="checkbox" name="lcso_no_css_min" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                   </td>
                   <td><span class="info"><?php _e('Check only noticing CSS issues', 'lcso_ml') ?></span></td>
                 </tr>
                 <tr>
                   <td class="lcso_label_td"><?php _e("Avoid minifying inline Javascript?", 'lcso_ml'); ?></td>
                   <td class="lcso_field_td">
                      <?php $checked = ($fdata['lcso_inl_js_ignore']) ? 'checked="checked"' : ''; ?>
                      <input type="checkbox" name="lcso_inl_js_ignore" value="1" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
                   </td>
                   <td><span class="info"><?php _e('Check only noticing Javascript issues', 'lcso_ml') ?></span></td>
                 </tr>
              </tbody>
            </table>


            <div style="float: right; position: relative; bottom: -17px;">
                <strong>NB: </strong> <?php _e("scripts are dynamically fetched also navigating pages", 'lcso_ml') ?>
            </div>
            <h3 style="font-size: 20px; line-height: 26px;">
				<?php _e('Scripts Filter', 'lcso_ml') ?>
                <input type="button" value="<?php _e('Fetch again', 'lcso_ml') ?>" class="button-secondary lcso_fetch_scripts" style="margin-left: 20px;" />
            </h3>
            
            <?php
			$stored_js = get_option('lcso_queued_scripts', array());
			$stored_css = get_option('lcso_queued_styles', array());
                       
			if(empty($stored_css)) :	
				?>
				<p><?php _e('No scripts fetched yet', 'lcso_ml') ?> ..</p>
				
			<?php 
			else : 
				ksort($stored_js);
				ksort($stored_css);
			?>
				<table class="widefat lcso_table lcso_scripts_filter">
				  <thead>
					<tr><td colspan="3">Javascript</td></tr>
					<tr>
						<td style="font-weight: 600; background-color: #f8f8f8; width: 250px;">Script</td>
						<td style="font-weight: 600; background-color: #f8f8f8; width: 190px;" title="<?php _e("Check to exclude element from scripts concatenation", 'lcso_ml') ?>"><?php _e('Do NOT concat?', 'lcso_ml') ?></td>
						<td style="font-weight: 600; background-color: #f8f8f8;">
							<?php _e('Use only in these pages', 'lcso_ml') ?> 
							<small style="padding-left: 5px;">(<?php _e('use URLs - one per row - supports regexp', 'lcso_ml') ?>)</small>
						</td>
					</tr>
				  </thead>
				  <tbody>
					<?php foreach($stored_js as $js => $data) : ?>
					  <?php
					  // SPECIAL CASE - if there's elementor or $ must be left untouched due to their damn coding flow
					  if(is_plugin_active('elementor/elementor.php') && $js == '$' || strpos($js, 'elementor') !== false) {
                          continue;
                      }
					  ?>
                      
                      <tr>
						<td title="<?php echo $data->src ?>"><?php echo $js ?></td>
						<td>
							<?php $checked = (is_array($fdata['lcso_js_ignore']) && in_array($js, $fdata['lcso_js_ignore'])) ? 'checked="checked"' : ''; ?>
							<input type="checkbox" name="lcso_js_ignore[]" value="<?php echo esc_attr($js) ?>" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
						</td>
						<td>
							<?php $val = (isset($fdata['lcso_js_filter'][$js])) ? $fdata['lcso_js_filter'][$js] : ''; ?>
							<textarea name="lcso_js_filter[<?php echo esc_attr($js) ?>]" onkeyup="lcso_textAreaAdjust(this)" autocomplete="off"><?php echo esc_textarea($val) ?></textarea>
						</td>
					 </tr>
					<?php endforeach; ?>
				  </tbody>
				</table>         
				
            
            
				<table class="widefat lcso_table lcso_scripts_filter">
				  <thead>
					<tr><td colspan="3">CSS</td></tr>
					<tr>
						<td style="font-weight: 600; background-color: #f8f8f8; width: 250px;">Script</td>
						<td style="font-weight: 600; background-color: #f8f8f8; width: 190px;"><?php _e('Do NOT optimize?', 'lcso_ml') ?></td>
						<td style="font-weight: 600; background-color: #f8f8f8;">
							<?php _e('Use only in these pages', 'lcso_ml') ?> 
							<small style="padding-left: 5px;">(<?php _e('use URLs - one per row - supports regexp', 'lcso_ml') ?>)</small>
						</td>
					</tr>
				  </thead>
				  <tbody>
					<?php 
                    foreach($stored_css as $css => $data) : 
                      if(strpos($css, 'elementor-post-') !== false) {continue;} // ignore Elementor post CSS
                      ?>
					  <tr>
						<td title="<?php echo $data->src ?>"><?php echo $css ?></td>
						<td>
							<?php $checked = (is_array($fdata['lcso_css_ignore']) && in_array($css, $fdata['lcso_css_ignore'])) ? 'checked="checked"' : ''; ?>
							<input type="checkbox" name="lcso_css_ignore[]" value="<?php echo $css ?>" <?php echo $checked; ?> class="lcso_lc_switch" autocomplete="off" />
						</td>
						<td>
							<?php $val = (isset($fdata['lcso_css_filter'][$css])) ? $fdata['lcso_css_filter'][$css] : ''; ?>
							<textarea name="lcso_css_filter[<?php echo $css ?>]" onkeyup="lcso_textAreaAdjust(this)" autocomplete="off"><?php echo trim(stripslashes($val)) ?></textarea>
						</td>
					 </tr>
					<?php endforeach; ?>
				  </tbody>
				</table>         
			<?php endif; ?>  
            
            
            
            <table class="widefat lcso_table">
                <thead><tr><td colspan="3"><?php _e('Ignored Scripts', 'lcso_ml') ?></td></tr></thead>
                <tbody>
                    <tr>
                        <td class="lcso_label_td"><?php _e("Any javascript file to ignore?<br/><em>(one URL per line, supports regular expressions)</em>", 'lcso_ml'); ?></td>
                        <td class="lcso_field_td" colspan="2">
                            <textarea name="lcso_ignored_js" autocomplete="off" onkeyup="lcso_textAreaAdjust(this)"><?php echo $fdata['lcso_ignored_js'] ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="lcso_label_td"><?php _e("Any CSS file to ignore?<br/><em>(one URL per line, supports regular expressions)</em>", 'lcso_ml'); ?></td>
                        <td class="lcso_field_td" colspan="2">
                            <textarea name="lcso_ignored_css" autocomplete="off" onkeyup="lcso_textAreaAdjust(this)"><?php echo $fdata['lcso_ignored_css'] ?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>    
            
            
            <p style="margin-top: 20px;"><input type="submit" name="lcso_settings_submit" value="<?php _e('Update', 'lcso_ml') ?>" class="button-primary" /></p>  
        </div>   
        <?php	
		// create a custom nonce for submit verification later
		echo '<input type="hidden" name="lcso_noncename" value="' . wp_create_nonce('lcso') . '" />';
		?>
    </form>
    
   
    
    
    <?php #### JAVASCRIPT #### ?>
    
    <script type="text/javascript">
	(function($) { 
        "use strict"; 

        window.lcso_textAreaAdjust = function (o) {
          o.style.height = "1px";
          o.style.height = (4 + o.scrollHeight)+"px";
        };


        $(document).ready(function($) {
            var lcso_is_acting = false;
            $('.lcso_scripts_filter tbody tr td textarea').trigger('keyup');

            // refetch scripts
            $(document).on('click', '.lcso_fetch_scripts', function() {
                if(lcso_is_acting) {
                    return false;
                }

                lcso_is_acting = true;
                var $subj = $(this);
                $subj.css('opacity', 0.5);

                var data = {
                    action: 'lcso_store_queued_scripts',
                    lcso_nonce: '<?php echo wp_create_nonce('lcso_ajax') ?>'
                };
                $.post(ajaxurl, data, function(response) {
                    if($.trim(response) == 'success') {
                        location.reload();
                    } else {
                        alert(response);	
                    }

                    $subj.css('opacity', 1);
                    lcso_is_acting = false;
                });
            });		



            // clean scripts cache
            $(document).on('click', '.lcso_clean_cache', function() {
                if(!lcso_is_acting && confirm("<?php esc_attr_e('Deleting cache files your server will need to recreate them. Continue?', 'lcso_ml') ?>")) {

                    lcso_is_acting = true;
                    var $subj = $(this);
                    $subj.css('opacity', 0.5);

                    var data = {
                        action: 'lcso_empty_scripts_cache',
                        lcso_nonce: '<?php echo wp_create_nonce('lcso_ajax') ?>'
                    };
                    $.post(ajaxurl, data, function(response) {
                        if($.trim(response) == 'success') {
                            $subj.replaceWith('<strong><?php esc_attr_e('Cache successfully cleaned!', 'lcso_ml') ?></strong>');	

                            setTimeout(function() {
                                location.reload();    
                            }, 1000);
                        } 
                        else {
                            alert(response);	
                        }

                        $subj.css('opacity', 1);
                        lcso_is_acting = false;
                    });
                }
            });		


            ////////////////////////////////////////////////////


            // fixed submit position
            const lcso_fixed_submit = function(btn_selector) {
                var $subj = $(btn_selector);
                if(!$subj.length) {return false;}

                var clone = $subj.clone().wrap("<div />").parent().html();

                setInterval(function() {

                    // if page has scrollers or scroll is far from bottom
                    if(($(document).height() > $(window).height()) && ($(document).height() - $(window).height() - $(window).scrollTop()) > 130) {
                        if(!$('.lcwp_settings_fixed_submit').length) {	
                            $subj.after('<div class="lcwp_settings_fixed_submit">'+ clone +'</div>');
                        }
                    }
                    else {
                        if($('.lcwp_settings_fixed_submit').length) {	
                            $('.lcwp_settings_fixed_submit').remove();
                        }
                    }
                }, 50);
            };
            lcso_fixed_submit('input[name=lcso_settings_submit]');


            
            ////////////////////////////////////////////////////

            

            // lc switch
            const lcso_lc_switch = function() { 
                lc_switch('.lcso_lc_switch', {
                    on_txt      : "<?php echo strtoupper(esc_attr__('yes')) ?>",
                    off_txt     : "<?php echo strtoupper(esc_attr__('no')) ?>",   
                });
            };
            lcso_lc_switch();

        });
        
    })(jQuery);     
	</script>
</div>