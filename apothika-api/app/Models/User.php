<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Solo atributos seguros para asignación masiva.
     * 'role', 'points_balance' y 'qr_code' se asignan explícitamente en el backend
     * para evitar escalada de privilegios o manipulación de saldo.
     */
    protected $fillable = [
        'pharmacy_id',
        'name',
        'email',
        'password',
        'dni',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points_balance' => 'integer',
        ];
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'patient_id');
    }

    public function redeemedCoupons(): HasMany
    {
        return $this->hasMany(RedeemedCoupon::class, 'patient_id');
    }

    /**
     * Relación N:M con trivias completadas por el paciente.
     */
    public function completedTrivias(): BelongsToMany
    {
        return $this->belongsToMany(Trivia::class, 'trivia_user')
            ->withPivot('answered_correctly', 'completed_at');
    }

    /**
     * Helpers de rol para mantener controladores y middlewares legibles.
     */
    public function isPharmacy(): bool
    {
        return $this->role === 'pharmacy';
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }
}