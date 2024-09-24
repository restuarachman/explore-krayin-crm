<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\Category as CategoryContract;

class Category extends Model implements CategoryContract
{
    /**
     * The attributes that are mass assignable.
     *
     * @var $fillable
     */
    protected $fillable = [
        'parent_id',
        'title',
        'description',
        'status'
    ];
}
