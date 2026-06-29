<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\TypeAlert;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedReportTestAlerts extends Command
{
    protected $signature = 'alerts:seed-report-test
        {--team=3 : Team id}
        {--dry-run : Run checks and roll back}';

    // php artisan alerts:seed-report-test --dry-run
    // php artisan alerts:seed-report-test

    protected $description = 'Create contextual test alerts for Report Test seeded manifest customers.';

    public function handle(): int
    {
        $teamId = (int) $this->option('team');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Seeding contextual report test alerts...');
        $this->line("Team: {$teamId}");
        $this->line('Database: ' . DB::connection()->getDatabaseName());

        DB::beginTransaction();

        try {
            $targets = DB::table('customer_manifests as cm')
                ->join('customers as c', 'c.id', '=', 'cm.customer_id')
                ->join('booking_items as bi', 'bi.id', '=', 'cm.booking_item_id')
                ->join('bookings as b', 'b.id', '=', 'bi.booking_id')
                ->where('cm.team_id', $teamId)
                ->whereNull('cm.deleted_at')
                ->where('c.full_name', 'like', 'Report Test%')
                ->select([
                    'cm.id as manifest_id',
                    'cm.booking_item_id',
                    'c.id as customer_id',
                    'c.full_name',
                    'b.id as booking_id',
                    'b.reference',
                    'b.team_id',
                ])
                ->orderBy('c.id')
                ->get();

            if ($targets->isEmpty()) {
                $this->error('No Report Test manifests found. Run reports:seed-marketing-optin-test first.');
                DB::rollBack();
                return self::FAILURE;
            }

            $users = User::where('team_id', $teamId)
                ->whereHas('roles', fn($query) => $query->whereIn('name', ['Manager', 'Consultant']))
                ->get();

            if ($users->isEmpty()) {
                $this->warn('No Manager/Consultant users found for this team; alerts will be created but not attached to users.');
            }

            $alertDefinitions = [
                'Report Test Optin Alpha' => ['Medical Condition', 5],
                'Report Test No Optin Beta' => ['Terms Not Agreed', 8],
                'Report Test Missing Email Gamma' => ['Not Fit', 7],
                'Report Test Child Foxtrot' => ['Check-in Complete', 9],
                'Report Test Child Hotel' => ['Medical Condition', 5],
            ];
            $flagAnswers = [
                'Report Test Optin Alpha' => [
                    'medical_id' => '1',
                    'medical_detail' => 'Asthma - carries inhaler',
                    'is_fit' => '1',
                    'is_agree' => '1',
                ],
                'Report Test No Optin Beta' => [
                    'medical_id' => '2',
                    'is_fit' => '1',
                    'is_agree' => '2',
                ],
                'Report Test Missing Email Gamma' => [
                    'medical_id' => '2',
                    'is_fit' => '2',
                    'is_agree' => '1',
                ],
                'Report Test Child Foxtrot' => [
                    'medical_id' => '2',
                    'is_fit' => '1',
                    'is_agree' => '1',
                ],
                'Report Test Child Hotel' => [
                    'medical_id' => '1',
                    'medical_detail' => 'Nut allergy',
                    'is_fit' => '1',
                    'is_agree' => '1',
                ],
            ];
            $questionIds = DB::table('questions')
                ->whereIn('slug', ['medical_id', 'medical_detail', 'is_fit', 'is_agree', 'pregnant'])
                ->pluck('id', 'slug')
                ->toArray();

            $created = 0;
            $attached = 0;
            $answersWritten = 0;
            $rows = [];

            foreach ($targets as $target) {
                if (!isset($alertDefinitions[$target->full_name])) {
                    continue;
                }

                [$reason, $typeAlertId] = $alertDefinitions[$target->full_name];
                $typeAlertId = TypeAlert::find($typeAlertId)?->id ?? TypeAlert::firstOrCreate(['name' => $reason])->id;
                $text = "{$reason}: {$target->full_name} / {$target->reference}";
                $link = route('admin.bookings.add', $target->booking_id, false);

                $alert = Alert::firstOrCreate(
                    [
                        'alert_text' => $text,
                        'team_id' => $teamId,
                    ],
                    [
                        'alert_link' => $link,
                        'type_alert_id' => $typeAlertId,
                        'booking_id' => $target->booking_id,
                        'booking_item_id' => $target->booking_item_id,
                        'customer_id' => $target->customer_id,
                        'customer_manifest_id' => $target->manifest_id,
                    ]
                );

                if ($alert->wasRecentlyCreated) {
                    $created++;
                }

                if (
                    $alert->alert_link !== $link ||
                    (int) $alert->type_alert_id !== (int) $typeAlertId ||
                    (int) $alert->booking_id !== (int) $target->booking_id ||
                    (int) $alert->booking_item_id !== (int) $target->booking_item_id ||
                    (int) $alert->customer_id !== (int) $target->customer_id ||
                    (int) $alert->customer_manifest_id !== (int) $target->manifest_id
                ) {
                    $alert->update([
                        'alert_link' => $link,
                        'type_alert_id' => $typeAlertId,
                        'booking_id' => $target->booking_id,
                        'booking_item_id' => $target->booking_item_id,
                        'customer_id' => $target->customer_id,
                        'customer_manifest_id' => $target->manifest_id,
                    ]);
                }

                foreach ($users as $user) {
                    $exists = DB::table('alert_user')
                        ->where('alert_id', $alert->id)
                        ->where('user_id', $user->id)
                        ->exists();

                    if (!$exists) {
                        $user->alerts()->attach($alert->id, [
                            'read' => false,
                            'complete' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $attached++;
                    }
                }

                foreach (($flagAnswers[$target->full_name] ?? []) as $slug => $answer) {
                    if (empty($questionIds[$slug])) {
                        $this->warn("Question slug {$slug} was not found; answer not seeded.");
                        continue;
                    }

                    $existingAnswer = DB::table('question_answers')
                        ->where('booking_item_id', $target->booking_item_id)
                        ->where('customer_id', $target->customer_id)
                        ->where('question_id', $questionIds[$slug])
                        ->first();

                    $payload = [
                        'answer' => $answer,
                        'question_id' => $questionIds[$slug],
                        'customer_id' => $target->customer_id,
                        'booking_item_id' => $target->booking_item_id,
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ];

                    if ($existingAnswer) {
                        DB::table('question_answers')->where('id', $existingAnswer->id)->update($payload);
                    } else {
                        $payload['created_at'] = now();
                        DB::table('question_answers')->insert($payload);
                    }

                    $answersWritten++;
                }

                $rows[] = [
                    'alert_id' => $alert->id,
                    'text' => $text,
                    'link' => $link,
                ];
            }

            $this->table(['alert_id', 'text', 'link'], $rows);
            $this->line("Created alerts: {$created}");
            $this->line("Attached user alerts: {$attached}");
            $this->line("Flag answers written: {$answersWritten}");

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
}
