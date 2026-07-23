<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiie extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inquiies';

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
    public $timestamps = false; // Uses legacy created_at and updated_at datetime

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'product_name',
        'supplier_id',
        'supplier_name',
        'name',
        'email',
        'phone',
        'country',
        'company',
        'quantity',
        'message',
        'status',
        'remark',
        'reply',
        'created_at',
        'updated_at',
        'admin_id'
    ];
}
