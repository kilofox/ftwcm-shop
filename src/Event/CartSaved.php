<?php

namespace Ftwcm\Shop\Event;

/**
 * 购物车保存完成事件。
 */
class CartSaved
{
    /** @var object $cart */
    public $cart;

    /**
     * Constructor.
     *
     * @param object $cart
     */
    public function __construct(object $cart)
    {
        $this->cart = $cart;
    }

}
