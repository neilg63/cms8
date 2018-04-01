<?php

define('SNIPPET_DIR', __DIR__ .'/files/snippets');

function vuejs_drupal_fetch_seo($path) {
	$seo          = new StdClass;
	$seo->title   = '';
	$seo->content = '';
	$seo->meta    = '';
	if (strpos($path, '/') !== 0) {
		$path = '/'.$path;
	}

	if (strlen($path) > 1 && $path != '/projects') {
		$file_root = preg_replace('#/#i', '__', $path);

	} else {
		$file_root = 'projects';

	}
	$content   = '';
	$file_path = SNIPPET_DIR.'/main.menu';
	if (file_exists($file_path)) {
		$content .= file_get_contents($file_path);
	}

	$root_path = SNIPPET_DIR.'/'.$file_root;
	$file_path = $root_path.'.html';

	if (file_exists($file_path)) {
		$content .= file_get_contents($file_path);

		$file_path = $root_path.'.title';
		if (file_exists($file_path)) {
			$seo->title = file_get_contents($file_path);
		}
		$file_path = $root_path.'.meta';
		if (file_exists($file_path)) {
			$seo->meta = file_get_contents($file_path);
		}
	}
	$seo->content   = '<section class="seo-content">'.$content.'</section>';
	$seo->core_data = '';
	$root_path      = SNIPPET_DIR.'/core-data';
	$file_path      = $root_path.'.json';

	if (file_exists($file_path)) {
		$seo->core_data = file_get_contents($file_path);
	}

	return $seo;
}

$seo = vuejs_drupal_fetch_seo($_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html><html><head><meta charset=utf-8><title><?php print $seo->title; ?></title><meta name=viewport content="width=480,maximum-scale=1"><?php print $seo->meta; ?><link href="https://fonts.googleapis.com/css?family=Raleway" rel=stylesheet><link rel=stylesheet href=/static/icomoon/style.css media=all><link rel=stylesheet href=/static/css/pure-min.css media=all><link rel=stylesheet href=/static/icomoon/style.css media=all><link rel=apple-touch-icon sizes=180x180 href=/static/favicons/apple-touch-icon.png><link rel=icon type=image/png sizes=32x32 href=/static/favicons/favicon-32x32.png><link rel=icon type=image/png sizes=16x16 href=/static/favicons/favicon-16x16.png><link rel=manifest href=/static/favicons/manifest.json><link rel=mask-icon href=/static/favicons/safari-pinned-tab.svg color=#5bbad5><meta name=theme-color content=#ffffff><script type=text/javascript src=/static/js/picturefill.min.js async=true></script><link href=/static/css/app.1335597134b48c7d2b580815ab627c31.css rel=stylesheet></head><body><div id=app><?php print $seo->content; ?></div><script type=text/javascript><?php print $seo->core_data; ?></script><script type=text/javascript src=/static/js/manifest.f73cc0f67ea4cd51d375.js></script><script type=text/javascript src=/static/js/vendor.eaa30c703bf3fe06a299.js></script><script type=text/javascript src=/static/js/app.a4fbb2812a75e0c73d11.js></script></body></html>