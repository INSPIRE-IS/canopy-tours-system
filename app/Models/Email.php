<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    public $table = 'emails';

    protected $fillable = [
        'sender',
        'receiver',
        'subject',
        'body',
        'attachment_path',
        'sent',
        'sent_at',
        'user_id',
        'booking_id',
        'team_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
