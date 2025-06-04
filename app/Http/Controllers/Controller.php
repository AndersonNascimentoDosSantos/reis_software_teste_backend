<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Sistema de Gerenciamento de Tarefas API",
 *     version="1.0.0",
 *     description="API REST para gerenciar tarefas pessoais com autenticação",
 *     @OA\Contact(
 *         email="devandersonnascimentodossantos@gmail.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost",
 *     description="Servidor de Desenvolvimento Local"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Laravel Sanctum Token Authentication"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints de autenticação"
 * )
 *
 * @OA\Tag(
 *     name="Tasks",
 *     description="Endpoints de gerenciamento de tarefas"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
