<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedMarketingOptInReportTestData extends Command
{
    protected $signature = 'reports:seed-marketing-optin-test
        {--team=3 : Team id to seed against}
        {--date=2026-06-27 : Activity date for the target bookings}
        {--booking-ref-1=REF-VD4JNJ : First booking reference}
        {--booking-ref-2=REF-4WJNE3 : Second booking reference}
        {--dry-run : Run all checks and inserts, then roll back}';

    protected $description = 'Seed controlled Marketing Opt-In report test data for two existing bookings.';

    public function handle(): int
    {
        $teamId = (int) $this->option('team');
        $date = (string) $this->option('date');
        $refs = [
            1 => (string) $this->option('booking-ref-1'),
            2 => (string) $this->option('booking-ref-2'),
        ];
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Seeding Marketing Opt-In report test data...');
        $this->line("Team: {$teamId}");
        $this->line("Date: {$date}");
        $this->line('Refs: ' . implode(', ', $refs));
        $this->line('Database: ' . DB::connection()->getDatabaseName());

        DB::beginTransaction();

        try {
            $targets = $this->targetBookingItems($teamId, $date, $refs);

            if ($targets->count() !== 2) {
                $this->error("Expected 2 target booking items, found {$targets->count()}.");
                $this->table(['target_no', 'reference', 'booking_id', 'booking_item_id', 'team_id', 'date', 'start_time'], $targets->map(fn($row) => (array) $row));
                DB::rollBack();
                return self::FAILURE;
            }

            $this->info('Target booking items found:');
            $this->table(['target_no', 'reference', 'booking_id', 'booking_item_id', 'team_id', 'date', 'start_time'], $targets->map(fn($row) => (array) $row));

            $questionIds = $this->questionIds();
            if (count($questionIds) !== 5) {
                $this->error('Expected 5 required question slugs, found ' . count($questionIds) . '.');
                $this->line('Found: ' . implode(', ', array_keys($questionIds)));
                DB::rollBack();
                return self::FAILURE;
            }

            $fixtures = $this->fixtures();
            $createdCustomers = 0;
            $createdLinks = 0;
            $createdManifests = 0;
            $createdAnswers = 0;
            $updatedAnswers = 0;

            foreach ($fixtures as $fixture) {
                $target = $targets->firstWhere('target_no', $fixture['target_no']);

                $customer = DB::table('customers')
                    ->where('full_name', $fixture['full_name'])
                    ->first();

                if (!$customer) {
                    $customerId = DB::table('customers')->insertGetId([
                        'full_name' => $fixture['full_name'],
                        'email_address' => $fixture['email_address'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $createdCustomers++;
                } else {
                    $customerId = $customer->id;
                    DB::table('customers')
                        ->where('id', $customerId)
                        ->update([
                            'email_address' => $fixture['email_address'],
                            'updated_at' => now(),
                        ]);
                }

                $linkExists = DB::table('booking_items_customer')
                    ->where('booking_item_id', $target->booking_item_id)
                    ->where('customer_id', $customerId)
                    ->exists();

                if (!$linkExists) {
                    DB::table('booking_items_customer')->insert([
                        'booking_item_id' => $target->booking_item_id,
                        'customer_id' => $customerId,
                        'team_id' => $teamId,
                        'is_lead' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $createdLinks++;
                }

                $manifest = DB::table('customer_manifests')
                    ->where('booking_item_id', $target->booking_item_id)
                    ->where('customer_id', $customerId)
                    ->first();

                $manifestPayload = [
                    'position' => $fixture['position_no'],
                    'type' => $fixture['manifest_type'],
                    'notes' => null,
                    'is_indemnity' => 1,
                    'booking_item_id' => $target->booking_item_id,
                    'customer_id' => $customerId,
                    'team_id' => $teamId,
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('customer_manifests', 'attendance_status')) {
                    $manifestPayload['attendance_status'] = 'no_show';
                }
                if (Schema::hasColumn('customer_manifests', 'is_feedback')) {
                    $manifestPayload['is_feedback'] = 0;
                }
                if (Schema::hasColumn('customer_manifests', 'deleted_at')) {
                    $manifestPayload['deleted_at'] = null;
                }

                if ($manifest) {
                    DB::table('customer_manifests')->where('id', $manifest->id)->update($manifestPayload);
                } else {
                    $manifestPayload['created_at'] = now();
                    DB::table('customer_manifests')->insert($manifestPayload);
                    $createdManifests++;
                }

                $answers = [
                    'full_name' => $fixture['full_name'],
                    'email_address' => $fixture['email_address'] ?? '',
                    'marketing_opt_in' => $fixture['marketing_opt_in'],
                    'birthday_month' => $fixture['birthday_month'] ?? '',
                    'birthday_day' => $fixture['birthday_day'] ?? '',
                ];

                foreach ($answers as $slug => $answer) {
                    $existingAnswer = DB::table('question_answers')
                        ->where('booking_item_id', $target->booking_item_id)
                        ->where('customer_id', $customerId)
                        ->where('question_id', $questionIds[$slug])
                        ->first();

                    $answerPayload = [
                        'answer' => $answer,
                        'question_id' => $questionIds[$slug],
                        'customer_id' => $customerId,
                        'booking_item_id' => $target->booking_item_id,
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('question_answers', 'deleted_at')) {
                        $answerPayload['deleted_at'] = null;
                    }

                    if ($existingAnswer) {
                        DB::table('question_answers')->where('id', $existingAnswer->id)->update($answerPayload);
                        $updatedAnswers++;
                    } else {
                        $answerPayload['created_at'] = now();
                        DB::table('question_answers')->insert($answerPayload);
                        $createdAnswers++;
                    }
                }
            }

            $counts = $this->counts();
            $this->info('Seed operation complete.');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Created customers', $createdCustomers],
                    ['Created booking links', $createdLinks],
                    ['Created manifests', $createdManifests],
                    ['Created answers', $createdAnswers],
                    ['Updated answers', $updatedAnswers],
                    ['Persisted booking links', $counts['links']],
                    ['Persisted manifests', $counts['manifests']],
                    ['Persisted answers', $counts['answers']],
                ]
            );

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete. Changes rolled back.');
                return self::SUCCESS;
            }

            DB::commit();
            $this->info('Changes committed.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function targetBookingItems(int $teamId, string $date, array $refs)
    {
        return DB::table('booking_items as bi')
            ->join('bookings as b', 'b.id', '=', 'bi.booking_id')
            ->leftJoin('booking_inventories as binv', 'binv.booking_item_id', '=', 'bi.id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'binv.inventory_time_slot_id')
            ->whereIn('b.reference', array_values($refs))
            ->where('bi.team_id', $teamId)
            ->whereDate('binv.date', $date)
            ->whereNull('bi.deleted_at')
            ->whereNull('b.deleted_at')
            ->selectRaw(
                'CASE b.reference WHEN ? THEN 1 WHEN ? THEN 2 END as target_no',
                [$refs[1], $refs[2]]
            )
            ->addSelect([
                'b.reference',
                'b.id as booking_id',
                'bi.id as booking_item_id',
                'bi.team_id',
                'binv.date',
                'its.start_time',
            ])
            ->orderBy('target_no')
            ->get();
    }

    private function questionIds(): array
    {
        return DB::table('questions')
            ->whereIn('slug', ['full_name', 'email_address', 'marketing_opt_in', 'birthday_month', 'birthday_day'])
            ->pluck('id', 'slug')
            ->toArray();
    }

    private function fixtures(): array
    {
        return [
            ['target_no' => 1, 'position_no' => 1, 'full_name' => 'Report Test Optin Alpha', 'email_address' => 'report.alpha@example.test', 'marketing_opt_in' => '1', 'birthday_month' => '3', 'birthday_day' => '12', 'manifest_type' => 'adult'],
            ['target_no' => 1, 'position_no' => 2, 'full_name' => 'Report Test No Optin Beta', 'email_address' => 'report.beta@example.test', 'marketing_opt_in' => '2', 'birthday_month' => '4', 'birthday_day' => '8', 'manifest_type' => 'adult'],
            ['target_no' => 1, 'position_no' => 3, 'full_name' => 'Report Test Missing Email Gamma', 'email_address' => null, 'marketing_opt_in' => '1', 'birthday_month' => '5', 'birthday_day' => '18', 'manifest_type' => 'adult'],
            ['target_no' => 1, 'position_no' => 4, 'full_name' => 'Report Test Missing Birthday Delta', 'email_address' => 'report.delta@example.test', 'marketing_opt_in' => '1', 'birthday_month' => null, 'birthday_day' => null, 'manifest_type' => 'adult'],
            ['target_no' => 1, 'position_no' => 5, 'full_name' => 'Report Test Optin Echo', 'email_address' => 'report.echo@example.test', 'marketing_opt_in' => '1', 'birthday_month' => '11', 'birthday_day' => '27', 'manifest_type' => 'adult'],
            ['target_no' => 2, 'position_no' => 1, 'full_name' => 'Report Test Child Foxtrot', 'email_address' => 'report.foxtrot@example.test', 'marketing_opt_in' => '1', 'birthday_month' => '1', 'birthday_day' => '5', 'manifest_type' => 'child'],
            ['target_no' => 2, 'position_no' => 2, 'full_name' => 'Report Test Child Golf', 'email_address' => 'report.golf@example.test', 'marketing_opt_in' => '2', 'birthday_month' => '2', 'birthday_day' => '9', 'manifest_type' => 'child'],
            ['target_no' => 2, 'position_no' => 3, 'full_name' => 'Report Test Child Hotel', 'email_address' => 'report.hotel@example.test', 'marketing_opt_in' => '1', 'birthday_month' => '7', 'birthday_day' => '21', 'manifest_type' => 'child'],
            ['target_no' => 2, 'position_no' => 4, 'full_name' => 'Report Test Child India', 'email_address' => 'report.india@example.test', 'marketing_opt_in' => '1', 'birthday_month' => '9', 'birthday_day' => '30', 'manifest_type' => 'child'],
        ];
    }

    private function counts(): array
    {
        return [
            'links' => DB::table('booking_items_customer')
                ->join('customers', 'customers.id', '=', 'booking_items_customer.customer_id')
                ->where('customers.full_name', 'like', 'Report Test%')
                ->count(),
            'manifests' => DB::table('customer_manifests')
                ->join('customers', 'customers.id', '=', 'customer_manifests.customer_id')
                ->where('customers.full_name', 'like', 'Report Test%')
                ->whereNull('customer_manifests.deleted_at')
                ->count(),
            'answers' => DB::table('question_answers')
                ->join('customers', 'customers.id', '=', 'question_answers.customer_id')
                ->where('customers.full_name', 'like', 'Report Test%')
                ->whereNull('question_answers.deleted_at')
                ->count(),
        ];
    }
}
