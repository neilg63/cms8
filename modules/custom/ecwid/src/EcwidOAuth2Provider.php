<?php
namespace Drupal\ecwid;

use \League\OAuth2\Client\Token\AccessToken as AccessToken;
class EcwidOAuth2Provider extends \League\OAuth2\Client\Provider\AbstractProvider
{

  public function __construct(array $config) {
    var_dump($config);exit;
  }
  
  public $uidKey = 'store_id';
  
  public function urlAuthorize() {
      return 'https://my.ecwid.com/api/oauth/authorize';
  }
  public function urlAccessToken() {
      return 'https://my.ecwid.com/api/oauth/token';
  }

  public function urlUserDetails(AccessToken $token) {}
  
  public function userDetails($response, AccessToken $token) {}    

}