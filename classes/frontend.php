<?php
// FRONTEND ACTUALIZATION

function lcso_init_fn() {
	if(is_admin() || $GLOBALS['pagenow'] === 'wp-login.php') {return false;}
	$plugin_status = get_option('lcso_enable');
	
	
	// if is storing scripts - always execute
	if(!isset($_REQUEST['lcso_store_scripts'])) {
	
		// if special keys are used or optimization is disabled - stop
		if(isset($_REQUEST['lcso_avoid']) || isset($_REQUEST['lcso_store_scripts']) || !$plugin_status) {return false;}
		
		
		// test mode - enabled for WP admins - stop
		if($plugin_status == 'test_mode' && !current_user_can('install_plugins')) {return false;}
		
		// dev mode - enabled for visitors - stop
		if($plugin_status == 'dev_mode' && current_user_can('install_plugins')) {return false;}
		
		
		// if is cornerstone or elementor's editor - skip
		if(isset($_REQUEST['elementor-preview']) || isset($_REQUEST['cornerstone_preview'])) {
			return false;	
		}
	}
	
	
	// let's start!
	$lcso = new lcso_init();
	add_action('template_redirect', 'lc_minify_wp_html_start', 900);
}
add_action('init', 'lcso_init_fn');





class lcso_init {
	private $htt_what;
	
	private $ignore_css 	= false; // (bool) whether to manage CSS or not
	private $ignore_js 		= false; // (bool) whether to manage JS or not
	private $only_filter_css = false; // (bool) whether to concat CSS or not
	private $only_filter_js	= false; // (bool) whether to concat JS or not
    
    private $ignored_script_urls = array(
        'js'    => array(),
        'css'   => array()   
    );
	
		
	#### STYLES VARS ####
	private $head_css = array();
	public $head_css_inject = array();
	public $head_css_attach = array();
	
	
	#### SCRIPTS VARS ####
	private $head_js = array();
	public $head_js_inject = array(); // extra - TODO
	public $head_js_attach = array();
	private $js_to_ignore = array();
	
	private $foot_js = array(); 
	public $foot_js_inject = array(); // extra - TODO - scripts to inject in footer
	public $foot_js_attach = array();	
	private $css_to_ignore = array();
	
	
	
	function __construct() {
		
		// async call to store registered scripts and styles
		if(isset($_REQUEST['lcso_store_scripts'])) {
			add_action('shutdown', array($this, 'store_js_queued_elements'), 99999);	
			add_action('shutdown', array($this, 'store_css_queued_elements'), 99999);
			
			return true;
		}
        
        // cache vals with a single DB call (WP 6.4>)
        if(function_exists('get_options')) {
            get_options(array(
                'lcso_not_for_css', 'lcso_not_for_js', 
                'lcso_only_filter_css', 'lcso_only_filter_js', 
                'lcso_css_filter', 'lcso_js_filter',
                'lcso_ignored_css', 'lcso_ignored_js'
            ));
        } 
		
		$this->ignore_css	= (bool)get_option('lcso_not_for_css', false);
		$this->ignore_js 	= (bool)get_option('lcso_not_for_js', false);
		
		$this->only_filter_css 	= (bool)get_option('lcso_only_filter_css', false);
		$this->only_filter_js 	= (bool)get_option('lcso_only_filter_js', false);
		
		
		// check filters
		if(!$this->ignore_js) {
			add_action('wp_print_scripts', array($this, 'strip_scripts'), 999998);
		}
		if(!$this->ignore_css) {
			add_action('wp_print_styles', array($this, 'strip_styles'), 999998);
		}
		
		
		// execute concat
		include_once(LCSO_DIR .'/classes/engine.php');
		$this->htt_what = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';

		if(!$this->ignore_js && !$this->only_filter_js) {
			add_action('wp_print_scripts', array($this, 'head_foot_skim_js'), 999999);
		}
		if(!$this->ignore_css && !$this->only_filter_css) {
			add_action('wp_print_styles', array($this, 'head_foot_skim_css'), 999999);
		}
		
		add_action('wp_head', array($this, 'print_head'), 9000);
		add_action('wp_footer', array($this, 'print_footer'), 9000);	
	}
	
	
	
	// print head scripts / styles
	public function print_head() {
		global $wp_scripts, $wp_styles, $lcso_opts;	
		
		
		// CSS
		if(!empty($this->head_css)) {
		
			$engine = new lcso_engine('css', $this->head_css, $wp_styles->base_url, $GLOBALS['lcso_basedir'], $GLOBALS['lcso_baseurl']);
			$url = $engine->get_optimized_url($lcso_opts);
			
			echo '
<link rel="stylesheet" id="lcso-head-styles" type="text/css" href="'. $url .'"/>
';
			
			if(!empty($this->head_css_attach)) {
				echo'
'. implode('
', $this->head_css_attach) .'
';
			}
		}
		

		// javascript
		if(!empty($this->head_js)) {
			$engine = new lcso_engine('js', $this->head_js, $wp_scripts->base_url, $GLOBALS['lcso_basedir'], $GLOBALS['lcso_baseurl']);
			$url = $engine->get_optimized_url($lcso_opts);
			
            if(!empty($this->head_js_attach)) {
				echo'
<script type="text/javascript">/* <![CDATA[ */ 
'. implode('
', $this->head_js_attach) .'
/* ]]> */</script>
';
			}
            
			echo '
<script type="text/javascript" src="'. $url .'"></script>
';
			
		}
	}
	
	
	
	// print footer scripts
	public function print_footer() {
		global $wp_scripts, $wp_styles, $lcso_opts;	
		
		// javascript
		if(!empty($this->foot_js)) {
		
			$engine = new lcso_engine('js', $this->foot_js, $wp_scripts->base_url, $GLOBALS['lcso_basedir'], $GLOBALS['lcso_baseurl']);
			$url = $engine->get_optimized_url($lcso_opts);
			
            if(!empty($this->foot_js_attach)) {
				echo'
<script type="text/javascript">/* <![CDATA[ */ 
'. implode('
', $this->foot_js_attach) .'
/* ]]> */</script>
';
			}
            
			echo '
<script type="text/javascript" src="'. $url .'"></script>
';
			
		}
	}
	
	
	
	/********************************************************************************/
	
	
	/* 
     * Store enqueued styles and scripts to let users choose what to NOT concat
     * @param (false|object) $manual_inclusion - whether to enqueue a new script (found in a website's page) in the plugin settigs page. Must pass a $GLOBALS['$wp_scripts'] element object
     */
	public function store_js_queued_elements($manual_inclusion = false) {
		global $wp_scripts;
		
		// store scripts
        if(!$manual_inclusion) {
            $queued_js = array();
            foreach($wp_scripts->queue as $s) {
                if(isset($wp_scripts->registered[$s])) {	

                    $data = $wp_scripts->registered[$s];

                    // skip ignored ones
                    if($this->ignore_script_url('js', $data->src)) {
                        $queued_js[$s] = $data;
                    }
                }
            }
        }
        
        else {
            $queued_js = get_option('lcso_queued_scripts', array()); 
            $mi = $manual_inclusion;
            
            if(isset($queued_js[ $mi->handle ])) {
                return true;   
            }
            $queued_js[ $mi->handle ] = $mi;
        }
		update_option('lcso_queued_scripts', $queued_js, false);
	}
    
    
	public function store_css_queued_elements($manual_inclusion = false) {
		global $wp_styles;	

		// store styles
        if(!$manual_inclusion) {
            $queued_css = array();
            foreach($wp_styles->queue as $s) {
                if(isset($wp_styles->registered[$s])) {

                    $data = $wp_styles->registered[$s];

                    // skip ignored ones
                    if($this->ignore_script_url('css', $data->src)) {
                        $queued_css[$s] = $data;
                    }
                }
            }
        }
        
        else {
            $queued_css = get_option('lcso_queued_styles', array()); 
            $mi = $manual_inclusion;
            
            if(isset($queued_css[ $mi->handle ])) {
                return true;   
            }
            $queued_css[ $mi->handle ] = $mi;
        }
        
		update_option('lcso_queued_styles', $queued_css, false);
	}
    
	public function shutdown() {
		die();	
	}
	
	
	/********************************************************************************/
	
	
	// filter scripts - check if there's some to exclude from this page
	public function strip_scripts() {
		$filters = get_option('lcso_js_filter', array());
		if(empty($filters)) {return false;}
		
		foreach($filters as $script_name => $allowed_on) {
			if(empty($allowed_on)) {continue;}
			$allowed_on = preg_split("/\\r\\n|\\r|\\n/", $allowed_on);
				
			if($this->strip_this($allowed_on)) {
				wp_deregister_script($script_name);
			}
		}
	}
	
	
	// filter styles - check if there's some to exclude from this page
	public function strip_styles() {
		$filters = get_option('lcso_css_filter', array());	
		if(empty($filters)) {return false;}
		
		foreach($filters as $style_name => $allowed_on) {
			if(empty($allowed_on)) {continue;}
			$allowed_on = preg_split("/\\r\\n|\\r|\\n/", $allowed_on);
			
			if($this->strip_this($allowed_on)) {
				wp_deregister_style($style_name);
			}
		}
	}
	
	
	
	/* whether to remove a script from this page 
	 * @param (array) allowed_on - arry containing URLs or regexp defining where script can be used
	 * @return (bool) true to strip this from the page
	 */
	private function strip_this($allowed_on) {

		// get current URL
		$curr_url = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
		$curr_url .= "://" . $_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];

		foreach($allowed_on as $url) {
			
			// regular URL check
			if(filter_var($url, FILTER_VALIDATE_URL)) {
				
				// strip arguments if are not included in restricted URL
				if(strpos($url, '?') === false && strpos($curr_url, '?') !== false) {
					$raw = explode('?', $curr_url);
					$curr_url = $raw[0];	
				}
				
				if($url == $curr_url) {
					return false;
					break;
				}
			}
			
			// regexp match
			else {
				// sanitize for patterns
				$url = str_replace('/', '\/', $url);
				
				if(substr($url, 0, 1) != '/') {$url = '/'. $url;}
				if(substr($url, -1) != '/' || substr($line, -2) == '\/') {$url .= '/';}
				
				if(preg_match((string)$url, $curr_url)) {
					return false;
					break;
				}
			}	
		}
		
		return true;
	}
    
    
    /* 
     * Defines whether a script URL has to be managed by the plugin or not
     *
     * @param (string) $type = js or css
     * @param (string) $url
     * @return (bool)
     */
    public function ignore_script_url($type, $url) {
        if(!isset($GLOBALS['lcso_suti_'. $type .'_prepared'])) {
            
            $raw = trim((string)get_option('lcso_ignored_'.$type, ''));
            $lines = explode('\n', $raw);
            
            $this->ignored_script_urls[$type] = $lines;
            $GLOBALS['lcso_suti_'. $type .'_prepared'] = true;
        }
        
        $to_consider = true;
        if($type == 'js') {
            $this->ignored_script_urls[$type][] = '/elementor/'; // elementor must be always excluded for JS
            
            if(did_action('elementor/loaded')) {
                $this->ignored_script_urls[$type][] = '/wp-includes/js/jquery/ui';
            }
        }
        
        
        foreach($this->ignored_script_urls[$type] as $line) {
            if(empty($line)) {
                continue;
            }
            
            if(filter_var($line, FILTER_VALIDATE_URL)) {
				
				// strip arguments
				if(strpos($url, '?') !== false) {
					$raw = explode('?', $url);
					$url = $raw[0];	
				}
				
				if($line == $url) {
					$to_consider = false;
					break;
				}
			}
			
			// regexp match
			else {
				// sanitize for patterns
				$line = str_replace('/', '\/', $line);
				
				if(substr($line, 0, 1) != '/') {$line = '/'. $line;}
				if(substr($line, -1) != '/' || substr($line, -2) == '\/') {$line .= '/';}
				
				if(preg_match((string)$line, $url)) {
					$to_consider = false;
					break;
				}
			}	
        }

        return $to_consider;
    }
    
    

	/********************************************************************************/
	


	// skim head from footer scripts
	public function head_foot_skim_js() {
		global $wp_scripts;		
		
		$this->js_to_ignore = (array)get_option('lcso_js_ignore', array()); 

		// SPECIAL CASE - if there's elementor - jQuery must be left untouched due to their damn coding flow
		if(is_plugin_active('elementor/elementor.php')) {
			$this->js_to_ignore[] = 'jquery';
			$this->js_to_ignore = array_unique($this->js_to_ignore);	
		}

		$wps = $wp_scripts;
		$queued 	= $wps->queue;
		$regist 	= $wps->registered;
		
		foreach($queued as $s) {
			if(
                !isset($regist[$s]) || 
                in_array($s, $this->js_to_ignore) ||
                !$this->ignore_script_url('js', $s)
            ) {
                continue;
            }
			
			$this->script_inclusion($s);
		}
	}
	
	
	
	/* manage scripts inclusion in concat file - standalone function to be recursive
	 *
	 * @param (string) $script_id
	 * @param (string) $caller_key = caller element key used in recursive mode - if deps are in footer - put also that in footer
	 */
	private function script_inclusion($script_id, $caller_key = '') {
		global $wp_scripts;	
		
		$s = $script_id;
		$regist = $wp_scripts->registered;
		
        // ignore?
        if(
            !is_string($s) ||
            !isset($regist[$s]) ||
            empty($regist[$s]->src) ||
            !$this->ignore_script_url('js', $regist[$s]->src)
        ) {
            return false;    
        }
        
        
		// check dependencies
		if(!empty($regist[$s]->deps)) {
			foreach($regist[$s]->deps as $dep) {
                
                if(
                    !isset($regist[$dep]) || 
                    in_array($dep, $this->js_to_ignore) || 
                    !$this->ignore_script_url('js', $regist[$dep]->src)
                ) {
                    continue;
                } 
				
				$this->script_inclusion($dep, $script_id);
			}
		}
        	
		//// SKIM
		$v = (!empty($regist[$s]->ver)) ? '?v='. $regist[$s]->ver : '';
		if(strpos($regist[$s]->src, '//') === false && !$v) {$v = '?v='.$wp_scripts->default_version;} // add WP version for WP scripts
		
		
		// footer
		if(isset($regist[$s]->extra['group']) && $regist[$s]->extra['group'] == 1) {
			$this->foot_js[$s] = $regist[$s]->src . $v; 
			if(substr($this->foot_js[$s], 0, 2) == '//') {
				$this->foot_js[$s] = $this->htt_what .':'. $this->foot_js[$s];
			}
			
			
			// if recursive - set also caller to footer
			if(!empty($caller_key)) {
				$wp_scripts->registered[$caller_key]->extra['group'] = 1;
			}
			
			// calculate extra scripts
			if(!empty($regist[$s]->extra) && isset($regist[$s]->extra['data']) && !empty($regist[$s]->extra['data'])) {
				$this->foot_js_attach[$s] = $regist[$s]->extra['data'];	
			}
		}
		
		// head
		else {
			$this->head_js[$s] = $regist[$s]->src . $v; 
			if(substr($this->head_js[$s], 0, 2) == '//') {
				$this->head_js[$s] = $this->htt_what .':'. $this->head_js[$s];
			}
			
			// calculate extra scripts
			if(!empty($regist[$s]->extra) && isset($regist[$s]->extra['data']) && !empty($regist[$s]->extra['data'])) {
				$this->head_js_attach[$s] = $regist[$s]->extra['data'];	
			}	
		}


        // maybe register in the engine if is found in the website
        $this->store_js_queued_elements($regist[$s]);
        
		// deregister from WP
		wp_deregister_script($s);
	}
	
	

	
	// skim head from footer styles
	public function head_foot_skim_css() {
		global $wp_styles;		
		$this->css_to_ignore = (array)get_option('lcso_css_ignore', array()); 
		
		$wps      = $wp_styles;
		$queued   = $wps->queue;
		$regist   = $wps->registered;

		foreach($queued as $s) {
            if(
                !isset($regist[$s]) || 
                in_array($s, $this->css_to_ignore) || 
                !$this->ignore_script_url('css', $regist[$s]->src)
            ) {
                continue;
            }
			
			$this->style_inclusion($s);
		}
	}
	
	
	/* manage style inclusion in concat file - standalone function to be recursive
	 *
	 * @param (string) $style_id
	 * @param (string) $caller_key = caller element key used in recursive mode - if deps are in footer - put also that in footer (TODO)
	 */
	private function style_inclusion($style_id, $caller_key = '') {
		global $wp_styles;	
		
		$s = $style_id;
		$regist = $wp_styles->registered;
        
        // ignore?
        if(
            !is_string($s) ||
            !isset($regist[$s]) || 
            !$this->ignore_script_url('css', $regist[$s]->src)
        ) {
            return false;    
        }
        
		
		// check dependencies
		if(!empty($regist[$s]->deps)) {
			foreach($regist[$s]->deps as $dep) {
				if(!isset($regist[$dep]) || in_array($dep, $this->css_to_ignore)) {continue;}	
				
				$this->style_inclusion($dep, $style_id);
			}
		}
		
		
		// if doesn't have source
		if(empty($regist[$s]->src)) {return false;}


		//// SKIM
		$v = (!empty($regist[$s]->ver)) ? '?v='. $regist[$s]->ver : '';
		if(strpos($regist[$s]->src, '//') === false && !$v) {$v = '?v='.$wp_styles->default_version;} // add WP version for WP scripts
		
				
		// footer - TODO
		if(isset($regist[$s]->extra['group']) && $regist[$s]->extra['group'] == 1) {}
		
		// head
		else {

			// consider RTL styles
			$url = (isset($regist[$s]->extra['rtl']) && is_rtl()) ? str_replace('.css', '-rtl.min.css', $regist[$s]->src) . $v : $regist[$s]->src . $v;
			
			// if is a conditional
			if(!isset($regist[$s]->extra['conditional']) || empty($regist[$s]->extra['conditional'])) {
				$this->head_css[$s] = (substr($url, 0, 2) == '//') ? $this->htt_what .':'.$url : $url; 
			}
			else {
				$this->head_css_attach[] = '
<!--[if '. $regist[$s]->extra['conditional'] .']>
<link rel="stylesheet" id="'. $s .'-css"  href="'. $url .'" type="text/css" media="all" />
<![endif]-->';	
			}	
		}
		
        
        // maybe register in the engine if is found in the website
        $this->store_css_queued_elements($regist[$s]);
		
		// deregister from WP
		wp_deregister_style($s);
	}
	
}

