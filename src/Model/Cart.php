<?php

declare (strict_types=1);

namespace Ftwcm\Shop\Model;

/**
 * 购物车模型。
 */
class Cart extends Model
{
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'carts';

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
