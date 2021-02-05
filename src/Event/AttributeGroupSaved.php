<?php

namespace Ftwcm\Shop\Event;

/**
 * 属性分组保存完成事件。
 */
class AttributeGroupSaved
{
    /** @var object $group */
    public $group;

    /**
     * Constructor.
     *
     * @param object $group
     */
    public function __construct(object $group)
    {
        $this->group = $group;
    }

}
