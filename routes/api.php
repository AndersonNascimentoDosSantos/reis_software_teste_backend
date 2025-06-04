<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


Route::post('/sanctum/token', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['As credenciais fornecidas estão incorretas.'],
        ]);
    }

    return $user->createToken($request->device_name)->plainTextToken;
});

Route::group([
//    'middleware' => CheckAuthenticated::class,
    'prefix' => 'auth' // Adicione um prefixo para a rota
],function () {
    Route::post('/register', [RegisterController::class, 'store'])->name('api.register');
    Route::post('/login', [LoginController::class, 'login'])->name('api.login');

    Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout'])->name('api.logout');

});
// Rotas de autenticação
// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tasks', TaskController::class);
    Route::post("/tasks/{task}/restore", [TaskController::class, 'restore'])->name('tasks.restore');
    Route::post("/tasks/trashed", [TaskController::class, 'trashed'])->name('tasks.trashed');

});
