<?php

declare (strict_types=1);

namespace Ftwcm\Shop\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\ModelCache\Cacheable;

/**
 * 产品选项模型。
 */
class ProductOption extends Model
{
    use Cacheable;
    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_option';

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
