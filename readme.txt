=== LC Scripts Optimizer ===
Contributors: LCweb Projects
Donate link: https://lcweb.it/donations
Tags: scripts, cache, minification, deregister, compress, combine, speed, seo, compatibility, compressor, CSS, JS, javascript, fast, acceleration, merge, user friendly, lightweight, optimizer, fastest, solution, filter, exclude, dequeue script, dequeue style, elementor, html minifier
Requires at least: 5.0
Tested up to: 6.4.0
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lightweight plugin aiming to solve one of most tedious Wordpress issues: amount of different scripts added by theme or plugins.

== Description ==

Speed up your wordpress website using only necessary scripts (CSS/JS) in targeted pages and combining them.
They literally slow down your website, increasing a lot page's speed and wasting a lot of server resources.

Only one settings page to control everything. Basically with just one click you could increase your Google speed rank of 10 points!

But what's the difference between this plugin and other ones? Is light. Extremely light and built to be compatible with any existing website and script.
You can choose which scripts to include in optimization and WHERE to use them: lightening pages and offering a better user experience.

Finally, features also an HTML minifier, to drastically reduce your pages weight!
Is also compatible with other WP optimizers (W3 total cache, WP-super-cache, etc). Offering a great combo to optimize wordpress all-around!

= Features list: =

*   lightweigt and super-simple setup
*   compatible with any theme and any script
*   scripts/styles combining
*   optimized files saved and served as static elements
*   filter by URL and/or regular expression
*   scripts/styles filter to set their usage across pages
*   option to exclude every script/style from optimization process 
*   CSS @import code processing
*   CSS minification 
*   HTML minification 
*   fallback granted for restricted servers through dynamic PHP script
*   manual cache cleaner system
*   testing mode to tune the plugin without affecting visitors
*   compatible with other WP optimizers

= Advantages =

*   faster pages
*   lower server impact
*   bandwidth saving
*   SEO score increasing


= Notes: =
*   Scripts/styles must be properly enqueued through WP register functions 
*   Static files creation requires writing permissions in wp-content folder
*   Your site pages must be accessible via wp_remote_get() to concatenate scripts
*   No support provided
 

== Installation ==

1. Upload `lc-scripts-optimizer` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. A new submenu item will be visible under "tools" 
4. Check system status and eventually unlock writing permissions
5. Click on "fetch again" button to store scripts
6. Enable optimization and update settings
7. Use "fetch again" button every time a new theme/plugin is activated to refresh the list. By default any enqueued script/style is managed

== Screenshots ==

1. Settings

== Changelog ==

= 1.4.0 =
*   improved get_options performances (w/ WP 6.4 compatibility)
*   scripts tracked and added to the settings page also navigating the website


= 1.3.2 =
*   inline javascript codes attached to scripts now appended before them


= 1.3.1 =
*   better javascript for the settings page


= 1.3 =
*   added fields for URL-based scripts exclusion from the engine
*   improved Elementor compatibility
*   better HTML minification start hook


= 1.2 =
*   added HTML minification engine
*   file_get_contents used to recall local files, working with hidden websites
*   fixed CSS bad paths composition
*   fixed CSS @font-face placement
*   fixed existing cached files check


= 1.1.2 =
*   Elementor Compatibility


= 1.1.1 =
*   Better scripts fetching in "Scripts Filter"
*   Elementor Compatibility (requires jQuery to be ignored during optimization)


= 1.1 =
*   Added options to exclude CSS and JS concatenation (to just use scripts filter)
*   Added testing mode to check optimizations only if logged as WP admin
*   Added sticky settings update button
*   Better engine initialization code
*   Minor UI improvements


= 1.0 =
Initial release

== Upgrade notice ==

none