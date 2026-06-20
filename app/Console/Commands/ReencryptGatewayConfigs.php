<?php

namespace App\Console\Commands;

use App\Models\GatewayConfig;
use Illuminate\Console\Command;

class ReencryptGatewayConfigs extends Command
{
    protected $signature = 'gateway-configs:reencrypt {--dry-run : Report rows without saving changes}';

    protected $description = 'Re-encrypt payment gateway config rows using PAYMENT_GATEWAY_CONFIG_KEY.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        GatewayConfig::withTrashed()
            ->orderBy('id')
            ->each(function (GatewayConfig $gatewayConfig) use ($dryRun, &$updated, &$skipped) {
                $rawConfig = $gatewayConfig->getRawOriginal('config');
                $config = $gatewayConfig->config;

                if (!empty($rawConfig) && empty($config)) {
                    $this->warn("Skipped gateway config {$gatewayConfig->id}: existing config could not be decrypted.");
                    $skipped++;
                    return;
                }

                if ($dryRun) {
                    $this->line("Would re-encrypt gateway config {$gatewayConfig->id}.");
                    return;
                }

                $gatewayConfig->config = $config;
                $gatewayConfig->save();
                $updated++;
            });

        $this->info($dryRun
            ? 'Dry run complete.'
            : "Re-encrypted {$updated} gateway config rows.");

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} rows that could not be decrypted with the current keys.");
        }

        return self::SUCCESS;
    }
}
