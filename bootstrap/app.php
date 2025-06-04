<?php

use App\Services\Logging\StructuredLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
        $middleware->statefulApi();

            $middleware->validateCsrfTokens(except: [
                'api/*'
            ]);


    })->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'Token inválido ou expirado'
                ], 401);
            }
            return response()->view('errors.401', [
                'message' => 'Registro não encontrado'
            ], 401);
        });
        // Tratamento para ModelNotFoundException
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Recurso não encontrado',
                    'message' => 'O registro solicitado não foi encontrado no sistema',
                    'code' => 'RESOURCE_NOT_FOUND'
                ], 404);
            }

            return response()->view('errors.404', [
                'message' => 'Registro não encontrado'
            ], 404);
        });

        // Tratamento para NotFoundHttpException
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Endpoint não encontrado',
                    'message' => 'A rota solicitada não existe',
                    'code' => 'ROUTE_NOT_FOUND'
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // Tratamento para ValidationException
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'message' => 'Os dados fornecidos são inválidos',
                    'errors' => $e->errors(),
                    'code' => 'VALIDATION_ERROR'
                ], 422);
            }

            return response()->view('errors.422', [], 422);
        });

        // Tratamento genérico para outras exceções
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Em produção, não expor detalhes do erro
                if (app()->environment('production')) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Erro interno do servidor',
                        'message' => 'Ocorreu um erro inesperado. Tente novamente mais tarde.',
                        'code' => 'INTERNAL_ERROR'
                    ], 500);
                }

                // Em desenvolvimento, mostrar detalhes
                return response()->json([
                    'success' => false,
                    'error' => 'Erro interno do servidor',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'code' => 'INTERNAL_ERROR'
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        });

        // Reportar exceções específicas
        $exceptions->report(function (ModelNotFoundException $e) {
             $logger = new StructuredLogger();
            // Log personalizado para registros não encontrados
            $logger->info('Tentativa de acesso a registro inexistente', [
                'model' => $e->getModel(),
                'ids' => $e->getIds(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'url' => request()->fullUrl()
            ]);
        });

    })->create();




