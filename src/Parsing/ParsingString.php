<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Parsing;

/**
 * Encapsulates string being parsed, providing multibyte-safe utility methods for inspecting & traversing the string.
 *
 * "Peek" methods will retrieve the following substring without modifying the internal pointer, "seek" methods will advance the pointer pointer, and "read" methods will retrieve the following substring and advance the pointer by the retrieved substring length.
 *
 * Attempts to peek, read or advance by a number of characters greater than remain will stop at the maximum remaining length.
 *
 * @internal
 */
final class ParsingString
{
  /** @var int The full length of the string. */
  private readonly int $length;

  /** @var int The current position within the string (0-indexed), also referred to as the pointer. */
  private int $index = 0;

  /**
   * @param string $string The string to operate on.
   * @param string $whitespacePattern A regular expression (without surrounding delimiters) that matches a continuous block of whitespace characters.
   */
  public function __construct(private readonly string $string, private readonly string $whitespacePattern = '\\s+')
  {
    $this->length = \mb_strlen($string);
  }

  /**
   * @return int The full length of the string.
   */
  public function getLength(): int
  {
    return $this->length;
  }

  /**
   * @return int The current internal pointer’s position within the string (0-indexed).
   */
  public function getIndex(): int
  {
    return $this->index;
  }

  /**
   * @return bool Whether the internal pointer is at the end of the string.
   */
  public function isAtEnd(): bool
  {
    return $this->index >= $this->length;
  }

  /**
   * @return void Resets the internal pointer to the beginning of the string.
   */
  public function rewind(): void
  {
    $this->index = 0;
  }

  /**
   * Retrieves the next substring without modifying the internal pointer.
   */
  public function peek(int $length): string
  {
    return mb_substr($this->string, $this->index, $length);
  }

  /**
   * Retrieves the next substring and advances the internal pointer by the retrieved substring’s length.
   */
  public function read(int $length): string
  {
    $result = $this->peek($length);
    $this->seekSame($result);
    return $result;
  }

  /**
   * Advances the internal pointer by a number of characters.
   */
  public function seek(int $length): void
  {
    $this->index = min($this->index + $length, $this->length);
  }

  /**
   * Retrieves the next substring of the same length as the provided string without modifying the internal pointer.
   */
  public function peekSame(string $string): string
  {
    return $this->peek(\mb_strlen($string));
  }

  /**
   * Retrieves the next substring of the same length as the provided string and advances the internal pointer by the retrieved substring’s length.
   */
  public function readSame(string $str): string
  {
    return $this->read(\mb_strlen($str));
  }

  /**
   * Advances the internal pointer by the length of the provided string.
   */
  public function seekSame(string $str): void
  {
    $this->seek(\mb_strlen($str));
  }

  /**
   * Retrieves the following matching substring without modifying the internal pointer.
   *
   * @param string $pattern A regular expression (without surrounding delimiters).
   */
  public function peekPattern(string $pattern): string
  {
    $substr = substr($this->string, $this->index);
    preg_match('~^' . $pattern . '~u', $substr, $matches);
    return $matches[0] ?? '';
  }

  /**
   * Retrieves the following matching substring and advances the internal pointer by the retrieved substring’s length.
   *
   * @param string $pattern A regular expression (without surrounding delimiters).
   */
  public function readPattern(string $pattern): string
  {
    $string = $this->peekPattern($pattern);
    $this->seekSame($string);
    return $string;
  }

  /**
   * Advances the internal pointer by the length of the following matching substring.
   *
   * @param string $pattern A regular expression (without surrounding delimiters).
   */
  public function seekPattern(string $pattern): void
  {
    $string = $this->peekPattern($pattern);
    $this->seekSame($string);
  }

  /**
   * Advances the internal until reaching the next non-whitespace character.
   */
  public function seekWhitespace(): void
  {
    $this->seekPattern($this->whitespacePattern);
  }

  /**
   * @return bool Whether the following characters are equal to the provided string.
   */
  public function nextEquals(string $test): bool
  {
    return $this->peekSame($test) === $test;
  }

  /**
   * @param string $pattern A regular expression (without surrounding delimiters).
   * @return bool Whether the following characters match the provided regular expression.
   */
  public function nextMatches(string $pattern): bool
  {
    return $this->peekPattern($pattern) !== '';
  }

  public function __toString(): string
  {
    return $this->string;
  }
}
