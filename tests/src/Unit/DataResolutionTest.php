<?php

namespace Drupal\Tests\data_resolver\Unit;

use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\data_resolver\DataResolution;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\data_resolver\DataResolution
 * @group data_resolver
 */
class DataResolutionTest extends UnitTestCase {

  /**
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($data, $path, $expect) {
    $resolution = DataResolution::create($data, $path);
    $this->assertEquals($expect, $resolution->resolve()->getValue());
  }

  /**
   * Provides data and expected resolutions.
   */
  public function resolveProvider() {
    return [
      [$this->createComplexData(['title' => $this->createTypedData('foo')]), 'title', 'foo'],
      [$this->createComplexData([
        'uid' => $this->createListData([
          $this->createComplexData([
            'entity' => $this->createComplexData([
              'name' => $this->createListData([
                $this->createComplexData([
                  'value' => $this->createTypedData('jane'),
                ]),
              ]),
            ]),
          ]),
        ]),
      ]), 'uid.0.entity.name.0.value', 'jane'],
    ];
  }

  protected function createListData($values) {
    $data = $this->prophesize(ListInterface::class);

    foreach ($values as $index => $value) {
      $data->get($index)->willReturn($value);
    }

    return $data->reveal();
  }

  protected function createComplexData($values) {
    $data = $this->prophesize(ComplexDataInterface::class);

    foreach ($values as $property => $value) {
      if ($property == 'entity') {
        $entity = $this->prophesize(EntityReference::class);
        $entity->getTarget()->willReturn($value);
        $data->get($property)->willReturn($entity->reveal());
      }
      else {
        $data->get($property)->willReturn($value);
      }
    }

    return $data->reveal();
  }

  protected function createTypedData($value) {
    $data = $this->prophesize(TypedDataInterface::class);
    $data->getValue()->willReturn($value);
    return $data->reveal();
  }

}
