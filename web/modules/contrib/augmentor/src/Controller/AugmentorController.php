<?php

namespace Drupal\augmentor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\augmentor\AugmentorManager;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for the route to executes augmentors with a given input via ajax.
 */
class AugmentorController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The csrf token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The Augmentor plugin manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * The Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructs a AugmentorController object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The Augmentor plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   */
  public function __construct(
    CsrfTokenGenerator $csrf_token_generator,
    AugmentorManager $augmentor_manager,
    RequestStack $request_stack) {
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->augmentorManager = $augmentor_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('plugin.manager.augmentor.augmentors'),
      $container->get('request_stack'),
    );
  }

  /**
   * Take the incoming data and hand it over for processing.
   *
   * @return string
   *   HTTP response, to be processed by the augmentor_library.js.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function execute() {
    try {
      $decoded_request_body = Json::decode($this->requestStack->getCurrentRequest()->getContent());
      $augmentor = $this->augmentorManager->getAugmentor($decoded_request_body['augmentor']);
      $result = $augmentor->execute($decoded_request_body['input']);
    }
    catch (\Throwable $error) {
      $result = ['_errors' => $error->getMessage()];
    }

    if (array_key_exists('_errors', $result)) {
      return new JsonResponse(Json::encode(
        $result['_errors'],
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
      ), 400);
    }

    return new JsonResponse(
      Json::encode(
        $result,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
      )
    );
  }

}
