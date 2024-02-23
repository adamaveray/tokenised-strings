<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Tests;

use Averay\TokenisedStrings\Rendering\RendererInterface;
use Averay\TokenisedStrings\Rendering\Traits\WithModifiers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Averay\TokenisedStrings\TokenizedStringBuilder;

/**
 * @psalm-import-type ParamProcessor from RendererInterface
 * @psalm-import-type Modifier from WithModifiers
 */
#[CoversClass(TokenizedStringBuilder::class)]
final class TokenizedStringBuilderTest extends TestCase
{
  /**
   * @param array<string,mixed> $params
   * @param array<string,mixed> $additionalParams
   * @param array<string,Modifier> $modifiers
   * @param ParamProcessor|null $paramProcessor
   */
  #[DataProvider('buildingStringDataProvider')]
  public function testBuildingString(
    string $expected,
    string $string,
    array $params,
    array $additionalParams = [],
    array $modifiers = [],
    ?callable $paramProcessor = null,
  ): void {
    $builder = new TokenizedStringBuilder();
    /** @psalm-suppress MixedAssignment */
    foreach ($params as $paramName => $paramValue) {
      $builder->addParam($paramName, $paramValue);
    }
    foreach ($modifiers as $modifierName => $modifierFn) {
      $builder->addModifier($modifierName, $modifierFn);
    }
    $result = $builder->build($string, $additionalParams, $paramProcessor);
    self::assertTrue($builder->canBuild($string, $additionalParams), 'The string should be buildable.');
    self::assertEquals($expected, $result, 'The correct string should be generated.');
  }

  /**
   * @return iterable<string, array{
   *   expected: string,
   *   string: string,
   *   params: array<string,mixed>,
   *   additionalParams: array<string,mixed>,
   * }>
   */
  public static function buildingStringDataProvider(): iterable
  {
    yield 'Simple' => [
      'expected' => 'A basic string',
      'string' => 'A basic string',
      'params' => [],
    ];

    yield 'With Tokens Replaced' => [
      'expected' => 'A string with a replaced value.',
      'string' => 'A string with a {{ test_token }}.',
      'params' => [
        'test_token' => 'replaced value',
      ],
    ];

    yield 'With Additional Tokens Replaced' => [
      'expected' => 'A string with a replaced value.',
      'string' => 'A string with a {{ test_token }}.',
      'params' => [],
      'additionalParams' => [
        'test_token' => 'replaced value',
      ],
    ];

    yield 'With Additional Tokens Overwriting Global' => [
      'expected' => 'A string with a subsequent value.',
      'string' => 'A string with a {{ test_token }}.',
      'params' => [
        'test_token' => 'initial value',
      ],
      'additionalParams' => [
        'test_token' => 'subsequent value',
      ],
    ];

    yield 'With Modifiers' => [
      'expected' => 'A string with a REPLACED VALUE.',
      'string' => 'A string with a {{ test_token | upper }}.',
      'params' => [
        'test_token' => 'replaced value',
      ],
      'additionalParams' => [],
      'modifiers' => ['upper' => \strtoupper(...)],
    ];

    yield 'With Custom Processor' => [
      'expected' => 'A string with a REPLACED VALUE.',
      'string' => 'A string with a {{ test_token }}.',
      'params' => [
        'test_token' => 'replaced value',
      ],
      'additionalParams' => [],
      'modifiers' => [],
      'paramProcessor' => \strtoupper(...),
    ];
  }

  /**
   * @param array<string,mixed> $params
   * @param array<string,mixed> $additionalParams
   */
  #[DataProvider('buildingUrlDataProvider')]
  public function testBuildingUrl(
    string $expected,
    string $string,
    array $params,
    array $additionalParams,
    bool $raw,
  ): void {
    $builder = new TokenizedStringBuilder();
    /** @psalm-suppress MixedAssignment */
    foreach ($params as $paramName => $paramValue) {
      $builder->addParam($paramName, $paramValue);
    }
    $result = $builder->buildAsUrl($string, $additionalParams, $raw);
    self::assertTrue($builder->canBuild($string, $additionalParams), 'The string should be buildable.');
    self::assertEquals($expected, $result, 'The correct URL should be generated.');
  }

  /**
   * @return iterable<string, array{
   *   expected: string,
   *   url: string,
   *   params: array<string,mixed>,
   *   additionalParams: array<string,mixed>,
   * }>
   */
  public static function buildingUrlDataProvider(): iterable
  {
    yield 'Simple' => [
      'expected' => 'https://www.example.com/',
      'url' => 'https://www.example.com/',
      'params' => [],
      'additionalParams' => [],
      'raw' => false,
    ];

    yield 'With Tokens Replaced' => [
      'expected' => 'https://www.example.com/test/replaced+value/',
      'url' => 'https://www.example.com/test/{{ test_token }}/',
      'params' => [
        'test_token' => 'replaced value',
      ],
      'additionalParams' => [],
      'raw' => false,
    ];

    yield 'With Additional Tokens Replaced' => [
      'expected' => 'https://www.example.com/test/replaced+value/',
      'url' => 'https://www.example.com/test/{{ test_token }}/',
      'params' => [],
      'additionalParams' => [
        'test_token' => 'replaced value',
      ],
      'raw' => false,
    ];

    yield 'With Additional Tokens Overwriting Global' => [
      'expected' => 'https://www.example.com/test/subsequent+value/',
      'url' => 'https://www.example.com/test/{{ test_token }}/',
      'params' => [
        'test_token' => 'initial value',
      ],
      'additionalParams' => [
        'test_token' => 'subsequent value',
      ],
      'raw' => false,
    ];

    yield 'With Raw Encoding' => [
      'expected' => 'https://www.example.com/test/replaced%20value/',
      'url' => 'https://www.example.com/test/{{ test_token }}/',
      'params' => [
        'test_token' => 'replaced value',
      ],
      'additionalParams' => [],
      'raw' => true,
    ];
  }

  /**
   * @param array<string,mixed> $params
   * @param array<string,mixed> $additionalParams
   */
  #[DataProvider('buildingHtmlDataProvider')]
  public function testBuildingHtml(string $expected, string $string, array $params, array $additionalParams): void
  {
    $builder = new TokenizedStringBuilder();
    /** @psalm-suppress MixedAssignment */
    foreach ($params as $paramName => $paramValue) {
      $builder->addParam($paramName, $paramValue);
    }
    $result = $builder->buildAsHtml($string, $additionalParams);
    self::assertTrue($builder->canBuild($string, $additionalParams), 'The string should be buildable.');
    self::assertEquals($expected, $result, 'The correct HTML should be generated.');
  }

  /**
   * @return iterable<string, array{
   *   expected: string,
   *   html: string,
   *   params: array<string,mixed>,
   *   additionalParams: array<string,mixed>,
   * }>
   */
  public static function buildingHtmlDataProvider(): iterable
  {
    yield 'Simple' => [
      'expected' => '<p>Hello world.</p>',
      'html' => '<p>Hello world.</p>',
      'params' => [],
      'additionalParams' => [],
    ];

    yield 'With Tokens Replaced' => [
      'expected' => '<p>Hello &lt;replaced value&gt;</p>',
      'html' => '<p>Hello {{ test_token }}</p>',
      'params' => [
        'test_token' => '<replaced value>',
      ],
      'additionalParams' => [],
    ];

    yield 'With Additional Tokens Replaced' => [
      'expected' => '<p>Hello &lt;subsequent value&gt;</p>',
      'html' => '<p>Hello {{ test_token }}</p>',
      'params' => [],
      'additionalParams' => [
        'test_token' => '<subsequent value>',
      ],
    ];

    yield 'With Additional Tokens Overwriting Global' => [
      'expected' => '<p>Hello &lt;replaced value&gt;</p>',
      'html' => '<p>Hello {{ test_token }}</p>',
      'params' => [
        'test_token' => '<initial value>',
      ],
      'additionalParams' => [
        'test_token' => '<replaced value>',
      ],
    ];
  }

  public function testUnbuildable(): void
  {
    $builder = new TokenizedStringBuilder();
    self::assertFalse($builder->canBuild('Example {{ undefinedValue }}'), 'The string should not be buildable.');
  }
}
