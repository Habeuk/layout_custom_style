<?php

namespace Drupal\layout_custom_style;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Base class for style_scss plugins.
 */
abstract class StyleScssPluginBase extends PluginBase implements StyleScssInterface {
  /**
   * Config settings.
   *
   * @var string
   */
  const CONFIG = 'bootstrap_styles.settings';
  
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  
  /**
   * Constructs a StylePluginBase object.
   *
   * @param array $configuration
   *        A configuration array containing information about the plugin
   *        instance.
   * @param string $plugin_id
   *        The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *        The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *        The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function config() {
    return $this->configFactory->getEditable(static::CONFIG);
  }
  
}
