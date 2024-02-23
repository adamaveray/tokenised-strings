<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering\Traits;

use Averay\TokenisedStrings\Rendering\Formatting\ParamFormatterInterface;

trait WithParamFormatter
{
  private ParamFormatterInterface $paramFormatter;

  final public function setParamFormatter(ParamFormatterInterface $paramFormatter): void
  {
    $this->paramFormatter = $paramFormatter;
  }

  final protected function formatValue(mixed $value): string
  {
    return $this->paramFormatter->format($value);
  }
}
