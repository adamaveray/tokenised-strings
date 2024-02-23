<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing;

use Averay\TokenisedStrings\Exceptions\ParsingException;

interface ParserInterface
{
  /**
   * @param string $string A template string.
   * @return Ast The AST for the provided template.
   * @throws ParsingException The template could not be parsed.
   */
  public function parse(string $string): Ast;
}
