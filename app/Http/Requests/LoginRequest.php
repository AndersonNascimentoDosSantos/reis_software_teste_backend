<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajuste se quiser alguma lógica de autorização
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'A senha é obrigatória.',
        ];
    }
}
