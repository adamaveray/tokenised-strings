<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing\Nodes;

/**
 * Static text.
 *
 * @internal
 */
final readonly class TextNode extends AbstractNode
{
  public function __construct(public string $content)
  {
  }

  /**
   * @psalm-assert-if-true non-empty-string $this->content
   * @psalm-assert-if-true empty $this->content
   */
  public function isEmpty(): bool
  {
    return $this->content === '';
  }
}
