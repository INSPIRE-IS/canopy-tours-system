<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'question_answers';

    protected $fillable = [
        'answer',
        'question_id',
        'customer_id',
        'booking_item_id',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }
}
