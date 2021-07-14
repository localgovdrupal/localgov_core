<?php

namespace Drupal\Tests\localgov_workflows\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\localgov_roles\RolesHelper;
use Drupal\node\Entity\Node;
use Drupal\workflows\Entity\Workflow;

/**
 * Test access to LocalGov workflows and permissions to transition content.
 */
class WorkflowsAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a content type with the LocalGov editorial workflow enabled.
    $this->drupalCreateContentType([
      'type' => 'localgov_services_page',
      'title' => 'Page',
    ]);
    $workflow = Workflow::load('localgov_editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'localgov_services_page');
    $workflow->save();
  }

  /**
   * Test contributor role.
   */
  public function testContributorRole() {
    $this->loginAs(RolesHelper::CONTRIBUTOR_ROLE);

    // Check options on node edit page.
    $this->drupalGet('node/add/localgov_services_page');
    $this->assertSession()->pageTextContains('Draft');
    $this->assertSession()->pageTextContains('Review');
    $this->assertSession()->pageTextNotContains('Published');
    $this->assertSession()->pageTextNotContains('Archived');

    // Draft > review > draft.
    $node = $this->createNodeWithState('draft');
    $this->updateState($node, 'review');
    $this->updateState($node, 'draft');

    // Check it's not possible to publish page.
    $this->expectException(\InvalidArgumentException::class);
    $this->updateState($node, 'published');
  }

  /**
   * Test author role.
   */
  public function testAuthorRole() {
    $this->loginAs(RolesHelper::AUTHOR_ROLE);

    // Check options on edit page.
    $this->drupalGet('node/add/localgov_services_page');
    $this->assertSession()->pageTextContains('Draft');
    $this->assertSession()->pageTextContains('Review');
    $this->assertSession()->pageTextContains('Published');
    $this->assertSession()->pageTextContains('Archived');

    // Draft > review > draft > archived.
    $node = $this->createNodeWithState('draft');
    $this->updateState($node, 'review');
    $this->updateState($node, 'draft');
    $this->updateState($node, 'archived');

    // Publish without review.
    $this->createNodeWithState('published');

    // Check it's not possible to edit someone else's content.
    $other_node = $this->drupalCreateNode([
      'type' => 'localgov_services_page',
      'title' => $this->randomMachineName(12),
      'uid' => 1,
      'moderation_state' => 'draft',
    ]);
    $this->drupalGet('node/' . $other_node->id() . '/edit');
    $this->assertSession()->pageTextContains('You are not authorized to access this page.');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test editor role.
   */
  public function testEditorRole() {
    $this->loginAs(RolesHelper::EDITOR_ROLE);

    // Check options on edit page.
    $this->drupalGet('node/add/localgov_services_page');
    $this->assertSession()->pageTextContains('Draft');
    $this->assertSession()->pageTextContains('Review');
    $this->assertSession()->pageTextContains('Published');
    $this->assertSession()->pageTextContains('Archived');

    // Draft > review > approve > archive.
    $node1 = $this->createNodeWithState('draft');
    $this->updateState($node1, 'review');
    $this->updateState($node1, 'published');
    $this->updateState($node1, 'archived');

    // Publish > archive > restore.
    $node2 = $this->createNodeWithState('published');
    $this->updateState($node2, 'archived');
    $this->updateState($node2, 'published');

    // Publish someone else's content.
    $node3 = $this->drupalCreateNode([
      'type' => 'localgov_services_page',
      'title' => $this->randomMachineName(12),
      'uid' => 1,
      'moderation_state' => 'review',
    ]);
    $this->updateState($node3, 'published');

    // Reject someone else's content.
    $node4 = $this->drupalCreateNode([
      'type' => 'localgov_services_page',
      'title' => $this->randomMachineName(12),
      'uid' => 1,
      'moderation_state' => 'review',
    ]);
    $this->updateState($node4, 'draft');
  }

  /**
   * Login user with role.
   *
   * @param string $role
   *   Role ID to login user with.
   */
  protected function loginAs($role) {
    $user = $this->drupalCreateUser();
    $user->addRole($role);
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Create and test a new node with given moderation state.
   *
   * @param string $state
   *   Moderation state.
   *
   * @returns \Drupal\node\Entity\Node
   */
  protected function createNodeWithState($state) {
    $title = $this->randomMachineName(12);
    $this->drupalGet('node/add/localgov_services_page');
    $this->submitForm([
      'title[0][value]' => $title,
      'moderation_state[0][state]' => $state,
    ], 'Save');
    $node = $this->getNodeByTitle($title);
    $this->assertEquals($state, $node->moderation_state->value);

    return $node;
  }

  /**
   * Update and test node with given moderation state.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node to update.
   * @param string $state
   *   Moderation state.
   *
   * @returns \Drupal\node\Entity\Node
   */
  protected function updateState(Node $node, string $state) {
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => $state,
    ], 'Save');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node_storage->resetCache([$node->id()]);
    $node = $node_storage->load($node->id());
    $this->assertEquals($state, $node->moderation_state->value);

    return $node;
  }

}
