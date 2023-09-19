<?php

namespace Drupal\layout_custom_style;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * StyleScss plugin manager.
 */
class StyleScssPluginManager extends DefaultPluginManager {
  
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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/StyleScss', $namespaces, $module_handler, 'Drupal\layout_custom_style\StyleScssInterface', 'Drupal\layout_custom_style\Annotation\StyleScss');
    $this->alterInfo('style_scss_info');
    $this->setCacheBackend($cache_backend, 'style_scss_plugins');
  }
  
  /**
   * Permet de construire une configuration en se basant sur tous les plugins
   * existant.
   */
  public function buildConfiguration(array &$form, FormStateInterface $form_state) {
    $plugins = $this->getDefinitions();
    foreach ($plugins as $i => $plugin) {
      \Stephane888\Debug\debugLog::kintDebugDrupal($plugin, 'buildConfiguration', true);
      // $this->createInstance($plugin_id)
    }
  }
  
}
