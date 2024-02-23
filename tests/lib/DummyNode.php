<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\TestsResources;

use Averay\TokenisedStrings\Parsing\Nodes\AbstractNode;

final readonly class DummyNode extends AbstractNode
{
  public function __construct(public string $key, public bool $empty = false)
  {
  }

  public function isEmpty(): bool
  {
    return $this->empty;
  }
}
