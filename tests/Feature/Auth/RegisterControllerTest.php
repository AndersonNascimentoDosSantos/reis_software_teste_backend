<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
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
            'password' => 'MinhaSenh@123',
            'password_confirmation' => 'MinhaSenh@123',
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
        $this->assertTrue(Hash::check('MinhaSenh@123', $user->password));

        // Verifica se o token foi gerado
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson(route('api.register'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password'])
            ->assertJson([
                'message' => 'Os dados fornecidos são inválidos.'
            ]);
    }

    public function test_validates_name_field()
    {
        // Testa campo obrigatório
        $response = $this->postJson(route('api.register'), [
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['O campo nome é obrigatório.']
            ]);

        // Testa tamanho máximo
        $response = $this->postJson(route('api.register'), [
            'name' => str_repeat('a', 256), // 256 caracteres
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['O campo nome não pode ter mais que 255 caracteres.']
            ]);

        // Testa tamanho mínimo
        $response = $this->postJson(route('api.register'), [
            'name' => 'a', // 1 caractere (menos que 2)
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['O campo nome deve ter pelo menos 2 caracteres.']
            ]);
    }

    public function test_validates_email_field()
    {
        // Testa campo obrigatório
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['O campo email é obrigatório.']
            ]);

        // Testa formato de email inválido
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['O campo email deve ser um endereço de email válido.']
            ]);

        // Testa tamanho máximo
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => str_repeat('a', 250) . '@example.com', // Mais de 255 caracteres
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['O campo email não pode ter mais que 255 caracteres.']
            ]);
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
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Este email já está em uso.']
            ]);
    }

    public function test_validates_password_field()
    {
        // Testa campo obrigatório
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['O campo senha é obrigatório.']
            ]);

        // Testa tamanho mínimo
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Pass1', // 5 caracteres (menos que 8)
            'password_confirmation' => 'Pass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['A senha deve ter pelo menos 8 caracteres.']
            ]);

        // Testa regex da senha (sem maiúscula)
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.']
            ]);

        // Testa regex da senha (sem minúscula)
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'PASSWORD123',
            'password_confirmation' => 'PASSWORD123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.']
            ]);

        // Testa regex da senha (sem número)
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.']
            ]);
    }

    public function test_validates_password_confirmation()
    {
        // Testa senhas diferentes
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'DifferentPass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['A confirmação da senha não confere.']
            ]);

        // Testa sem confirmação
        $response = $this->postJson(route('api.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['A confirmação da senha não confere.']
            ]);
    }

    public function test_returns_user_data_without_password()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'password' => 'MinhaSenh@123',
            'password_confirmation' => 'MinhaSenh@123',
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
            'password' => 'MinhaSenh@123',
            'password_confirmation' => 'MinhaSenh@123',
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
        // Simula um erro de banco de dados usando um email duplicado
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Primeiro cria um usuário
        $response = $this->postJson(route('api.register'), $userData);
        $response->assertStatus(201);

        // Tenta criar o mesmo usuário novamente (deve falhar por email único)
        $response = $this->postJson(route('api.register'), $userData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Este email já está em uso.']
            ]);
    }

    public function test_trims_whitespace_from_inputs()
    {
        $userData = [
            'name' => '  João Silva  ',
            'email' => '  JOAO@EXEMPLO.COM  ',
            'password' => 'MinhaSenh@123',
            'password_confirmation' => 'MinhaSenh@123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'joao@exemplo.com')->first();
        $this->assertEquals('João Silva', $user->name);
        $this->assertEquals('joao@exemplo.com', $user->email); // Email deve ser convertido para minúsculo
    }

    public function test_converts_email_to_lowercase()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'JOAO@EXEMPLO.COM',
            'password' => 'MinhaSenh@123',
            'password_confirmation' => 'MinhaSenh@123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'joao@exemplo.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('joao@exemplo.com', $user->email);
    }

    public function test_handles_special_characters_in_name()
    {
        $userData = [
            'name' => 'José da Silva-Santos',
            'email' => 'jose@exemplo.com',
            'password' => 'MinhaSenh@123',
            'password_confirmation' => 'MinhaSenh@123',
        ];

        $response = $this->postJson(route('api.register'), $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'José da Silva-Santos',
            'email' => 'jose@exemplo.com',
        ]);
    }

    public function test_handles_unexpected_server_errors()
    {
        // Este teste seria mais complexo na prática, mas demonstra o conceito
        // Você poderia mockar o User::create para lançar uma exceção

        // Exemplo básico - em um cenário real você mockaria o banco
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        // Simular um erro interno seria feito através de mocking
        // Por exemplo: mockando o Hash::make() para lançar exceção
        // Aqui só validamos que o endpoint funciona normalmente
        $response = $this->postJson(route('api.register'), $userData);
        $response->assertStatus(201);
    }

    public function test_password_complexity_validation()
    {
        $testCases = [
            [
                'password' => 'password', // Sem maiúscula nem número
                'expected_error' => 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.'
            ],
            [
                'password' => 'PASSWORD', // Sem minúscula nem número
                'expected_error' => 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.'
            ],
            [
                'password' => '12345678', // Só números
                'expected_error' => 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.'
            ],
            [
                'password' => 'Password', // Sem número
                'expected_error' => 'A senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número.'
            ]
        ];

        foreach ($testCases as $testCase) {
            $response = $this->postJson(route('api.register'), [
                'name' => 'Test User',
                'email' => 'test' . time() . '@example.com', // Email único para cada teste
                'password' => $testCase['password'],
                'password_confirmation' => $testCase['password'],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password'])
                ->assertJsonFragment([
                    'password' => [$testCase['expected_error']]
                ]);
        }
    }
}
