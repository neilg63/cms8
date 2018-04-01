<?php
/**
 * @file
 */

namespace Drupal\ecwid\Controller;

use \Drupal\Core\Controller\ControllerBase;

use \Drupal\views\Views;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Drupal\ecwid\EcwidOAuth2Provider as EcwidOAuth2Provider;
/**
 * Class ContentController
 * @package Drupal\ecwid\Controller
 */

class EcwidController extends ControllerBase {

  const API_URL = 'https://app.ecwid.com/api/v3';

  protected $storeId = '13508575';

  protected $token = 'public_XL2FgpiM8xj1d1acSSQHpquUw1hL22TJ';

  function listProducts() {
    return "X";
  }

  function products() {
    $data = $this->getProducts();
    return new JsonResponse($data);
  }

  function getProducts() {
    $data = array();
    $this->authenticate();
    return $data;
  }


  protected function authenticate() {
    $client = \Drupal::httpClient();

    $url = $this->storeRequestUrl('products',[
      'limit' => 10,
      'category' => 0
    ]);
    
    $response = $client->get($url);
    var_dump($response);exit;

  }

  protected function storeRequestUrl($verb = 'profile', array $params = array()) {
    $defaultParams = [
      'token' => $this->token
    ];
    $params += $defaultParams;
    $queryString = '?' . http_build_query($params);
    return self::API_URL . '/' . $this->storeId . '/' . $verb . $queryString;
  }


}