<?php

/** 
 * CSS/Javascript basic minification engine
 * Functions taken from https://github.com/matthiasmullie/minify - thanks to Matthias Mullie <minify@mullie.eu>
 */
 
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1); 
 
 
class lcso_minify {
	public $code = '';
	public $subj = ''; // CSS or JS
	
	
	public function __construct($code, $subj) {
		$this->code = $code;
		$this->subj = $subj;
	}
	
	
	public function get_css($deep_css_minif = true) {	
		$css = $this->css_stripComments($this->code);
		
		if($deep_css_minif) {
			$css = $this->css_stripWhitespace($css);
			$css = $this->css_shortenZeroes($css);
			$css = $this->css_stripEmptyTags($css);
		}
		
		return trim($css);
	}
		
		
	public function get_js($deep_js_minif = false) {
		$js = $this->code;
		
		if($deep_js_minif) {
			// to be improved - https://github.com/matthiasmullie/minify/blob/master/src/JS.php
			
			$js = $this->js_stripComments($js);
			$js = $this->js_shortenBools($js);
			$js = $this->js_stripWhitespace($js);
		}
		
		return trim($js);
	}
	
	
	
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//// CSS METHODS //////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	/**
     * Strip comments from source code.
     */
    protected function css_stripComments($content) {
        return preg_replace('/\/\*.*?\*\//s', '', $content);
    }
	
	
	/**
     * Strip whitespace.
     * @param string $content The CSS content to strip the whitespace for.
     * @return string
     */
    protected function css_stripWhitespace($content) {
        // remove leading & trailing whitespace
        $content = preg_replace('/^\s*/m', '', $content);
        $content = preg_replace('/\s*$/m', '', $content);

        // replace newlines with a single space
        $content = preg_replace('/\s+/', ' ', $content);

        // remove whitespace around meta characters
        // inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex
        $content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
        $content = preg_replace('/([\[(:])\s+/', '$1', $content);
        $content = preg_replace('/\s+([\]\)])/', '$1', $content);
        $content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);

        // whitespace around + and - can only be stripped in selectors, like
        // :nth-child(3+2n), not in things like calc(3px + 2px) or shorthands
        // like 3px -2px
        $content = preg_replace('/\s*([+-])\s*(?=[^}]*{)/', '$1', $content);

        // remove semicolon/whitespace followed by closing bracket
        $content = str_replace(';}', '}', $content);

        return trim($content);
    }
	

	/**
     * Shorthand 0 values to plain 0, instead of e.g. -0em.
     * @param string $content The CSS content to shorten the zero values for.
     * @return string
     */
    private function css_shortenZeroes($content) {
		
        // reusable bits of code throughout these regexes:
        // before & after are used to make sure we don't match lose unintended
        // 0-like values (e.g. in #000, or in http://url/1.0)
        // units can be stripped from 0 values, or used to recognize non 0
        // values (where wa may be able to strip a .0 suffix)
        $before = '(?<=[:(, ])';
        $after = '(?=[ ,);}])';
        $units = '(em|ex|%|px|cm|mm|in|pt|pc|ch|rem|vh|vw|vmin|vmax|vm)';

        // strip units after zeroes (0px -> 0)
        // NOTE: it should be safe to remove all units for a 0 value, but in
        // practice, Webkit (especially Safari) seems to stumble over at least
        // 0%, potentially other units as well. Only stripping 'px' for now.
        // @see https://github.com/matthiasmullie/minify/issues/60
        $content = preg_replace('/'.$before.'(-?0*(\.0+)?)(?<=0)px'.$after.'/', '\\1', $content);

        // strip 0-digits (.0 -> 0)
        $content = preg_replace('/'.$before.'\.0+'.$units.'?'.$after.'/', '0\\1', $content);
        // strip trailing 0: 50.10 -> 50.1, 50.10px -> 50.1px
        $content = preg_replace('/'.$before.'(-?[0-9]+\.[0-9]+)0+'.$units.'?'.$after.'/', '\\1\\2', $content);
        // strip trailing 0: 50.00 -> 50, 50.00px -> 50px
        $content = preg_replace('/'.$before.'(-?[0-9]+)\.0+'.$units.'?'.$after.'/', '\\1\\2', $content);
        // strip leading 0: 0.1 -> .1, 01.1 -> 1.1
        $content = preg_replace('/'.$before.'(-?)0+([0-9]*\.[0-9]+)'.$units.'?'.$after.'/', '\\1\\2\\3', $content);

        // strip negative zeroes (-0 -> 0) & truncate zeroes (00 -> 0)
        $content = preg_replace('/'.$before.'-?0+'.$units.'?'.$after.'/', '0\\1', $content);

        // remove zeroes where they make no sense in calc: e.g. calc(100px - 0)
        // the 0 doesn't have any effect, and this isn't even valid without unit
        // strip all `+ 0` or `- 0` occurrences: calc(10% + 0) -> calc(10%)
        // looped because there may be multiple 0s inside 1 group of parentheses
        do {
            $previous = $content;
            $content = preg_replace('/\(([^\(\)]+)\s+[\+\-]\s+0(\s+[^\(\)]+)?\)/', '(\\1\\2)', $content);
        } while ( $content !== $previous );
        // strip all `0 +` occurrences: calc(0 + 10%) -> calc(10%)
        $content = preg_replace('/\(\s*0\s+\+\s+([^\(\)]+)\)/', '(\\1)', $content);
        // strip all `0 -` occurrences: calc(0 - 10%) -> calc(-10%)
        $content = preg_replace('/\(\s*0\s+\-\s+([^\(\)]+)\)/', '(-\\1)', $content);
        // I'm not going to attempt to optimize away `x * 0` instances:
        // it's dumb enough code already that it likely won't occur, and it's
        // too complex to do right (order of operations would have to be
        // respected etc)
        // what I cared about most here was fixing incorrectly truncated units

        return $content;
    }
	

    /**
     * Strip empty tags from source code
     * @param string $content
     * @return string
     */
    protected function css_stripEmptyTags($content) {
        return preg_replace('/(^|\})[^\{\}]+\{\s*\}/', '\\1', $content);
    }
	
	
	
	
	//////////////////////////////////////////////////
	//// JS METHODS //////////////////////////////////
	//////////////////////////////////////////////////
	
	
	 /**
     * Strip comments from source code.
     */
    protected function js_stripComments($content) {
        /*// single-line comments
        $content = preg_replace('/\/\/.*$/m', '', $content);

        // multi-line comments
        return preg_replace('/\/\*.*?\*\//s', '', $content);*/
		
		return preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', ' ', $content);
    }
	
	
	/**
     * Replaces true & false by !0 and !1.
     * @param string $content
     * @return string
     */
    protected function js_shortenBools($content) {
        $content = preg_replace('/\btrue\b(?!:)/', '!0', $content);
        $content = preg_replace('/\bfalse\b(?!:)/', '!1', $content);

        // for(;;) is exactly the same as while(true)
        $content = preg_replace('/\bwhile\(!0\){/', 'for(;;){', $content);

        // now make sure we didn't turn any do ... while(true) into do ... for(;;)
        preg_match_all('/\bdo\b/', $content, $dos, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        // go backward to make sure positional offsets aren't altered when $content changes
        $dos = array_reverse($dos);
        foreach ($dos as $do) {
            $offsetDo = $do[0][1];

            // find all `while` (now `for`) following `do`: one of those must be
            // associated with the `do` and be turned back into `while`
            preg_match_all('/\bfor\(;;\)/', $content, $whiles, PREG_OFFSET_CAPTURE | PREG_SET_ORDER, $offsetDo);
            foreach ($whiles as $while) {
                $offsetWhile = $while[0][1];

                $open = substr_count($content, '{', $offsetDo, $offsetWhile - $offsetDo);
                $close = substr_count($content, '}', $offsetDo, $offsetWhile - $offsetDo);
                if ($open === $close) {
                    // only restore `while` if amount of `{` and `}` are the same;
                    // otherwise, that `for` isn't associated with this `do`
                    $content = substr_replace($content, 'while(!0)', $offsetWhile, strlen('for(;;)'));
                    break;
                }
            }
        }

        return $content;
    }
	
	
	
	/**
     * Replaces true & false by !0 and !1.
     * @param string $content
     * @return string
     */
    protected function js_stripWhitespace($content) {

		// uniform line endings, make them all line feed
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        // collapse all non-line feed whitespace into a single space
        $content = preg_replace('/[^\S\n]+/', ' ', $content);
        // strip leading & trailing whitespace
        $content = str_replace(array(" \n", "\n "), "\n", $content);
        // collapse consecutive line feeds into just 1
        $content = preg_replace('/\n+/', "\n", $content);
		
		return $content;
	}
}