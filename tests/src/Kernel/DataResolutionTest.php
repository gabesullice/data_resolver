<?php

namespace Drupal\Tests\data_resolver\Unit;

use Drupal\data_resolver\DataResolution;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;


/**
 * @coversDefaultClass \Drupal\data_resolver\DataResolution
 * @group data_resolver
 */
class DataResolutionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'node', 'user'];

  /**
   * Create test node types.
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);

    NodeType::create([
      'type' => 'article',
    ])->save();

    $this->user = User::create([
      'name' => 'user0',
      'mail' => 'test@example.com',
    ]);
    $this->user->save();

    //var_dump(array_keys($this->user->getTypedData()->getDataDefinition()->getPropertyDefinitions()));

    $this->node0 = Node::create([
      'type' => 'article',
      'title' => 'node0',
      'uid' => $this->user->id(),
    ]);
    $this->node0->save();

    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * @covers ::resolve
   * @dataProvider resolveTypedDataProvider
   */
  public function testResolve_typed_data($data, $path, $expect) {
    $typed_data = $this->createTypedData($data['type'], $data['value']);
    $resolution = DataResolution::create($typed_data, $path);
    $this->assertEquals($expect, $resolution->resolve()->getValue());
  }

  /**
   * @covers ::resolve
   * @dataProvider resolveEntityDataProvider
   */
  public function testResolve_entity_data($path, $expect, $many) {
    $resolution = DataResolution::create($this->node0->getTypedData(), $path);
    $actual = $resolution->resolve();
    if ($many) {
      foreach ($expect as $index => $item) {
        foreach ($item as $key => $value) {
          $this->assertEquals($value, $actual[$index]->first()->get($key)->getValue());
        }
      }
    }
    else {
      if (!empty($expect)) {
        $this->assertEquals($expect, $actual[0]->getValue());
      }
      $this->assertEquals($expect, $actual);
    }
  }

  /**
   * @covers ::resolve
   */
  public function testResolve_invalid_path() {
    $this->setExpectedException(
      \InvalidArgumentException::class,
      "'foo' is not a valid property name in the path 'uid.foo' for the given entity:node:article."
    );
    $resolution = DataResolution::create($this->node0->getTypedData(), 'uid.foo');
  }

  /**
   * Provides data and expected resolutions.
   */
  public function resolveEntityDataProvider() {
    return [
      ['uid.entity.name.1', [], FALSE],
      ['title', [['value' => 'node0']], TRUE],
      ['uid', [['target_id' => 1]], TRUE],
      ['uid.entity.name', [['value' => 'user0']], TRUE],
      ['uid.entity.roles.0', [], FALSE],
      ['uid.0.entity.name', [['value' => 'user0']], TRUE],
      ['uid.0.entity.roles.0', [], FALSE],
      ['uid.0.entity.roles.1', [], FALSE],
    ];
  }

  /**
   * Provides data and expected resolutions.
   */
  public function resolveTypedDataProvider() {
    return [
      [['type' => 'string', 'value' => 'foo'], NULL, 'foo'],
    ];
  }

  public function createTypedData($type, $value) {
    $definition = $this->typedDataManager->createDataDefinition($type);
    return $this->typedDataManager->create($definition, $value);
  }

}
