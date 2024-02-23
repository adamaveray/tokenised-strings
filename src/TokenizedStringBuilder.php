<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings;

use Averay\TokenisedStrings\Parsing\Nodes;
use Averay\TokenisedStrings\Parsing\Parser;
use Averay\TokenisedStrings\Parsing\ParserInterface;
use Averay\TokenisedStrings\Parsing\ParserTokenEnum;
use Averay\TokenisedStrings\Rendering\Renderer;
use Averay\TokenisedStrings\Rendering\RendererInterface;
use Averay\TokenisedStrings\Rendering\Traits\WithModifiers;

/**
 * @psalm-import-type ParamProcessor from RendererInterface
 * @psalm-import-type Modifier from WithModifiers
 */
final readonly class TokenizedStringBuilder implements TokenizedStringBuilderInterface
{
  final public const TOKEN_OPEN = '{{';
  final public const TOKEN_CLOSE = '}}';

  private ParserInterface $parser;
  private Renderer $renderer;

  public function __construct()
  {
    // Define custom syntax
    $this->parser = new Parser();
    $this->parser->setTokens([
      ParserTokenEnum::TagOpen->value => self::TOKEN_OPEN,
      ParserTokenEnum::TagClose->value => self::TOKEN_CLOSE,
    ]);

    $this->renderer = new Renderer();
  }

  public function addParam(string $key, mixed $value): static
  {
    $this->renderer->addValue($key, $value);
    return $this;
  }

  /** @param Modifier $fn */
  public function addModifier(string $name, callable $fn): static
  {
    $this->renderer->addModifier($name, $fn);
    return $this;
  }

  public function build(string $string, array $additionalParams = [], ?callable $paramProcessor = null): string
  {
    return $this->render($string, $additionalParams, $paramProcessor);
  }

  public function buildAsUrl(string $url, array $additionalParams = [], bool $raw = false): string
  {
    return $this->build($url, $additionalParams, $raw ? \rawurlencode(...) : \urlencode(...));
  }

  public function buildAsHtml(string $html, array $additionalParams = []): string
  {
    return $this->build(
      $html,
      $additionalParams,
      static fn(string $value): string => \htmlspecialchars(
        $value,
        \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5,
        'UTF-8',
      ),
    );
  }

  /**
   * @param array<string, mixed> $additionalParams
   * @param (callable(string):string)|null $valueProcessor
   */
  private function render(string $string, array $additionalParams = [], ?callable $valueProcessor = null): string
  {
    $ast = $this->parser->parse($string);
    return $this->renderer->render($ast, $additionalParams, $valueProcessor);
  }

  /** @param array<string, mixed> $additionalParams */
  public function canBuild(string $string, array $additionalParams = []): bool
  {
    // Create temporary value bag including provided additional params
    $values = clone $this->renderer->getValueBag();
    $values->add($additionalParams);

    // Check bag has each value in provided string
    $tree = $this->parser->parse($string)->getTree();
    foreach ($tree as $node) {
      if ($node instanceof Nodes\TagNode && !$values->has($node->param)) {
        // Missing value
        return false;
      }
    }

    // All values present
    return true;
  }
}
