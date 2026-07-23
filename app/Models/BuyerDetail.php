<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'buyer_details';

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
    public $timestamps = false; // Uses legacy created_at timestamp

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_name',
        'supplier_names',
        'supplier_company',
        'supplier_email',
        'supplier_phone',
        'supplier_address',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'buyer_product',
        'buyer_payment_terms',
        'Country',
        'added_by',
        'created_at',
        'qantity',
        'destination_port',
        'buyer_location',
        'target_price',
        'shipping_terms',
        'specification',
        'whatsapp',
        'document',
        'comments',
        'deal_status',
        'remarks'
    ];

    /**
     * Get the buyer who owns these details.
     */
    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'buyer_email', 'email');
    }
}
