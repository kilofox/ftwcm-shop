<?php

namespace Ftwcm\Shop\Listener;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Ftwcm\Shop\Event\OptionSaved;

/**
 * OptionSavedListener
 */
class OptionSavedListener implements ListenerInterface
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
            OptionSaved::class,
        ];
    }

    /**
     * @param object $event
     * @return void
     */
    public function process(object $event)
    {
        // 清除列表缓存
        $key = 's_options';
        $this->cache->delete($key);
    }

}
