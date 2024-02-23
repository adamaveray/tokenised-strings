<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing\Nodes;

/**
 * @internal
 */
abstract readonly class AbstractNode
{
  /**
   * @return bool Whether the node content is empty.
   */
  public function isEmpty(): bool
  {
    return true;
  }
}
