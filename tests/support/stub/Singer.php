<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii\base\Model;

/**
 * Form model with a defaulted required attribute and a conditionally required attribute.
 */
final class Singer extends Model
{
    public string|null $firstName = null;
    public string|null $lastName = null;
    public string|null $test = null;

    public function rules(): array
    {
        return [
            [['lastName'], 'default', 'value' => 'Lennon'],
            [['lastName'], 'required'],
            [['test'], 'required', 'when' => static fn(self $model): bool => $model->firstName === 'cebe'],
        ];
    }
}
