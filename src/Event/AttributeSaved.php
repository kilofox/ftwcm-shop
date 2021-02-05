<?php

namespace Ftwcm\Shop\Event;

/**
 * 属性保存完成事件。
 */
class AttributeSaved
{
    /** @var object $attribute */
    public $attribute;

    /**
     * Constructor.
     *
     * @param object $attribute
     */
    public function __construct(object $attribute)
    {
        $this->attribute = $attribute;
    }

}
