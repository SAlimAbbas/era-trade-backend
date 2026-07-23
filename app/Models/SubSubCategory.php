<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubCategory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_sub_categories';

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
        'sub_sub_categories',
        'sub_categories_id',
        'status',
        'slug',
        'admin_id'
    ];

    /**
     * Get the parent subcategory.
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_categories_id', 'id');
    }

    /**
     * Get all products in this sub-subcategory.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'subsubcategories_id', 'id');
    }
}
