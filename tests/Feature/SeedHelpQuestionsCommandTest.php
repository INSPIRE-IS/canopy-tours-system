<?php

use App\Models\HelpAnswer;
use App\Models\HelpQuestion;

test('help seeder persists current baseline articles idempotently', function () {
    $this->artisan('seed:help-questions', ['--with-samples' => true])
        ->assertSuccessful();

    $questionCount = HelpQuestion::count();
    $expiryQuestion = HelpQuestion::where('question', 'Why do unpaid online bookings expire?')->firstOrFail();

    expect(HelpAnswer::where('help_question_id', $expiryQuestion->id)->value('answer'))
        ->toContain('15 minutes')
        ->toContain('3 hours');

    $this->artisan('seed:help-questions', ['--with-samples' => true])
        ->assertSuccessful();

    expect(HelpQuestion::count())->toBe($questionCount);
});
