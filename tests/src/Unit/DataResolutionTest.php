<?php

namespace Drupal\Tests\data_resolver\Unit;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\data_resolver\DataResolution;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\data_resolver\DataResolution
 * @group data_resolver
 */
class DataResolutionTest extends UnitTestCase {

  /**
   * @covers ::resolve
   */
  public function testResolve() {
    $title = $this->prophesize(TypedDataInterface::class);
    $title->getString()->willReturn('foo');

    $data = $this->prophesize(ComplexDataInterface::class);
    $data->get('title')->willReturn($title->reveal());

    $resolution = DataResolution::create($data->reveal(), 'title');

    $this->assertEquals('foo', $resolution->resolve()->getString());
  }

}
