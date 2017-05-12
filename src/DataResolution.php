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
   * @return \Drupal\Core\TypedData\ListInterface
   *   This method will always return a list, regardless of whether any data
   *   actually exists. In that case, the list will simply be empty.
   */
  public function resolve() {
    // If there is no path, just return the data.
    if (empty($this->chain)) return $this->data;

    // If the data is NULL, don't bother, return NULL.
    if (is_null($this->data)) return NULL;

    // For each property of the path, resolve the next bit of data.
    $resolved = $this->data;
    foreach ($this->chain as $property) {
      $resolved = $this->resolveProperty($resolved, $property);
    }

    return $this->typedDataManager()->create(
      $this->typedDataManager()->createDataDefinition('list'),
      $resolved
    );
  }

  /**
   * Sets the typed data manager for the object.
   *
   * Simply here for future testability.
   */
  public function setTypedDataManager(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * Resolves a single property of a path from a piece of data.
   *
   * The input data may be a ListData, ComplexData, or DataReference instance,
   * or an array of any of the aforementioned types.
   *
   * @param mixed $data
   *   The data from which to get a value. See above.
   * @param mixed $data
   *   The single property to fetch.
   *
   * @return array
   *   Always returns an array. Empty or filled with the fetched property.
   */
  protected function resolveProperty($data, $property) {
    if (is_array($data)) {
      $items = [];
      foreach ($data as $item) {
        $items[] = $this->resolveProperty($item, $property);
      }
      return (empty($items)) ? [] : $this->merge($items);
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

  /**
   * Validates that the requested path *can* exist.
   *
   * This step is to avoid unnecessary queries when there is no chance to
   * resolved the requested data path. It may be wise to use this in an assert.
   */
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

  /**
   * Resolves a DataDefinition for a particular property.
   */
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

  /**
   * Helper which splits a path on a dot (.) and converts offsets to integers.
   *
   * @param string $path
   *   The property path to expand.
   *
   * @return array
   *   The expanded property path.
   */
  protected function expandPath($path) {
    if (is_null($path) || empty($path)) return [];

    $bits = explode('.', $path);

    $bits = array_map(function ($bit) {
      return (is_numeric($bit)) ? intval($bit) : $bit;
    }, $bits);

    return $bits;
  }

  /**
   * Gets the typed data manager from the container if one is not already set.
   */
  protected function typedDataManager() {
    if (!isset($this->typedDataManager)) {
      $this->typedDataManager = \Drupal::typedDataManager();
    }
    return $this->typedDataManager;
  }

  /**
   * Helper function to flatten a two-dimensional array into a single array.
   *
   * @param array $list
   *   An array of arrays to flatten.
   *
   * @return array
   *   The flattened array.
   */
  protected function merge(array $list) {
    return call_user_func_array('array_merge', $list);
  }

}
