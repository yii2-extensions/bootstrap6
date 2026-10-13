<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii2\extensions\bootstrap6\ActiveField;

/**
 * Field subclass recording the calls to the protected extension points of {@see ActiveField}.
 */
final class RecordingActiveField extends ActiveField
{
    /**
     * @var list<string>
     */
    public array $calls = [];

    protected function createLayoutConfig(array $instanceConfig): array
    {
        $this->calls[] = 'createLayoutConfig';

        return parent::createLayoutConfig($instanceConfig);
    }

    protected function horizontalCssClass(string $key): array|string
    {
        $this->calls[] = 'horizontalCssClass';

        return parent::horizontalCssClass($key);
    }

    protected function layout(): string
    {
        $this->calls[] = 'layout';

        return parent::layout();
    }

    protected function registerFloatingFeedbackStyle(): void
    {
        $this->calls[] = 'registerFloatingFeedbackStyle';

        parent::registerFloatingFeedbackStyle();
    }

    protected function renderBooleanInput(string $type, array $options, bool $enclosedByLabel): void
    {
        $this->calls[] = 'renderBooleanInput';

        parent::renderBooleanInput($type, $options, $enclosedByLabel);
    }

    protected function renderLabelParts(string|null $label = null, array $options = []): void
    {
        $this->calls[] = 'renderLabelParts';

        parent::renderLabelParts($label, $options);
    }

    protected function renderListInput(string $type, array $items, array $options): void
    {
        $this->calls[] = 'renderListInput';

        parent::renderListInput($type, $items, $options);
    }

    protected function renderWrapperParts(): void
    {
        $this->calls[] = 'renderWrapperParts';

        parent::renderWrapperParts();
    }
}
