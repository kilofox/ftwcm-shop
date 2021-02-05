<?php

namespace Ftwcm\Shop;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'commands' => [
            ],
            'listeners' => [
                Listener\AttributeGroupSavedListener::class,
                Listener\AttributeSavedListener::class,
            ],
            'annotations' => [
            ],
            'init_routes' => [
                __DIR__ . '/../config/routes.php'
            ],
            'publish' => [
                [
                    'id' => 'database',
                    'description' => 'The database for ftwcm-shop.',
                    'source' => __DIR__ . '/../migrations/create_shop_tables.php',
                    'destination' => BASE_PATH . '/migrations/2021_01_25_161301_create_shop_tables.php',
                ]
            ]
        ];
    }

}
