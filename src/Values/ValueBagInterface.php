<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Values;

use Averay\TokenisedStrings\Exceptions\InaccessibleNestedValueException;

interface ValueBagInterface
{
  /**
   * Adds values to the bag, overwriting any conflicts while preserving existing non-conflicting values.
   *
   * @param array<string,mixed> $values
   */
  public function add(array $values): void;

  /**
   * @param non-empty-list<string> $keys A hierarchy of keys to traverse within the bag.
   * @return bool Whether the bag contains the value.
   */
  public function has(array $keys): bool;

  /**
   * @param non-empty-list<string> $keys A hierarchy of keys to traverse within the bag.
   * @return mixed The value for the keys hierarchy.
   * @throws InaccessibleNestedValueException The value cannot be retrieved.
   */
  public function get(array $keys): mixed;
}
