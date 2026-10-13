<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;
use function is_string;

/**
 * Renders a Bootstrap OTP input: a single `.otp-input` text field inside an `.otp` container drawn as code slots.
 *
 * {@see $options} applies to the input. The container receives the `id` `{id}-otp`, carries the plugin `data-bs-*`
 * attributes and the client events.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/otp-input/
 */
class OtpInput extends InputWidget
{
    /**
     * Allowed values of {@see $type}.
     *
     * @var list<string>
     */
    protected const array TYPES = ['numeric', 'alphanumeric', 'alpha'];

    /**
     * @var array<string, mixed> HTML attributes of the `.otp` container.
     */
    public array $containerOptions = [];
    /**
     * @var list<int> Group sizes separated, for example `[3, 3]`.
     */
    public array $groups = [];
    /**
     * Number of slots, rendered as the `maxlength` attribute of the input.
     */
    public int $length = 6;
    /**
     * Whether to mask the entered characters, or `null` for the plugin default (`false`).
     */
    public bool|null $mask = null;
    /**
     * Character rendered between {@see $groups}, or `null` for the plugin default (`·`).
     */
    public string|null $separator = null;
    /**
     * Allowed characters (`numeric`, `alphanumeric`, `alpha`), or `null` for the plugin default (`numeric`).
     */
    public string|null $type = null;

    /**
     * Initializes the widget and validates {@see $type}.
     *
     * @throws InvalidConfigException if {@see $type} is invalid.
     */
    public function init(): void
    {
        parent::init();

        if ($this->type !== null && !in_array($this->type, static::TYPES, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->type, 'type', implode(', ', static::TYPES)),
            );
        }
    }

    public function run(): string
    {
        $original = $this->options;

        $id = $this->inputId();
        $containerId = is_string($this->containerOptions['id'] ?? null) ? $this->containerOptions['id'] : "{$id}-otp";
        $containerOptions = [...$this->containerOptions, 'id' => $containerId];

        Html::addCssClass($containerOptions, 'otp');

        $containerOptions['data-bs-otp'] = true;
        $containerOptions['data-bs-type'] = $this->type;
        $containerOptions['data-bs-mask'] = $this->mask === null ? null : ($this->mask ? 'true' : 'false');
        $containerOptions['data-bs-groups'] = $this->groups === [] ? null : $this->groups;
        $containerOptions['data-bs-separator'] = $this->separator;

        Html::addCssClass($this->options, 'otp-input');

        $this->options['maxlength'] = $this->length;

        $input = $this->renderInputHtml('text');

        $this->options['id'] = $containerId;

        $this->registerPlugin('OtpInput');

        $this->options = $original;

        return Html::tag(
            'div',
            "\n" . $input . "\n",
            $containerOptions,
        );
    }

    /**
     * Returns the input `id` assigned by {@see init()}.
     */
    private function inputId(): string
    {
        /** @var string */
        return $this->options['id'];
    }
}
