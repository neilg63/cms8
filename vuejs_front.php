<?php
/*

*/
define('FRONTEND_ALIASES', 'home|products|about|contact|info|work|terms|blog|sunglasses|sun|eyeglasses|spectacles|prodotti|optical');

function vuejs_front() {
  if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];

    if (strlen($uri) < 2 || preg_match('#^/('.FRONTEND_ALIASES.')#i', $uri)) {
      $override = false;
      if (isset($_GET['show'])) {
        $override = $_GET['show'] == 'raw';
      }
      if (!$override) {
        $vue_html_with_content = __DIR__ . '/files/snippets/vuejs.html';
        if (file_exists($vue_html_with_content)) {
          require_once $vue_html_with_content;
          exit;
        } else {
          $vuefront = __DIR__ .'/vuejs.html';
          if (file_exists($vuefront)) {
            require_once $vuefront;
            exit;
          }
        }
      }
    }
  }
}