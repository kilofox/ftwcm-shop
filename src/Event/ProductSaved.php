<?php

namespace Ftwcm\Shop\Event;

/**
 * 商品保存完成事件。
 */
class ProductSaved
{
    /** @var object $product */
    public $product;

    /**
     * Constructor.
     *
     * @param object $product
     */
    public function __construct(object $product)
    {
        $this->product = $product;
    }

}
