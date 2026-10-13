<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\item\BreadcrumbLink;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\BreadcrumbLinkTest} test cases.
 */
final class BreadcrumbLinkProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, BreadcrumbLink}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Stringable');

        yield 'label only' => [
            ['label' => 'A'],
            BreadcrumbLink::to('A'),
        ];
        yield 'Stringable label' => [
            ['label' => $label],
            BreadcrumbLink::to($label),
        ];
        yield 'url' => [
            ['label' => 'A', 'url' => '/a'],
            BreadcrumbLink::to('A', '/a'),
        ];
        yield 'route' => [
            ['label' => 'A', 'url' => ['site/index']],
            BreadcrumbLink::to('A', ['site/index']),
        ];
        yield 'url non-`string`' => [
            ['label' => 'A', 'url' => 1],
            BreadcrumbLink::to('A'),
        ];
        yield 'template' => [
            ['label' => 'A', 'template' => '<span>{link}</span>'],
            BreadcrumbLink::to('A')->withTemplate('<span>{link}</span>'),
        ];
        yield 'template non-`string`' => [
            ['label' => 'A', 'template' => 5],
            BreadcrumbLink::to('A'),
        ];
        yield 'active `true`' => [
            ['active' => true, 'label' => 'A'],
            BreadcrumbLink::to('A')->withActive(true),
        ];
        yield 'active `false`' => [
            ['active' => false, 'label' => 'A'],
            BreadcrumbLink::to('A')->withActive(false),
        ];
        yield 'active `null`' => [
            ['active' => null, 'label' => 'A'],
            BreadcrumbLink::to('A')->withActive(false),
        ];
        yield 'active non-`bool`' => [
            ['active' => 1, 'label' => 'A'],
            BreadcrumbLink::to('A')->withActive(false),
        ];
        yield 'encode `true`' => [
            ['encode' => true, 'label' => 'A'],
            BreadcrumbLink::to('A')->withEncode(true),
        ];
        yield 'encode `false`' => [
            ['encode' => false, 'label' => 'A'],
            BreadcrumbLink::to('A')->withEncode(false),
        ];
        yield 'encode `null`' => [
            ['encode' => null, 'label' => 'A'],
            BreadcrumbLink::to('A')->withEncode(true),
        ];
        yield 'encode non-`bool`' => [
            ['encode' => 0, 'label' => 'A'],
            BreadcrumbLink::to('A')->withEncode(true),
        ];
        yield 'link attributes' => [
            ['data-x' => 1, 'label' => 'A', 'title' => 'T', 'url' => '#'],
            BreadcrumbLink::to('A', '#')->withOptions(['data-x' => 1, 'title' => 'T']),
        ];
        yield 'every key' => [
            [
                'active' => true,
                'class' => 'link',
                'encode' => false,
                'label' => 'A',
                'template' => '<li>{link}</li>',
                'url' => '/a',
            ],
            BreadcrumbLink::to('A', '/a')
                ->withActive(true)
                ->withEncode(false)
                ->withTemplate('<li>{link}</li>')
                ->withOptions(['class' => 'link']),
        ];
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function invalidLabelCases(): iterable
    {
        yield 'empty' => [[]];
        yield 'url without label' => [['url' => '#']];
        yield 'label `null`' => [['label' => null]];
        yield 'label `int`' => [['label' => 5]];
    }

    /**
     * @return iterable<string, array{Closure(BreadcrumbLink): BreadcrumbLink, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'active' => [
            static fn(BreadcrumbLink $link): BreadcrumbLink => $link->withActive(null),
            'active',
            null,
        ];
        yield 'encode' => [
            static fn(BreadcrumbLink $link): BreadcrumbLink => $link->withEncode(true),
            'encode',
            true,
        ];
        yield 'label' => [
            static fn(BreadcrumbLink $link): BreadcrumbLink => $link->withLabel('New'),
            'label',
            'New',
        ];
        yield 'options' => [
            static fn(BreadcrumbLink $link): BreadcrumbLink => $link->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'template' => [
            static fn(BreadcrumbLink $link): BreadcrumbLink => $link->withTemplate(null),
            'template',
            null,
        ];
        yield 'url' => [
            static fn(BreadcrumbLink $link): BreadcrumbLink => $link->withUrl(['site/index']),
            'url',
            ['site/index'],
        ];
    }
}
