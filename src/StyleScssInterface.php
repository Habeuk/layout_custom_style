<?php

namespace Drupal\layout_custom_style;

/**
 * Interface for style_scss plugins.
 */
interface StyleScssInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
