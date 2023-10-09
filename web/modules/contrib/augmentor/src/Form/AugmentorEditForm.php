<?php

namespace Drupal\augmentor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an edit form for augmentor_entities.
 *
 * @internal
 */
class AugmentorEditForm extends AugmentorFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $augmentor = NULL) {
    $form = parent::buildForm($form, $form_state, $augmentor);
    $form['#title'] = $this->t('Edit %label augmentor', ['%label' => $this->augmentor->label()]);
    $form['actions']['submit']['#value'] = $this->t('Update augmentor');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAugmentor($augmentor) {
    return $this->augmentorManager->getAugmentor($augmentor);
  }

}
