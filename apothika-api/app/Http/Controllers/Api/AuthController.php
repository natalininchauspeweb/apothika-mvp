<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Registro público de nuevos pacientes (B2C).
     * Asigna rol 'patient', saldo 0 y genera su código QR identificador único.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'dni' => ['required', 'string', 'regex:/^[0-9]{7,9}$/', 'unique:users,dni'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'pharmacy_slug' => ['nullable', 'string', 'exists:pharmacies,slug'],
        ], [
            'name.required' => 'Ingresá tu nombre y apellido.',
            'name.min' => 'El nombre debe tener al menos :min caracteres.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe una cuenta registrada con ese correo.',
            'dni.required' => 'Ingresá tu DNI (sin puntos ni espacios).',
            'dni.regex' => 'El DNI debe contener entre 7 y 9 dígitos numéricos sin puntos.',
            'dni.unique' => 'Ya existe una cuenta registrada con ese DNI.',
            'password.required' => 'Ingresá una contraseña.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Asociamos al paciente con la farmacia del slug (por defecto Farmacia Dalton en el MVP)
        $pharmacySlug = $validated['pharmacy_slug'] ?? 'farmacia-dalton';
        $pharmacy = Pharmacy::where('slug', $pharmacySlug)->firstOrFail();

        // Creación segura: los campos sensibles se asignan explícitamente fuera de $fillable
        $user = new User([
            'pharmacy_id' => $pharmacy->id,
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'dni' => $validated['dni'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->role = 'patient';
        $user->points_balance = 0;
        $user->qr_code = $this->buildPatientQrCode($pharmacy, $validated['dni']);
        $user->save();

        // Cargamos la relación pharmacy (eager loading) para enviar el tema visual al frontend
        $user->load('pharmacy');

        $token = $user->createToken('apothika-pwa')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada con éxito. ¡Bienvenido/a al club de beneficios!',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    /**
     * Inicio de sesión unificado para Pacientes (B2C) y Farmacias (B2B).
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $user = User::with('pharmacy')
            ->where('email', Str::lower($credentials['email']))
            ->first();

        // Verificamos contraseña hasheada con bcrypt mediante la fachada Hash
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ], 401);
        }

        $token = $user->createToken('apothika-pwa')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada con éxito.',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    /**
     * Devuelve los datos actualizados del usuario autenticado (saldo, QR y marca de farmacia).
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('pharmacy');

        return response()->json([
            'user' => $user,
        ], 200);
    }

    /**
     * Cierra la sesión actual revocando únicamente el token vigente.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    /**
     * Genera un identificador legible y único para el código QR del paciente.
     */
    private function buildPatientQrCode(Pharmacy $pharmacy, string $dni): string
    {
        $prefix = Str::upper(Str::substr(Str::slug($pharmacy->name, ''), 0, 3));

        return "APO-{$prefix}-{$pharmacy->id}-{$dni}";
    }
}
