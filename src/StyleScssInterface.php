<?php

namespace Drupal\layout_custom_style;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for style_scss plugins.
 */
interface StyleScssInterface {
  
  /**
   * Returns the translated plugin label.
   *
   * @return string The translated title.
   */
  public function label();
  
  /**
   * Permet d'ajouter un #id unique au layout si c'est pas deja definit.
   * Afin d'encapsuler le style scss dans cette attribue.
   */
  public function build(array $build);
  
  /**
   * Permet de retouner la scss.
   */
  public function getScss();
  
  /**
   * Permet de recuperer les informations de la configuration.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state);
  
}
