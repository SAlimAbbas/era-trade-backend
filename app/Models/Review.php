<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reviews';

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
        'reviewer_id',
        'reviewer_name',
        'reviewer_email',
        'rating',
        'review_text',
        'title',
        'verified_purchase',
        'helpful_count',
        'reply_text',
        'reply_date',
        'updated_at',
        'ip_address',
        'country',
        'device_type',
        'browser',
        'platform',
        'network_type',
        'user_agent',
        'created_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'reply_date' => 'datetime',
            'rating' => 'integer',
            'verified_purchase' => 'boolean',
            'helpful_count' => 'integer',
        ];
    }
}
