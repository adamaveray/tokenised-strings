<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering;

use Averay\TokenisedStrings\Parsing\Ast;
use Averay\TokenisedStrings\Parsing\Nodes;
use Averay\TokenisedStrings\Rendering\Formatting\SimpleParamFormatter;
use Averay\TokenisedStrings\Rendering\Traits\WithModifiers;
use Averay\TokenisedStrings\Rendering\Traits\WithParamFormatter;
use Averay\TokenisedStrings\Rendering\Traits\WithParams;
use Averay\TokenisedStrings\Values\ValueBag;

/**
 * @psalm-import-type Params from RendererInterface
 * @psalm-import-type ParamProcessor from RendererInterface
 */
final class Renderer implements RendererInterface
{
  use WithModifiers;
  use WithParamFormatter;
  use WithParams;

  public function __construct()
  {
    $this->setParamFormatter(new SimpleParamFormatter());
    $this->setValueBag(new ValueBag());
  }

  public function render(Ast $ast, array $params = [], ?callable $paramProcessor = null): string
  {
    $values = clone $this->valueBag;
    $values->add($params);

    $rendered = '';
    foreach ($ast->getTree() as $node) {
      if ($node instanceof Nodes\TextNode) {
        $rendered .= $node->content;
      } elseif ($node instanceof Nodes\TagNode) {
        /** @psalm-suppress MixedAssignment */
        $value = $values->get($node->param);

        $renderedTag = $this->formatValue($this->applyModifiers($value, $node->modifiers));
        if ($paramProcessor !== null) {
          // Apply renderer
          $renderedTag = $paramProcessor($renderedTag);
        }
        $rendered .= $renderedTag;
      } else {
        throw new \OutOfBoundsException(sprintf('Unknown node type "%s".', $node::class));
      }
    }
    return $rendered;
  }
}
