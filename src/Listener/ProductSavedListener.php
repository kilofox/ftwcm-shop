<?php

namespace Ftwcm\Shop\Listener;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Ftwcm\Shop\Event\ProductSaved;

/**
 * ProductSavedListener
 */
class ProductSavedListener implements ListenerInterface
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
            ProductSaved::class,
        ];
    }

    /**
     * @param object $event
     * @return void
     */
    public function process(object $event)
    {
        // 清除商品列表缓存
        $keyPrefix = 's_product_list:';
        $page = 1;

        do {
            $this->cache->delete($keyPrefix . $page);
            $page++;
        } while ($this->cache->has($keyPrefix . $page));

        // 清除商品属性列表
        $key = 's_product_attributes:' . $event->id;
        $this->cache->delete($key);

        // 清除商品选项列表
        $key = 's_product_options:' . $event->id;
        $this->cache->delete($key);
    }

}
