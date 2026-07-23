<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message';

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
    public $timestamps = false; // Uses legacy timestamp field

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'message_text',
        'timestamp',
        'is_read',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_delivered'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'is_read' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * Scope a query to get chat history between two users.
     */
    public function scopeBetweenUsers($query, $user1Id, $user1Type, $user2Id, $user2Type)
    {
        return $query->where(function ($q) use ($user1Id, $user1Type, $user2Id, $user2Type) {
            $q->where('sender_id', $user1Id)
              ->where('sender_type', $user1Type)
              ->where('receiver_id', $user2Id)
              ->where('receiver_type', $user2Type);
        })->orWhere(function ($q) use ($user1Id, $user1Type, $user2Id, $user2Type) {
            $q->where('sender_id', $user2Id)
              ->where('sender_type', $user2Type)
              ->where('receiver_id', $user1Id)
              ->where('receiver_type', $user1Type);
        })->orderBy('timestamp', 'asc');
    }
}
