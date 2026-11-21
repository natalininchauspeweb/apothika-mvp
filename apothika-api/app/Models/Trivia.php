<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trivia extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'question',
        'options',
        'correct_option_index',
        'explanation',
        'points_reward',
        'is_active',
    ];

    /**
     * Ocultamos la respuesta correcta al serializar la lista pública de trivias.
     * Solo se revela en la respuesta del endpoint cuando el paciente contesta.
     */
    protected $hidden = [
        'correct_option_index',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option_index' => 'integer',
            'points_reward' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function completedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trivia_user')
            ->withPivot('answered_correctly', 'completed_at');
    }
}