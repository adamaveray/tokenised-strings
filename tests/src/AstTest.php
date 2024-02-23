<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Averay\TokenisedStrings\Parsing\Ast;
use Averay\TokenisedStrings\Parsing\Nodes\AbstractNode;
use Averay\TokenisedStrings\TestsResources\DummyNode;

#[CoversClass(Ast::class)]
final class AstTest extends TestCase
{
  /**
   * @covers ::pushNode
   * @covers ::getTree
   * @covers ::<!public>
   */
  public function testBuildTree(): void
  {
    $expected = [new DummyNode('a'), new DummyNode('b'), new DummyNode('c'), new DummyNode('d')];

    $ast = new Ast();
    $ast->pushNode(new DummyNode('a'));
    $ast->pushNode(new DummyNode('b'));
    $ast->pushNode(new DummyNode('c'));
    $ast->pushNode(new DummyNode('d'));
    self::assertEquals($expected, $ast->getTree(), 'The correct tree should be built.');
  }

  /**
   * @covers ::getCurrentNode
   * @covers ::<!public>
   */
  public function testCurrentNode(): void
  {
    $ast = new Ast();

    $node = new DummyNode('a');
    $ast->pushNode($node);
    self::assertSame($node, $ast->getCurrentNode(), 'The correct node should be returned.');

    $node = new DummyNode('b');
    $ast->pushNode($node);
    self::assertSame($node, $ast->getCurrentNode(), 'The updated node should be returned.');
  }

  /**
   * @covers ::pruneEmpty
   * @covers ::<!public>
   */
  public function testPruneEmpty(): void
  {
    $expectedFullTree = ['a', 'b', 'c', 'd', 'e', 'f'];
    $expectedPrunedTree = ['c', 'e'];

    $ast = new Ast();
    $ast->pushNode(new DummyNode('a', empty: true));
    $ast->pushNode(new DummyNode('b', empty: true));
    $ast->pushNode(new DummyNode('c', empty: false));
    $ast->pushNode(new DummyNode('d', empty: true));
    $ast->pushNode(new DummyNode('e', empty: false));
    $ast->pushNode(new DummyNode('f', empty: true));

    $fullTree = $ast->getTree();
    self::assertNodesEqualKeys($expectedFullTree, $fullTree, 'The full tree should be output.');

    $ast->pruneEmpty();
    $prunedTree = $ast->getTree();
    self::assertNodesEqualKeys($expectedPrunedTree, $prunedTree, 'The tree should be pruned.');
  }

  /**
   * @covers ::isEmpty
   * @covers ::<!public>
   */
  public function testEmptyAst(): void
  {
    $ast = new Ast();
    self::assertTrue($ast->isEmpty(), 'An AST should be detected correctly.');

    $ast->pushNode(new DummyNode('a'));
    self::assertFalse($ast->isEmpty(), 'A non-AST should be detected correctly.');
  }

  /**
   * @covers ::isEmpty
   * @covers ::getCurrentNode
   * @covers ::<!public>
   */
  public function testAccessEmptyAstNode(): void
  {
    $this->expectException(\UnderflowException::class);

    $ast = new Ast();
    $ast->getCurrentNode();
  }

  /**
   * @param list<string> $expectedKeys
   * @param list<AbstractNode> $nodes
   */
  private static function assertNodesEqualKeys(array $expectedKeys, array $nodes, string $message): void
  {
    $keys = array_map(static function (AbstractNode $node) use ($message): string {
      self::assertInstanceOf(DummyNode::class, $node, $message);
      return $node->key;
    }, $nodes);

    self::assertEquals($expectedKeys, $keys, $message);
  }
}
