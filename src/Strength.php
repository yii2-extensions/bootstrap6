<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

use function array_keys;
use function implode;
use function in_array;
use function str_repeat;

/**
 * Renders a Bootstrap password strength meter, segmented or as a single bar, with optional text feedback.
 *
 * Without {@see $input} the plugin evaluates the first `input[type="password"]` of the parent element, so render the
 * widget next to the password field.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/password-strength/
 */
class Strength extends Widget
{
    /**
     * Strength levels accepted as {@see $messages} keys.
     *
     * @var list<string>
     */
    protected const array LEVELS = ['weak', 'fair', 'good', 'strong'];

    /**
     * CSS selector of the password input, or `null` to use the password input of the parent element.
     */
    public string|null $input = null;
    /**
     * @var array<string, string> Text feedback per level (`weak`, `fair`, `good`, `strong`), passed to the plugin
     */
    public array $messages = [];
    /**
     * Minimum password length for the first strength point, or `null` for the plugin default (`8`).
     */
    public int|null $minLength = null;
    /**
     * Whether to render the four `.strength-segment` elements; `false` renders the `.strength-bar` variant.
     */
    public bool $segments = true;
    /**
     * Whether to render the `.strength-text` element that displays {@see $messages}.
     */
    public bool $showText = false;

    /**
     * Initializes the widget and validates the {@see $messages} keys.
     *
     * @throws InvalidConfigException if a {@see $messages} key is not a strength level.
     */
    public function init(): void
    {
        parent::init();

        foreach (array_keys($this->messages) as $level) {
            if (!in_array($level, static::LEVELS, true)) {
                throw new InvalidConfigException(
                    Message::INVALID_VALUE->getMessage($level, 'messages', implode(', ', static::LEVELS)),
                );
            }
        }
    }

    public function run(): string
    {
        $options = $this->options;

        Html::addCssClass($options, $this->segments ? 'strength' : 'strength-bar');

        $options['data-bs-strength'] = true;
        $options['data-bs-input'] = $this->input;
        $options['data-bs-min-length'] = $this->minLength;

        if ($this->messages !== [] && $this->clientOptions !== false) {
            $this->clientOptions = ['messages' => $this->messages, ...$this->clientOptions];
        }

        $this->registerPlugin('Strength', true);

        $content = $this->segments
            ? "\n" . str_repeat(Html::tag('div', '', ['class' => 'strength-segment']) . "\n", 4)
            : '';
        $html = Html::tag('div', $content, $options);

        if ($this->showText) {
            $html .= "\n" . Html::tag('span', '', ['class' => 'strength-text']);
        }

        return $html;
    }
}
