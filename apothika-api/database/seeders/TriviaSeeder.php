<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TriviaSeeder extends Seeder
{
    public function run(): void
    {
        $trivias = [
            [
                'category' => 'Dermocosmética',
                'question' => '¿Cada cuántas horas conviene reaplicar el protector solar cuando estás al aire libre?',
                'options' => json_encode([
                    'Una sola vez a la mañana alcanza para todo el día',
                    'Cada 2 horas, o después de transpirar o meterte al agua',
                    'Solo cuando sentís calor o ardor en la piel',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'Los filtros solares pierden eficacia con la radiación UV, el sudor y el roce. Reaplicar cada 2 horas mantiene la protección activa incluso en días nublados.',
                'points_reward' => 30,
            ],
            [
                'category' => 'Uso Responsable',
                'question' => 'Si el médico te recetó un antibiótico por 7 días y al día 3 ya te sentís bien, ¿qué debés hacer?',
                'options' => json_encode([
                    'Suspenderlo para no sobrecargar el organismo',
                    'Tomar la mitad de la dosis hasta terminar la caja',
                    'Completar los 7 días indicados por el profesional',
                ]),
                'correct_option_index' => 2,
                'explanation' => 'Interrumpir un antibiótico antes de tiempo permite que sobrevivan las bacterias más fuertes y genera resistencia antimicrobiana.',
                'points_reward' => 35,
            ],
            [
                'category' => 'Botiquín en Casa',
                'question' => '¿Cuál es el mejor lugar de la casa para guardar tus medicamentos de uso habitual?',
                'options' => json_encode([
                    'En el botiquín del baño para tenerlos a mano',
                    'En un lugar fresco, seco, protegido de la luz y fuera del alcance de niños',
                    'Sobre la mesada de la cocina cerca de la hornalla',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'El vapor de la ducha y el calor de la cocina degradan los principios activos. Un placard o cajón seco y ventilado conserva intacta su eficacia.',
                'points_reward' => 25,
            ],
            [
                'category' => 'Hidratación',
                'question' => '¿Cuándo es el momento ideal para hidratarse durante el día?',
                'options' => json_encode([
                    'Solo cuando sentís mucha sed',
                    'De forma distribuida a lo largo del día, incluso antes de tener sed',
                    'Únicamente durante las comidas principales',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'La sed es una señal tardía de deshidratación leve. Tomar agua de forma constante ayuda a la concentración, la piel y la función renal.',
                'points_reward' => 25,
            ],
            [
                'category' => 'Cuidado Cardiovascular',
                'question' => 'Antes de medirte la presión arterial en la farmacia o en casa, ¿qué hábito mejora la precisión?',
                'options' => json_encode([
                    'Tomar un café rápido para estar despierto',
                    'Reposar sentado 5 minutos con la espalda apoyada y sin cruzar las piernas',
                    'Medirla apenas terminás de caminar rápido',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'El reposo previo de 5 minutos, apoyar los pies en el piso sin cruzar las piernas y evitar cafeína 30 minutos antes evita lecturas falsamente elevadas.',
                'points_reward' => 30,
            ],
            [
                'category' => 'Dermocosmética',
                'question' => '¿En qué orden conviene aplicar una rutina facial básica de mañana?',
                'options' => json_encode([
                    'Protector solar → Limpiador → Crema hidratante',
                    'Limpiador → Hidratante → Protector solar como último paso',
                    'Crema hidratante → Protector solar → Limpiador',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'Primero se limpia la piel, luego se hidrata y por último se sella con protector solar para formar una barrera uniforme frente a los rayos UV.',
                'points_reward' => 30,
            ],
            [
                'category' => 'Descanso y Bienestar',
                'question' => '¿Qué hábito nocturno favorece una mejor calidad de sueño?',
                'options' => json_encode([
                    'Usar el celular con brillo alto hasta quedarse dormido',
                    'Mantener horarios regulares y reducir pantallas 45 minutos antes de acostarse',
                    'Cenar comidas muy pesadas justo antes de dormir',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'La luz azul de las pantallas inhibe la liberación natural de melatonina. Bajar estímulos antes de dormir facilita un descanso reparador.',
                'points_reward' => 25,
            ],
            [
                'category' => 'Prevención',
                'question' => '¿Qué significa la fecha de vencimiento impresa en un medicamento?',
                'options' => json_encode([
                    'Que sirve hasta 6 meses después si la caja está cerrada',
                    'El último mes hasta el cual el laboratorio garantiza su seguridad y eficacia',
                    'Solo aplica a jarabes y cremas, no a comprimidos',
                ]),
                'correct_option_index' => 1,
                'explanation' => 'Pasada esa fecha no debe consumirse ningún medicamento. Consultá siempre en tu farmacia cómo desechar de forma segura los envases vencidos.',
                'points_reward' => 35,
            ],
        ];

        foreach ($trivias as $trivia) {
            DB::table('trivias')->insert(array_merge($trivia, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}