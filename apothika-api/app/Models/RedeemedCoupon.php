<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedeemedCoupon extends Model
{
    use HasFactory;

    /**
     * 'status' y 'used_at' no son mass-assignable: se modifican
     * explícitamente al validar el cupón en caja.
     */
    protected $fillable = [
        'patient_id',
        'pharmacy_id',
        'reward_id',
        'redeem_code',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}