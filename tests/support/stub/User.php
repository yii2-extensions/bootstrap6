<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii\base\Model;

/**
 * Form model with typed, length-constrained, and hinted attributes.
 */
final class User extends Model
{
    public string|null $firstName = null;
    public int|null $id = null;
    public string|null $lastName = null;
    public string|null $password = null;
    public string|null $username = null;

    public function attributeHints(): array
    {
        return [
            'username' => 'Your username must be at least 4 characters long',
            'password' => 'Your password must be 8-20 characters long, contain letters and numbers, and must not '
                . 'contain spaces, special characters, or emoji.',
        ];
    }

    public function rules(): array
    {
        return [
            ['id', 'integer'],
            [['firstName', 'lastName'], 'string'],
            ['username', 'string', 'min' => 4],
            ['password', 'string', 'min' => 8, 'max' => 20],
            [['username', 'password'], 'required'],
        ];
    }
}
