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

  protected $storeId = '13508240';

  protected $token = 'public_XL2FgpiM8xj1d1acSSQHpquUw1hL22TJ';

  public function products() {

    return 'x';
  }


  public function ListProducts() {
    $settings      = \Drupal::config('ecwid.settings');
    $ecwid_id = $settings->get('store_id');
    if (empty($ecwid_id)) {
      $ecwid_id = '13508240';
    }
    $products = $settings->get('products');
    if (empty($products) || !is_array($products)) {
      $products = array();
    }
    if (!empty($products)) {
      $products = $this->formatProductItems($products);
    }
    return [
      '#theme' => 'jsload',
      '#ecwid_id' => $ecwid_id,
      '#products' => $products,
      '#has_products' => count($products) > 0,
      '#attached' => ['library' => ['ecwid/sync']]
    ];
  }

  function getProducts() {
    $data = array();
    $this->authenticate();
    return $data;
  }

  function saveProducts() {
    $data = new \StdClass;
    $data->items = [];
    $data->saved = false;
    $items = \Drupal::request()->request->get('items');
    $config = \Drupal::service('config.factory')->getEditable('ecwid.settings') ; 
    if (is_array($items) && !empty($items)) {
      foreach ($items as $item) {
        if ($this->isProductItem($item)) {
          $data->items[] = $item;
        }
      }
      $config->set('products', $data->items)->save();
      $data->saved = true;
    }
    $response = new JsonResponse($data);
    return $response->send();
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

  private function isProductItem(&$item) {
    $valid = false;
    if (is_array($item)) {
      if (array_key_exists('id',$item) && array_key_exists('price',$item) && array_key_exists('title',$item)) {
        if (is_numeric($item['price'])) {
          $item['price'] = (float) $item['price'];
          $valid = true;
        }
      }
    }
    return $valid;
  }

  private function formatProductItems(array $products = array()) {
    return ecwid_format_product_items($products);
  }

}