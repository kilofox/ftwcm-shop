<?php

declare (strict_types=1);

namespace Ftwcm\Shop\Model;

/**
 * 商品分类路径模型。
 */
class CategoryPath extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_path';

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
