<?php

namespace Drupal\augmentor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Augmentor plugin manager.
 */
class AugmentorManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The array of augmentors.
   *
   * @var array
   */
  protected $augmentors = [];

  /**
   * Constructs AugmentorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct(
      'Plugin/Augmentor',
      $namespaces,
      $module_handler,
      'Drupal\augmentor\AugmentorInterface',
      'Drupal\augmentor\Annotation\Augmentor'
    );
    $this->configFactory = $config_factory;
    $this->alterInfo('augmentor_info');
    $this->setCacheBackend($cache_backend, 'augmentor_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getAugmentor($augmentor_id) {
    $augmentor_type = $this->getAugmentors()[$augmentor_id]['type'];
    $augmentor = $this->createInstance($augmentor_type);
    $augmentor->setConfiguration(
      $this->getAugmentors()[$augmentor_id]['configuration'],
    );
    $augmentor->setUuid($augmentor_id);

    return $augmentor;
  }

  /**
   * {@inheritdoc}
   */
  public function getAugmentors() {
    $this->augmentors = $this->getAugmentorConfig()->get('augmentors');
    return $this->augmentors;
  }

  /**
   * {@inheritdoc}
   */
  public function getAugmentorConfig() {
    return $this->configFactory->getEditable('augmentor.settings');
  }

}
