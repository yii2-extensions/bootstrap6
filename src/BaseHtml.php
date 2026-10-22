<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use BackedEnum;
use Stringable;
use UIAwesome\Html\Helper\Enum;
use UnitEnum;
use Yii;
use yii\base\{InvalidConfigException, Model};
use yii\helpers\{ArrayHelper, Html as YiiHtml};
use yii2\extensions\bootstrap6\enum\{Breakpoint, Theme};
use yii2\extensions\bootstrap6\exception\Message;

use function array_column;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function explode;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function ord;
use function sprintf;
use function str_contains;
use function str_split;

/**
 * Provides the Bootstrap 6 implementation of {@see Html}.
 *
 * Checkboxes and radios render the `.check` and `.radio` classes, switches render the `.switch` wrapper, and error
 * blocks default to `.invalid-feedback`.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/field/
 */
abstract class BaseHtml extends YiiHtml
{
    /**
     * Prefix of the `id` values generated for checkbox and radio labels.
     */
    public static string $autoIdPrefix = 'i';
    /**
     * Counter used to generate the `id` values for checkbox and radio labels.
     *
     * @internal
     */
    public static int $counter = 0;
    /**
     * Whether to remove duplicated classes when rendering the `class` attribute.
     *
     * @var bool
     */
    public static $normalizeClassAttribute = true;

    /**
     * Generates a static form control for the given model attribute.
     *
     * The `value` option, when present, replaces the attribute value.
     *
     * @param Model $model Model holding the attribute.
     * @param string $attribute Attribute name or expression.
     * @param array<mixed> $options Tag attributes, see {@see staticControl()}.
     *
     * @return string Generated HTML.
     */
    public static function activeStaticControl(Model $model, string $attribute, array $options = []): string
    {
        $value = ArrayHelper::remove($options, 'value', static::getAttributeValue($model, $attribute));

        return static::staticControl(self::stringValue($value) ?? '', $options);
    }

    /**
     * Adds CSS classes to the options; enum cases are converted to their value (backed) or name (pure).
     *
     * @template TKey of array-key
     *
     * @param array<TKey, mixed> $options Tag attributes to modify.
     * @param array<mixed>|string|Stringable|UnitEnum $class Class, or list of classes (keys are kept), each a `string`,
     * an enum case, or a {@see Stringable} value; `null` entries are ignored, and the options are left unchanged when
     * no entry remains.
     *
     * @param-out array<TKey, mixed> $options
     */
    public static function addCssClass(&$options, $class): void
    {
        if (!is_array($class)) {
            parent::addCssClass($options, Enum::normalizeStringValue($class));

            return;
        }

        $class = array_filter($class, static fn(mixed $item): bool => $item !== null);

        if ($class !== []) {
            parent::addCssClass($options, Enum::normalizeArray($class));
        }
    }

    /**
     * Generates a badge.
     *
     * The `encode` option controls whether the label is HTML-encoded (default `true`).
     *
     * @param string|Stringable $label Badge content.
     * @param string|Theme|null $theme Theme color, or `null` for the default color.
     * @param array<mixed> $options Tag attributes.
     *
     * @throws InvalidConfigException if `$theme` is a `string` that is not a {@see Theme} value.
     *
     * @return string Generated HTML, for example `<span class="badge theme-primary">New</span>`.
     *
     * @see https://getbootstrap.com/docs/6.0/components/badge/
     */
    public static function badge(string|Stringable $label, string|Theme|null $theme = null, array $options = []): string
    {
        $encode = ArrayHelper::remove($options, 'encode', true);

        self::prependCssClass($options, 'badge');
        static::theme($options, $theme);

        return static::tag('span', $encode === false ? $label : static::encode($label), $options);
    }

    /**
     * Generates a list of Bootstrap checkboxes, each wrapped in a `.form-field` element.
     *
     * Besides the core options (`item`, `itemOptions`, `encode`, `separator`, `tag`, `unselect`, `strict`), the
     * following options are handled:
     *
     * - `inline`: `bool`, whether to lay out the items horizontally (`d-flex flex-wrap gap-5` on the container instead
     *   of `form-group`).
     * - `itemOptions['wrapperOptions']`: `array`, HTML attributes of each `.form-field` item wrapper, for example
     *   `form-field-card`.
     *
     * When `item` is set, the callback renders each item and neither the wrapper nor the `.form-field` element is
     * added to it.
     *
     * @param string $name Input name; `[]` is appended when missing.
     * @param array<mixed>|string|null $selection Selected value or values.
     * @param array<mixed> $items Data items, value => label.
     * @param array<mixed> $options Container attributes and the special options above.
     *
     * @see https://getbootstrap.com/docs/6.0/forms/field/#grouped
     *
     * @return string Generated HTML.
     */
    public static function checkboxList($name, $selection = null, $items = [], $options = []): string
    {
        return parent::checkboxList($name, $selection, $items, self::listOptions('checkbox', $options));
    }

    /**
     * Generates a close button with a translated `aria-label`.
     *
     * @param array<mixed> $options Tag attributes.
     *
     * @return string Generated HTML, for example
     * `<button type="button" class="btn-close" aria-label="Close"></button>`.
     *
     * @see https://getbootstrap.com/docs/6.0/components/close-button/
     */
    public static function closeButton(array $options = []): string
    {
        $options['type'] ??= 'button';
        $options['aria-label'] ??= Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Close');

        self::prependCssClass($options, 'btn-close');

        return static::tag('button', '', $options);
    }

    /**
     * Returns the `string` value of an enum-typed value, validating strings against the enum.
     *
     * It is the single normalization point of the enum-typed widget properties: widgets call it from `init()` so that
     * `widget()` and `begin()` fail fast, and again while rendering to obtain the value. Enum cases are converted with
     * {@see Enum::normalizeStringValue()}; `null` is returned unchanged.
     *
     * With `$responsive`, a `string` may also be a space-separated list of values, each optionally prefixed with a
     * {@see Breakpoint} value, for example `bottom-start md:top-end`.
     *
     * @template T of BackedEnum
     *
     * @param class-string<T> $enum String-backed enum the value belongs to.
     * @param string|T|null $value Enum case, its `string` value, or `null`.
     * @param string $property Property name reported in the exception message.
     * @param bool $responsive Whether to accept breakpoint-prefixed values.
     *
     * @throws InvalidConfigException if `$value` is a `string` that is not a value of `$enum`.
     *
     * @return string|null Value of the enum case, the validated `string`, or `null`.
     */
    public static function enumValue(
        string $enum,
        BackedEnum|string|null $value,
        string $property,
        bool $responsive = false,
    ): string|null {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && !self::isEnumString($enum, $value, $responsive)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage(
                    $value,
                    $property,
                    implode(', ', array_column($enum::cases(), 'value')),
                ),
            );
        }

        return Enum::normalizeStringValue($value);
    }

    /**
     * Generates the validation error element with the default Bootstrap feedback class.
     *
     * @param Model $model Model holding the attribute.
     * @param string $attribute Attribute name or expression.
     * @param array<mixed> $options Tag options; the `class` attribute defaults to `invalid-feedback`.
     *
     * @return string Generated HTML.
     */
    public static function error($model, $attribute, $options = []): string
    {
        if (!array_key_exists('class', $options)) {
            $options['class'] = 'invalid-feedback';
        }

        return parent::error($model, $attribute, $options);
    }

    /**
     * Escapes a string for use as a CSS identifier in a selector, as `CSS.escape()` does.
     *
     * Bytes from `0x80` up are kept verbatim, so every non-ASCII character of a UTF-8 string passes through unchanged.
     *
     * @param string $identifier Raw identifier, such as an element `id` or a class name.
     *
     * @return string Identifier safe to interpolate after `#` or `.`, for example `account\:edit` for `account:edit`.
     *
     * @see https://drafts.csswg.org/cssom/#serialize-an-identifier
     */
    public static function escapeCssIdentifier(string $identifier): string
    {
        if ($identifier === '-') {
            return '\\-';
        }

        $escaped = '';

        foreach (str_split($identifier) as $index => $char) {
            $byte = ord($char);
            $digit = $byte >= 0x30 && $byte <= 0x39;

            $escaped .= match (true) {
                $byte === 0 => "\u{FFFD}",
                $byte <= 0x1F,
                $byte === 0x7F,
                $digit && ($index === 0 || ($index === 1 && $identifier[0] === '-')) => sprintf('\\%x ', $byte),
                $digit,
                $byte >= 0x80,
                $char === '-',
                $char === '_',
                $byte >= 0x41 && $byte <= 0x5A,
                $byte >= 0x61 && $byte <= 0x7A => $char,
                default => '\\' . $char,
            };
        }

        return $escaped;
    }

    /**
     * Generates a list of Bootstrap radios, each wrapped in a `.form-field` element.
     *
     * Accepts the same options as {@see checkboxList()}.
     *
     * @param string $name Input name.
     * @param array<mixed>|string|null $selection Selected value.
     * @param array<mixed> $items Data items, value => label.
     * @param array<mixed> $options Container attributes and the special options of {@see checkboxList()}.
     *
     * @see https://getbootstrap.com/docs/6.0/forms/field/#grouped
     *
     * @return string Generated HTML.
     */
    public static function radioList($name, $selection = null, $items = [], $options = []): string
    {
        return parent::radioList($name, $selection, $items, self::listOptions('radio', $options));
    }

    /**
     * Generates a read-only plain-text input.
     *
     * @param string $value Control value.
     * @param array<mixed> $options Tag attributes.
     *
     * @return string Generated HTML, for example
     * `<input type="text" class="form-control-plaintext" value="x" readonly>`.
     *
     * @see https://getbootstrap.com/docs/6.0/forms/form-control/#readonly-plain-text
     */
    public static function staticControl(string $value, array $options = []): string
    {
        self::prependCssClass($options, 'form-control-plaintext');

        $options['readonly'] = true;

        return static::input('text', null, $value, $options);
    }

    /**
     * Adds the `theme-{value}` class to the options.
     *
     * @param array<mixed> $options Tag attributes to modify.
     * @param string|Theme|null $theme Theme color, or `null` to leave the options unchanged.
     *
     * @throws InvalidConfigException if `$theme` is a `string` that is not a {@see Theme} value.
     */
    public static function theme(array &$options, string|Theme|null $theme): void
    {
        $theme = static::enumValue(Theme::class, $theme, 'theme');

        if ($theme !== null) {
            static::addCssClass($options, "theme-{$theme}");
        }
    }

    /**
     * Generates a Bootstrap checkbox, switch, or radio.
     *
     * Besides the core options (`uncheck`, `label`, `labelOptions`), the following options are handled:
     *
     * - `switch`: `bool`, checkbox only. Renders
     *   `<div class="switch"><input type="checkbox" role="switch" switch></div>` instead of the `.check` class.
     * - `switchOptions`: `array`, HTML attributes of the `.switch` wrapper, for example `theme-success` or `switch-lg`.
     * - `wrapInput`: `bool`, whether to render the control inside the `<label>` instead of before it.
     *
     * When `label` is set, the control receives an auto-generated `id` (see {@see $autoIdPrefix}) unless one is given,
     * and the label is rendered as `<label for="id">`. The label is NOT HTML-encoded.
     *
     * @param string $type Input type, either `checkbox` or `radio`.
     * @param string $name Input name.
     * @param bool $checked Whether the input is checked; the `checked` option takes precedence.
     * @param array<mixed> $options Tag attributes and the special options above.
     *
     * @return string Generated HTML.
     */
    protected static function booleanInput($type, $name, $checked = false, $options = []): string
    {
        $options['checked'] ??= $checked;

        $label = self::stringValue(ArrayHelper::remove($options, 'label'));
        $labelOptions = self::arrayValue(ArrayHelper::remove($options, 'labelOptions'));
        $switch = ArrayHelper::remove($options, 'switch') === true && $type === 'checkbox';
        $switchOptions = self::arrayValue(ArrayHelper::remove($options, 'switchOptions'));
        $uncheck = self::stringValue(ArrayHelper::remove($options, 'uncheck'));
        $value = self::stringValue(ArrayHelper::remove($options, 'value', '1'));
        $wrapInput = ArrayHelper::remove($options, 'wrapInput') === true;

        $hidden = $uncheck === null
            ? ''
            : static::hiddenInput($name, $uncheck, array_intersect_key($options, array_flip(['disabled', 'form'])));

        if ($label === null) {
            return $hidden . self::control($type, $name, $value, $options, $switch, $switchOptions);
        }

        $id = self::stringValue($options['id'] ?? null) ?? static::getId();

        $options['id'] = $id;

        $input = self::control($type, $name, $value, $options, $switch, $switchOptions);

        return $hidden . (
            $wrapInput
                ? static::label($input . $label, $id, $labelOptions)
                : $input . "\n" . static::label($label, $id, $labelOptions)
        );
    }

    /**
     * Returns the next auto-generated `id` for checkbox and radio labels.
     *
     * @return string Next identifier using the configured prefix and incremented counter.
     */
    protected static function getId(): string
    {
        return static::$autoIdPrefix . static::$counter++;
    }

    /**
     * Returns the value when it is an array, or an empty array otherwise.
     *
     * @param mixed $value Value to inspect.
     *
     * @return array<mixed> The array value, or an empty array for other types.
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Renders the checkbox or radio input, wrapped in the `.switch` element for switches.
     *
     * @param string $type Input type, either `checkbox` or `radio`.
     * @param string $name Input name.
     * @param string|null $value Input value, or `null` to omit the value attribute.
     * @param array<mixed> $options Input attributes.
     * @param bool $switch Whether to wrap the checkbox as a switch.
     * @param array<mixed> $switchOptions Attributes of the `.switch` wrapper.
     *
     * @return string Input markup, including the switch wrapper when requested.
     */
    private static function control(
        string $type,
        string $name,
        string|null $value,
        array $options,
        bool $switch,
        array $switchOptions,
    ): string {
        if ($switch === false) {
            self::prependCssClass($options, $type === 'radio' ? 'radio' : 'check');

            return static::input($type, $name, $value, $options);
        }

        $options['role'] ??= 'switch';
        $options['switch'] = true;

        self::prependCssClass($switchOptions, 'switch');

        return static::tag('div', static::input($type, $name, $value, $options), $switchOptions);
    }

    /**
     * Returns whether the `string` is a value of the enum, or a list of breakpoint-prefixed values when `$responsive`.
     *
     * @param class-string<BackedEnum> $enum String-backed enum the value belongs to.
     * @param string $value Value or space-separated list of responsive values to validate.
     * @param bool $responsive Whether to accept breakpoint-prefixed values.
     *
     * @return bool Whether every value belongs to the enum and uses an allowed breakpoint prefix.
     */
    private static function isEnumString(string $enum, string $value, bool $responsive): bool
    {
        if ($enum::tryFrom($value) !== null) {
            return true;
        }

        if ($responsive === false) {
            return false;
        }

        foreach (explode(' ', $value) as $token) {
            [$breakpoint, $case] = str_contains($token, ':') ? explode(':', $token, 2) : [null, $token];

            if (($breakpoint !== null && Breakpoint::tryFrom($breakpoint) === null) || $enum::tryFrom($case) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Applies the container classes and the default `.form-field` item renderer to list options.
     *
     * @param string $type Input type, either `checkbox` or `radio`.
     * @param array<mixed> $options List options, see {@see checkboxList()}.
     *
     * @return array<mixed> Options for the core list implementation.
     */
    private static function listOptions(string $type, array $options): array
    {
        $inline = ArrayHelper::remove($options, 'inline') === true;

        static::addCssClass($options, $inline ? ['d-flex', 'flex-wrap', 'gap-5'] : 'form-group');

        if (isset($options['item'])) {
            return $options;
        }

        $encode = ArrayHelper::getValue($options, 'encode', true) !== false;
        $itemOptions = self::arrayValue(ArrayHelper::remove($options, 'itemOptions'));
        $wrapperOptions = self::arrayValue(ArrayHelper::remove($itemOptions, 'wrapperOptions'));

        self::prependCssClass($wrapperOptions, 'form-field');

        $options['item'] = static function (
            mixed $index,
            mixed $label,
            string $name,
            bool $checked,
            mixed $value,
        ) use ($encode, $itemOptions, $type, $wrapperOptions): string {
            $label = self::stringValue($label) ?? '';

            $input = static::booleanInput(
                $type,
                $name,
                $checked,
                [
                    'value' => $value,
                    'label' => $encode ? static::encode($label) : $label,
                    ...$itemOptions,
                ],
            );

            return static::tag('div', $input, $wrapperOptions);
        };

        return $options;
    }

    /**
     * Adds a Bootstrap base class before the classes already configured in the options.
     *
     * @param array<mixed> $options Tag attributes to modify.
     * @param string $class Base class, for example `check`.
     */
    private static function prependCssClass(array &$options, string $class): void
    {
        $configured = $options['class'] ?? null;

        $options['class'] = $class;

        if (is_string($configured) || is_array($configured)) {
            static::addCssClass($options, $configured);
        }
    }

    /**
     * Returns the value as a `string` when it is scalar or {@see Stringable}, or `null` otherwise.
     *
     * @param mixed $value Value to convert when it is scalar or implements `Stringable`.
     *
     * @return string|null Converted scalar or `Stringable` value, or `null` for unsupported values.
     */
    private static function stringValue(mixed $value): string|null
    {
        return is_scalar($value) || $value instanceof Stringable ? (string) $value : null;
    }
}
