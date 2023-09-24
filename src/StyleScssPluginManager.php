<?php

namespace Drupal\layout_custom_style;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\generate_style_theme\Services\ManageFileCustomStyle;

/**
 * StyleScss plugin manager.
 */
class StyleScssPluginManager extends DefaultPluginManager {
  
  /**
   *
   * @var ManageFileCustomStyle
   */
  protected $ManageFileCustomStyle;
  
  /**
   * Constructs StyleScssPluginManager object.
   *
   * @param \Traversable $namespaces
   *        An object that implements \Traversable which contains the root paths
   *        keyed by the corresponding namespace to look for plugin
   *        implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *        Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *        The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ManageFileCustomStyle $ManageFileCustomStyle) {
    parent::__construct('Plugin/StyleScss', $namespaces, $module_handler, 'Drupal\layout_custom_style\StyleScssInterface', 'Drupal\layout_custom_style\Annotation\StyleScss');
    $this->alterInfo('style_scss_info');
    $this->setCacheBackend($cache_backend, 'style_scss_plugins');
    $this->ManageFileCustomStyle = $ManageFileCustomStyle;
  }
  
  /**
   * Cette fonction doit s'excuter en administration.
   */
  public function build(&$build, array $storage) {
    $this->addIdHtmlInSection($build, $storage);
  }
  
  /**
   * Permet de construire une configuration en se basant sur tous les plugins
   * existant.
   */
  public function buildConfiguration(array &$form, FormStateInterface $form_state, array $storage) {
    $plugins = $this->getDefinitions();
    foreach ($plugins as $plugin) {
      if (empty($storage[$plugin['id']]))
        $storage[$plugin['id']] = [];
      /**
       *
       * @var \Drupal\layout_custom_style\StyleScssPluginBase $instance
       */
      $instance = $this->createInstance($plugin['id'], $storage[$plugin['id']]);
      
      $form[$plugin['id']] = [
        '#type' => 'details',
        '#title' => $instance->label(),
        // '#description' => $instance->description(),
        '#open' => false
      ];
      $instance->buildConfigurationForm($form[$plugin['id']], $form_state);
    }
  }
  
  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param array $storage
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$storage) {
    // check #id
    if (empty($storage['id'])) {
      /**
       *
       * @var \Drupal\layout_builder\Form\ConfigureSectionForm $object
       */
      $object = $form_state->getFormObject();
      /**
       *
       * @var \Drupal\Core\Layout\LayoutInterface $layout
       */
      $layout = $object->getCurrentLayout();
      $id = Html::getUniqueId($layout->getPluginId() . '--' . rand(100, 9999));
      $storage['id'] = $id;
    }
    // save scss in theme.
    $plugins = $this->getDefinitions();
    foreach ($plugins as $plugin) {
      if (empty($storage[$plugin['id']])) {
        $storage[$plugin['id']] = [];
      }
      /**
       *
       * @var \Drupal\layout_custom_style\StyleScssPluginBase $instance
       */
      $instance = $this->createInstance($plugin['id'], $storage[$plugin['id']]);
      $instance->submitConfigurationForm($form, $form_state);
      $storage[$plugin['id']] = $instance->getConfiguration();
      $scss = '#' . $storage['id'] . ' {';
      $scss .= $instance->getScss();
      $scss .= '}';
      $js = '';
      $key = $storage['id'];
      $this->ManageFileCustomStyle->saveStyle($key, $plugin['provider'], $scss, $js);
    }
  }
  
  /**
   * Verifie si l'entité a un #id sinon on l'ajoute.
   */
  protected function addIdHtmlInSection(&$build, &$storage) {
    if (!empty($storage['id'])) {
      $build['#attributes']['id'] = $storage['id'];
    }
  }
  
}
