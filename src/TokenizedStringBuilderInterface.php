<?php
declare(strict_types=1);

namespace Averay\TokenisedStrings;

use Averay\TokenisedStrings\Rendering\RendererInterface;
use Averay\TokenisedStrings\Rendering\Traits\WithModifiers;

/**
 * @psalm-import-type Params from RendererInterface
 * @psalm-import-type ParamProcessor from RendererInterface
 * @psalm-import-type Modifier from WithModifiers
 */
interface TokenizedStringBuilderInterface
{
  /**
   * Adds a global parameter for use in templates.
   *
   * @param string $key The top-level key that templates can reference the parameter by.
   * @param mixed $value The value that will be used when the parameter is referenced in templates.
   */
  public function addParam(string $key, mixed $value): static;

  /** @param Modifier $fn */
  public function addModifier(string $name, callable $fn): static;

  /**
   * Determines whether a template is valid and can be rendered by checking all referenced parameters are present in either this instance’s shared parameters store or the provided additional parameters.
   *
   * @param string $string A template string.
   * @param Params $additionalParams Additional parameters to use while validating the template.
   * @return bool Whether the template is valid and all required parameters are present.
   */
  public function canBuild(string $string, array $additionalParams = []): bool;

  /**
   * Renders a templated string.
   *
   * @param string $string A template string.
   * @param Params $additionalParams Additional parameters that may be used within the template.
   * @param ParamProcessor|null $paramProcessor A processor that will be applied to each parameter when inserted into the template. If unset no processing will be applied to the parameters.
   * @return string The rendered template.
   */
  public function build(string $string, array $additionalParams = [], ?callable $paramProcessor = null): string;

  /**
   * Renders a templated string with each parameter URL encoded.
   *
   * @param string $url A template URL.
   * @param Params $additionalParams Additional parameters that may be used within the template.
   * @param bool $raw Whether to encode parameters as raw URL components (as encoded by `rawurlencode()`) or not (as encoded by `urlencode()`).
   * @return string The rendered template.
   */
  public function buildAsUrl(string $url, array $additionalParams = [], bool $raw = false): string;

  /**
   * Renders a templated string with each parameter HTML encoded.
   *
   * @param string $html Template HTML.
   * @param Params $additionalParams Additional parameters that may be used within the template.
   * @return string The rendered template.
   */
  public function buildAsHtml(string $html, array $additionalParams = []): string;
}
