<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\exception;

use function sprintf;

/**
 * Represents error message templates for exceptions thrown by the extension.
 *
 * Use {@see Message::getMessage()} to format a template with `sprintf()` arguments.
 */
enum Message: string
{
    /**
     * Error when a widget requires a non-empty `items` option.
     *
     * Format: "The 'items' option is required."
     */
    case INVALID_ITEMS = "The 'items' option is required.";

    /**
     * Error when an option receives a value outside its allowed set.
     *
     * Format: "Invalid value '%s' for '%s'. Allowed values: %s."
     */
    case INVALID_VALUE = "Invalid value '%s' for '%s'. Allowed values: %s.";

    /**
     * Error when an item is missing its `content` option.
     *
     * Format: "The 'content' option is required."
     */
    case ITEM_CONTENT_REQUIRED = "The 'content' option is required.";

    /**
     * Error when an item is missing its `label` option.
     *
     * Format: "The 'label' option is required."
     */
    case ITEM_LABEL_REQUIRED = "The 'label' option is required.";

    /**
     * Error when an asset bundle lists no script to import as a module.
     *
     * Format: "The asset bundle '%s' does not list a module script."
     */
    case MODULE_SCRIPT_UNAVAILABLE = "The asset bundle '%s' does not list a module script.";

    /**
     * Error when a configuration array is missing a required option.
     *
     * Format: "The '%s' option is required."
     */
    case OPTION_REQUIRED = "The '%s' option is required.";

    /**
     * Error when the version of an npm package cannot be read for CDN delivery.
     *
     * Format: "Unable to read the version of npm package '%s' from '%s'."
     */
    case PACKAGE_VERSION_UNAVAILABLE = "Unable to read the version of npm package '%s' from '%s'.";

    /**
     * Error when a widget property that has no usable default is not set.
     *
     * Format: "The '%s' property must be set."
     */
    case PROPERTY_REQUIRED = "The '%s' property must be set.";

    /**
     * Error when a widget registers a plugin that the Bootstrap bundle does not export.
     *
     * Format: "Unknown Bootstrap plugin '%s'."
     */
    case UNKNOWN_PLUGIN = "Unknown Bootstrap plugin '%s'.";

    /**
     * Returns the formatted message string for the error case.
     *
     * @param int|string ...$args Values to insert into the message template.
     *
     * @return string Formatted error message with interpolated arguments.
     */
    public function getMessage(int|string ...$args): string
    {
        return sprintf($this->value, ...$args);
    }
}
