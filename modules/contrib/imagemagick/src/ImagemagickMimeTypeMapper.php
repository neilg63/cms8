<?php

namespace Drupal\imagemagick;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Maps MIME types to file extensions.
 */
class ImagemagickMimeTypeMapper {

  /**
   * The extension MIME type guesser.
   *
   * @var \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser
   */
  protected $mimeTypeGuesser;

  /**
   * The MIME types mapping array after going through the module handler.
   *
   * Copied via Reflection from
   * \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser.
   *
   * @var array
   */
  protected $mapping;

  /**
   * Constructs an ImagemagickMimeTypeMapper object.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $extension_mimetype_guesser
   *   The extension MIME type guesser.
   */
  public function __construct(MimeTypeGuesserInterface $extension_mimetype_guesser) {
    $this->mimeTypeGuesser = $extension_mimetype_guesser;
  }

  /**
   * Returns the MIME types mapping array from ExtensionMimeTypeGuesser.
   *
   * Copied via Reflection from
   * \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser.
   *
   * @return array
   *   The MIME types mapping array.
   */
  protected function getMapping() {
    if (!$this->mapping) {
      // Guess a fake file name just to ensure the guesser loads any mapping
      // alteration through the hooks.
      $this->mimeTypeGuesser->guess('fake.png');
      // Use Reflection to get a copy of the protected $mapping property in the
      // guesser class. Get the proxied service first, then the actual mapping.
      $reflection = new \ReflectionObject($this->mimeTypeGuesser);
      $proxied_service = $reflection->getProperty('service');
      $proxied_service->setAccessible(TRUE);
      $service = $proxied_service->getValue(clone $this->mimeTypeGuesser);
      $reflection = new \ReflectionObject($service);
      $reflection_mapping = $reflection->getProperty('mapping');
      $reflection_mapping->setAccessible(TRUE);
      $this->mapping = $reflection_mapping->getValue(clone $service);
    }
    return $this->mapping;
  }

  /**
   * Returns the appropriate extensions for a given MIME type.
   *
   * @param string $mimetype
   *   A MIME type.
   *
   * @return string[]
   *   An array of file extensions matching the MIME type, without leading dot.
   */
  public function getExtensionsForMimeType($mimetype) {
    $mapping = $this->getMapping();
    if (!in_array($mimetype, $mapping['mimetypes'])) {
      return [];
    }
    $key = array_search($mimetype, $mapping['mimetypes']);
    $extensions = array_keys($mapping['extensions'], $key, TRUE);
    sort($extensions);
    return $extensions;
  }

  /**
   * Returns known MIME types.
   *
   * @return string[]
   *   An array of MIME types.
   */
  public function getMimeTypes() {
    return array_values($this->getMapping()['mimetypes']);
  }

}
