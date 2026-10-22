<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii2\extensions\bootstrap6\asset\BootstrapAsset;

use function implode;
use function is_array;

/**
 * Renders a Bootstrap button toolbar from HTML strings and {@see ButtonGroup} configurations.
 *
 * @see https://getbootstrap.com/docs/6.0/components/button-group/#button-toolbar
 */
class ButtonToolbar extends Widget
{
    /**
     * Groups of the toolbar, each an HTML `string` or a {@see ButtonGroup} configuration array.
     *
     * Configuration arrays without a `buttons` key are skipped.
     *
     * @var array<array<string, mixed>|string>
     */
    public array $buttonGroups = [];

    /**
     * Returns the button toolbar with its button groups.
     *
     * @throws \yii\base\InvalidConfigException if a configured group, or one of its buttons, has a `size`, `theme`,
     * or `variant` that is not a value of its enum.
     *
     * @return string Rendered ButtonToolbar markup.
     */
    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;

        Html::addCssClass($options, 'btn-toolbar');

        $options['role'] ??= 'toolbar';

        return Html::tag(
            'div',
            $this->renderButtonGroups(),
            $options,
        );
    }

    /**
     * Renders the button groups separated by line breaks.
     *
     * @throws \yii\base\InvalidConfigException if a configured group or one of its buttons has an invalid size,
     * theme, or variant.
     *
     * @return string Button group markup separated by line breaks.
     */
    protected function renderButtonGroups(): string
    {
        $groups = [];

        foreach ($this->buttonGroups as $group) {
            if (is_array($group)) {
                if (!isset($group['buttons'])) {
                    continue;
                }

                $group['view'] = $this->getView();

                $group = ButtonGroup::widget($group);
            }

            $groups[] = $group;
        }

        return implode("\n", $groups);
    }
}
