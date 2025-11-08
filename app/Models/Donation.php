<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    protected $fillable = [
        'donator_id',
        'amount',
        'donor_phone_number',
        'whatsapp_number',
        'global_donation_number',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function donator(): BelongsTo
    {
        return $this->belongsTo(Donator::class);
    }
}
