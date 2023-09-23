<?php

namespace Drupal\layout_custom_style\Plugin\StyleScss;

use Drupal\layout_custom_style\StyleScssPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the style_scss.
 * This plugins add text_field in configuration.
 *
 * @StyleScss(
 *   id = "scss",
 *   label = @Translation("Scss"),
 *   description = @Translation("Content Scss and Css")
 * )
 */
class Scss extends StyleScssPluginBase {
  
  public function defaultConfiguration() {
    return [
      'scss' => 'Test OK'
    ];
  }
  
  /**
   * Cette fonction est utilisé pour construire le rendu.
   *
   * @param array $build
   */
  public function build(array $build) {
    // Nothing
    return $build;
  }
  
  /**
   * --
   */
  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['scss_field'] = [
      '#type' => 'textarea',
      '#title' => 'Scss',
      '#default_value' => $this->configuration['scss_field'],
      '#description' => 'automatique import mixin and variable for current theme. @see wbu-atomique'
    ];
  }
  
  /**
   * Retourne le contenu de la scss.
   */
  public function getScss() {
    return $this->configuration['scss_field'];
  }
  
}