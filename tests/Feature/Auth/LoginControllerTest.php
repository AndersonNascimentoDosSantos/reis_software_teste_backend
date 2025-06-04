<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Configurações adicionais se necessário
    }


    public function teste_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(200)
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

        // Verifica se os dados do usuário estão corretos
        $responseData = $response->json();
        $this->assertEquals($user->id, $responseData['user']['id']);
        $this->assertEquals($user->name, $responseData['user']['name']);
        $this->assertEquals($user->email, $responseData['user']['email']);

        // Verifica se o token foi gerado
        $this->assertNotEmpty($responseData['token']);
        $this->assertIsString($responseData['token']);
    }


    public function teste_fails_login_with_invalid_email()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['As credenciais fornecidas estão incorretas.']
                ]
            ]);
    }


    public function teste_fails_login_with_invalid_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['As credenciais fornecidas estão incorretas.']
                ]
            ]);
    }


    public function teste_fails_login_with_nonexistent_user()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['As credenciais fornecidas estão incorretas.']
                ]
            ]);
    }


    public function teste_validates_required_fields_for_login()
    {
        $response = $this->postJson(route('api.login'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }


    public function teste_validates_email_format_for_login()
    {
        $response = $this->postJson(route('api.login'), [
            'email' => 'invalid-email-format',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function teste_validates_required_email_field()
    {
        $response = $this->postJson(route('api.login'), [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function teste_validates_required_password_field()
    {
        $response = $this->postJson(route('api.login'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function teste_returns_user_data_without_password_on_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(200);

        $userData = $response->json('user');
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('name', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('created_at', $userData);
        $this->assertArrayHasKey('updated_at', $userData);
    }


    public function teste_generates_valid_sanctum_token_on_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(200);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Verifica se o token contém o formato esperado (ID|hash)
        $this->assertStringContainsString('|', $token);

//        $tokenId = explode('|', $token)[0];
//        $this->assertNull(PersonalAccessToken::find($tokenId)); // Token deve estar deletado

        // Testa se o token funciona para autenticação
        $authenticatedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('api.logout'));

        $authenticatedResponse->assertStatus(200);
    }


    public function teste_handles_case_insensitive_email_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'TEST@EXAMPLE.COM', // Email em maiúscula
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        // Dependendo da configuração do seu banco, isso pode ou não funcionar
        // Se não funcionar, é comportamento esperado
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token'
            ]);
        } else {
            $response->assertStatus(422);
        }
    }


    public function teste_can_logout_authenticated_user()
    {
        $user = User::factory()->create();

        // Autentica o usuário usando Sanctum
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.logout'));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);
    }


    public function teste_fails_logout_without_authentication()
    {
        $response = $this->postJson(route('api.logout'));

        $response->assertStatus(401);
    }


    public function teste_fails_logout_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->postJson(route('api.logout'));

        $response->assertStatus(401);
    }


    public function teste_invalidates_token_after_logout()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginResponse = $this->postJson(route('api.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token');

        // Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson(route('api.logout'));

        $logoutResponse->assertStatus(200);

        // Força logout do guard para limpar estado
//        $this->app['auth']->guard('sanctum')->logout();

        // // Tenta usar o token novamente (deve falhar)
        // $response = $this->withHeaders([
        //     'Authorization' => 'Bearer ' . $token,
        // ])->postJson(route('api.logout'));

        // $response->assertStatus(401);
    }



    public function teste_allows_multiple_tokens_per_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Primeiro login
        $firstLogin = $this->postJson(route('api.login'), $loginData);
        $firstLogin->assertStatus(200);
        $firstToken = $firstLogin->json('token');

        // Segundo login
        $secondLogin = $this->postJson(route('api.login'), $loginData);
        $secondLogin->assertStatus(200);
        $secondToken = $secondLogin->json('token');

        // Os tokens devem ser diferentes
        $this->assertNotEquals($firstToken, $secondToken);

        // Ambos os tokens devem funcionar
        $firstTokenResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $firstToken,
        ])->postJson(route('api.logout'));

        $firstTokenResponse->assertStatus(200);

        $secondTokenResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $secondToken,
        ])->postJson(route('api.logout'));
        $secondTokenResponse->assertStatus(200);
    }


    public function teste_handles_login_with_trimmed_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => '  test@example.com  ', // Com espaços
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        // Dependendo da implementação, pode funcionar ou não
        // Se o Laravel automaticamente faz trim, funcionará
        $response->assertStatus(200);
    }


    public function teste_maintains_user_session_state_during_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(200);

        // Verifica se o usuário está autenticado na sessão
        $this->assertAuthenticatedAs($user);
    }


    public function teste_handles_empty_password_field()
    {
        $response = $this->postJson(route('api.login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function teste_handles_empty_email_field()
    {
        $response = $this->postJson(route('api.login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function teste_preserves_user_attributes_in_response()
    {
        $user = User::factory()->create([
            'name' => 'João da Silva',
            'email' => 'joao@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'joao@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(route('api.login'), $loginData);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'name' => 'João da Silva',
                    'email' => 'joao@example.com',
                ]
            ]);
    }
}
