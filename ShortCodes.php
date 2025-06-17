<?php
$thisfile = basename(__FILE__, ".php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

register_plugin(
	$thisfile,
	'Short-Codes',
	'1.0',
	'CE Team',
	'https://www.getsimple-ce.ovh/',
	'Allows theme_functions to be used in Page main content area.',
	'pages',
	'shortcodes_settings'
);

add_action('pages-sidebar', 'createSideMenu', [$thisfile, 'Short-Codes <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.2em" height="1.2em" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><path fill="none" stroke="#0033FF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4H3v16h4M17 4h4v16h-4m-9-4h.01M12 16h.01M16 16h.01"/></svg>']);

add_action('theme-header', 'init_shortcodes_filter');

function init_shortcodes_filter() {
	add_filter('content', 'template_shortcodes_filter', 10);
}

// ======================
// FALLBACK FUNCTIONS 
// ======================

function get_page_excerpt_fallback($len = 200, $striphtml = true, $ellipsis = '...', $echo = true) {
	global $content;
	$content = $content ?? '';
	$excerpt = $striphtml ? strip_tags(strip_decode($content)) : strip_decode($content);
	$excerpt = substr($excerpt, 0, $len) . (strlen($excerpt) > $len ? $ellipsis : '');
	if ($echo) echo $excerpt;
	return $excerpt;
}

function get_page_meta_keywords_fallback($echo = true) {
	global $metak;
	$metak = $metak ?? '';
	if (function_exists('get_page_meta_keywords')) return get_page_meta_keywords($echo);
	$output = encode_quotes(strip_decode($metak));
	if ($echo) echo $output;
	return $output;
}

function get_page_meta_desc_fallback($echo = true) {
	global $metad;
	$metad = $metad ?? '';
	if (function_exists('get_page_meta_desc')) return get_page_meta_desc($echo);
	$output = encode_quotes(strip_decode($metad));
	if ($echo) echo $output;
	return $output;
}

function get_page_title_fallback($echo = true) {
	global $title;
	$title = $title ?? '';
	if (function_exists('get_page_title')) return get_page_title($echo);
	$output = strip_decode($title);
	if ($echo) echo $output;
	return $output;
}

function get_page_slug_fallback($echo = true) {
	global $url;
	$url = $url ?? '';
	if (function_exists('get_page_slug')) return get_page_slug($echo);
	if ($echo) echo $url;
	return $url;
}

function get_page_date_fallback($format = "l, F jS, Y - g:i A", $echo = true) {
	global $date, $TIMEZONE;
	$date = $date ?? date('Y-m-d H:i:s');
	$TIMEZONE = $TIMEZONE ?? 'UTC';
	
	if (function_exists('get_page_date')) return get_page_date($format, $echo);
	
	if (function_exists('date_default_timezone_set') && in_array($TIMEZONE, timezone_identifiers_list())) {
		date_default_timezone_set($TIMEZONE);
	}
	
	$timestamp = strtotime($date) ?: time();
	$formatted = date($format, $timestamp);
	if ($echo) echo $formatted;
	return $formatted;
}

function get_page_url_fallback($echo = false) {
	global $url, $parent;
	$url = $url ?? '';
	$parent = $parent ?? '';
	if (function_exists('get_page_url')) return get_page_url($echo);
	$page_url = find_url($url, $parent);
	if (!$echo) echo $page_url;
	return $page_url;
}

function get_site_name_fallback($echo = true) {
	global $SITENAME;
	$SITENAME = $SITENAME ?? '';
	if (function_exists('get_site_name')) return get_site_name($echo);
	if ($echo) echo $SITENAME;
	return $SITENAME;
}

function get_site_url_fallback($echo = true) {
	global $SITEURL;
	$SITEURL = $SITEURL ?? '';
	if (function_exists('get_site_url')) return get_site_url($echo);
	if ($echo) echo $SITEURL;
	return $SITEURL;
}

function get_theme_url_fallback($echo = true) {
	global $SITEURL, $TEMPLATE;
	$SITEURL = $SITEURL ?? '';
	$TEMPLATE = $TEMPLATE ?? '';
	if (function_exists('get_theme_url')) return get_theme_url($echo);
	$url = trim($SITEURL . "theme/" . $TEMPLATE);
	if ($echo) echo $url;
	return $url;
}

function get_data_uploads_fallback($filename = '', $echo = true) {
	global $SITEURL;
	$SITEURL = $SITEURL ?? '';
	$filename = $filename ?? '';
	if (function_exists('get_data_uploads')) return get_data_uploads($filename, $echo);
	$url = trim($SITEURL . "data/uploads/" . ltrim($filename, '/'));
	if ($echo) echo $url;
	return $url;
}

function get_data_thumbs_fallback($filename = '', $echo = true) {
	global $SITEURL;
	$SITEURL = $SITEURL ?? '';
	$filename = $filename ?? '';
	if (function_exists('get_data_thumbs')) return get_data_thumbs($filename, $echo);
	$url = trim($SITEURL . "data/thumbs/" . ltrim($filename, '/')); 
	if ($echo) echo $url;
	return $url;
}

// ======================
// SHORTCODE PROCESSOR
// ======================

function template_shortcodes_filter($content) {
	return preg_replace_callback(
		'/\[\%\s*([a-zA-Z0-9_]+)(?:\s+([^\%]+))?\s*\%\]/',
		function($matches) {
			$params = [];
			if (isset($matches[2])) {
				preg_match_all('/(\w+)=([^\s"]+|"[^"]*")/', $matches[2], $paramMatches);
				foreach ($paramMatches[1] as $i => $key) {
					$params[$key] = trim($paramMatches[2][$i], '"');
				}
			}

			ob_start();
			switch ($matches[1]) {
				case 'get_page_excerpt':
					get_page_excerpt_fallback(
						isset($params['len']) ? (int)$params['len'] : 200,
						isset($params['striphtml']) ? filter_var($params['striphtml'], FILTER_VALIDATE_BOOLEAN) : true,
						$params['ellipsis'] ?? '...'
					);
					break;
				case 'get_page_meta_keywords':
					get_page_meta_keywords_fallback();
					break;
				case 'get_page_meta_desc':
					get_page_meta_desc_fallback();
					break;
				case 'get_page_date':
					get_page_date_fallback(
						$params['format'] ?? "l, F jS, Y - g:i A"
					);
					break;
				case 'get_page_title':
					get_page_title_fallback();
					break;
				case 'get_page_slug':
					get_page_slug_fallback();
					break;
				case 'get_page_url':
					get_page_url_fallback();
					break;
				case 'get_site_name':
					get_site_name_fallback();
					break;
				case 'get_site_url':
					get_site_url_fallback();
					break;
				case 'get_theme_url':
					get_theme_url_fallback();
					break;
				case 'get_data_uploads':
					get_data_uploads_fallback($params['filename'] ?? '');
					break;
				case 'get_data_thumbs':
					get_data_thumbs_fallback($params['filename'] ?? '');
					break;
				default:
					return $matches[0];
			}
			return ob_get_clean();
		},
		$content
	);
}

// ======================
// ADMIN PANEL
// ======================

function shortcodes_settings() {
	global $USR;
	echo '
	<div class="w3-parent w3-container"><!-- Start Plugin -->
		<h3>Short-Codes <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.2em" height="1.2em" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><path fill="none" stroke="#0033FF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4H3v16h4M17 4h4v16h-4m-9-4h.01M12 16h.01M16 16h.01"/></svg></h3>
		<p>Allows template functions in content blocks.</p>
		
		<hr>
		<ul class="w3-ul w3-border w3-hoverable" style="width:90%">
			<li class="w3-green"><strong>Available shortcodes:</strong></li>
			
			<li><code class="tpl">[% get_site_name %]</code></li>
			<li><code class="tpl">[% get_site_url %]</code></li>
			<li class="w3-white"></li>
			<li><code class="tpl">[% get_page_title %]</code></li>
			<li><code class="tpl">[% get_page_slug %]</code></li>
			<li><code class="tpl">[% get_page_url %]</code></li>
			<li><code class="tpl">[% get_page_excerpt %]</code> (defaults: len=200, striphtml=true, ellipsis=...)</li>
			<li><code class="tpl">[% get_page_excerpt len=100 striphtml=false ellipsis="→" %]</code></li>
			<li><code class="tpl">[% get_page_date %]</code> (default format: "l, F jS, Y - g:i A")</li>
			<li><code class="tpl">[% get_page_date format="Y-m-d" %]</code></li>
			<li><code class="tpl">[% get_page_meta_keywords %]</code></li>
			<li><code class="tpl">[% get_page_meta_desc %]</code></li>
			<li class="w3-white"></li>
			<li><code class="tpl">[% get_theme_url %]</code></li>
			<li class="w3-white"></li>
			<li><code class="tpl">[% get_data_uploads filename="example.jpg" %]</code></li>
			<li><code class="tpl">[% get_data_thumbs filename="thumbnail.example.jpg" %]</code></li>
		</ul>
		
		<hr>
		
		<p><strong>Example values:</strong></p>
		<ul>
			<li><b>Site Name:</b> ' . htmlspecialchars(get_site_name_fallback(false)) . '</li>
			<li><b>Site URL:</b> ' . htmlspecialchars(get_site_url_fallback(false)) . '</li>
			<li><b>Site Theme:</b> ' . htmlspecialchars(get_theme_url_fallback(false)) . '</li>
			<li><b>Page Date:</b> ' . htmlspecialchars(get_page_date_fallback("Y-m-d", false)) . '</li>
			<li><b>Image call:</b> ' . htmlspecialchars(get_data_uploads_fallback("example.jpg", false)) . '</li>
			<!--li>Page Excerpt: "' . htmlspecialchars(get_page_excerpt_fallback(50, true, '...', false)) . '"</li>
			<li>Page Slug: ' . htmlspecialchars(get_page_slug_fallback(false)) . '</li-->
		</ul>
		
		<hr>
		
		<footer class="w3-padding-top-32 margin-bottom-none">
			<p class="w3-small clear w3-margin-bottom w3-margin-left">Made with <span class="credit-icon">❤️</span> especially for "<b>'.$USR.'</b>". Is this plugin useful to you?
			<span class="w3-btn w3-khaki w3-border w3-border-red w3-round-xlarge"><a href="https://getsimple-ce.ovh/donate" target="_blank" class="donateButton"><b>Buy Us A Coffee </b><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" fill-opacity="0" d="M17 14v4c0 1.66 -1.34 3 -3 3h-6c-1.66 0 -3 -1.34 -3 -3v-4Z"><animate fill="freeze" attributeName="fill-opacity" begin="0.8s" dur="0.5s" values="0;1"/></path><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="48" stroke-dashoffset="48" d="M17 9v9c0 1.66 -1.34 3 -3 3h-6c-1.66 0 -3 -1.34 -3 -3v-9Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="48;0"/></path><path stroke-dasharray="14" stroke-dashoffset="14" d="M17 9h3c0.55 0 1 0.45 1 1v3c0 0.55 -0.45 1 -1 1h-3"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.6s" dur="0.2s" values="14;0"/></path><mask id="lineMdCoffeeHalfEmptyFilledLoop0"><path stroke="#fff" d="M8 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4M12 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4M16 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4"><animateMotion calcMode="linear" dur="3s" path="M0 0v-8" repeatCount="indefinite"/></path></mask><rect width="24" height="0" y="7" fill="currentColor" mask="url(#lineMdCoffeeHalfEmptyFilledLoop0)"><animate fill="freeze" attributeName="y" begin="0.8s" dur="0.6s" values="7;2"/><animate fill="freeze" attributeName="height" begin="0.8s" dur="0.6s" values="0;5"/></rect></g></svg></a></span></p>
		</footer>
	</div>
	';
}
?>
