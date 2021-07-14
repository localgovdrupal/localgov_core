<?php

namespace Drupal\Tests\localgov_workflows\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\localgov_roles\RolesHelper;
use Drupal\workflows\Entity\Workflow;

/**
 * Functional tests workflow approvals dashboard.
 *
 * @group localgov_workflows
 */
class ApprovalsDashboardTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_workflows',
  ];

  /**
   * Test approvals dashboard links.
   */
  public function testApprovalsDashboardLinks() {
    $this->drupalLogin($this->rootUser);

    // Test approvals dashboard tab is visible and content moderation in hidden.
    $this->drupalGet('admin/content');
    $this->assertSession()->responseContains('Approvals dashboard');
    $this->assertSession()->responseNotContains('Content moderation');

    // Test content moderation tab is visible after enabling another workflow.
    $workflow = new Workflow([
      'id' => 'test_workflow',
      'type' => 'content_moderation',
    ], 'workflow');
    $workflow->save();
    $this->drupalGet('admin/content');
    $this->assertSession()->responseContains('Approvals dashboard');
    $this->assertSession()->responseContains('Moderated content');
  }

  /**
   * Test approvals view.
   */
  public function testApprovalsDashboardView() {

    // Create a content type with the LocalGov editorial workflow enabled.
    $this->drupalCreateContentType([
      'type' => 'localgov_services_page',
      'title' => 'Page',
    ]);
    $workflow = Workflow::load('localgov_editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'localgov_services_page');
    $workflow->save();

    // Create an editor and an author.
    $editor = $this->drupalCreateUser();
    $editor->addRole(RolesHelper::EDITOR_ROLE);
    $editor->save();
    $author = $this->drupalCreateUser();
    $author->addRole(RolesHelper::AUTHOR_ROLE);
    $author->save();

    // Create content.
    $title = $this->randomMachineName(12);
    $node = $this->drupalCreateNode([
      'type' => 'localgov_services_page',
      'title' => $title,
      'uid' => 1,
      'moderation_state' => 'draft',
    ]);

    // Check draft content not included.
    $this->drupalLogin($editor);
    $this->drupalGet('admin/content/localgov_approvals');
    $this->assertSession()->pageTextNotContains($title);

    // Check review content is visible.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'review',
    ], 'Save');
    $this->drupalGet('admin/content/localgov_approvals');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->elementContains('css', '.views-table', 'Page');
    $this->assertSession()->elementContains('css', '.views-table', 'Review');
    $this->assertSession()->elementContains('css', '.views-table', 'Unpublished');
    $this->assertSession()->elementContains('css', '.views-table', 'Edit');

    // Check review content not editable by authors.
    // This is dependent on the permissions set in:
    // https://github.com/localgovdrupal/localgov_core/pull/99
    // @codingStandardsIgnoreStart
    // $this->drupalLogin($author);
    // $this->drupalGet('admin/content/localgov_approvals');
    // $this->assertSession()->pageTextContains($title);
    // $this->assertSession()->elementNotContains('css', '.views-table', 'Edit');
    // @codingStandardsIgnoreEnd

    // Check published content not included.
    $node->set('moderation_state', 'published');
    $node->save();
    $this->drupalGet('admin/content/localgov_approvals');
    $this->assertSession()->pageTextNotContains($title);
  }

}
