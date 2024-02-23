<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering\Traits;

use Averay\TokenisedStrings\Values\ValueBagInterface;

trait WithParams
{
  private ValueBagInterface $valueBag;

  final public function setValueBag(ValueBagInterface $valueBag): void
  {
    $this->valueBag = $valueBag;
  }

  final public function addValue(string $key, mixed $value): void
  {
    $this->valueBag->add([$key => $value]);
  }

  /** @param array<string,mixed> $values */
  final public function addValues(array $values): void
  {
    $this->valueBag->add($values);
  }

  final public function getValueBag(): ValueBagInterface
  {
    return $this->valueBag;
  }
}
