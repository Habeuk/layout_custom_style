<?php

namespace Drupal\layout_custom_style;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\generate_style_theme\Services\ManageFileCustomStyle;
use Drupal\layout_builder\Form\ConfigureSectionForm;
use Drupal\Core\Form\FormState;
use Drupal\Component\Utility\Html;

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
    $this->addClassHtmlInSection($build, $storage);
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
      // Merge config with defaultConfiguration
      $storage[$plugin['id']] += $plugin['class']::defaultConfiguration();
      /**
       *
       * @var \Drupal\layout_custom_style\StyleScssPluginBase $instance
       */
      $instance = $this->createInstance($plugin['id'], $storage[$plugin['id']]);
      
      $form[$plugin['id']] = [
        '#type' => 'details',
        '#title' => $instance->label(),
        '#description' => $instance->description() . "BUG : Editer scss dans un nouveau onglet",
        '#open' => false
      ];
      $instance->buildConfigurationForm($form[$plugin['id']], $form_state);
      //
      // /**
      // *
      // * @var \Drupal\layout_builder\Form\ConfigureSectionForm $object
      // */
      // $object = $form_state->getFormObject();
      // $typePlugin = $object->getSectionStorage()->getPluginId();
      // if ($typePlugin == 'overrides') {
      // /**
      // *
      // * @var \Drupal\Core\Plugin\Context\EntityContext $entityContext
      // */
      // $entityContext = $object->getSectionStorage()->getContext('entity');
      // /**
      // *
      // * @var \Drupal\Core\Entity\EntityInterface $ContextValue
      // */
      // $ContextValue = $entityContext->getContextValue();
      // // dd($object);
      // }
    }
  }
  
  /**
   * On a un soucis de sauvegarde de la configuration, elle est sauvegardée dans
   * l'entité file_style et aussi dans la configuration de la section( layout),
   * il
   * faudroit voir comment sauvegarder cela de manière unique.
   * ( l'ideale serait de sauvegarder uniquement en bd, afin d'alleger le
   * fichier de configuration pour la version 2x).
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param array $storage
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$storage) {
    $this->saveConfiguration($form, $storage, $form_state);
  }
  
  /**
   * Permet à des modules externes de mettre à jour les styles de layouts.
   */
  public function saveConfiguration(array $form, array &$storage, $form_state = null) {
    if ($form_state)
      $this->getDefaultId($storage, $form_state);
    // Save scss/js in theme.
    $key = $storage['id'];
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
      // Si $form_state est definit, alors les valeurs des champs y sont
      // present, on met à jour la configuration.
      if ($form_state) {
        $instance->submitConfigurationForm($form, $form_state);
        $storage[$plugin['id']] = $instance->getConfiguration();
      }
      $contentScss = $instance->getScss();
      $contentJs = $instance->getJs();
      // get scss.
      $scss = '';
      if (!empty($contentScss)) {
        $scss = "\n." . $key . " {\n";
        $scss .= $instance->getScss();
        $scss .= "\n}\n";
      }
      // get js.
      $js = '';
      if (!empty($contentJs)) {
        $js = "\n(function (Drupal, once) {\n";
        $js .= $contentJs;
        $js .= "\n})(window.Drupal, window.once);\n";
      }
      if (!empty($scss) || !empty($js)) {
        $this->ManageFileCustomStyle->saveStyle($key, $plugin['provider'], $scss, $js);
      }
      else {
        $this->ManageFileCustomStyle->deleteStyle($key, $plugin['provider']);
      }
    }
  }
  
  /**
   *
   * @param array $storage
   * @param FormStateInterface $form_state
   */
  protected function getDefaultId(array &$storage, FormStateInterface $form_state) {
    /**
     *
     * @var ConfigureSectionForm $object
     */
    $object = $form_state->getFormObject();
    if ($object) {
      $typePlugin = $object->getSectionStorage()->getPluginId();
      /**
       * Les affichages par defaut.
       */
      if ($typePlugin == 'defaults') {
        if (empty($storage['id'])) {
          /**
           * On doit etre dans le type defautls et dans ce cas le context
           * display
           * existe.
           *
           * @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $LayoutEntityViewDipsly
           */
          $LayoutEntityViewDipsly = $object->getSectionStorage()->getContext('display')->getContextValue();
          $key = str_replace(".", "__", $LayoutEntityViewDipsly->id());
          $delta = \Drupal::routeMatch()->getParameter('delta');
          if ($delta)
            $key = $key . '__' . $delta;
          $storage['id'] = $key;
        }
      }
      /**
       * Les affichages surchargés ne tiennent pas compte du display, mais de
       * l'id.
       * De plus, seuls l'affichage par defaut peut etre surcharger.
       */
      elseif ($typePlugin == 'overrides') {
        if (empty($storage['id']))
          $storage['id'] = '';
        /**
         *
         * @var \Drupal\Core\Plugin\Context\EntityContext $entityContext
         */
        $entityContext = $object->getSectionStorage()->getContext('entity');
        /**
         *
         * @var \Drupal\Core\Entity\EntityInterface $ContextValue
         */
        $ContextValue = $entityContext->getContextValue();
        if ($ContextValue instanceof \Drupal\Core\Entity\EntityInterface) {
          $id = $ContextValue->id();
          // On opte pour etre strict afin de reduire les erreurs humaines.
          if (!str_contains($storage['id'], '---' . $id)) {
            $bundle = $ContextValue->bundle() ? $ContextValue->bundle() : $ContextValue->getEntityTypeId();
            $key = $ContextValue->getEntityTypeId() . '__' . $bundle . '---' . $id;
            $delta = \Drupal::routeMatch()->getParameter('delta');
            if ($delta)
              $key = $key . '__' . $delta;
            $storage['id'] = $key;
          }
        }
        else {
          throw new \Exception('ContextValue is not an instance of \Drupal\Core\Entity\EntityInterface');
        }
      }
      else {
        throw new \Exception("Type pluginId 'SectionStorage' not found");
      }
      //
      if (empty($storage['id_html'])) {
        $storage['id_html'] = Html::getUniqueId($storage['id']);
      }
      return $storage['id'];
    }
  }
  
  /**
   * Permet à des modules externes de sauvegarder des styles.
   *
   * @param array $storage
   */
  public function addConfigs(array $storage) {
    if (!empty($storage['id'])) {
      $plugins = $this->getDefinitions();
      foreach ($plugins as $plugin) {
        if (!empty($storage[$plugin['id']])) {
          /**
           *
           * @var \Drupal\layout_custom_style\StyleScssPluginBase $instance
           */
          $instance = $this->createInstance($plugin['id'], $storage[$plugin['id']]);
          $storage[$plugin['id']] = $instance->getConfiguration();
          $contentScss = $instance->getScss();
          $key = $storage['id'];
          if (!empty($contentScss)) {
            $scss = '.' . $storage['id'] . ' {';
            $scss .= $instance->getScss();
            $scss .= '}';
            $js = '';
            $this->ManageFileCustomStyle->saveStyle($key, $plugin['provider'], $scss, $js);
          }
          else {
            $this->ManageFileCustomStyle->deleteStyle($key, $plugin['provider']);
          }
        }
      }
    }
  }
  
  /**
   * Ajoute la valeur $storage['id'] dans la class.
   * La classe est adapté car on peut avoir un model de teaser qui s'applique
   * que plusieurs contenu. example les teasers d'articles.
   */
  protected function addClassHtmlInSection(&$build, &$storage) {
    if (!empty($storage['id'])) {
      if (empty($build['#attributes']['class']))
        $build['#attributes']['class'] = [];
      $build['#attributes']['class'][] = $storage['id'];
    }
    if (!empty($storage['id_html'])) {
      $build['#attributes']['id'] = $storage['id_html'];
    }
  }
}
