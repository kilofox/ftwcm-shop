<?php

namespace Ftwcm\Shop\Listener;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Ftwcm\Shop\Event\ManufacturerSaved;

/**
 * ManufacturerSavedListener
 */
class ManufacturerSavedListener implements ListenerInterface
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->cache = $container->get(CacheInterface::class);
    }

    /**
     * @return array
     */
    public function listen(): array
    {
        return [
            ManufacturerSaved::class,
        ];
    }

    /**
     * @param object $event
     * @return void
     */
    public function process(object $event)
    {
        // 清除列表缓存
        $keyPrefix = 's_manufacturer_list:';
        $page = 1;

        do {
            $this->cache->delete($keyPrefix . $page);
            $page++;
        } while ($this->cache->has($keyPrefix . $page));
    }

}
