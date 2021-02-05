<?php

declare (strict_types=1);

namespace Ftwcm\Shop\Model;

/**
 * 制造商/品牌模型。
 */
class Manufacturer extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'manufacturers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

}
