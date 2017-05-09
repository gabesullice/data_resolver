<?php

namespace Drupal\data_resolver;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Entity\EntityInterface;

class DataResolver {

  /**
   * The data from which to resolve data.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $data;

  /**
   * Makes a data resolver instance.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The data from which to resolve a value.
   */
  public function __construct(TypedDataInterface $data) {
    $this->data = $data;
  }

  /**
   * Creates a new instance a new DataResolver instance.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\TypedData\TypedDataInterface $data
   *   The data from which to resolve a value.
   */
  public static function create($data) {
    assert(
      $data instanceof EntityInterface || $data instanceof TypedDataInterface,
      sprintf(
        'Argument must be of either the type %s or %s',
        EntityInterface::class,
        TypedDataInterface::class
      )
    );

    return new static(
      ($data instanceof EntityInterface) ? $data->getTypedData() : $data
    );
  }

  public function get($path) {
    return DataResolution::create($this->data, $path);
  }

}
