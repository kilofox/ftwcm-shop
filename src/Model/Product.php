<?php

declare (strict_types=1);

namespace Ftwcm\Shop\Model;

/**
 * 商品模型。
 */
class Product extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

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
     * @var \Ftwcm\Shop\Model\Attribute
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute');
    }

    /**
     * @var \Ftwcm\Shop\Model\Option
     */
    public function options()
    {
        return $this->belongsToMany(Option::class, 'product_option');
    }

    /**
     * @var \Ftwcm\Shop\Model\Category
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    /**
     * @var \Ftwcm\Shop\Model\Store
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'product_store');
    }

}
