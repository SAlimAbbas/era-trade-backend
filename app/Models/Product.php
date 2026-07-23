<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product';

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
    public $timestamps = false; // Uses legacy added_on timestamp

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'productid',
        'categories_id',
        'subcategories_id',
        'subsubcategories_id',
        'title',
        'slug',
        'description',
        'qty',
        'price',
        'currency',
        'unit',
        'image',
        'supplierid',
        'status',
        'added_on',
        'added_by',
        'admin_id'
    ];

    /**
     * Get the supplier that owns this product.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierid', 'id');
    }

    /**
     * Get the category this product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'categories_id', 'id');
    }

    /**
     * Get the subcategory this product belongs to.
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'subcategories_id', 'id');
    }

    /**
     * Get the sub-subcategory this product belongs to.
     */
    public function subSubCategory()
    {
        return $this->belongsTo(SubSubCategory::class, 'subsubcategories_id', 'id');
    }
}
