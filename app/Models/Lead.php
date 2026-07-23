<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leads';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'Id'; // Note the capitalized 'Id' in legacy database schema

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Uses legacy Date column

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'LeadID',
        'Type',
        'Date',
        'Qty',
        'Unit',
        'Title',
        'Description',
        'PaymentTerms',
        'CompanyName',
        'Name',
        'Contact',
        'Address',
        'Email',
        'Country',
        'admin_id'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Date' => 'datetime',
        ];
    }
}
