<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Rendering\Formatting;

/**
 * Converts a value to a string for inserting within templates.
 */
interface ParamFormatterInterface
{
  /**
   * @param mixed $value The value to format.
   * @return string The formatted value.
   */
  public function format(mixed $value): string;
}
