<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use App\Traits\Loggable;

class LoginController extends Controller
{
    use Loggable;

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login do usuário",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="joao@exemplo.com"),
     *             @OA\Property(property="password", type="string", format="password", example="123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="joao@exemplo.com"),
     *                 @OA\Property(property="created_at", type="string", format="datetime"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime")
     *             ),
     *             @OA\Property(property="token", type="string", example="2|abcdef123456...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect.")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            if (!Auth::attempt($credentials)) {
                $this->logWarning('Tentativa de login falhou', [
                    'email' => $request->email,
                    'reason' => 'Credenciais inválidas'
                ]);

                throw ValidationException::withMessages([
                    'email' => ['As credenciais fornecidas estão incorretas.'],
                ]);
            }

            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            $this->logInfo('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        } catch (ValidationException $e) {
            $this->logError('Erro de validação no login', [
                'errors' => $e->errors(),
                'email' => $request->email
            ]);

            throw $e;
        } catch (\Exception $e) {
            $this->logError('Erro inesperado no login', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao tentar fazer login.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout do usuário",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
//    use Laravel\Sanctum\PersonalAccessToken;


    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->bearerToken();
        $token = PersonalAccessToken::findToken($accessToken);
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }




}
