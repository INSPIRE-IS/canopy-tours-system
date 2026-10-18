<?php

use App\Console\Commands\SeedHelpQuestions;

function seededHelpQuestions(): array
{
    $method = new ReflectionMethod(SeedHelpQuestions::class, 'sampleQuestions');

    return $method->invoke(new SeedHelpQuestions);
}

function seededHelpQuestion(string $question): array
{
    return collect(seededHelpQuestions())->firstWhere('question', $question) ?? [];
}

test('maintained help articles have unique category and question keys', function () {
    $questions = collect(seededHelpQuestions());
    $keys = $questions->map(fn (array $item) => $item['category'].'|'.$item['question']);

    expect($keys->unique()->count())->toBe($keys->count());
});

test('public booking help documents the two stage expiry lifecycle', function () {
    $article = seededHelpQuestion('Why do unpaid online bookings expire?');

    expect($article)
        ->not->toBeEmpty()
        ->and($article['business_rules'])->toContain('15 minutes')
        ->and($article['business_rules'])->toContain('3 hours')
        ->and($article['business_rules'])->toContain('does not create a customer, booking, invoice, or payment record');
});

test('help articles distinguish current public features from legacy or external features', function () {
    $allText = collect(seededHelpQuestions())
        ->map(fn (array $item) => implode(' ', array_filter($item, 'is_string')))
        ->implode("\n");

    expect($allText)
        ->not->toContain('must be set to Bookable/Gift/Both')
        ->not->toContain('Voucher codes apply at checkout')
        ->and(seededHelpQuestion('How does slot funneling work?'))->not->toBeEmpty()
        ->and(seededHelpQuestion('What does GA4 purchase tracking include?'))->not->toBeEmpty();
});
