<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Exceptions;

class InaccessibleNestedValueException extends \OutOfBoundsException
{
  public function __construct(string $message, string $propertyPath, \Throwable $previous = null)
  {
    $message .= sprintf(' (at path "%s")', $propertyPath);
    parent::__construct($message, 0, $previous);
  }
}
