<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing;

use Averay\TokenisedStrings\Exceptions\ParsingException;

class Parser implements ParserInterface
{
  private const PATTERN_VALUE = '[a-z](?:[a-zA-Z0-9_]*[a-zA-Z0-9])?';
  private const PATTERN_MODIFIER = '[a-z](?:[a-z0-9_]*[a-z0-9])?';

  /** @var array<value-of<ParserTokenEnum>, string> */
  private array $tokens = [
    ParserTokenEnum::Escape->value => '\\',
    ParserTokenEnum::TagOpen->value => '{',
    ParserTokenEnum::TagClose->value => '}',
    ParserTokenEnum::TagModifier->value => '|',
    ParserTokenEnum::TagPropertyAccessor->value => '.',
  ];

  /** The string currently being parsed. */
  private ParsingString $string;
  /** The AST for the current string. */
  private Ast $ast;

  public function __construct()
  {
    $this->string = new ParsingString('');
    $this->ast = new Ast();
  }

  /**
   * Defines new syntax strings for some or all tokens to be used in future parses. Any token types not specified will continue using their current syntax strings.
   *
   * @param array<value-of<ParserTokenEnum>, string> $tokens New strings for tokens.
   */
  public function setTokens(array $tokens): void
  {
    $this->tokens = array_merge($this->tokens, $tokens);
  }

  /**
   * @return string The syntax string for the given token.
   */
  private function t(ParserTokenEnum $token): string
  {
    /** @psalm-suppress PossiblyUndefinedStringArrayOffset -- All tokens are always defined. */
    return $this->tokens[$token->value];
  }

  /** Triggers a generic error. */
  private function fail(string $error, string|int|float ...$values): never
  {
    throw new ParsingException(sprintf($error, ...$values), (string) $this->string, $this->string->getIndex());
  }

  /** Triggers a error parsing a specific token. */
  private function failToken(ParserTokenEnum $token, ?string $error = null, string|int|float ...$values): void
  {
    $extra = $error === null ? '' : ' ' . sprintf($error, ...$values);
    $this->fail('Unexpected token ' . $token->value . $extra);
  }

  public function parse(string $string): Ast
  {
    $this->string = new ParsingString($string);

    // Reset state
    $this->ast = new Ast();

    // Parse
    $this->parseString();
    $this->ast->pruneEmpty();

    return $this->ast;
  }

  /** Parses a raw string until reaching a token or the end of the template. */
  private function parseString(): void
  {
    $end = function (string $nodeString): void {
      if ($nodeString !== '') {
        $this->ast->pushNode(new Nodes\TextNode($nodeString));
      }
    };

    $nodeString = '';
    $isEscaping = false;
    foreach ($this->walkString() as $string) {
      if ($isEscaping) {
        // Next value following escape
        $nodeString .= $this->handleEscaping();
        $isEscaping = false;
        continue;
      }

      if ($string->nextEquals($this->t(ParserTokenEnum::Escape))) {
        // Start escaped sequence
        $string->seekSame($this->t(ParserTokenEnum::Escape));
        $isEscaping = true;
        continue;
      }

      if ($string->nextEquals($this->t(ParserTokenEnum::TagOpen))) {
        // Starting tag
        $end($nodeString);
        $nodeString = '';

        $this->parseTag();
        continue;
      }

      if ($string->nextEquals($this->t(ParserTokenEnum::TagClose))) {
        // Ending tag
        $this->failToken(ParserTokenEnum::TagClose, 'outside tag');
      }

      // Continuing string
      $nodeString .= $string->read(1);
    }

    if ($isEscaping) {
      $this->fail('Unexpected end of string');
    }

    $end($nodeString);
  }

  /** Processes the content after an escape token. */
  private function handleEscaping(): string
  {
    $tokenEscape = $this->t(ParserTokenEnum::Escape);
    if ($this->string->nextEquals($tokenEscape)) {
      // Escaping an escape sequence - insert single entry
      $this->string->seekSame($tokenEscape);
      return $tokenEscape;
    }

    $nextIsOpen = $this->string->nextEquals($this->t(ParserTokenEnum::TagOpen));
    $nextIsClose = $this->string->nextEquals($this->t(ParserTokenEnum::TagClose));
    if ($nextIsOpen || $nextIsClose) {
      // Escaping a tag delimiter
      if (!$this->ast->isEmpty() && !($this->ast->getCurrentNode() instanceof Nodes\TextNode)) {
        $this->failToken($nextIsOpen ? ParserTokenEnum::TagOpen : ParserTokenEnum::TagClose, 'escaped outside string');
      }

      $token = $this->t($nextIsOpen ? ParserTokenEnum::TagOpen : ParserTokenEnum::TagClose);
      $this->string->seekSame($token);
      return $token;
    }

    // Escaping non-escapable character
    $this->fail('Unexpected escaped character "%s"', $this->string->peek(1));
  }

  /** Parses a complete tag. */
  private function parseTag(): void
  {
    $string = $this->string;

    // Handle opening
    if (!$string->nextEquals($this->t(ParserTokenEnum::TagOpen))) {
      $this->fail('Unexpected value');
    }
    $string->seekSame($this->t(ParserTokenEnum::TagOpen));
    $string->seekWhitespace();

    // Parse parameter
    $param = $this->parseTagParam();
    $string->seekWhitespace();

    // Parse modifiers
    $modifiers = $this->parseTagModifiers();

    // Handle closing
    $string->seekWhitespace();
    if (!$string->nextEquals($this->t(ParserTokenEnum::TagClose))) {
      $this->fail('Unexpected value "' . $string->peek(1) . '" 1');
    }
    $string->seekSame($this->t(ParserTokenEnum::TagClose));

    $this->ast->pushNode(new Nodes\TagNode($param, $modifiers));
  }

  /**
   * Parses a tag’s parameter component.
   *
   * @return non-empty-list<string> The parameter’s nested keys.
   */
  private function parseTagParam(): array
  {
    /** @var array<int,string> $nesting */
    $nesting = [];

    $currentDepth = 0;
    foreach ($this->walkString() as $string) {
      $value = $string->readPattern(self::PATTERN_VALUE);
      if ($value !== '') {
        // Value
        $nesting[$currentDepth] = $value;
        continue;
      }

      if ($string->nextEquals($this->t(ParserTokenEnum::TagPropertyAccessor))) {
        // Accessing child
        if (($nesting[$currentDepth] ?? '') === '') {
          $this->failToken(ParserTokenEnum::TagPropertyAccessor);
        }
        $currentDepth++;
        $string->seekSame($this->t(ParserTokenEnum::TagPropertyAccessor));
        continue;
      }

      break;
    }

    if (empty($nesting)) {
      $this->fail('Unexpected empty tag');
    }

    return array_values($nesting);
  }

  /**
   * Parses a tag’s modifiers component.
   *
   * @return list<string> Modifier identifiers.
   */
  private function parseTagModifiers(): array
  {
    /** @var list<string> $modifiers */
    $modifiers = [];
    foreach ($this->walkString() as $string) {
      if (!$string->nextEquals($this->t(ParserTokenEnum::TagModifier))) {
        // End of modifiers
        break;
      }

      $string->seekSame($this->t(ParserTokenEnum::TagModifier));
      $string->seekWhitespace();

      $modifier = $string->readPattern(self::PATTERN_MODIFIER);
      if ($modifier === '') {
        $this->fail('Unexpected value "' . $string->peek(1) . '" 2');
      }

      $modifiers[] = $modifier;
      $string->seekWhitespace();
    }
    return $modifiers;
  }

  /**
   * Iterates while the parsing string’s internal pointer is not at its end, providing the string instance on each iteration for ease of access.
   *
   * @return iterable<ParsingString>
   */
  private function walkString(): iterable
  {
    while (!$this->string->isAtEnd()) {
      yield $this->string;
    }
  }
}
