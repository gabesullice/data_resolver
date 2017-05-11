<?php

namespace Drupal\Tests\data_resolver\Unit;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\data_resolver\DataResolver;
use Drupal\data_resolver\DataResolution;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\TermInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\data_resolver\DataResolver
 * @group data_resolver
 */
class DataResolverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'node', 'user'];

  /**
   * Create test node types.
   */
  public function setUp() {
    parent::setUp();
    NodeType::create(['type' => 'article'])->save();
  }

  /**
   * @covers ::create
   */
  public function testCreate() {
    $values = ['type' => 'article', 'title' => 'example'];
    $data = Node::create($values)->getTypedData();
    $resolver = DataResolver::create($data);
    $this->assertTrue($resolver instanceof DataResolver);
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    $values = ['type' => 'article', 'title' => 'example'];
    $data = Node::create($values)->getTypedData();
    $resolver = DataResolver::create($data);
    $this->assertTrue($resolver->get('title') instanceof DataResolution);
  }

}
