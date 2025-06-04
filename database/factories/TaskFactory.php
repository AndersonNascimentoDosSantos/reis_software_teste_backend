<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement(['pending', 'completed']),
            'due_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+3 months'),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the task has no due date.
     */
    public function withoutDueDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => null,
        ]);
    }

    /**
     * Indicate that the task has a specific due date.
     */
    public function dueDate(string $datetime): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $datetime,
        ]);
    }

    /**
     * Indicate that the task is overdue (due date in the past and status pending).
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'due_date' => $this->faker->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }

    /**
     * Indicate that the task is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->addHours(rand(1, 23)),
        ]);
    }

    /**
     * Indicate that the task is due this week.
     */
    public function dueThisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }

    /**
     * Indicate that the task belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a task with a short title and description.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->sentence(2),
            'description' => $this->faker->sentence(6),
        ]);
    }

    /**
     * Create a task with a long title and description.
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->sentence(8),
            'description' => $this->faker->paragraphs(5, true),
        ]);
    }

    /**
     * Create a task with urgent priority (due soon).
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'due_date' => $this->faker->dateTimeBetween('now', '+3 days'),
            'title' => 'URGENT: ' . $this->faker->sentence(3),
        ]);
    }
}
