# Tokenised Strings

A lightweight system for rendering tokens in templated strings using a familiar syntax inspired by Jinja or Twig.

## Motivation

The library aims to provide a more flexible substitute for PHP’s `str_replace` function.

Manual string concatenation does not allow templating, and simple `str_replace` substitutions offer limited functionality and require repetition. Meanwhile, a full templating engine such as Twig provides a lot of power at the cost of significantly increased complexity.

This library aims to fill a middle ground between the two options, allowing templates to be defined with a standard syntax and rendered throughout a system without needing to manage a full templating engine. The library is intended for rendering short tokens defined separately from where they are rendered, such as strings stored in configuration files or a database.

## Syntax

The default configuration delimits tokens using double curly braces (e.g. `{{ token_name }}`). Nested values can be accessed using periods (e.g. `{{ parent.child }}`. Modifiers a.k.a. filters can be applied using pipes (e.g. `{{ value | modifier }}`) and multiple can be applied. All whitespace between components is optional. The syntax is completely customisable (see below).

```
Report ID: {{ id }}
Name: {{ user.name }}
Profile: https://www.example.com/user?id={{ user.id | urlencode }}
```

No in-template logic such as assignments, conditions, or loops are supported (i.e. any functionality Twig’s `{% ... %}` tags provide), nor are comments.

## Usage

The primary class provides a method for rendering templated strings, and two additional helper methods for rendering URLs and HTML:

```php
<?php
$formatter = new \Averay\TokenisedStrings\TokenizedStringBuilder();

// Simple strings
echo $formatter->build('Today is {{ day }}.', ['day' => date('l')]);

// URLs (values will be URL encoded)
echo $formatter->buildUrl(
  'https://www.example.com/?page={{ page_id }}&ref=home',
  ['page_id' => 'example page'],
);

// HTML (values will be HTML encoded)
echo $formatter->buildUrl(
  '<p>Your order is <strong>{{ status }}</strong></p>.',
  ['status' => 'preparing'],
);
```

A custom value formatter can be provided, which will be applied to each value when rendered in the template:

```php
<?php
$formatter = new \Averay\TokenisedStrings\TokenizedStringBuilder();

$addEmoji = fn(string $string) => '⭐️' . $string . '⭐️';

echo $formatter->build('Today is {{ day }}.', ['day' => date('l')], $addEmoji);
```

Global parameters can be defined, which will be available in all templates:

```php
<?php
$formatter = new \Averay\TokenisedStrings\TokenizedStringBuilder();
$formatter->addParam('day', date('l'))->addParam('colour', 'purple');

echo $formatter->build('Today is {{ day }} and the colour is {{ colour }}.');
echo $formatter->build('Today’s colour is {{ colour }}.');
```

Global modifiers can also be defined, which will be available in all templates:

```php
<?php
$formatter = new \Averay\TokenisedStrings\TokenizedStringBuilder();
$formatter->addModifier('upper', \strtoupper(...));

echo $formatter->build('Today is {{ day | upper }}.', ['day' => date('l')]);
```

To define a custom syntax, the underlying Parser and Renderer classes can be used directly. In the following example, the custom syntax `[% value->property >> modifier %]` is used:

```php
<?php
$template = 'The code is [% code %].';
$tokens = ['code' => 'ABC123'];

// Configure parser with custom syntax
$parser = new \Averay\TokenisedStrings\Parsing\Parser();
$parser->setTokens([
  ParserTokenEnum::TagOpen->value => '[%',
  ParserTokenEnum::TagClose->value => '%]',
  ParserTokenEnum::TagModifier->value => '>>',
  ParserTokenEnum::TagPropertyAccessor->value => '->',
]);

// Render template
$renderer = new \Averay\TokenisedStrings\Rendering\Renderer();
echo $renderer->render($parser->parse($template), $tokens);
```

A system may wish to move the configuration out to a global utility function, to allow rendering templates from anywhere in the same way `str_replace` would be used:

```php
<?php
function renderString(string $template, array $values): string
{
  static $formatter;
  if ($formatter === null) {
    $formatter = new \Averay\TokenisedStrings\TokenizedStringBuilder();
    // Configure standard parameters, modifiers, etc...
  }

  return $formatter->build($template, $values);
}

// ...

echo renderString('Today is {{ day }}.', ['day' => date('l')]);
```
