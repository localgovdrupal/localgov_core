<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Media types.
 *
 * Tests the following:
 * - Presence of the Document, Image, and Remote video Media bundles.
 * - Presence of different image croppers in the Media Image edit form.
 *
 * @group localgov_media
 */
class MediaSetupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['localgov_media', 'path'];

  /**
   * Test Media bundles.
   *
   * At the least, we should have the Document, Image, and Remote video bundles.
   */
  public function testMediaBundles() {

    $account = $this->drupalCreateUser(['create media', 'update media']);
    $this->drupalLogin($account);

    $this->drupalGet('media/add/document');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('media/add/image');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('media/add/remote_video');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test the presence of image croppers in Media image edit form.
   *
   * We have added the following image croppers to the Media image edit form:
   * 3:2, 16:9, 7:3, 8:7, square.
   */
  public function testImageCroppersInImageEditForm() {

    $account = $this->drupalCreateUser(['create media', 'update media']);
    $this->drupalLogin($account);

    $this->drupalGet('media/add/image');
    $this->assertSession()->statusCodeEquals(200);

    // Upload an image in the Media image add form.
    $page = $this->getSession()->getPage();
    $page->fillField('name[0][value]', 'An image');
    $page->fillField('files[field_media_image_0]', \Drupal::service('file_system')->realpath('core/modules/image/sample.png'));
    $this->submitForm([], 'Upload');
    $this->assertSession()->statusCodeEquals(200);

    // This should bring us to the Media image edit form where we should find
    // the image croppers.
    $this->assertSession()->elementExists('css', '[data-drupal-iwc-id="3_2"]');
    $this->assertSession()->elementExists('css', '[data-drupal-iwc-id="16_9"]');
    $this->assertSession()->elementExists('css', '[data-drupal-iwc-id="8_7"]');
    $this->assertSession()->elementExists('css', '[data-drupal-iwc-id="7_3"]');
    $this->assertSession()->elementExists('css', '[data-drupal-iwc-id="square"]');
  }

  /**
   * Theme to use during the functional tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

}
