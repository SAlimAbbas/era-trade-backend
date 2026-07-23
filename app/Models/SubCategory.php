<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_categories';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sub_categories',
        'categories_id',
        'status',
        'admin_id'
    ];

    /**
     * Get the parent category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'categories_id', 'id');
    }

    /**
     * Get sub-subcategories under this subcategory.
     */
    public function subSubCategories()
    {
        return $this->hasMany(SubSubCategory::class, 'sub_categories_id', 'id');
    }

    /**
     * Get all products in this subcategory.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'subcategories_id', 'id');
    }
}
