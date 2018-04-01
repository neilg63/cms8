<?php
/**
 * @file
 */

namespace Drupal\jsonstyles\Controller;

use \Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\file\Entity\File;
use \Drupal\views\Views;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Class ContentController
 * @package Drupal\jsonstyles\Controller
 */

class ContentController extends ControllerBase {


  protected $styles = array();

  protected $settings;

  protected $nodeFields = [
    'nid' => ['multiple' => false, 'type' => 'int'],
    'title' => ['multiple' => false, 'type' => 'string'],
    'body'  => ['multiple' => false, 'type' => 'string'],
    'field_date'  => ['multiple' => false, 'type' => 'date'],
    'field_section' => ['multiple' => true, 'type' => 'paragraph'],
    'field_images' => ['multiple' => true, 'type' => 'image'],
    'field_image' => ['multiple' => false, 'type' => 'image'],
    'field_ecwid' => ['multiple' => false, 'type' => 'string'],
    'field_category' => ['multiple' => false, 'type' => 'term'],
    'field_tags' => ['multiple' => true, 'type' => 'term'],
    'field_weight' => ['multiple' => false, 'type' => 'int'],
    'changed' => ['multiple' => false, 'type' => 'int']
  ];

  protected $paraFields = [
    'field_title' => ['multiple' => false, 'type' => 'string'],
    'field_text' => ['multiple' => false, 'type' => 'string'],
    'field_images' => ['multiple' => true, 'type' => 'image'],
    'field_image' => ['multiple' => false, 'type' => 'image'],
    'field_media' => ['multiple' => false, 'type' => 'media'],
    'field_video' => ['multiple' => false, 'type' => 'media'],
    'field_link' => ['multiple' => false, 'type' => 'link'],
  ];

  public function __construct() {
    $this->styles = jsonstyles_fetch_stylers();
    $this->settings = \Drupal::config('jsonstyles.settings');
  }

  function home() {
    $result = $query = \Drupal::entityQuery('node')
      ->condition('type','slide_show')
      ->condition('status',1)
      ->condition('promote',1)
      ->range(0, 1)
      ->execute();
    $node = null;
    if (!empty($result) && is_array($result)) {
      $nid = (int) array_shift(array_values($result));
      if ($nid > 0) {
        $node = node_load($nid);
      }
    }
    $this->nodeJson($node);
  }

  function blogs() {
    $perPage = (int) $this->getSetting('blogs_per_page', 20);
    $this->blogListing($start, $perPage);
  }

  function productsFull() {
    $perPage = (int) $this->getSetting('products_per_page', 12);
    $this->products(0, $perPage);
  }
  

  function productsFullMore() {
    $perPage = (int) $this->getSetting('products_per_page', 12);
    $max = $perPage * 5;
    $this->products($perPage, $max);
  }

  function nodeFull($node) {
    $this->nodeJson($node);
  }

  protected function products($start = 0, $perPage = 12) {
    $items = $this->getProducts($start, $perPage);

    $data = new \StdClass;

    $data->valid = count($items) > 0;
    $data->num_items = count($items);

    $data->items = $items;

    $response = new JsonResponse($data);
    $response->send();
    exit;
  }

  protected function blogListing($start = 0, $perPage = 12) {
    $items = $this->getBlogs($start, $perPage);

    $data = new \StdClass;

    $data->valid = count($items) > 0;
    $data->num_items = count($items);

    $data->items = $items;

    $response = new JsonResponse($data);
    $response->send();
    exit;
  }

  protected function getProducts($start = 0, $perPage = 12) {
    $result = $query = \Drupal::entityQuery('node')
      ->condition('type','product')
      ->condition('status',1)
      ->sort('field_weight')
      ->range($start, $perPage)
      ->execute();
    return $this->loadNodesData($result);
  }

  protected function getBlogs($start = 0, $perPage = 20) {
    $result = $query = \Drupal::entityQuery('node')
      ->condition('type','blog')
      ->condition('status',1)
      ->sort('field_date', 'DESC')
      ->range($start, $perPage)
      ->execute();
    return $this->loadNodesData($result);
  }

  private function loadNodesData($result) {
    $nids = array();
    
    if (!empty($result)) {
      foreach ($result as $key => $val) {
        $nids[] = (int) $val;
      }
    }
    $nodes = node_load_multiple($nids);
    
    $data = array();
    $index = 0;
    foreach ($nodes as $nid => $node) {
      $item = $this->parseFields($node, $this->nodeFields);
      if (is_object($item)) {
        $item->path = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $item->nid);
        $this->rewriteDate($item);
        if (!empty($item->category)) {
          $filter = strtolower(trim($item->category));
        } else {
          $filter = 'none';
        }
        $item->filter = $filter;
        $data[] = $item;
        $index++;
      }
    }
    return $data;
  }

  private function rewriteDate($item) {
    if (isset($item->date) && !empty($item->date) && preg_match('#\d\d\d\-\d\d-\d\d#', $item->date)) {
      $dt = new \DateTime($item->date);
      $formatted = $dt->format("j M Y");
      $item->date_1 = $item->date;
      $item->date = $formatted;
    } else {
      $item->date = "";
      $item->date_1 = "";
    }
  }


  private function parseFields($entity, $fieldNames) {
    $item = new \StdClass;
    foreach ($fieldNames as $fieldName => $info) {
      $vals = null;
      if ($entity->hasField($fieldName)) {
        $vals = $entity->get($fieldName)->getValue();
      }
      $isField = strpos($fieldName, 'field_') == 0;
      $multiple = false;
      if (is_array($vals)) {
        $key = $this->translateFieldName($fieldName, $isField);
        if (!isset($info['type'])) {
          $info['type'] = 'default';
        }
        if (!isset($info['multiple']) || !$isField) {
          $info['multiple'] =  false;
        } else {
          $info['multiple'] =  (bool) $info['multiple'];
        }

        $values = $this->simplifyValue($vals,$info['type']);
        $item->{$key} = $this->parseValues($values, $info);
      }
    }
    $item->type = $entity->bundle();
    return $item;
  }

  protected function parseValues(array $vals = array(), array $info = array()) {
    $out = array();
    $index = 0;
    foreach ($vals as $val) {

      switch ($info['type']) {
        case 'term':
          $val = (int) $val;
          $out[] = $this->getTerm($val);
          break;
        case 'paragraph':
          $val = (int) $val;
          $section = $this->getSection($val, $index);
          if (!empty($section)) {
            $out[] = $section;
            $index++; 
          }
          break;
        case 'image':
          $out[] = $this->processImage($val);
          break;
        case 'link':
          $out[] = $this->processLink($val);
          break;
        case 'date':
          $out[] = $val;
          break;
        case 'int':
          $out[] = (int) $val;
          break;
        case 'bool':
          $out[] = (bool) $val;
          break;
        default:
          $out[] = $val;
          break;
      }
    }
    if ($info['multiple']) {
      return $out;
    } else if (count($out) > 0) {
      return $out[0];
    }
  }

  function productFull($node) {
    $this->nodeJson($node);
  }

  protected function nodeJson($node) {
    $data = $this->nodeData($node);
    $response = new JsonResponse($data);
    $response->send();
    exit;
  }

  protected function nodeData($node) {
    $data = new \StdClass;
    $data->valid = false;
    if (is_object($node)) {
      $data->valid = $node->id() > 0;
      if ($data->valid) {
        $data = $this->parseFields($node, $this->nodeFields);
        $path = '/node/' . $node->id();
        $data->path = \Drupal::service('path.alias_manager')->getAliasByPath($path);
        $data->valid = true;
      }
    }
    return $data;
  }

  private function simplifyValue(array $vals, $type = null) {
    $items = array();
    if (count($vals) > 0 && is_array($vals[0])) {
      switch ($type) {
        case 'image':
        case 'file':
        case 'media':
          $matchTarget = false;
          break;
        default:
          $matchTarget = true;
          break;
      }
      foreach ($vals as $index => $val) {
        if (isset($val['value'])) {
          $row = $val['value'];
        } else if ($matchTarget && isset($val['target_id'])) {
          $row = (int) $val['target_id'];
        } else {
          $row = $val;
        }
        if (!empty($row)) {
          $items[] = $row;
        }
      }
    }
    return $items;
  }

  private function translateFieldName($fieldName = "", $isField = false) { 
    if ($isField) {
      $key = preg_replace('#^field_#', '', $fieldName);
      switch ($key) {
        case 'section':
          $key .= 's';
          break;
      }
    } else {
      $key = $fieldName;
    }
    return $key;
  }

  private function processImage($row = array()) {
    $file = File::load($row['target_id']);
    $image = array();
    if (is_object($file)) {
      $uri = $file->getFileUri();
      $image = $this->processStyles($uri);
      unset($row['target_id']);
      $image['attributes'] = $row;
    }
    return $image;
  }

  protected function processLink($row = array()) {
    $link = ['url' => '', 'title' => $row['title']];
    $value = $row['uri'];
    if (strpos($value,'internal:') === 0) {
        $parts = explode(':',$value);
        array_shift($parts);
        $value = implode(':',$parts);
      }
    else if (strpos($value,'entity:') === 0) {
      $parts = explode(':',$value);
      array_shift($parts);
      $value = '/' . implode(':',$parts);
      $value = \Drupal::service('path.alias_manager')->getAliasByPath($value);
    }
    $link['url'] = $value;
    return $link;
  }

  protected function processStyles($uri) {
    $images = array();
    $image_set = array();
    $parts = explode('://',$uri);
    $type = 'image/jpeg';
    $picMode = true;
    if (count($parts)>1) {
      $ps = explode('.', $parts[1]);
      $ext = array_pop($ps);
      $ext = strtolower($ext);
      switch ($ext) {
        case 'jpg';
        case 'jpeg';
          break;
        case 'gif':
        case 'png':
        case 'svg':
          $type = 'image/' . $ext;
          $picMode = false;
          break;
        default:
          $type = 'other';
          $picMode = false;
          break;
      }
      if ($picMode) {
        foreach ($this->styles as $key => $style) {

          $uri = $style->buildUrl($parts[1]);
          $image_set[$key] = _jsonstyles_clean_uri($uri);
        }
      } else {
        $image_set['orig'] = file_create_url($uri);
      }
    }
    if ($type != 'other') {
      $image = array('sizes' => $image_set,'type' => $type,'picture' => $picMode);
    }
    return $image;
  }

  private function getSection($id = 0, $index = 0) {
    if ($id > 0) {
      $para = Paragraph::load($id);
      if (is_object($para)) {
        $section = $this->parseFields($para, $this->paraFields);
        if (is_object($section) && empty($section)) {
          
          $section->has_media = (isset($section->media) || isset($section->video));
        }
        $hasData = false;
        foreach ($section as $key => $val) {
          switch ($key) {
            case 'type':
              break;
            default:
              if (!$hasData) {
                $hasData = !empty($val) || is_numeric($val) || is_bool($val);
                if ($hasData) {
                  break;
                }
              }
              break;
          }
        }
        if ($hasData) {
          $section->delta = $index;
          return $section;
        }
      }
    }
  }

  private function getTerm($tid = 0) {
    if ($tid > 0) {
      $entity = Term::load($tid);
      if (is_object($entity)) {
        $map = $entity->get('name')->getValue();
        $item = $this->simplifyValue($map);
        if (!empty($item) && is_array($item)) {
          return array_shift($item);
        }
      }
    }
  }

  private function getSetting($name, $default = null) {
    if (is_object($this->settings)) {
      $value = $this->settings->get($name);
    } else {
      $value = null;
    }
    if (is_null($value)) {
      $value = $default;
    }
    return $value;
  }

}
