<?php

declare(strict_types=1);

namespace Drupal\localgov_char_count\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a LocalGov Character Counter form.
 */
class CharacterCounterSettingsForm extends FormBase {

  /**
   * Default title length.
   *
   * @var int
   */
  const DEFAULT_TITLE_LENGTH = 60;

  /**
   * Default summary length.
   *
   * @var int
   */
  const DEFAULT_SUMMARY_LENGTH = 160;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'localgov_char_count.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'localgov_char_count_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $field_manager = \Drupal::service('entity_field.manager');

    $config = $this->config(static::SETTINGS);

    // Determine which fields to apply character counting to.
    $char_count_field_names = [
      'title' => 'Title',
      'body' => 'Summary',
      'localgov_subsites_summary' => 'Subsites summary',
    ];
    $char_count_fields = [];
    $node_types = $entity_type_manager->getStorage('node_type')->loadMultiple();
    $field_map = $field_manager->getFieldMap();
    foreach ($char_count_field_names as $field_name => $label) {
      if (isset($field_map['node'][$field_name]['bundles'])) {
        foreach ($field_map['node'][$field_name]['bundles'] as $bundle) {
          if (str_starts_with($bundle, 'localgov_')) {
            $char_count_fields[$bundle][$field_name] = $label;
          }
        }
      }
    }

    // Build form.
    $form = [
      '#tree' => TRUE,
    ];
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('This form allows you to easily apply character counting to title and summary fields for LocalGov Drupal content types. If you need more control than this form allows, it\'s possible to configure each field individually. For further information, see the docs of the <a href="https://www.drupal.org/project/textfield_counter">Textfield Counter module</a>.'),
    ];
    $form['lengths'] = [
      '#type' => 'details',
      '#title' => $this->t('Field lengths'),
      '#description' => $this->t('Set the length to be applied to text fields.'),
      '#open' => TRUE,

    ];
    $form['lengths']['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Title length'),
      '#default_value' => $config->get('title_length') ?? static::DEFAULT_TITLE_LENGTH,
    ];
    $form['lengths']['summary_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Summary length'),
      '#default_value' => $config->get('summary_length') ?? static::DEFAULT_SUMMARY_LENGTH,
    ];
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields to apply character counting to.'),
      '#description' => $this->t('Checked fields will have character counting added to them if it already set. Unchecked fields will remove character counting if already setup.'),
      '#open' => TRUE,
    ];
    foreach ($char_count_fields as $bundle => $fields) {
      $form['fields'][$bundle] = [
        '#type' => 'checkboxes',
        '#title' => $node_types[$bundle]->label(),
        '#options' => $fields,
        '#default_value' => array_keys($fields),
      ];
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();


    $x=0;
  }

}
