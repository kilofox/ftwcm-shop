<?php

declare (strict_types=1);

namespace Ftwcm\Shop\Model;

/**
 * 选项模型。
 */
class Option extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'options';

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

    /**
     * @var \Ftwcm\Shop\Model\Product
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_option');
    }

}
