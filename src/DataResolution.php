<?php

namespace Drupal\data_resolver;

use Drupal\Core\TypedData\TypedDataInterface;

class DataResolution {

  /**
   * The data from which to resolve data.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface|NULL
   */
  protected $data;

  /**
   * The data path from which to resolve a data value.
   *
   * @var string
   */
  protected $path;

  /**
   * The expanded path parts from which to resolve a data value.
   *
   * @var array
   */
  protected $chain;

  /**
   * Makes a data resolver instance.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The data from which to resolve a value.
   */
  public function __construct(TypedDataInterface $data, $path) {
    $this->data = $data;
    $this->path = $path;
    $this->chain = $this->expandPath($path);
  }

  /**
   * Creates a new instance a new DataResolution instance.
   */
  public static function create(TypedDataInterface $data, $path) {
    return new static($data, $path);
  }

  /**
   * Resolves a data value, including any conditions.
   */
  public function resolve() {
    if (strlen($this->path) == 0) return $this->data;

    if (is_null($this->data)) return NULL;

    $value = array_reduce($this->chain, function ($value, $bit) {
      if ($bit == 'entity') return $value->get($bit)->getTarget();
      return $value->get($bit);
    }, $this->data);

    return ($value) ? $value : NULL;
  }

  protected function expandPath($path) {
    return explode('.', $path);
  }

}
