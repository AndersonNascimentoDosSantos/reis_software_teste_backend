<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;

use App\Services\Logging\StructuredLogger;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    use Loggable;

    public function __construct(
        private readonly StructuredLogger $logger
    ) {}

    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Listar todas as tarefas do usuário autenticado",
     *     tags={"Tasks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por status da tarefa",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "completed"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tarefas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Completar projeto"),
     *                 @OA\Property(property="description", type="string", example="Finalizar o desenvolvimento da API"),
     *                 @OA\Property(property="status", type="string", enum={"pending", "completed"}, example="pending"),
     *                 @OA\Property(property="due_date", type="string", format="datetime", example="2025-06-15T14:30:00Z", nullable=true),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="datetime"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime")
     *             )
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
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Task::where('user_id', Auth::id());

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $tasks = $query->get();

            $this->logInfo('Listagem de tarefas realizada', [
                'user_id' => Auth::id(),
                'filter' => $request->status ?? 'all',
                'count' => $tasks->count()
            ]);

            return response()->json($tasks);
        } catch (\Exception $e) {
            $this->logError('Erro ao listar tarefas', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao listar as tarefas.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Criar nova tarefa",
     *     tags={"Tasks"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description"},
     *             @OA\Property(property="title", type="string", example="Nova tarefa"),
     *             @OA\Property(property="description", type="string", example="Descrição detalhada da tarefa"),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed"}, example="pending"),
     *             @OA\Property(property="due_date", type="string", format="datetime", example="2025-06-15T14:30:00Z", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tarefa criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Nova tarefa"),
     *             @OA\Property(property="description", type="string", example="Descrição detalhada da tarefa"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="due_date", type="string", format="datetime", example="2025-06-15T14:30:00Z", nullable=true),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
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
     * @throws ValidationException
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {


        try {
            $validated = $request->validated();
            $validated['user_id'] = Auth::id();
            $validated['status'] = $validated['status']??"pending";
             $task = Task::create($validated);


            $this->logInfo('Tarefa criada com sucesso', [
                'user_id' => Auth::id(),
                'task_id' => $task->id,
                'status' => $task->status
            ]);

            return response()->json($task, 201);

        } catch (ValidationException $e) {

//            dd( $e->errors());
            $this->logError('Erro de validação ao criar tarefa', [
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);

            $this->logError('Erro ao criar tarefa', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao criar a tarefa.',
                'trace' =>   $e->getTrace(),
                'linha' => $e->getLine(),
            ], 500);
        }


    }

    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     summary="Buscar tarefa específica por ID",
     *     tags={"Tasks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da tarefa",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarefa encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Completar projeto"),
     *             @OA\Property(property="description", type="string", example="Finalizar o desenvolvimento da API"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="due_date", type="string", format="datetime", example="2025-06-15T14:30:00Z", nullable=true),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tarefa não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso negado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied.")
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
    public function show(Task $task): JsonResponse
    {
        try {
            if ($task->user_id !== Auth::id()) {
                $this->logWarning('Tentativa de acesso não autorizado a tarefa', [
                    'user_id' => Auth::id(),
                    'task_id' => $task->id
                ]);

                return response()->json([
                    'message' => 'Não autorizado.'
                ], 403);
            }

            $this->logInfo('Tarefa visualizada', [
                'user_id' => Auth::id(),
                'task_id' => $task->id
            ]);

            return response()->json($task);

        } catch (\Exception $e) {
            $this->logError('Erro ao visualizar tarefa', [
                'user_id' => Auth::id(),
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao visualizar a tarefa.'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     summary="Atualizar tarefa existente",
     *     tags={"Tasks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da tarefa",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Tarefa atualizada"),
     *             @OA\Property(property="description", type="string", example="Nova descrição da tarefa"),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed"}, example="completed"),
     *             @OA\Property(property="due_date", type="string", format="datetime", example="2025-06-20T16:00:00Z", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarefa atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Tarefa atualizada"),
     *             @OA\Property(property="description", type="string", example="Nova descrição da tarefa"),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="due_date", type="string", format="datetime", example="2025-06-20T16:00:00Z", nullable=true),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tarefa não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
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
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        try {
            if ($task->user_id !== Auth::id()) {

                $this->logWarning('Tentativa de atualização não autorizada de tarefa', [
                    'user_id' => Auth::id(),
                    'task_id' => $task->id
                ]);

                return response()->json([
                    'message' => 'Não autorizado.'
                ], 403);
            }

            $validated = $request->validated();

            $task->update($validated);

            $this->logInfo('Tarefa atualizada com sucesso', [
                'user_id' => Auth::id(),
                'task_id' => $task->id,
                'changes' => $validated
            ]);

            return response()->json($task);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                $this->logError('Erro de validação ao atualizar tarefa', [
                    'user_id' => Auth::id(),
                    'task_id' => $task->id,
                    'errors' => $e->errors()
                ]);

                // Re-throw para manter o comportamento original
                throw $e;
            } else {
                $this->logError('Erro ao atualizar tarefa', [
                    'user_id' => Auth::id(),
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'message' => 'Ocorreu um erro ao atualizar a tarefa.'
                ], 500);
            }
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     summary="Excluir tarefa",
     *     tags={"Tasks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da tarefa",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarefa excluída com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tarefa não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task not found.")
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
    public function destroy(Task $task): JsonResponse
    {
        try {
            if ($task->user_id !== Auth::id()) {
                $this->logWarning('Tentativa de exclusão não autorizada de tarefa', [
                    'user_id' => Auth::id(),
                    'task_id' => $task->id
                ]);

                return response()->json([
                    'message' => 'Não autorizado.'
                ], 403);
            }

            $task->delete();

            $this->logInfo('Tarefa excluída com sucesso', [
                'user_id' => Auth::id(),
                'task_id' => $task->id
            ]);

            return response()->json(null, 204);

        } catch (\Exception $e) {

            $this->logError('Erro ao excluir tarefa', [
                'user_id' => Auth::id(),
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao excluir a tarefa.'
            ], 500);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $task = Task::withTrashed()->findOrFail($id);

            if ($task->user_id !== Auth::id()) {
                $this->logWarning('Tentativa de restauração não autorizada de tarefa', [
                    'user_id' => Auth::id(),
                    'task_id' => $task->id
                ]);

                return response()->json([
                    'message' => 'Não autorizado.'
                ], 403);
            }

            $task->restore();

            $this->logInfo('Tarefa restaurada com sucesso', [
                'user_id' => Auth::id(),
                'task_id' => $task->id
            ]);

            return response()->json($task);
        } catch (\Exception $e) {
            $this->logError('Erro ao restaurar tarefa', [
                'user_id' => Auth::id(),
                'task_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao restaurar a tarefa.'
            ], 500);
        }
    }

    public function trashed(): JsonResponse
    {
        try {
            $tasks = Task::where('user_id', Auth::id())
                ->softDeleted()->get();

            $this->logInfo('Listagem de tarefas excluídas realizada', [
                'user_id' => Auth::id(),
                'count' => $tasks->count()
            ]);

            return response()->json($tasks);
        } catch (\Exception $e) {
            $this->logError('Erro ao listar tarefas excluídas', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao listar as tarefas excluídas.'
            ], 500);
        }
    }
}
