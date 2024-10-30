<?php

class lc_minify_wp_html {
    protected $html;
	
    public function __construct($html) {
		$this->parseHTML($html);
    }
	
	
    public function get_code() {
		return $this->html;
    }
	
	

    protected function bottomComment($raw, $compressed) {
		$raw = strlen($raw);
		$compressed = strlen($compressed);
		
		$savings = ($raw-$compressed) / $raw * 100;
		
		$savings = round($savings, 2);
		
		return '<!-- LC Script Optimizer '.$savings.'% size saved. From '.$raw.' to '.$compressed.' bytes -->';
    }
    
	

	protected function minifyHTML($html) {
		
		$pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
		
		$overriding = false;
		$raw_tag = false;
	
		// Variable reused for output
		$html = '';
		foreach ($matches as $token) {
			$tag = (isset($token['tag'])) ? strtolower($token['tag']) : NULL;
   			$content = $token[0];
   		 
			if(is_null($tag)) {
			 
				if ( !empty($token['script']) ) {
					if(!get_option('lcso_inl_js_ignore')) {
						$content = preg_replace(
							array(
								// Remove comment(s)
								'#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
								// Remove white-space(s) outside the string and regex
								'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
								// Remove the last semicolon
								'#;+\}#',
								// Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
								'#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
								// --ibid. From `foo['bar']` to `foo.bar`
								'#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
							),
							array(
								'$1',
								'$1$2',
								'}',
								'$1$3',
								'$1.$3'
							),
						$content);
					}
					
					$strip = false;
				}
				else if ($content == '<!--wp-html-compression no compression-->') {
					$overriding = !$overriding;
					 
					// Don't print the comment
					continue;
				}
				
				if (!$overriding && $raw_tag != 'textarea') {
					// Remove any HTML comments, except MSIE conditional comments
					$content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
				}
			}
			else {
   			 	if ($tag == 'pre' || $tag == 'textarea') {
   					$raw_tag = $tag;
   				}
				else if ($tag == '/pre' || $tag == '/textarea') {
					$raw_tag = false;
				}
				else {
					if ($raw_tag || $overriding) {
						$strip = false;
					}
					else {
						$strip = true;
						 
						// Remove any empty attributes, except:
						// action, alt, content, src
						$content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
						 
						// Remove any space before the end of self-closing XHTML tags
						// JavaScript excluded
						$content = str_replace(' />', '/>', $content);
					}
				}
			}
			 
			if($strip) {
				$content = $this->removeWhiteSpace($content);
			}
   		 
			$html .= $content;
		}
   	 
		return $html;
    }
   	 
	 
	 
    public function parseHTML($html) {
		$this->html = $this->minifyHTML($html);
   		$this->html .= "\n" . $this->bottomComment($html, $this->html);
    }
    
	
	
    protected function removeWhiteSpace($str) {
		$str = str_replace("\t", ' ', $str);
		$str = str_replace("\n",  '', $str);
		$str = str_replace("\r",  '', $str);
		
		while (stristr($str, '  ')) {
			$str = str_replace('  ', ' ', $str);
		}
		
		return $str;
	}
}





function lc_minify_wp_html_finish($html) {
    $lcmh = new lc_minify_wp_html($html);
	return $lcmh->get_code();
}

function lc_minify_wp_html_start() {
    if(!get_option('lcso_not_for_html')) {
		ob_start('lc_minify_wp_html_finish');
	}
}



