<?php

/**
 * This is configuration for the Laminas\DeveloperTools development toolbar.
 *
 * It will be enabled when you enable development mode.
 */
return [
    'laminas-developer-tools' => [
        /**
         * General Profiler settings
         */
        'profiler' => [
            'enabled' => filter_var(getenv('DEV_TOOLS_PROFILER_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
            'strict' => filter_var(getenv('DEV_TOOLS_PROFILER_STRICT') ?: true, FILTER_VALIDATE_BOOLEAN),
            'flush_early' => filter_var(getenv('DEV_TOOLS_PROFILER_FLUSH_EARLY') ?: false, FILTER_VALIDATE_BOOLEAN),
            'cache_dir' => getenv('DEV_TOOLS_CACHE_DIR') ?: 'data/cache',
            'matcher' => [],
            'collectors' => [],
        ],
        'events' => [
            'enabled' => filter_var(getenv('DEV_TOOLS_EVENTS_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
            'collectors' => [],
            'identifiers' => [],
        ],
        /**
         * General Toolbar settings
         */
        'toolbar' => [
            'enabled' => filter_var(getenv('DEV_TOOLS_TOOLBAR_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
            'auto_hide' => filter_var(getenv('DEV_TOOLS_TOOLBAR_AUTO_HIDE') ?: false, FILTER_VALIDATE_BOOLEAN),
            'position' => getenv('DEV_TOOLS_TOOLBAR_POSITION') ?: 'bottom',
            'version_check' => filter_var(getenv('DEV_TOOLS_TOOLBAR_VERSION_CHECK') ?: false, FILTER_VALIDATE_BOOLEAN),
            'entries' => [],
        ],
    ],
];
