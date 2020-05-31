<?php

namespace Drupal\assignment\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @RestResource(
 *  id = "assignment",
 *  label = @Translation("Assignment"),
 *  uri_paths = {
 *    "canonical" = "/getnodedata/{id}"
 *  }
 * )
 */
class Assignment extends ResourceBase implements ContainerFactoryPluginInterface{
  public $entityTypeManager;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entityTypeManager;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager')
    );
  }
  
  public function get($id = NULL) {
    $node = $this->entityTypeManager->getStorage('node')->load($id);
    
    if ($id == NULL || $node == NULL) {
      return new ResourceResponse(['Please enter valid node id'], 400);
    }
    
    $nodeData = $this->formattedData($node);
    
    return new ResourceResponse($nodeData);
  }
  
  public function formattedData(Node $node) {
    $data = [];
    $data['nid'] = $node->id();
    $data['title'] = $node->getTitle();
    $data['media'] = $this->getMedia($node->get('field_media')->getValue());
    $data['taxonomy'] = $this->getTaxonomy($node->get('field_term_reference')->getValue());  
    $data['block'] = $this->getBlock($node->get('field_block_reference')->getValue());
    return $data;
  }
  
  public function getMedia(array $data) {
    $data = reset($data);
    if (empty($data)) {
      return '';
    }
    $id = $data['target_id'];
    $media = $this->entityTypeManager->getStorage('media')->load($id);
    $fid = $media->getSource()->getSourceFieldValue($media);
    $file = File::load($fid);
    $uri = $file->getFileUri();
    $url = file_create_url($uri);
    $mediaData = [];
    $mediaData['type'] = $media->bundle();
    $mediaData['url'] = $url;
    return $mediaData;
  }
  
  public function  getTaxonomy(array $data) {
    $data = reset($data);
    if (empty($data)) {
      return '';
    }
    $id = $data['target_id'];
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($id);
    return $term->getName();
  }
  
  public function getBlock(array $data) {
    $data = reset($data);
    if (empty($data)) {
      return '';
    }
    $id = $data['target_id'];
    $block = $this->entityTypeManager->getStorage('block_content')->load($id);
    $blockData = [];
    $blockData['title'] = $block->label();
    $blockData['body'] = $block->get('body')->getValue()[0]['value'];
    return $blockData;
  }
}
