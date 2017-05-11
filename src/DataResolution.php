<?php

namespace Drupal\data_resolver;

use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;

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
   * $path is a dot (.) separated string that describes a path to traverse the
   * given typed data. If a specific index is desired, you may include an
   * integer as an specific index to traverse, otherwise all values for all
   * multivalue properties will be resolved.
   *
   * You should not be concerned about the existence of values along the path.
   * When any empty values are found along the chain, an empty value is assumed.
   *
   * Examples:
   *   $resolution = new DataResolution($data, 'uid');
   *   $resolution = new DataResolution($data, 'uid.0');
   *   $resolution = new DataResolution($data, 'uid.0.entity.name');
   *   $resolution = new DataResolution($data, 'uid.0.entity.0.name');
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The data from which to resolve a value.
   * @param string $path
   *   A property path from which resolve data.
   */
  public function __construct(TypedDataInterface $data, $path = NULL) {
    $this->data = $data;
    $this->path = $path;
    $this->chain = $this->expandPath($path);
    $this->validatePath();
  }

  /**
   * Creates a new instance a new DataResolution instance.
   */
  public static function create(TypedDataInterface $data, $path) {
    return new static($data, $path);
  }

  /**
   * Resolves a data value, including any conditions.
   *
   * @return array
   *   This method will always return an array, even when no values exists. In
   *   that case, an empty array will be returned.
   */
  public function resolve() {
    // If there is no path, just return the data.
    if (empty($this->chain)) return $this->data;

    // If the data is NULL, don't bother, return NULL.
    if (is_null($this->data)) return NULL;

    // Collect all of the values into an array.
    return $this->collectTypedData();
  }

  protected function collectTypedData() {
    $reducer = function ($resolved, $property) {
      $values = $this->resolveProperty($resolved, $property);
      return $values;
    };

    // For each property of the path, resolve the next bit of data.
    $resolved = array_reduce($this->chain, $reducer, $this->data);

    return $resolved;
  }

  protected function merge(array $list) {
    return call_user_func_array('array_merge', $list);
  }

  protected function resolveProperty($data, $property) {
    if (is_array($data)) {
      foreach ($data as $item) {
        $items[] = $this->resolveProperty($item, $property);
      }
      return $this->merge($items);
    }
    elseif ($data instanceof ListInterface) {
      if ($data->isEmpty()) {
        return [];
      }

      if (is_int($property)) {
        $value = $data->get($property);
        return (is_null($value)) ? [] : [$value];
      }

      foreach ($data as $item) {
        $items[] = $this->resolveProperty($item, $property);
      }

      return $this->merge($items);
    }
    elseif ($data instanceof DataReferenceInterface) {
      return $this->resolveProperty($data->getTarget(), $property);
    }
    else {
      return [$data->get($property)];
    }
  }

  protected function validatePath() {
    $reducer = function ($definition, $property) {
      $definition = $this->resolveDefinition($definition, $property);
      if (is_null($definition)) {
        $message = sprintf(
          "'%s' is not a valid property name in the path '%s' for the given %s.",
          $property, $this->path, $this->data->getDataDefinition()->getDataType()
        );
        throw new \InvalidArgumentException($message);
      }
      return $definition;
    };

    $base_definition = array_reduce(
      $this->chain,
      $reducer,
      $this->data->getDataDefinition()
    );
  }

  protected function resolveDefinition($definition, $property) {
    if ($definition instanceof ListDataDefinitionInterface) {
      if (is_int($property)) {
        return $definition->getItemDefinition();
      }

      return $this->resolveDefinition(
        $definition->getItemDefinition(),
        $property
      );
    }

    if ($definition instanceof DataReferenceDefinition) {
      $referenced_definition = $definition->getTargetDefinition();
      return $this->resolveDefinition(
        $referenced_definition,
        $property
      );
    }

    return $definition->getPropertyDefinition($property);
  }

  protected function expandPath($path) {
    if (is_null($path) || empty($path)) return [];

    $bits = explode('.', $path);

    $bits = array_map(function ($bit) {
      return (is_numeric($bit)) ? intval($bit) : $bit;
    }, $bits);

    return $bits;
  }

  protected function typedDataManager() {
    if (!isset($this->typedDataManager)) {
      $this->typedDataManager = \Drupal::typedDataManager();
    }
    return $this->typedDataManager;
  }

}
