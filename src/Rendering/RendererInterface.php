<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering;

use Averay\TokenisedStrings\Parsing\Ast;
use Averay\TokenisedStrings\Parsing\Nodes;
use Averay\TokenisedStrings\Rendering\Formatting\SimpleParamFormatter;
use Averay\TokenisedStrings\Rendering\Formatting\ParamFormatterInterface;
use Averay\TokenisedStrings\Values\ValueBag;
use Averay\TokenisedStrings\Values\ValueBagInterface;

/**
 * @psalm-type Params = array<string,mixed>
 * @psalm-type ParamProcessor = callable(string):string
 */
interface RendererInterface
{
  /**
   * @param Params $params
   * @param ParamProcessor|null $paramProcessor A processor that will be applied to each parameter when inserted into the template. If unset no processing will be applied to the parameters.
   */
  public function render(Ast $ast, array $params = [], ?callable $paramProcessor = null): string;
}
