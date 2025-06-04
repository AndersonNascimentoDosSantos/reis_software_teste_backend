<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Testa listagem de tarefas sem autenticação
     */
    public function test_index_requires_authentication()
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Testa listagem de tarefas com usuário autenticado
     */
    public function test_index_returns_user_tasks()
    {
        Sanctum::actingAs($this->user);

        // Criar tarefas para o usuário autenticado
        $userTasks = Task::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Criar tarefa para outro usuário (não deve aparecer)
        $otherUser = User::factory()->create();
        Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'due_date',
                    'user_id',
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Verificar se apenas as tarefas do usuário autenticado são retornadas
        $responseData = $response->json();
        foreach ($responseData as $task) {
            $this->assertEquals($this->user->id, $task['user_id']);
        }
    }

    /**
     * Testa filtro por status na listagem
     */
    public function test_index_filters_by_status()
    {
        Sanctum::actingAs($this->user);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);

        // Testar filtro por status pending
        $response = $this->getJson('/api/tasks?status=pending');
        $response->assertStatus(200)
            ->assertJsonCount(1);

        $this->assertEquals('pending', $response->json()[0]['status']);

        // Testar filtro por status completed
        $response = $this->getJson('/api/tasks?status=completed');
        $response->assertStatus(200)
            ->assertJsonCount(1);

        $this->assertEquals('completed', $response->json()[0]['status']);
    }

    /**
     * Testa criação de tarefa sem autenticação
     */
    public function test_store_requires_authentication()
    {
        $taskData = [
            'title' => 'Nova Tarefa',
            'description' => 'Descrição da tarefa'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Testa criação de tarefa com dados válidos
     */
    public function test_store_creates_task_with_valid_data()
    {
        Sanctum::actingAs($this->user);

        $taskData = [
            'title' => 'Nova Tarefa',
            'description' => 'Descrição detalhada da tarefa',
            'status' => 'pending',
            'due_date' => '2025-12-31 14:30:00'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'status',
                'due_date',
                'user_id',
                'created_at',
                'updated_at'
            ])
            ->assertJsonFragment([
                'title' => 'Nova Tarefa',
                'description' => 'Descrição detalhada da tarefa',
                'status' => 'pending',
                'user_id' => $this->user->id
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Nova Tarefa',
            'description' => 'Descrição detalhada da tarefa',
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Testa criação de tarefa com status padrão
     */
    public function test_store_defaults_status_to_pending()
    {
        Sanctum::actingAs($this->user);

        $taskData = [
            'title' => 'Nova Tarefa',
            'description' => 'Descrição da tarefa'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'pending']);
    }

    /**
     * Testa validação na criação de tarefa
     */
    public function test_store_validates_required_fields()
    {
        Sanctum::actingAs($this->user);

        // Testar sem title
        $response = $this->postJson(route("tasks.store"), [
            'description' => 'Descrição da tarefa'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        // Testar sem description
        $response = $this->postJson(route("tasks.store"), [
            'title' => 'Título da tarefa'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        // Testar status inválido
        $response = $this->postJson(route("tasks.store"), [
            'title' => 'Título',
            'description' => 'Descrição',
            'status' => 'invalid_status'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Testar data no passado
        $response = $this->postJson(route("tasks.store"), [
            'title' => 'Título',
            'description' => 'Descrição',
            'due_date' => '2020-01-01 10:00:00'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    /**
     * Testa busca de tarefa específica sem autenticação
     */
    public function test_show_requires_authentication()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route("tasks.show",['task'=>$task->id]));

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Testa busca de tarefa específica do usuário
     */
    public function test_show_returns_user_task()
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson(route("tasks.show",['task'=>$task->id]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'status',
                'due_date',
                'user_id',
                'created_at',
                'updated_at'
            ])
            ->assertJsonFragment([
                'id' => $task->id,
                'title' => $task->title,
                'user_id' => $this->user->id
            ]);
    }

    /**
     * Testa busca de tarefa inexistente
     */
    public function test_show_returns_404_for_nonexistent_task()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route("tasks.show",['task'=>999999]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }

    /**
     * Testa busca de tarefa de outro usuário
     */
    public function test_show_returns_404_for_other_user_task()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson(route("tasks.show",['task'=>$otherTask->id]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }

    /**
     * Testa atualização de tarefa sem autenticação
     */
    public function test_update_requires_authentication()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson(route("tasks.update",['task'=>$task->id]), [
            'title' => 'Título atualizado'
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Testa atualização de tarefa com dados válidos
     */
    public function test_update_updates_task_with_valid_data()
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Título atualizado',
            'description' => 'Descrição atualizada',
            'status' => 'completed',
            'due_date' => '2025-12-31 16:00:00'
        ];

        $response = $this->putJson(route("tasks.update",['task'=>$task->id]), $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $task->id,
                'title' => 'Título atualizado',
                'description' => 'Descrição atualizada',
                'status' => 'completed',
                'user_id' => $this->user->id
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Título atualizado',
            'description' => 'Descrição atualizada',
            'status' => 'completed'
        ]);
    }

    /**
     * Testa atualização parcial de tarefa
     */
    public function test_update_allows_partial_updates()
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Título original',
            'description' => 'Descrição original',
            'status' => 'pending'
        ]);

        // Atualizar apenas o status
        $response = $this->putJson(route("tasks.update",['task'=>$task->id]), [
            'status' => 'completed'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Título original',
                'description' => 'Descrição original',
                'status' => 'completed'
            ]);
    }

    /**
     * Testa atualização de tarefa inexistente
     */
    public function test_update_returns_404_for_nonexistent_task()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson(route("tasks.update",['task'=>999999]), [
            'title' => 'Novo título'
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }

    /**
     * Testa atualização de tarefa de outro usuário
     */
    public function test_update_returns_404_for_other_user_task()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson(route("tasks.update",['task'=>$otherTask->id]), [
            'title' => 'Título atualizado'
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }

    /**
     * Testa validação na atualização
     */
    public function test_update_validates_data()
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        // Testar status inválido
        $response = $this->putJson(route("tasks.update",['task'=>$task->id]), [
            'status' => 'invalid_status'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Testar data no passado
        $response = $this->putJson(route("tasks.update",['task'=>$task->id]), [
            'due_date' => '2020-01-01 10:00:00'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    /**
     * Testa exclusão de tarefa sem autenticação
     */
    public function test_destroy_requires_authentication()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson(route("tasks.destroy",['task'=>$task->id]));

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Testa exclusão de tarefa com sucesso
     */
    public function test_destroy_deletes_user_task()
    {
        Sanctum::actingAs($this->user);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson(route("tasks.destroy",['task'=>$task->id]));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully.']);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    /**
     * Testa exclusão de tarefa inexistente
     */
    public function test_destroy_returns_404_for_nonexistent_task()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson(route("tasks.destroy",['task'=>999999]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);
    }

    /**
     * Testa exclusão de tarefa de outro usuário
     */
    public function test_destroy_returns_404_for_other_user_task()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson(route("tasks.destroy",['task'=>$otherTask->id]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found.']);

        // Verificar que a tarefa não foi excluída
        $this->assertDatabaseHas('tasks', [
            'id' => $otherTask->id
        ]);
    }

    /**
     * Testa ordenação das tarefas por data de criação
     */
    public function test_index_orders_tasks_by_created_at_desc()
    {
        Sanctum::actingAs($this->user);

        $firstTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        $secondTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay()
        ]);

        $thirdTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        $response = $this->getJson(route("tasks.index"));

        $response->assertStatus(200);

        $tasks = $response->json();

        // Verificar ordem decrescente por created_at
        $this->assertEquals($thirdTask->id, $tasks[0]['id']);
        $this->assertEquals($secondTask->id, $tasks[1]['id']);
        $this->assertEquals($firstTask->id, $tasks[2]['id']);
    }
}
