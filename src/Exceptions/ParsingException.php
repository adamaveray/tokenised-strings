<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Exceptions;

class ParsingException extends \RuntimeException
{
  public function __construct(string $message, string $string, int $offset, \Throwable $previous = null)
  {
    $message .= sprintf(' (at offset %d of "%s")', $offset, $string);
    parent::__construct($message, 0, $previous);
  }
}
