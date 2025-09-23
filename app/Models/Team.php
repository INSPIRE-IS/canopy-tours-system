<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'teams';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'full_name',
        'owner_id',
        'description',
        'logo',
        'image',
        'color',
        'reg_no',
        'address',
        'address_code',
        'account_owner',
        'account_number',
        'account_type',
        'bank_name',
        'branch_code',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
