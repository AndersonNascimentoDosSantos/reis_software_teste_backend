<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    use Loggable;

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Registrar novo usuário",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@exemplo.com"),
     *             @OA\Property(property="password", type="string", format="password", example="MinhaSenh@123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="MinhaSenh@123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="joao@exemplo.com"),
     *                 @OA\Property(property="created_at", type="string", format="datetime"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abcdef123456...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Os dados fornecidos são inválidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        try {
            // Os dados já estão validados pelo RegisterRequest
            $validated = $request->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            $this->logInfo('Usuário registrado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            $this->logError('Erro inesperado no registro', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro interno. Tente novamente mais tarde.'
            ],  $e->getCode());
        }
    }
}
