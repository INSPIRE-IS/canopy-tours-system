<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;

class Team extends Model
{
    use SoftDeletes, HasFactory, MultiTenantModelTrait, Auditable;

    public $table = 'teams';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'full_name',
        'description',
        'color',
        'reg_no',
        'address',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'address_code',
        'telephone',
        'email',
        'website_url',
        'account_owner',
        'account_number',
        'account_type',
        'bank_name',
        'bank_branch_name',
        'branch_code',
        'is_published',
        'invoice_note',
        'vat_no',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
        'owner_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function file_logo()
    {
        return $this->hasOne(File::class, 'table_row_id')->where('table_name', 'teams')->where('type_file_id', 1);
    }

    public function file_image()
    {
        return $this->hasOne(File::class, 'table_row_id')->where('table_name', 'teams')->where('type_file_id', 2);
    }

    public function file_invoice_quote_icon()
    {
        return $this->hasOne(File::class, 'table_row_id')->where('table_name', 'teams')->where('type_file_id', 8);
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function gatewayConfigs()
    {
        return $this->hasMany(GatewayConfig::class);
    }

    // public function bookingItemCustomers()
    // {
    //     return $this->hasMany(bookingItemCustomers::class);
    // }
}
