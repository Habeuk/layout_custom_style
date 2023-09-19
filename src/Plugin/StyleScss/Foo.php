<?php

namespace Drupal\layout_custom_style\Plugin\StyleScss;

use Drupal\layout_custom_style\StyleScssPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the style_scss.
 * This plugins add text_field in configuration.
 *
 * @StyleScss(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description.")
 * )
 */
class Foo extends StyleScssPluginBase {
  
  public function defaultConfiguration() {
    return [
      'foo_example' => 'Test OK'
    ];
  }
  
  /**
   * Cette fonction est utilisé pour construire le rendu.
   *
   * @param array $build
   */
  public function build(array $build) {
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->configuration['foo_example']
    ];
    return $build;
  }
  
  /**
   * --
   */
  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['foo_example'] = [
      '#type' => 'textfield',
      '#title' => 'foo example',
      '#default_value' => $this->configuration['foo_example'],
      '#description' => 'example : 400px '
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->config()->set('foo_example', $form_state->getValue('foo_example'))->save();
  }
  
}
