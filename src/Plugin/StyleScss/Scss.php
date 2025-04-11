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
 *   label = @Translation("Scss and JS"),
 *   description = @Translation("Content Scss, Css and JS")
 * )
 */
class Scss extends StyleScssPluginBase {
  
  public static function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
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
      '#attributes' => [
        'class' => [
          'codemirror',
          'lang_scss'
        ]
      ],
      '#description' => 'automatique import mixin and variable for current theme. @see wbu-atomique'
    ];
    $form['file_js'] = [
      '#type' => 'textarea',
      '#title' => $this->t(' Custom js '),
      '#default_value' => $this->configuration['file_js'],
      '#rows' => '30',
      '#attributes' => [
        'class' => [
          'codemirror',
          'lang_js'
        ]
      ],
      "#description" => 'Vous pouvez ajouter les mixins et les librairies inclut dans @stephane888/wbu-atomique<br>
<br>
<b>Example de code valide :</b>
<pre>
Drupal.behaviors.open_coloris = {
  attach: function (context, settings) {
    // "settings" contient les variables provenant de Drupal.
    // On se rassure que l\'element est present dans le "context".    
    if (context.querySelectorAll && context.querySelectorAll(".static-format.field__items")) {
      // On se rasure que l\'element est lu 1 seule foix.
      // "open_coloris_key_unique" doit etre ajuster, elle doit etre unique par element.
      // ".static-format.field__items" represente le selecteur css.
      once("open_coloris_key_unique", ".static-format.field__items", context).forEach((item) => {
        // console.log(" item element : ", item);
        const link = item.querySelector(".open_coloris");
        if (link) {
          link.addEventListener(
            "click",
            () => {
              AddClass(item, link);
            },
            false
          );
        }
      });
      const AddClass = (item, link) => {
        item.classList.add("show-all");
        link.style.display = "none";
      };
    }
  },
};
</pre>
<p>Your content will be contained within "(function (Drupal, once) {" and "})(window.Drupal, window.once);"</p>
'
    ];
    $form['#attached']['library'][] = 'generate_style_theme/codemirror_admin';
  }
  
  /**
   * scss_field
   * Retourne le contenu de la scss.
   */
  public function getScss() {
    return $this->configuration['scss_field'];
  }
  
  public function getJs() {
    return $this->configuration['file_js'];
  }
}