<?php

namespace Drupal\Tests\localgov_char_count\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\localgov_char_count\Form\CharacterCounterSettingsForm;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the character count settings form.
 *
 * @group localgov_char_count
 */
class SettingsFormTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'localgov_char_count',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a LocalGov content type with title and body fields.
    $this->createContentType([
      'type' => 'localgov_page',
      'title' => 'LocalGov Page',
    ]);
  }

  /**
   * Test settings form.
   */
  public function testSettingsForm(): void {
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer nodes',
      'administer site configuration',
      'bypass node access',
      'localgov character counting admin',
    ]);
    $this->drupalLogin($admin_user);

    $title_counter_message = '0 / ' . CharacterCounterSettingsForm::DEFAULT_TITLE_LENGTH . ' characters';
    $summary_counter_message = '0 / ' . CharacterCounterSettingsForm::DEFAULT_SUMMARY_LENGTH . ' characters';

    // Enable character counting.
    $this->drupalGet(Url::fromRoute('localgov_char_count.character_counter_settings'));
    $this->getSession()->getPage()->hasUncheckedField('fields[localgov_page][title]');
    $this->getSession()->getPage()->hasUncheckedField('fields[localgov_page][body]');
    $this->getSession()->getPage()->checkField('fields[localgov_page][title]');
    $this->getSession()->getPage()->checkField('fields[localgov_page][body]');
    $this->getSession()->getPage()->pressButton('Apply configuration changes');
    $this->assertSession()->addressEquals(Url::fromRoute('localgov_char_count.character_counter_settings'));
    $this->getSession()->getPage()->hasCheckedField('fields[localgov_page][title]');
    $this->getSession()->getPage()->hasCheckedField('fields[localgov_page][body]');

    // Check node edit form does include character counter.
    $this->drupalGet('/node/add/localgov_page');
    $this->assertSession()->elementTextContains('css', '.form-item-title-0-value', $title_counter_message);
    $this->assertSession()->elementTextContains('css', '.form-item-body-0-summary', $summary_counter_message);

    // Disable character counting.
    $this->drupalGet(Url::fromRoute('localgov_char_count.character_counter_settings'));
    $this->getSession()->getPage()->uncheckField('fields[localgov_page][title]');
    $this->getSession()->getPage()->uncheckField('fields[localgov_page][body]');
    $this->getSession()->getPage()->pressButton('Apply configuration changes');

    // Check node edit form doesn't include character counter.
    $this->drupalGet('/node/add/localgov_page');
    $this->assertSession()->elementTextNotContains('css', '.form-item-title-0-value', $title_counter_message);
    $this->assertSession()->elementTextNotContains('css', '.form-item-body-0-summary', $summary_counter_message);
  }

}
