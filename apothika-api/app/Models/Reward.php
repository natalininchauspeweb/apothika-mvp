<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_id',
        'title',
        'description',
        'points_cost',
        'image_path',
        'is_active',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->image_path) {
                return null;
            }

            if (str_starts_with($this->image_path, 'http')) {
                return $this->image_path;
            }

            return Storage::url($this->image_path);
        });
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function redeemedCoupons(): HasMany
    {
        return $this->hasMany(RedeemedCoupon::class);
    }
}