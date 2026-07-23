<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'supplier';

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
    public $timestamps = false; // Using legacy date columns: added_on, updated_at, created_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'OwnerID',
        'SuplierID',
        'Name',
        'CompanyName',
        'Address',
        'logo',
        'Description',
        'Contact',
        'Email',
        'State',
        'City',
        'Country',
        'Zipcode',
        'PaymentTerms',
        'plan',
        'Crn',
        'Cey',
        'importcode',
        'status',
        'Whatsappno',
        'Alternateno',
        'Businesscategories',
        'Primarybusiness',
        'Banner',
        'recsuppliy',
        'added_by',
        'added_on',
        'updated_at',
        'created_at',
        'admin_id'
    ];

    /**
     * Get the user who added this supplier.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    /**
     * Get the products owned by this supplier.
     * Maps using legacy `SuplierID` column instead of standard `supplier_id`.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'supplierid', 'id');
    }
}
