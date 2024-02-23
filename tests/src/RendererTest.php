<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Averay\TokenisedStrings\Parsing\Ast;
use Averay\TokenisedStrings\Parsing\Parser;
use Averay\TokenisedStrings\Rendering\Renderer;

#[CoversClass(Renderer::class)]
final class RendererTest extends TestCase
{
  /**
   * @covers ::render
   * @covers ::addModifiers
   * @covers ::addValues
   * @covers ::<!public>
   *
   * @param array<string,mixed> $values
   * @param array<string,callable(mixed):mixed> $modifiers
   */
  #[DataProvider('renderDataProvider')]
  public function testRender(string $expected, string $string, array $values = [], array $modifiers = []): void
  {
    $renderer = new Renderer();
    $renderer->addValues($values);
    $renderer->addModifiers($modifiers);

    $result = $renderer->render(self::parse($string));
    self::assertEquals($expected, $result, 'The correct string should be rendered.');
  }

  public static function renderDataProvider(): iterable
  {
    yield 'No tags' => [
      'expected' => 'Hello world.',
      'string' => 'Hello world.',
    ];

    yield 'Simple tags' => [
      'expected' => 'Hello world.',
      'string' => 'Hello {value}.',
      'values' => ['value' => 'world'],
    ];

    yield 'Nested tags' => [
      'expected' => 'Hello world.',
      'string' => 'Hello {value.with.nesting}.',
      'values' => ['value' => ['with' => ['nesting' => 'world']]],
    ];

    yield 'Modified tags' => [
      'expected' => 'Hello world.',
      'string' => 'Hello {value | lower}.',
      'values' => ['value' => 'WORLD'],
      'modifiers' => ['lower' => \strtolower(...)],
    ];

    yield 'Modified nested tags' => [
      'expected' => 'Hello world.',
      'string' => 'Hello {value.with.nesting | lower}.',
      'values' => ['value' => ['with' => ['nesting' => 'WORLD']]],
      'modifiers' => ['lower' => \strtolower(...)],
    ];
  }

  /**
   * @covers ::addValues
   * @covers ::addValue
   * @covers ::<!public>
   */
  #[Depends('testRender')]
  public function testValueOverrides(): void
  {
    $renderer = new Renderer();

    $renderer->addValue('one', 'one[start]');
    $renderer->addValues(['one' => 'one[group]', 'two' => 'two[group]']);
    $renderer->addValue('two', 'two[end]');

    self::assertEquals(
      'one[group], two[end]',
      $renderer->render(self::parse('{one}, {two}')),
      'Values should be set and overridden correctly.',
    );
  }

  /**
   * @covers ::addModifiers
   * @covers ::addModifier
   * @covers ::<!public>
   */
  #[Depends('testRender')]
  public function testModifierOverrides(): void
  {
    $renderer = new Renderer();
    $renderer->addValue('value', 'Value');

    $renderer->addModifier('one', 'str_rot13');
    $renderer->addModifiers(['one' => 'strtolower', 'two' => 'strtolower']);
    $renderer->addModifier('two', 'strtoupper');

    self::assertEquals(
      'value, VALUE',
      $renderer->render(self::parse('{value|one}, {value|two}')),
      'Modifiers should be set and overridden correctly.',
    );
  }

  /**
   * @covers ::render
   * @covers ::<!public>
   * @param array<string,mixed> $values
   */
  #[DataProvider('nestedValuesDataProvider')]
  #[Depends('testRender')]
  public function testNestedValues(string $expected, string $accessor, array $values): void
  {
    $renderer = new Renderer();
    $renderer->addValues($values);
    $result = $renderer->render(self::parse('{' . $accessor . '}'));
    self::assertEquals($expected, $result, 'The nested value should be accessed correctly.');
  }

  /**
   * @return iterable<string, array{
   *   expected: string,
   *   accessor: string,
   *   values: array<string,mixed>,
   * }>
   */
  public static function nestedValuesDataProvider(): iterable
  {
    yield 'Root' => [
      'expected' => 'result',
      'accessor' => 'value',
      'values' => ['value' => 'result'],
    ];

    yield 'Arrays' => [
      'expected' => 'result',
      'accessor' => 'a.b.c',
      'values' => ['a' => ['b' => ['c' => 'result']]],
    ];

    yield 'Object Properties' => [
      'expected' => 'result',
      'accessor' => 'a.b.c',
      'values' => ['a' => (object) ['b' => (object) ['c' => 'result']]],
    ];

    yield 'Object Accessors' => [
      'expected' => 'result',
      'accessor' => 'a.b.c',
      'values' => [
        'a' => new class {
          public function getB(): object
          {
            return new class {
              public function getC(): string
              {
                return 'result';
              }
            };
          }
        },
      ],
    ];

    yield 'Mixed' => [
      'expected' => 'result',
      'accessor' => 'a.b.c.d.e',
      'values' => [
        'a' => new class {
          public function getB(): array
          {
            return [
              'c' => (object) [
                'd' => new class {
                  public function getE(): string
                  {
                    return 'result';
                  }
                },
              ],
            ];
          }
        },
      ],
    ];
  }

  public function testUndefinedValue(): void
  {
    $renderer = new Renderer();
    $this->expectException(\OutOfBoundsException::class);
    $renderer->render(self::parse('Hello {world}.'));
  }

  private static function parse(string $string): Ast
  {
    return (new Parser())->parse($string);
  }
}
