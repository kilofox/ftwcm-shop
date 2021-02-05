<?php

namespace Ftwcm\Shop\Event;

/**
 * 选项保存完成事件。
 */
class OptionSaved
{
    /** @var object $option */
    public $option;

    /**
     * Constructor.
     *
     * @param object $option
     */
    public function __construct(object $option)
    {
        $this->option = $option;
    }

}
