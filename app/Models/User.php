<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

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
    public $timestamps = false; // We disable standard Laravel timestamps in favor of legacy added_on and updated_at fields

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'types',
        'country',
        'state',
        'city',
        'zipcode',
        'membertype',
        'verification_status',
        'verification_id',
        'memberid',
        'plan',
        'otp',
        'status',
        'added_on',
        'reset_token',
        'token_expiry',
        'last_seen',
        'is_online',
        'updated_at',
        'admin_id',
        'country_code',
        'phone_with_code',
        'ip_address',
        'ip_country',
        'ip_city',
        'ip_region',
        'ip_latitude',
        'ip_longitude',
        'ip_timezone',
        'ip_isp'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'added_on' => 'datetime',
            'updated_at' => 'datetime',
            'last_seen' => 'datetime',
            'token_expiry' => 'datetime',
            'is_online' => 'boolean',
            'verification_status' => 'integer',
        ];
    }

    /**
     * Relationship with Supplier profiles.
     */
    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'added_by', 'id');
    }
}
