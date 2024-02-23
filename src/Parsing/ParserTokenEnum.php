<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing;

enum ParserTokenEnum: string
{
  case Escape = 'ESCAPE';
  case TagOpen = 'TAG_OPEN';
  case TagClose = 'TAG_CLOSE';
  case TagModifier = 'TAG_MODIFIER';
  case TagPropertyAccessor = 'TAG_PROPERTY_ACCESSOR';
}
