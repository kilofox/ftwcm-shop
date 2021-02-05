<?php

namespace Ftwcm\Shop\Event;

/**
 * 分类保存完成事件。
 */
class CategorySaved
{
    /** @var object $category */
    public $category;

    /**
     * Constructor.
     *
     * @param object $category
     */
    public function __construct(object $category)
    {
        $this->category = $category;
    }

}
