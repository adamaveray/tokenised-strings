<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering\Traits;

/**
 * @psalm-type Modifier callable(mixed):mixed
 */
trait WithModifiers
{
  /** @var array<string,Modifier> */
  private array $modifiers = [];

  /** @param Modifier $fn */
  final public function addModifier(string $name, callable $fn): void
  {
    $this->modifiers[$name] = $fn;
  }

  /** @param array<string,Modifier> $modifiers */
  final public function addModifiers(array $modifiers): void
  {
    foreach ($modifiers as $modifier => $fn) {
      $this->addModifier($modifier, $fn);
    }
  }

  /**
   * @param list<string> $modifierIds
   * @return mixed The provided value after applying all modifiers
   */
  final protected function applyModifiers(mixed $value, array $modifierIds): mixed
  {
    foreach ($modifierIds as $modifierId) {
      $modifier = $this->modifiers[$modifierId] ?? null;
      if ($modifier === null) {
        throw new \OutOfBoundsException(sprintf('Undefined modifier "%s".', $modifierId));
      }
      /** @psalm-suppress MixedAssignment -- Modifiers can return any value */
      $value = $modifier($value);
      if ($value === null) {
        throw new \UnexpectedValueException(sprintf('Empty value returned from modifier "%s".', $modifierId));
      }
    }
    return $value;
  }
}
