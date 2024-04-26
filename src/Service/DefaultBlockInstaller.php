<?php

namespace Drupal\localgov_core\Service;

use use Symfony\Component\Yaml\Yaml;

class DefaultBlockInstaller {

  protected $entityTypeManager;

  protected $fileSystem;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  protected $themeRegions = [];

  public function __construct() {
    $this->entityTypeManager = \Drupal::service('entity_type.repository');
    $this->fileSystem = \Drupal::service('file_system');
    $this->moduleHandler = \Drupal::service('module_handler');
    $this->themeHandler = \Drupal::service('theme_handler');
    $this->themeManager = \Drupal::service('theme.manager');
  }


  /**
   * Read the yaml files provided by modules.
   */
  protected function blockDefinitions($module) {

    $modulePath = $this->moduleHandler->getModule($module)->getPath();
    $moduleBlockDefinitionsPath = $modulePath . '/config/localgov';
    $files = $this->fileSystem->scanDirectory($moduleBlockDefinitionsPath, '/block\..+\.yml$/');
    $blocks = [];
    foreach ($files as $file) {
      $blocks[] = Yaml::parseFile($file);
    }

    return $blocks;
  }

  protected function targetThemes() {

    // @todo: These should be a setting.
    // @todo: Add a setting at the same time to prevent default blocks being installed entirely.
    $themes = ['localgov_base', 'localgov_scarfolk'];

    $activeTheme = $this->themeManager->getActiveTheme()->getName();

    if (!in_array($activeTheme, $this->themes)) {
      $themes[] = $activeTheme;
    }

    return $themes;
  }

  function install($module): void {

    $blocks = $this->blockDefinitions($module);

    // Loop over every theme and block definition, so we set up all the blocks in
    // all the relevant themes.
    foreach ($this->targetThemes() as $theme) {
      foreach ($blocks as $block) {

        if (!$this->themeHasRegion($theme, $block['region'])) {
          continue;
        }

        $block['id'] = $theme . '_' . $block['plugin'];
        $block['theme'] = $theme;

        $this->entityTypeManager
          ->getStorage('block')
          ->create($block)
          ->save();
      }
    }
  }

  protected function themeHasRegion($theme, $region) {
    return in_array($region, $this->themeRegions($theme));
  }

  /**
   * Gets the regions for the given theme.
   */
  protected function themeRegions($theme) {
    if (!isset($this->themeRegions[$theme])) {
      $themeInfo = $this->themeHandler->getTheme($theme);
      if (empty($themeInfo)) {
        $regions = [];
      }
      else {
        $regions = array_keys($themeInfo->info['regions']);
      }
      $this->themeRegions[$theme] = $regions;
    }
    return $this->themeRegions[$theme];
  }

}
