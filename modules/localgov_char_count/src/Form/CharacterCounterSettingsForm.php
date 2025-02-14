<?php

declare(strict_types=1);

namespace Drupal\localgov_char_count\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a LocalGov Character Counter form.
 */
class CharacterCounterSettingsForm extends ConfigFormBase {

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
   * The Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected EntityFieldManager $entityFieldManager;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *    The entity type manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManager $entity_field_manager,
    EntityTypeManager $entity_type_manager,
    protected $typed_config_manager = NULL,

  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'localgov_char_count_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    // Determine which fields to apply character counting to.
    $char_count_field_names = [
      'title' => 'Title',
      'body' => 'Summary',
      'localgov_subsites_summary' => 'Subsites summary',
    ];
    $char_count_fields = [];
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $field_map = $this->entityFieldManager->getFieldMap();
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
    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Field configuration'),
      '#open' => TRUE,

    ];
    $form['config']['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Title length'),
      '#description' => $this->t('The maximum number of characters allowed in the title field.'),
      '#default_value' => $config->get('title_length') ?? static::DEFAULT_TITLE_LENGTH,
      '#required' => TRUE,
    ];
    $form['config']['summary_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Summary length'),
      '#description' => $this->t('The maximum number of characters allowed in the summary field.'),
      '#default_value' => $config->get('summary_length') ?? static::DEFAULT_SUMMARY_LENGTH,
      '#required' => TRUE,
    ];
    $form['config']['status_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Status message'),
      '#description' => $this->t('Enter the message to show to users indicating the current status of the character count. The variables <strong>@maxlength</strong>, <strong>@current_length</strong> and <strong>@remaining_count</strong> can be used in this field. For the real-time counter to work, said variables must be wrapped in HTML span tags with their classes respectively set to <strong>maxlength_count</strong>, <strong>current_count</strong> and <strong>remaining_count</strong>.'),
      '#default_value' => $config->get('status_message') ?? '@current_length / @maxlength characters',
      '#required' => TRUE,
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

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Apply configuration changes');

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

    // Save settings.
    $config = $this->config(static::SETTINGS);
    $config
      ->set('title_length', $values['config']['title_length'])
      ->set('summary_length', $values['config']['summary_length'])
      ->set('status_message', $values['config']['status_message'])
      ->save();

    // Determine what's changing.
    $add = [];
    $remove = [];
    foreach ($values['fields'] as $bundle => $fields) {
      if ($fields) {
        $add[$bundle] = $fields;
      }
      else {
        $remove[$bundle] = $fields;
      }
    }

    $x=0;
  }
}
