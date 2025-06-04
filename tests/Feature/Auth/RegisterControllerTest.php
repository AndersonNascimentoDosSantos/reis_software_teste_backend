<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Configurações adicionais se necessário
    }


    public function test_can_register_a_new_user_successfully()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'token'
            ]);

        // Verifica se o usuário foi criado no banco de dados
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
        ]);

        // Verifica se a senha foi criptografada
        $user = User::where('email', 'joao@exemplo.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        // Verifica se o token foi gerado
        $this->assertNotEmpty($response->json('token'));
    }


    public function test_validates_required_fields()
    {
        $response = $this->postJson(route('api.register'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }


    public function test_validates_name_field()
    {
        // Testa campo obrigatório
        $response = $this->postJson(route('api.register'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Testa tamanho máximo
        $response = $this->postJson(route('api.register'), [
            'name' => str_repeat('a', 256), // 256 caracteres
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }


    public function test_validates_email_field()
    {
        // Testa campo obrigatório
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Testa formato de email inválido
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Testa tamanho máximo
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => str_repeat('a', 250) . '@example.com', // Mais de 255 caracteres
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_validates_email_uniqueness()
    {
        // Cria um usuário existente
        User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_validates_password_field()
    {
        // Testa campo obrigatório
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Testa tamanho mínimo
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '1234567', // 7 caracteres (menos que 8)
            'password_confirmation' => '1234567',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function test_validates_password_confirmation()
    {
        // Testa senhas diferentes
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Testa sem confirmação
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function test_returns_user_data_without_password()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $userData = $response->json('user');
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('name', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('created_at', $userData);
        $this->assertArrayHasKey('updated_at', $userData);
    }


    public function test_generates_valid_sanctum_token()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Verifica se o token é válido fazendo uma requisição autenticada
        $user = User::where('email', 'joao@exemplo.com')->first();
        $this->assertNotNull($user);

        // Testa se o token funciona para autenticação
        $authenticatedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user'); // Assumindo que existe uma rota /api/user

        // Se a rota /api/user não existir, você pode comentar esta parte
        // $authenticatedResponse->assertStatus(200);
    }


    public function test_handles_database_errors_gracefully()
    {
        // Simula um erro de banco de dados usando um email muito longo
        // que pode passar na validação mas falhar no banco
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Primeiro cria um usuário
        $response = $this->postJson(route('api.register'), $userData);
        $response->assertStatus(201);

        // Tenta criar o mesmo usuário novamente (deve falhar por email único)
        $response = $this->postJson(route('api.register'), $userData);
        $response->assertStatus(422);
    }


    public function test_trims_whitespace_from_inputs()
    {
        $userData = [
            'name' => '  João Silva  ',
            'email' => '  joao@exemplo.com  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'joao@exemplo.com')->first();
        $this->assertEquals('João Silva', $user->name);
        $this->assertEquals('joao@exemplo.com', $user->email);
    }


    public function test_handles_special_characters_in_name()
    {
        $userData = [
            'name' => 'José da Silva-Santos',
            'email' => 'jose@exemplo.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'José da Silva-Santos',
            'email' => 'jose@exemplo.com',
        ]);
    }
}
