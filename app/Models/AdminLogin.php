<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AdminLogin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin_login';

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
        'username',
        'password',
        'email',
        'role',
        'assign_role',
        'status',
        'panel_type',
        'last_login',
        'last_logout',
        'login_attempts',
        'last_attempt',
        'last_ip_address',
        'last_device_info',
        'last_browser',
        'last_os',
        'last_country',
        'last_city',
        'login_history',
        'CRM-id' // Note the legacy hyphenated column name!
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'last_logout' => 'datetime',
            'last_attempt' => 'datetime',
            'login_attempts' => 'integer',
            'role' => 'integer',
        ];
    }
}
