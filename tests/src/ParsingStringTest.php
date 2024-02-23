<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Averay\TokenisedStrings\Parsing\ParsingString;

#[CoversClass(ParsingString::class)]
final class ParsingStringTest extends TestCase
{
  /**
   * @covers ::peek
   * @covers ::peekPattern
   * @covers ::peekSame
   * @covers ::getIndex
   * @covers ::<!public>
   */
  public function testPeek(): void
  {
    $string = new ParsingString('Hello World');

    self::assertEquals('H', $string->peek(1));
    self::assertEquals('Hel', $string->peek(3));
    self::assertEquals('Hello World', $string->peek(999999), 'The substring should handle overflow.');
    self::assertEquals('Hello ', $string->peekSame('abcdef'));
    self::assertEquals('Hello', $string->peekPattern('\w+'));

    self::assertEquals(0, $string->getIndex(), 'The cursor should not have advanced.');
  }

  /**
   * @covers ::read
   * @covers ::readPattern
   * @covers ::readSame
   * @covers ::getIndex
   * @covers ::<!public>
   */
  public function testRead(): void
  {
    $string = new ParsingString('Hello World');

    self::assertEquals('H', $string->read(1));
    self::assertEquals('ell', $string->read(3));
    self::assertEquals('o Worl', $string->readSame('abcdef'));

    $string->rewind();
    self::assertEquals('Hello', $string->readPattern('\w+'));

    self::assertEquals(5, $string->getIndex(), 'The cursor should have advanced.');
  }

  /**
   * @covers ::seek
   * @covers ::seekPattern
   * @covers ::seekSame
   * @covers ::getIndex
   * @covers ::<!public>
   */
  public function testSeek(): void
  {
    $string = new ParsingString('Hello World');

    $string->seek(1);
    self::assertEquals(1, $string->getIndex());
    $string->seek(3);
    self::assertEquals(4, $string->getIndex());

    $string->seekSame('abcdef');
    self::assertEquals(10, $string->getIndex());

    $string->seek(9999);
    self::assertEquals(11, $string->getIndex(), 'The cursor should not overflow.');

    $string->rewind();
    $string->seekPattern('\w+');
    self::assertEquals(5, $string->getIndex());
  }

  /**
   * @covers ::rewind
   * @covers ::<!public>
   */
  #[Depends('testSeek')]
  public function testRewind(): void
  {
    $string = new ParsingString('Hello World');

    self::assertEquals(0, $string->getIndex(), 'The cursor should start at 0.');
    $string->seek(5);
    self::assertNotEquals(0, $string->getIndex(), 'The cursor should have advanced.');
    $string->rewind();
    self::assertEquals(0, $string->getIndex(), 'The cursor should return to 0.');
  }

  /**
   * @covers ::getLength
   * @covers ::<!public>
   */
  public function testLength(): void
  {
    $string = new ParsingString('Hello World');
    self::assertEquals(11, $string->getLength());
  }

  /**
   * @covers ::isAtEnd
   * @covers ::<!public>
   */
  #[Depends('testSeek')]
  #[Depends('testRewind')]
  public function testEnd(): void
  {
    $string = new ParsingString('Hello World');
    self::assertFalse($string->isAtEnd());
    $string->seek(2);
    self::assertFalse($string->isAtEnd());
    $string->seek(99999);
    self::assertTrue($string->isAtEnd());
    $string->rewind();
    self::assertFalse($string->isAtEnd());
  }

  /**
   * @covers ::seekWhitespace
   * @covers ::<!public>
   */
  #[Depends('testPeek')]
  #[Depends('testSeek')]
  public function testSeekWhitespace(): void
  {
    $string = new ParsingString('Hello    world');
    $string->seek(5);
    self::assertEquals('    world', $string->peek(9999));
    $string->seekWhitespace();
    self::assertEquals(9, $string->getIndex(), 'Whitespace should have been skipped.');
    self::assertEquals('world', $string->peek(9999), 'Whitespace should have been skipped.');
  }

  /**
   * @covers ::setWhitespacePattern
   * @covers ::seekWhitespace
   * @covers ::<!public>
   */
  #[Depends('testSeekWhitespace')]
  public function testCustomWhitespace(): void
  {
    $string = new ParsingString('%&%&%&% %& Hello % World %&%&%&', whitespacePattern: '[%&]+');
    $string->seekWhitespace();
    self::assertEquals(7, $string->getIndex(), 'Custom whitespace characters should be skipped.');
  }

  /**
   * @covers ::nextEquals
   * @covers ::nextMatches
   * @covers ::<!public>
   */
  public function testNext(): void
  {
    $string = new ParsingString('Hello World');

    self::assertTrue($string->nextEquals('Hello'));
    self::assertFalse($string->nextEquals('World'));

    self::assertTrue($string->nextMatches('\w+'));
    self::assertFalse($string->nextMatches('\d+'));
  }

  /**
   * @covers ::__toString
   * @covers ::<!public>
   */
  public function testToString(): void
  {
    $rawString = 'Hello World';
    $string = new ParsingString($rawString);
    self::assertSame($rawString, (string) $string);
  }
}
