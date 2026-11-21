<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Pharmacy extends Model
{
    use HasFactory;

    /**
     * Campos editables desde el panel de personalización White-Label (B2B).
     */
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'primary_color',
        'secondary_color',
        'surface_color',
        'text_color',
        'font_preset',
        'points_per_1000_ars',
        'is_active',
    ];

    /**
     * Agrega automáticamente la URL pública del logo al serializar a JSON.
     */
    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'points_per_1000_ars' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accessor: resuelve la URL pública del logo usando la API de File Storage.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->logo_path) {
                return null;
            }

            if (str_starts_with($this->logo_path, 'http')) {
                return $this->logo_path;
            }

            return Storage::url($this->logo_path);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function redeemedCoupons(): HasMany
    {
        return $this->hasMany(RedeemedCoupon::class);
    }
}