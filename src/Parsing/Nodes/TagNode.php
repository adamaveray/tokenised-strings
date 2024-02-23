<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing\Nodes;

/**
 * A template tag.
 *
 * @internal
 */
final readonly class TagNode extends AbstractNode
{
  /**
   * @param non-empty-list<string> $param The parameter identifier.
   * @param list<string> $modifiers Identifiers of modifiers to be applied to the parameter.
   */
  public function __construct(public array $param, public array $modifiers)
  {
  }

  public function isEmpty(): bool
  {
    return false;
  }
}
