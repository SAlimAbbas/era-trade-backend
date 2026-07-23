<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactReveal extends Model
{
    use HasFactory;

    protected $table = 'contact_reveals';

    protected $fillable = [
        'user_id',
        'user_type',
        'revealed_id',
        'revealed_type',
        'reveal_date'
    ];
}
