<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Values;

/**
 * A value bag that will retrieve values from the provided object if no matching value is set.
 */
class ObjectValueBag extends ValueBag
{
  public function __construct(private readonly object $object)
  {
  }

  /**
   * @param list<string> $keys
   * @psalm-assert non-empty-list<string> $keys
   */
  public function get(array $keys): mixed
  {
    if (empty($keys)) {
      throw new \InvalidArgumentException('Params cannot be empty.');
    }

    if (\array_key_exists(current($keys), $this->values)) {
      // Assume accessing manual value
      return parent::get($keys);
    }

    // Access directly on model
    return self::getNestedValue($this->object, $keys);
  }
}
