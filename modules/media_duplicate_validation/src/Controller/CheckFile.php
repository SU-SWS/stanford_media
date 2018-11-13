<?php

namespace Drupal\media_duplicate_validation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CheckFile extends ControllerBase {

  /**
   * Media Duplication manager service.
   *
   * @var \Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager
   */
  protected $duplicationManager;

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.media_duplicate_validation'),
      $container->get('request_stack')
    );
  }

  public function __construct(MediaDuplicateValidationManager $duplication_manager, RequestStack $request_stack) {
    $this->duplicationManager = $duplication_manager;
    $this->requestStack = $request_stack;
  }

  public function checkFile() {
    $file_contents = $this->requestStack->getCurrentRequest()->request->get('file');
    return new Response(var_export($file_contents, true));
  }

}
