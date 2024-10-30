<?php
/**
  * Class used to manage static files cache and put together optimized scripts code
  */
  
class lcso_engine {
	
	public $cache_folder 		= 'lcso-cache'; // cache folder name
	public $basedir 	= ''; // cache folder's wrapper - basedir
	public $baseurl 	= ''; // cache folder's wrapper - baseurl
	public $wp_scripts_baseurl 	= ''; // wordpress baseurl for WP default scripts

	protected $subj 	= ''; // subject (css or js)
	protected $src		= array(); // script sources
	protected $filename = ''; // cache filename
	protected $can_write = false; // whether server allows direct files management
	
	private $css_import = array(); // array containing imports to be prepended
	
	protected $optimiz_opts = array( // optimization options - associatice array (avoid import / don't minify css / which JS to minify)
		'no_import' 	=> false,
		'no_css_min' 	=> false,
		'min_js' 		=> array()
	); 


	/* setup variables
	 * 
	 * @param $subj (string)
	 * @param $src (array)
	 * @param $wp_scripts_baseurl (string) - wordpress baseurl for WP default scripts
	 * @param $basedir (string) - custom basedir - otherwise autocalculated
	 * @param $baseurl(string) - custom baseurl
	 */
	function __construct($subj, $src, $wp_scripts_baseurl, $basedir = '', $baseurl = '') {
		
		$this->subj = (in_array($subj, array('js', 'css'))) ? $subj : 'css';
		$this->src = (array)$src;
		$this->wp_scripts_baseurl = $wp_scripts_baseurl;

		// paths management
		if(empty($basedir) && empty($baseurl)) {
			$this->auto_paths();
		} else {
			$this->basedir = $basedir;
			$this->baseurl = $baseurl;	
		}
		
		// know files management rights
		$this->can_write = $this->can_write();
	}
	
	
	
	
	// auto basepath and baseurl finder - made for wordpress - uses wp-content folder
	private function auto_paths() {
		
		// directory
		$dir_arr = explode('/', __DIR__);
		for($a=0; $a <= 2; $a++) { 
			array_pop($dir_arr); 
		}
		
		$this->basedir = implode('/', $dir_arr);
		
		// url 
		$baseurl = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
		$baseurl .= "://" . $_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
		
		$url_arr = explode('/', $baseurl);
		for($a=0; $a <= 3; $a++) {
			array_pop($url_arr); 
		}
		
		$this->baseurl = implode('/', $url_arr);		
	}
	
	
	
	
	/* can manage files?
	 * @return (bool)
	 */
	public function can_write() {
		return (ini_get('allow_url_fopen') && is_writable($this->basedir)) ? true : false;	
	}
	 
	
	
	
	/*
	 * cache folder existence check - creates it if not exists
	 * @return (bool) - if returns false means folder can't be created
	 */
	public function cache_folder_check($second_check = false) {
		$cache_folder = $this->basedir .'/'. $this->cache_folder;
		
		if(!file_exists($cache_folder)) {
			if($second_check) {return false;}
			
			@mkdir($cache_folder, 0755);
			return $this->cache_folder_check(true);
		}	
		else {
			return true;	
		}
	}
	
	
	
	
	/*
	 * Given minification parameters and involved scripts, returns the cache file name
	 * @return (string)
	 */
	protected function get_cache_filename() {
		$opts = $this->optimiz_opts;
		$id = 'subj='. $this->subj .'&baseurl='. $this->wp_scripts_baseurl .'&src='. urlencode(implode('|', $this->src));
		
		if($this->subj == 'css') {	
			if($opts['no_import']) 			{$id .= '&no_import';}
			if($opts['no_css_min']) 		{$id .= '&no_css_min';}
		}
		else {
			$id .= '&min_js='. urlencode(implode('|', (array)$opts['min_js']));
		}  
		return md5($id) .'.'. $this->subj;	
	}
	
	
	
	
	/* try creating the cached script
	 * @return (bool) - if returns false means folder can't be created
	 */
	protected function create_cache_file($code) {

		// place @font-face them on top of the rest
		if($this->subj == 'css') {	
			preg_match_all('/@font-face\K[^}]*(?=})/', $code, $matches, PREG_SET_ORDER);
			if(is_array($matches)) {
				
				// remove existing ones
				preg_replace('/@font-face\K[^}]*(?=})/', '', $code); 
	
				foreach($matches as $m) {
					$code = '@font-face'. $m[0]. '}'. $code;	
				}
			}
		}
		
		$file_path = $this->basedir .'/'. $this->cache_folder .'/'. $this->get_cache_filename(); 
		return (@file_put_contents($file_path, trim($code), LOCK_EX)) ? true : false;	
	}
	 
	
	
	
	// override options
	private function setup_optimiz_opts($optimiz_opts = array()) {
		$new_opts = array();
		foreach($this->optimiz_opts as $key => $val) {
			$new_opts[ $key ] = (isset($optimiz_opts[$key])) ? $optimiz_opts[$key] : $val;
		}
		
		$this->optimiz_opts = $new_opts;
	}
	
	
	
	
	/* create optimized code and returns cache file URL or remote php deliver URL
	 * @return (bool)
	 */
	public function get_optimized_url($optimiz_opts = array()) {
		$this->setup_optimiz_opts($optimiz_opts);
		
		
		// writing server permissions 
		if($this->can_write && $this->cache_folder_check()) { 
		
			$filename 	= $this->get_cache_filename();
			$file_path 	= $this->basedir .'/'. $this->cache_folder .'/'. $filename; 
			$url 		= $this->baseurl .'/'. $this->cache_folder .'/'. $filename; 
			
			// check for existing cached file
			if( file_exists($file_path) ) {
				return $url;	
			}
			
			// otherwise create code
			$code = $this->create_code($optimiz_opts); 
			if(empty($code)) {return '';}
			
			$this->create_cache_file($code);
		}
		
		// return remote deliver URL
		else {
			$plugin_url = (defined('LCSO_URL')) ? LCSO_URL : $this->baseurl .'/plugins/lc_scripts_optimizer'; 
			
			$opts = $this->optimiz_opts;
			$url = $plugin_url .'/deliver.php?subj='. $this->subj .'&baseurl='. $this->wp_scripts_baseurl .'&src='. urlencode(implode('|', $this->src));
			
			if($opts['no_import']) 		{$url .= '&no_import';}
			if($opts['no_css_min']) 	{$url .= '&no_css_min';}
			if(!empty($opts['min_js'])) {$url .= '&min_js='. urlencode(implode('|', (array)$opts['min_js']));}
		}
		
		return $url;
	}
	



	// turn CSS relative paths to absolute, import @import files and move @font-face on top
	private function css_rel2abs($src, $code) {
		if(empty($code)) {
            return '';
        }
		$opts = $this->optimiz_opts;
		
		// get css file baseurl
		$arr = explode('?', $src);
		$src = $arr[0];
		
		$arr = explode('/', $src);
		array_pop($arr);
		$path = implode('/', $arr).'/';
		
		
		// replace relative uRLS
		$absoluteUrl = '((?:https?:)?//)';
		$rawData = '(?:data\:?:)';
		$relativeUrl = '\s*([\'"]?((' . $absoluteUrl . ')|(' . $rawData . ')))';
		$search = '#url\((?!' . $relativeUrl . ')\s*([\'"])?#';
		$replace = "url($6{$path}";
		
		$code = preg_replace($search, $replace, $code);	
		
		if(strpos(strtolower($src), 'https') !== false) {
			$code = str_replace('http://', 'https://', $code);	
		}
		

		// @import management
		if(preg_match_all('/(@import) (url)\(([^>]*?)\);/', $code, $matches)) {
			
			// complete @import inclusion
			if(!$opts['no_import']){
				$found = array();
				
				foreach($matches[0] as $match) {
					$found[] = trim(str_replace( array("'", '"', ')', '(', 'url', '@import', ';'), '', $match));
				}
			  	
				$a = 0;
				foreach($found as $remote_url) {

					// replace // with http(s)://
					if(substr($remote_url, 0, 2) == '//') {
						$remote_url = (strpos(strtolower($remote_url), 'https') !== false) ? 'https:'.$remote_url : 'http:'.$remote_url;	
					}
					
					// strip URL parameters
					if(strpos($match, '?') !== false) {
						$raw = explode('?', $match);
						$match = $raw[0];	
					}
					
					// add
					$remote = '
			
/* @import src - '. $remote_url .' */

'. trim($this->css_rel2abs($remote_url, $this->lcso_curl($remote_url))); // recursive
					
					$code = str_replace($matches[0][$a], $remote, $code);
					$a++;
				}
			}
			
			// move @imports on top
			else {
				foreach($matches[0] as $row) {
					$orig_row = $row; 
					$code = str_replace($orig_row, '', $code);
					
					// replace // with http(s)://
					if(strpos(strtolower($row), 'http') === false && strpos(strtolower($row), 'https') === false) {
						$arr = explode('//', $row);
						$arr[0] = (strpos(strtolower($src), 'https') !== false) ? $arr[0].'https:' : $arr[0].'http:';
						$row = implode('//', $arr);
					}
					
					// add
					$this->css_import[] = $row;
					$code = str_replace($row, '', $code);
				}
			}
		}


		// avoid bad URLs
		$code = preg_replace('/wp-content\K[^.]*(?=wp-content)/', '', $code); 
		$code = str_replace('wp-contentwp-content', 'wp-content', $code); 
		
		return $code;
	}
	
	
	
	// file_get_contents/cURL call
	private function lcso_curl($url) {
		$subj = $this->subj;
		$opts = $this->optimiz_opts;

		// local file - set to recall through file_get_contents - not for Fontawesome
		if(strpos(strtolower($url), site_url()) !== false) {
			$url = str_replace(site_url(), '', $url);
			
			if(substr($url, 0, 1) != '/') {
				$url = '/'. $url;
			}
		}


		// manage wp default scripts
		if(strpos(strtolower($url), 'http') === false) {
			
			$pos = strrpos($this->basedir, '/');
			$basepath = substr($this->basedir, 0, $pos);
			
			$url_arr = explode('?', $url);
			$code = (current_user_can('manage_options')) ? file_get_contents( $basepath . $url_arr[0]) : @file_get_contents( $basepath . $url_arr[0]);
		}

		else {

			// whether to use WP HTTP APIs or cURL
			if(function_exists('wp_remote_get')) {
				$args = array(
					'timeout'     => 3,
					'redirection' => 3,
				); 
				
				$response = wp_remote_get($url, $args);
				if(is_wp_error($response) || !isset($response['body'])) {
                    return '';
                }
				
				$data 		= wp_remote_retrieve_body($response);
				$mime 		= wp_remote_retrieve_header($response, 'content-type');
				$http_code 	= wp_remote_retrieve_response_code($response);
			}
			
			else {
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_AUTOREFERER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
				curl_setopt($ch, CURLOPT_URL, $url);
				
				$data = curl_exec($ch);
				$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
				curl_close($ch);
			}
		
			// continue only if HTTP code is ok
			if($http_code != 200) {
				return '/* error '. $http_code .' */';
			}
	
	
			// be sure fetched data is congruous
			if($subj == 'js') {
				$code = (strpos($mime, 'text/javascript') !== false || strpos($mime, 'application/javascript') !== false) ? $data : '';	
			} else {
				$code = (strpos($mime, 'text/css') !== false) ? $data : '';	
			}
		}
		
		$code = trim($code);
		
		// minification and CSS import management
		if(!empty($code)) {
			require_once('minify.php');
			
			if($subj == 'css') {
				if(!$opts['no_css_min']) { 
					$min = new lcso_minify($code, $subj);
					$code = $min->get_css();
				}
				
				$code = $this->css_rel2abs($url, $code);
			}
			
			else {
				// whether to minify JS
				foreach($opts['min_js'] as $to_min) {
					if(strpos($url, $to_min) !== false) {
						$min = new lcso_minify($code, $subj);
						$code = $min->get_js(true);
						break;	
					}
				}
			}
			
			return trim($code);
		}
		
		return '';
	}

	

	/* optimized script creation
	 * @return (string) code block ready to be used
	 */
	 
	public function create_code() {
		$code = '';
		
		foreach($this->src as $src) {
			
			// JS
			if($this->subj == 'js') {			
				if(isset($_REQUEST['lcso_avoid'])) {
					$code .= '
				
/* src - '. $src .' */

'. trim($this->lcso_curl($src));	
				}
				else {
					$code .= trim( $this->strip_init_comment( $this->lcso_curl($src)));	
				}
			}
			
			
			// CSS
			else {
				if(isset($_REQUEST['lcso_avoid'])) {
					$code .= '
					
/* src - '. $src .' */

'. trim($this->css_rel2abs($src, $this->lcso_curl($src)));
				}
				else {
					$code .= trim( $this->strip_init_comment( $this->css_rel2abs($src, $this->lcso_curl($src))));	
				}
			}
		}
		
		
		// if is css - prepend imports
		if($this->subj == 'css' && !empty($this->css_import)) {
			$code = implode('
', $this->css_import). '
'. $code;
		}
		
		return trim($code);
	}
	
	
	
	
	/* remove the first comment starting with "/*" from a CSS or javascript file */
	private function strip_init_comment($code) {
		$trimmed = trim($code);
		
		if(substr($code, 0, 2) == '/*') {
			$comm_end_pos  = strpos($trimmed, '*/');
			
			if($comm_end_pos !== false) {
				$code = substr($trimmed, $comm_end_pos + 2);	
			}
		}
		return $code;	
	}

	
}

