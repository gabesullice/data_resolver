<?php

namespace Drupal\Tests\data_resolver\Unit;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\data_resolver\DataResolver;
use Drupal\data_resolver\DataResolution;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\data_resolver\DataResolver
 * @group data_resolver
 */
class DataResolverTest extends UnitTestCase {

  /**
   * @covers ::create
   * @dataProvider createProvider
   */
  public function testCreate($data) {
    $resolver = DataResolver::create($data);
    $this->assertTrue($resolver instanceof DataResolver);
  }

  public function createProvider() {
    $typed_data = $this->prophesize(TypedDataInterface::class)->reveal();
    $node = $this->prophesize(NodeInterface::class);
    $term = $this->prophesize(TermInterface::class);

    $node->getTypedData()->willReturn($typed_data);
    $term->getTypedData()->willReturn($typed_data);

    return [
      [$typed_data],
      [$node->reveal()],
      [$term->reveal()],
    ];
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    $data = $this->prophesize(TypedDataInterface::class);

    $resolver = DataResolver::create($data->reveal());

    $this->assertTrue($resolver->get('title') instanceof DataResolution);
  }

}
