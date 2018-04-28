<?php
define('FRONTEND_ALIASES', 'home|products|about|contact|info|work|terms|blog|sunglasses|sun|eyeglasses|spectacles|prodotti|optical');
/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */
if (isset($_SERVER['REQUEST_URI'])) {
	$uri = $_SERVER['REQUEST_URI'];
	if (strlen($uri) < 2 || preg_match('#^/('.FRONTEND_ALIASES.')#i', $uri)) {
		$override = false;
		if (isset($_GET['show'])) {
			$override = $_GET['show'] == 'raw';
		}
		if (!$override) {
			$vuefront = __DIR__ .'/vuejs.html';
			if (file_exists($vuefront)) {
				require_once $vuefront;
				exit;
			}
		}
	}
}
/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

$kernel = new DrupalKernel('prod', $autoloader);

$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
