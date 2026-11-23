<?php

namespace Tests\Feature;

use App\Models\ChapterQuizQuestion;
use App\Models\LearningPath;
use App\Models\LearningPathChapter;
use App\Models\User;
use App\Services\ChapterQuizService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChapterQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_answers_award_xp_once_and_the_student_view_hides_the_answer_key(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $chapter = LearningPathChapter::where('slug', 'premiers-pas')->firstOrFail();
        $question = $chapter->quizQuestions()->firstOrFail();
        $wrongOption = collect(array_keys($question->options))
            ->first(fn (string $option) => $option !== $question->correct_option);

        $this->actingAs($student)
            ->get(route('student.chapter-course', $chapter))
            ->assertOk()
            ->assertDontSee('correct_option');

        Livewire::actingAs($student)
            ->test('student.chapter-course', ['chapter' => $chapter])
            ->call('answerQuestion', $question->id, $wrongOption)
            ->assertSet('lastAnswerCorrect', false)
            ->assertSet('xpAwarded', 0);

        $this->assertDatabaseCount('chapter_quiz_completions', 0);
        $this->assertSame(0, $student->fresh()->total_xp);

        $component = Livewire::actingAs($student)
            ->test('student.chapter-course', ['chapter' => $chapter])
            ->call('answerQuestion', $question->id, $question->correct_option)
            ->assertSet('lastAnswerCorrect', true)
            ->assertSet('xpAwarded', $question->xp_reward);

        $component->call('answerQuestion', $question->id, $question->correct_option)
            ->assertSet('xpAwarded', 0);

        $this->assertDatabaseCount('chapter_quiz_completions', 1);
        $this->assertDatabaseCount('xp_transactions', 1);
        $this->assertSame($question->xp_reward, $student->fresh()->total_xp);
    }

    public function test_course_chapters_stay_locked_until_all_previous_qcms_are_passed(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $firstChapter = LearningPathChapter::where('slug', 'premiers-pas')->firstOrFail();
        $secondChapter = LearningPathChapter::where('slug', 'variables-et-types')->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.chapter-course', $secondChapter))
            ->assertForbidden();

        $service = app(ChapterQuizService::class);
        $firstChapter->quizQuestions->each(
            fn (ChapterQuizQuestion $question) => $service->answer(
                $student,
                $question,
                $question->correct_option
            )
        );

        $this->actingAs($student)
            ->get(route('student.chapter-course', $secondChapter))
            ->assertOk();
    }

    public function test_admin_can_manage_course_content_and_questions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $chapter = LearningPathChapter::where('slug', 'premiers-pas')->firstOrFail();

        $editor = Livewire::actingAs($admin)
            ->test('admin.chapter-course', ['chapter' => $chapter])
            ->assertSee('Leçon du chapitre')
            ->set('courseContent', 'Leçon modifiée.')
            ->call('save');

        $questionCount = $chapter->quizQuestions()->count();

        for ($index = 0; $index < $questionCount; $index++) {
            $editor->call('removeQuestion', 0);
        }

        $editor->call('addQuestion')
            ->set('questions.0.prompt', 'Question ajoutée')
            ->set('questions.0.options.a', 'Réponse A')
            ->set('questions.0.options.b', 'Réponse B')
            ->set('questions.0.options.c', 'Réponse C')
            ->set('questions.0.options.d', 'Réponse D')
            ->set('questions.0.correct_option', 'a')
            ->set('questions.0.xp_reward', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Leçon modifiée.', $chapter->fresh()->course_content);
        $this->assertSame(1, $chapter->quizQuestions()->count());
        $this->assertDatabaseHas('chapter_quiz_questions', ['prompt' => 'Question ajoutée']);

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)
            ->get(route('admin.chapter-course', $chapter))
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_a_chapter_after_students_earn_its_quiz_xp(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $chapter = LearningPathChapter::where('slug', 'premiers-pas')->firstOrFail();
        $question = $chapter->quizQuestions()->firstOrFail();

        app(ChapterQuizService::class)->answer($student, $question, $question->correct_option);

        $path = LearningPath::with('chapters')->whereKey($chapter->learning_path_id)->firstOrFail();
        $remainingChapters = $path->chapters
            ->reject(fn (LearningPathChapter $candidate) => $candidate->is($chapter))
            ->map(fn (LearningPathChapter $candidate) => [
                'id' => $candidate->id,
                'title' => $candidate->title,
                'description' => $candidate->description ?? '',
                'sort_order' => $candidate->sort_order,
            ])
            ->values()
            ->all();

        Livewire::actingAs($admin)
            ->test('admin.learning-paths')
            ->call('edit', $path->id)
            ->set('chapters', $remainingChapters)
            ->call('save')
            ->assertHasErrors(['chapters']);

        $this->assertDatabaseHas('learning_path_chapters', ['id' => $chapter->id]);
        $this->assertSame($question->xp_reward, $student->fresh()->total_xp);
    }
}
