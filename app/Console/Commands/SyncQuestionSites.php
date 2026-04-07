<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\QuestionSite;
use Illuminate\Console\Command;

class SyncQuestionSites extends Command
{
    protected $signature = 'question-sites:sync';
    protected $description = 'Sync question_sites.enabled with question library defaults';

    public function handle(): int
    {
        $updated = 0;

        Question::select('id', 'is_visible', 'is_disabled', 'is_enabled')
            ->orderBy('id')
            ->chunkById(200, function ($questions) use (&$updated) {
                foreach ($questions as $question) {
                    $enabled = $question->is_disabled
                        ? true
                        : ($question->is_visible ? (bool) $question->is_enabled : false);

                    $updated += QuestionSite::where('question_id', $question->id)
                        ->update(['enabled' => $enabled]);
                }
            });

        $this->info("Synced question_sites for {$updated} rows.");

        return self::SUCCESS;
    }
}
