<?php
/**
 * @file
 */

namespace Drupal\jsonstyles\Controller;

use \Drupal\Core\Controller\ControllerBase;

use \Drupal\views\Views;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SiteInfoController
 * @package Drupal\jsonstyles\Controller
 */

class SiteInfoController extends ControllerBase {

	function jsonView() {

		$data['menu']    = $this->extractMenu('main');
		$jsSettings      = \Drupal::config('jsonstyles.settings');
		$copyrightNotice = $jsSettings->get('copyright');
		$copyrightNotice = preg_replace('#!year\b#i', date("Y"), $copyrightNotice);
		$data['footer']  = array(
			'copyright' => $copyrightNotice,
			'menu'      => $this->extractMenu('footer'),
			'email'     => $jsSettings->get('email')
		);
		$config              = \Drupal::config('system.site');
		$data['site_name']   = $config->get('name');
		$slogan              = $config->get('slogan');
		$data['site_slogan'] = $slogan;
		$parts               = explode('|', $slogan);
		$data['owner']       = trim($parts[0]);
		$strapline           = count($parts) > 1?trim($parts[1]):'';
		$data['strapline']   = $strapline;
		$data['nodes']       = $this->allNodeAliasTitles();
		$response            = new JsonResponse($data);
		$response->send();
		exit;
	}

	protected function extractMenu($menuName = 'main') {
		$html  = $this->renderMenuTree($menuName);
		$parts = explode('</li>', $html);
		$json  = array();
		$regex = '#<a\b[^>"]*?href="([^>"]*?)".*?>([^<]*?)</a>#i';
		foreach ($parts as $part) {
			if (strpos($part, '<a') >= 0) {
				if (preg_match($regex, $part, $match)) {
					$json[] = array('link' => $match[1], 'title' => html_entity_decode($match[2]));
				}
			}
		}
		return $json;
	}

	protected function renderMenuTree($menuName = 'main') {
		$menu_tree    = \Drupal::menuTree();
		$menu_name    = $menuName;
		$parameters   = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
		$tree         = $menu_tree->load($menu_name, $parameters);
		$menu         = $menu_tree->build($tree);
		$manipulators = array(
			// Only show links that are accessible for the current user.
			array('callable' => 'menu.default_tree_manipulators:checkAccess'),
			// Use the default sorting of menu links.
			array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
		);
		$tree = $menu_tree->transform($tree, $manipulators);

		// Finally, build a renderable array from the transformed tree.
		$menu = $menu_tree->build($tree);

		return drupal_render($menu);
	}

	function userView() {
		$user = \Drupal::currentUser();
		$data = array();
		if ($user instanceof \Drupal\Core\Session\AccountProxy) {
			$data['id']       = $user->id();
			$data['loggedin'] = $user->id() > 0;
			$account          = $user->getAccount();
			if (is_object($account)) {
				$data['name']         = $account->getUsername();
				$data['display_name'] = $account->getDisplayName();
				$data['roles']        = $account->getRoles();
				$data['email']        = $account->getEmail();
				$data['last_access']  = $account->getLastAccessedTime();
				$data['is_editor']    = in_array('administrator', $data['roles']) || $account->hasPermission('bypass node access');
			}
		}

		$response = new JsonResponse($data);
		$response->send();
		exit;
	}

	function editedView() {
		$data          = array();
		$data['nodes'] = $this->allNodeAliasTitles(true, false);
		$data['total'] = count($data['nodes']);
		$response      = new JsonResponse($data);
		$response->send();
		exit;
	}

	function writeSnippets() {

		$view = \Drupal\views\Views::getView('projects');
		$view->setDisplay('block_1');
		if (is_object($view)) {
			$render = $view->render();
			if (is_array($render) && !empty($render)) {
				jsonstyles_write_snippet('projects.html', render($render));
			}
		}

		$menu_html = $this->renderMenuTree();
		if (!empty($menu_html)) {
			jsonstyles_write_snippet('main.menu', $menu_html);
		}
		$data = $this->allNodeAliasTitles();

		foreach ($data as $row) {
			$node = node_load($row->nid);
			if (is_object($node) && $node instanceof EntityInterface) {

				jsonstyles_render_node_snippet($node, $row->alias);

			}
		}

		usleep(50);
		$this->writeCoreData();
		usleep(50);
		return new RedirectResponse('/admin/content');
	}

	function writeCoreData() {
		$strData = '';
		$prefix  = 'http';
		if ($_SERVER['SERVER_PORT'] == 443) {
			$prefix .= 's';
		}
		$prefix .= '://';
		$host = $prefix.$_SERVER['HTTP_HOST'];
		$uri  = $host.'/jsonstyles/siteinfo';
		$json = file_get_contents($uri);
		$strData .= "\n" . 'var $preload={};';
		if (is_string($json)) {
			$strData .= "\n".'$preload.site_info = '.$json.";\n";
		}
		$extraPreloadAliases = array();
		$obj = json_decode($json);
		if (isset($obj->nodes) && is_array($obj->nodes)) {
			foreach ($obj->nodes as $nd) {
				if (!preg_match('#^/(home|projects)#', $nd->alias) && strlen($nd->alias) > 2) {
					$alias = 'node_full__' . $nd->nid;
					$extraPreloadAliases[$alias] = '/jsonstyles/node-full/' . $nd->nid;
				}
			}
		}
		usleep(250);
		$uri  = $host.'/jsonstyles/home';
		$json = file_get_contents($uri);

		if (is_string($json)) {
			$strData .= "\n".'$preload.home_slides = '.$json.";\n";
		}
		usleep(250);
		$uri  = $host.'/jsonstyles/projects-full';
		$json = file_get_contents($uri);
		if (is_string($json)) {
			$strData .= "\n".'$preload.top_projects = '.$json.";\n";
		}
		if (!empty($extraPreloadAliases)) {
			foreach ($extraPreloadAliases as $key => $path) {
				$uri = $host . $path;
				$json = file_get_contents($uri);
				if (is_string($json)) {
					$strData .= "\n".'$preload.'.$key.' = '.$json.";\n";
					usleep(250);
				}
			}
		}
		jsonstyles_write_snippet('core-data.json', $strData);
	}

	function allNodeAliasTitles($recentOnly = false, $addTitles = true) {
		$data  = array();
		$query = db_select('node', 'n')->fields('n', array('nid'));

		$query->join('node_field_data', 'nd', "n.nid=nd.nid AND n.vid=nd.vid");
		if ($addTitles) {
			$query->addField('nd', 'title');
		}
		$query->addField('nd', 'changed');

		$query->join('url_alias', 'ua', "CONCAT('/node/',n.nid) = ua.source");
		$query->addField('ua', 'alias');

		$query->condition('nd.status', 0, '>');
		if ($recentOnly) {
			$oneDayAgo = time()-(24*60*60);
			$query->condition('nd.changed', $oneDayAgo, '>');
		}
		$result = $query->execute();
		if ($result) {
			$data = $result->fetchAll();
		}
		return $data;
	}

	function matchView($path = '') {
		$data = (object) array('valid' => false);
		if (is_string($path) && strlen($path) > 2) {
			$path  = trim($path);
			$parts = explode('.', $path);
			if (count($parts) > 1) {
				$ext  = array_pop($parts);
				$path = implode('.', $parts);
			}
			$query = db_select('node', 'n')->fields('n', array('nid'));

			$query->join('node_field_data', 'nd', "n.nid=nd.nid AND n.vid=nd.vid");
			$query->addField('nd', 'title');
			$query->addField('nd', 'changed');

			$query->join('url_alias', 'ua', "CONCAT('/node/',n.nid) = ua.source");
			$query->addField('ua', 'alias');

			$query->leftJoin('redirect', 'r', "r.redirect_redirect__uri = CONCAT('internal:',ua.alias)");
			$query->addField('r', 'redirect_source__path', 'source_path');
			$regex   = '[[:<:]]'.preg_replace('#[^a-z0-9]+#i', '#.*?#', $path).'[[:>:]]';
			$regex_2 = $regex.'(\.htm?)?';
			$query->where("ua.alias REGEXP '".$regex."' OR r.redirect_source__path REGEXP '".$regex_2."'");

			$result = $query->execute();
			if ($result) {
				$row = $result->fetch();
				if (is_object($row) && isset($row->nid) && $row->nid > 0) {
					$data        = $row;
					$data->valid = true;
				}
			}
		}
		$response = new JsonResponse($data);
		$response->send();
		exit;
	}

	protected function styleData() {
		$theme    = 'frontend';
		$settings = theme_get_setting($theme);
		
		$color    = \Drupal::configFactory()->getEditable('color.theme.'.$theme)->get('palette');
		if (empty($color) || !is_array($color)) {
			$theme_info = color_get_info($theme);
			if (isset($theme_info['schemes']['default'])) {
				$color = $theme_info['schemes']['default']['colors'];
			}
		}

		$data = array(
			'logo'                  => $settings['logo'],
			'image_caption_opacity' => $settings['image_caption_opacity'],
			'color'                 => $color,
		);
		return $data;
	}

	function styleView() {
		$data     = $this->styleData();
		$response = new JsonResponse($data);
		$response->send();
		exit;
	}

	function colorCssView() {
		$str_color   = 'color: %s;';
		$str_bgcolor = 'background-color: %s;';
		$str_opacity = 'opacity: %s;';
		$data        = $this->styleData();
		$map         = array(
			'bg'     => array(
				'prop'  => 'background-color',
				'paths' => array('ul.slides', 'body', '#top-nav')
			),
			'text'   => array(
				'prop'  => 'color',
				'paths' => array('#app', '#top-nav a')
			),
			'highlight' => array(
				'prop'     => 'color',
				'paths'    => array(
					'#app header .site-info',
					'#app .content-container h2',
					'#app .content-container h3',
					'#app .content-container h4',
					'#app .menu a.router-link-active')
			),
			'link'   => array(
				'prop'  => 'color',
				'paths' => array('#app a')
			),
			'caption_opacity' => array(
				'prop'           => 'background-opacity',
				'paths'          => array('#app .main-content figure:hover figcaption')
			)
		);
		$css_strs = array();

		$colors                    = $data['color'];
		$colors['caption_opacity'] = $data['image_caption_opacity'];

		foreach ($colors as $key => $val) {
			if (array_key_exists($key, $map)) {
				if (isset($map[$key])) {
					$item = $map[$key];
					$prop = $item['prop'];
					switch ($item['prop']) {
						case 'background-opacity':
							$prop  = 'background-color';
							$bgKey = 'caption_hover';
							$bgVal = null;
							if (isset($colors[$bgKey])) {
								$bgVal = $colors[$bgKey];
							} else if (isset($colors['text'])) {
								$bgVal = $colors['text'];
							}
							if (!empty($bgVal)) {
								$hx    = substr($bgVal, 1);
								$parts = str_split($hx, 2);
								if (count($parts) == 3) {
									$val = 'rgba('.hexdec($parts[0]).','.hexdec($parts[1]).','.hexdec($parts[2]).', '.(floatval($val)/100).')';
								}
							}
							break;
					}
					$css_props = $prop.':'.$val.';';
					if (is_array($item['paths'])) {
						$str_decl   = implode(', ', $item['paths']).'{'.$css_props.'}';
						$css_strs[] = $str_decl;
					}
					switch ($key) {
						case 'highlight':
							$css_props  = 'border-bottom: solid 1px '.$val.';';
							$str_decl   = '#app .menu a.router-link-active {'.$css_props.'}';
							$css_strs[] = $str_decl;
							break;
					}
				}

			}
		}
		header("Content-type: text/css");
		print implode("\n", $css_strs);
		exit;
	}

}
