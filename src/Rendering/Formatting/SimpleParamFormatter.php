<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering\Formatting;

final readonly class SimpleParamFormatter implements ParamFormatterInterface
{
  public function format(mixed $value): string
  {
    return match (\gettype($value)) {
      'boolean' => $value ? 'true' : 'false',
      'array' => \json_encode($value, \JSON_THROW_ON_ERROR),
      default => (string) $value,
    };
  }
}
