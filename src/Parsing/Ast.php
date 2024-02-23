<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing;

use Averay\TokenisedStrings\Parsing\Nodes\AbstractNode;

/**
 * A parsed template’s syntax tree.
 *
 * @internal
 */
final class Ast
{
  /** @param list<AbstractNode> $tree */
  public function __construct(private array $tree = [])
  {
  }

  public function pushNode(AbstractNode $node): void
  {
    $this->tree[] = $node;
  }

  /** @return list<AbstractNode> */
  public function getTree(): array
  {
    return $this->tree;
  }

  /**
   * @psalm-assert-if-true empty $this->tree
   * @psalm-assert-if-false non-empty-list<AbstractNode> $this->tree
   */
  public function isEmpty(): bool
  {
    return $this->tree === [];
  }

  /**
   * @return AbstractNode The current final node on the AST.
   */
  public function getCurrentNode(): AbstractNode
  {
    if ($this->isEmpty()) {
      throw new \UnderflowException('The AST is empty.');
    }
    return $this->tree[\count($this->tree) - 1];
  }

  /**
   * Removes all empty nodes.
   */
  public function pruneEmpty(): void
  {
    $this->tree = array_values(array_filter($this->tree, static fn(AbstractNode $node): bool => !$node->isEmpty()));
  }
}
