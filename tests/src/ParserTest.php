<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Averay\TokenisedStrings\Exceptions\ParsingException;
use Averay\TokenisedStrings\Parsing\Nodes;
use Averay\TokenisedStrings\Parsing\Parser;
use Averay\TokenisedStrings\Parsing\ParserTokenEnum;

#[CoversClass(Parser::class)]
final class ParserTest extends TestCase
{
  /**
   * @covers ::parse
   * @covers ::<!public>
   */
  #[DataProvider('parseDataProvider')]
  public function testParse(string $string, array $expectedTree): void
  {
    $parser = new Parser();
    $ast = $parser->parse($string);
    self::assertEquals($expectedTree, $ast->getTree(), 'The correct AST should be generated.');
  }

  public static function parseDataProvider(): iterable
  {
    yield 'No Tags' => [
      'string' => 'Hello World',
      'nodes' => [
        0 => new Nodes\TextNode('Hello World'),
      ],
    ];

    yield 'Simple Tag' => [
      'string' => 'Hello {tag} World',
      'nodes' => [
        0 => new Nodes\TextNode('Hello '),
        1 => new Nodes\TagNode(['tag'], []),
        2 => new Nodes\TextNode(' World'),
      ],
    ];

    yield 'Tag-Only' => [
      'string' => '{tag}',
      'nodes' => [
        0 => new Nodes\TagNode(['tag'], []),
      ],
    ];

    yield 'Nested Tag Properties' => [
      'string' => '{tag.with.nesting}',
      'nodes' => [
        0 => new Nodes\TagNode(['tag', 'with', 'nesting'], []),
      ],
    ];

    yield 'Complex' => [
      'string' => <<<'TXT'
      { tag.with.nesting|modifier1|modifier2 }{ another.tag } Some Content { tag|modifier }{ final }
      TXT
      ,
      'nodes' => [
        0 => new Nodes\TagNode(['tag', 'with', 'nesting'], ['modifier1', 'modifier2']),
        1 => new Nodes\TagNode(['another', 'tag'], []),
        2 => new Nodes\TextNode(' Some Content '),
        3 => new Nodes\TagNode(['tag'], ['modifier']),
        4 => new Nodes\TagNode(['final'], []),
      ],
    ];
  }

  /**
   * @covers ::setTokens
   * @covers ::parse
   */
  public function testCustomTokens(): void
  {
    $string = 'Hello *[[ [[ world->with->properties > and > modifiers ]] the end';
    $expected = [
      0 => new Nodes\TextNode('Hello [[ '),
      1 => new Nodes\TagNode(['world', 'with', 'properties'], ['and', 'modifiers']),
      2 => new Nodes\TextNode(' the end'),
    ];

    $parser = new Parser();
    $parser->setTokens([
      ParserTokenEnum::Escape->value => '*',
      ParserTokenEnum::TagModifier->value => '>',
      ParserTokenEnum::TagOpen->value => '[[',
      ParserTokenEnum::TagClose->value => ']]',
      ParserTokenEnum::TagPropertyAccessor->value => '->',
    ]);
    $ast = $parser->parse($string);
    self::assertEquals($expected, $ast->getTree(), 'The correct AST should be generated.');
  }

  /**
   * @covers ::parse
   * @covers ::<!public>
   */
  #[DataProvider('invalidStringDataProvider')]
  public function testInvalidString(string $string): void
  {
    $parser = new Parser();

    $this->expectException(ParsingException::class);
    $parser->parse($string);
  }

  public static function invalidStringDataProvider(): iterable
  {
    yield 'Invalid Escape' => ['Hello \\world'];
    yield 'Escaping Nothing' => ['Hello world\\'];

    yield 'Unclosed Tag' => ['Hello {world'];
    yield 'Closing Unopened Tag' => ['Hello world}'];
    yield 'Tag-Within-Tag' => ['Hello {wor{ld}}'];
    yield 'Empty Tag' => ['Hello { } world'];

    yield 'Missing Modifiers' => ['Hello { world | }'];
    yield 'Missing Tag' => ['Hello { | world }'];
    yield 'Invalid Modifier' => ['Hello { world | | world }'];
  }
}
