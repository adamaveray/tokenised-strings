<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Values;

use Averay\TokenisedStrings\Exceptions\InaccessibleNestedValueException;

/**
 * A simple collection of values, capable of accessing simple nested values from array and object values.
 */
class ValueBag implements ValueBagInterface
{
  /** @var array<string,mixed> */
  protected array $values = [];

  /** @param array<string,mixed> $values */
  public function add(array $values): void
  {
    /** @psalm-suppress MixedAssignment */
    foreach ($values as $key => $value) {
      $this->values[$key] = $value;
    }
  }

  /**
   * @param list<string> $keys
   * @psalm-assert non-empty-list<string> $keys
   */
  public function has(array $keys): bool
  {
    try {
      $this->get($keys);
      return true;
    } catch (InaccessibleNestedValueException) {
      return false;
    }
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

    return self::getNestedValue($this->values, $keys);
  }

  /**
   * @param mixed $container
   * @param non-empty-list<string> $keys
   * @throws InaccessibleNestedValueException The value cannot be retrieved.
   */
  final protected static function getNestedValue(mixed $container, array $keys): mixed
  {
    $path = '';

    if ($container === null) {
      throw new InaccessibleNestedValueException('Container is null.', $path);
    }

    /** @var mixed $current Stores the current depth's value while accessing each level. */
    $current = $container;
    foreach ($keys as $key) {
      $path .= ($path === '' ? '' : '.') . $key;

      /** @psalm-suppress MixedAssignment -- Tested on each iteration */
      $current = match (true) {
        \is_array($current) => self::getNestedFromArray($current, $key, $path),
        \is_object($current) => self::getNestedFromObject($current, $key, $path),
        default => throw new InaccessibleNestedValueException(
          sprintf('Cannot access properties on %s.', \gettype($current)),
          $path,
        ),
      };
    }
    return $current;
  }

  private static function getNestedFromArray(array $array, string $key, string $path): mixed
  {
    if (\array_key_exists($key, $array)) {
      return $array[$key];
    }

    throw new InaccessibleNestedValueException('Undefined array key.', $path);
  }

  private static function getNestedFromObject(object $current, string $key, string $path): mixed
  {
    $getter = 'get' . ucfirst($key);
    if (method_exists($current, $getter) || method_exists($current, '__call')) {
      return $current->{$getter}();
    }

    if (property_exists($current, $key)) {
      return $current->{$key};
    }

    throw new InaccessibleNestedValueException('Cannot access property.', $path);
  }
}
