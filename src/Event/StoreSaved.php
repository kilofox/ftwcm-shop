<?php

namespace Ftwcm\Shop\Event;

/**
 * 商店保存完成事件。
 */
class StoreSaved
{
    /** @var object $store */
    public $store;

    /**
     * Constructor.
     *
     * @param object $store
     */
    public function __construct(object $store)
    {
        $this->store = $store;
    }

}
