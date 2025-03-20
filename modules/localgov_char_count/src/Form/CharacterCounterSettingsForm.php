<?php

declare(strict_types=1);

namespace Drupal\localgov_char_count\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\WidgetPluginManager;
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
   * Default status message.
   *
   * @var string
   */
  const DEFAULT_STATUS_MESSAGE = '<span class="current_count">@current_length</span> / <span class="maxlength_count">@maxlength</span> characters';

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'localgov_char_count.settings';

  /**
   * The Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected EntityDisplayRepository $entityDisplayRepository;

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
   * The Widget Plugin Manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected WidgetPluginManager $widgetPluginManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_plugin_manager
   *   The widget plugin manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityDisplayRepository $entity_display_repository,
    EntityFieldManager $entity_field_manager,
    EntityTypeManager $entity_type_manager,
    WidgetPluginManager $widget_plugin_manager,
    protected $typed_config_manager = NULL,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->widgetPluginManager = $widget_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.widget'),
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
      '#default_value' => $config->get('status_message') ?? static::DEFAULT_STATUS_MESSAGE,
      '#required' => TRUE,
    ];
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields to apply character counting to.'),
      '#description' => $this->t('Checking a field will add character counting to it if not already configured. Unchecking a field will remove character counting from it.'),
      '#open' => TRUE,
    ];
    foreach ($char_count_fields as $bundle => $fields) {
      $default_value = [];
      $form_display = $this->entityDisplayRepository->getFormDisplay('node', $bundle, 'default');
      foreach ($fields as $field => $label) {
        $component = $form_display->getComponent($field);
        if ($component && $this->isTextCounterComponent($component)) {
          $default_value[] = $field;
        }
      }
      $form['fields'][$bundle] = [
        '#type' => 'checkboxes',
        '#title' => $node_types[$bundle]->label(),
        '#options' => $fields,
        '#default_value' => $default_value,
      ];
    }

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Apply configuration changes');

    return $form;
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

    // Determine which field widgets are changing.
    foreach ($values['fields'] as $bundle => $fields) {
      $form_display = $this->entityDisplayRepository->getFormDisplay('node', $bundle, 'default');
      foreach ($fields as $field => $status) {
        if ($component = $form_display->getComponent($field)) {

          // Convert to text field counter.
          if ($this->isTextComponent($component) && $status !== 0) {
            $component = $this->convertToTextCounter($field, $component);
            $form_display->setComponent($field, $component);
            $form_display->save();
          }

          // Convert to text field.
          if ($this->isTextCounterComponent($component) && $status === 0) {
            $component = $this->convertToText($component);
            $form_display->setComponent($field, $component);
            $form_display->save();
          }
        }
      }
    }
  }

  /**
   * Is text field component?
   *
   * @param array $component
   *   The component to check.
   *
   * @return bool
   *   TRUE if the component is a text field component, FALSE otherwise.
   */
  protected function isTextComponent(array $component): bool {
    return match ($component['type']) {
      'string_textarea',
      'string_textfield',
      'text_textarea',
      'text_textarea_with_summary',
      'text_textfield' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Is text field counter component?
   *
   * @param array $component
   *   The component to check.
   *
   * @return bool
   *   TRUE if the component is a text field counter component, FALSE otherwise.
   */
  protected function isTextCounterComponent(array $component): bool {
    return match ($component['type']) {
      'string_textarea_with_counter',
      'string_textfield_with_counter',
      'text_textarea_with_counter',
      'text_textarea_with_summary_and_counter',
      'text_textfield_with_counter' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Convert text field counter component config to text field.
   *
   * @param array $component
   *   The component to convert.
   *
   * @return array
   *   The converted component.
   */
  protected function convertToText(array $component): array {

    if ($component['type'] === 'text_textarea_with_summary_and_counter') {
      $component['type'] = 'text_textarea_with_summary';
      $field_settings = $this->widgetPluginManager->getDefaultSettings($component['type']);
      $field_settings['show_summary'] = TRUE;
    }
    else {
      $component['type'] = str_replace('_with_counter', '', $component['type']);
      $field_settings = $this->widgetPluginManager->getDefaultSettings($component['type']);
    }
    $component['settings'] = $field_settings;

    return $component;
  }

  /**
   * Convert text field component config to text counter field.
   *
   * @param string $field
   *   Name of field to convert.
   * @param array $component
   *   The component to convert.
   *
   * @return array
   *   The converted component.
   */
  protected function convertToTextCounter(string $field, array $component): array {
    $config = $this->config(static::SETTINGS);

    if ($component['type'] === 'text_textarea_with_summary') {
      $component['type'] = 'text_textarea_with_summary_and_counter';
      $field_settings = $this->widgetPluginManager->getDefaultSettings($component['type']);
      $field_settings['summary_maxlength'] = $config->get('summary_length');
      $field_settings['show_summary'] = TRUE;
    }
    else {
      $component['type'] .= '_with_counter';
      $field_settings = $this->widgetPluginManager->getDefaultSettings($component['type']);
      if ($field === 'title') {
        $field_settings['maxlength'] = $config->get('title_length');
      }
      else {
        $field_settings['maxlength'] = $config->get('summary_length');
      }
    }

    $field_settings['count_html_characters'] = FALSE;
    $field_settings['count_only_mode'] = TRUE;
    $field_settings['js_prevent_submit'] = FALSE;
    $field_settings['textcount_status_message'] = $config->get('status_message');
    $component['settings'] = $field_settings;

    return $component;
  }

}
