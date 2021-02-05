<?php

namespace Ftwcm\Shop\Event;

/**
 * 制造商/品牌保存完成事件。
 */
class ManufacturerSaved
{
    /** @var object $manufacturer */
    public $manufacturer;

    /**
     * Constructor.
     *
     * @param object $manufacturer
     */
    public function __construct(object $manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

}
