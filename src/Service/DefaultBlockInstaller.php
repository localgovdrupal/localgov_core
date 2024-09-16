<?php

namespace Drupal\localgov_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Service to install default blocks.
 */
class DefaultBlockInstaller {

  /**
   * Array of regions in each theme.
   *
   * @var array
   */
  protected $themeRegions = [];

  /**
   * Constructor.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem,
    protected ModuleHandlerInterface $moduleHandler,
    protected ThemeHandlerInterface $themeHandler,
    protected ThemeManagerInterface $themeManager,
  ) {
  }

  /**
   * Read the yaml files provided by modules.
   */
  protected function blockDefinitions(string $module): array {

    $modulePath = $this->moduleHandler->getModule($module)->getPath();
    $moduleBlockDefinitionsPath = $modulePath . '/config/localgov';
    $blocks = [];

    if (is_dir($moduleBlockDefinitionsPath)) {
      $files = $this->fileSystem->scanDirectory($moduleBlockDefinitionsPath, '/block\..+\.yml$/');
      foreach ($files as $file) {
        $blocks[] = Yaml::parseFile($moduleBlockDefinitionsPath . '/' . $file->filename);
      }
    }

    return $blocks;
  }

  /**
   * The themes we'll be installing blocks into.
   */
  protected function targetThemes(): array {

    $themes = ['localgov_base', 'localgov_scarfolk'];

    $activeTheme = $this->getActiveThemeName();
    if ($activeTheme && !in_array($activeTheme, $themes, TRUE)) {
      $themes[] = $activeTheme;
    }

    // Don't try to use themes that don't exist.
    foreach ($themes as $i => $theme) {
      if (!$this->themeHandler->themeExists($theme)) {
        unset($themes[$i]);
      }
    }

    return $themes;
  }

  /**
   * Gets the name of the active theme, if there is one.
   */
  protected function getActiveThemeName(): ?string {
    $activeTheme = $this->themeManager->getActiveTheme()->getName();
    if ($activeTheme === 'core') {
      // 'core' is what Drupal returns when there's no themes available.
      // See \Drupal\Core\Theme\ThemeInitialization::getActiveThemeByName().
      // This mainly happens in the installer.
      return NULL;
    }
    return $activeTheme;
  }

  /**
   * Installs the default blocks for the given modules.
   *
   * If the site is configured not to allow default blocks to be installed, this
   * method will do nothing.
   */
  public function install(array $modules): void {

    // If localgov_core.settings.install_default_blocks is set to FALSE, don't
    // install default blocks. This lets site owners opt out if desired.
    $config = $this->configFactory->get('localgov_core.settings');
    if ($config && ($config->get('install_default_blocks') === FALSE)) {
      return;
    }

    foreach ($modules as $module) {
      $this->installForModule($module);
    }
  }

  /**
   * Installs the default blocks for the given module.
   */
  protected function installForModule(string $module): void {

    $blocks = $this->blockDefinitions($module);
    $blockStorage = $this->entityTypeManager->getStorage('block');

    // Loop over every theme and block definition, so we set up all the blocks
    // in all the relevant themes.
    foreach ($this->targetThemes() as $theme) {
      foreach ($blocks as $block) {

        // Verify that the theme we're using has the requested region.
        if (!$this->themeHasRegion($theme, $block['region'])) {
          continue;
        }

        $block['id'] = $this->sanitiseId($theme . '_' . $block['plugin']);
        $block['theme'] = $theme;

        // If there's no block with this ID already, create and save this block.
        if ($blockStorage->load($block['id']) === NULL) {
          $blockStorage->create($block)->save();
        }
      }
    }
  }

  /**
   * Replace characters that aren't allowed in config IDs.
   *
   * This is partly based on
   * \Drupal\Core\Block\BlockPluginTrait::getMachineNameSuggestion().
   */
  protected function sanitiseId(string $id): string {

    // Shift to lower case.
    $id = mb_strtolower($id);

    // Limit to alphanumeric chars, dot and underscore.
    $id = preg_replace('@[^a-z0-9_.]+@', '_', $id);

    // Remove non-alphanumeric chars from the beginning and end of the id.
    $id = preg_replace('@^([^a-z0-9]+)|([^a-z0-9]+)$@', '', $id);

    return $id;
  }

  /**
   * Does the given theme have the given region?
   */
  protected function themeHasRegion(string $theme, string $region): bool {
    return in_array($region, $this->themeRegions($theme), TRUE);
  }

  /**
   * Gets the regions for the given theme.
   */
  protected function themeRegions(string $theme): array {
    if (!isset($this->themeRegions[$theme])) {
      $themeInfo = $this->themeHandler->getTheme($theme);
      if (!$themeInfo instanceof Extension) {
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
