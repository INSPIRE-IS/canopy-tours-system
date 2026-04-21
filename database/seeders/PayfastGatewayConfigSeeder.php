<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\GatewayConfig;
use Illuminate\Database\Seeder;

class PayfastGatewayConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payfastConfig = config('payfast');
        if (empty($payfastConfig['merchant_id']) || empty($payfastConfig['merchant_key'])) {
            return;
        }

        $gateway = Gateway::firstOrCreate(['name' => 'PayFast']);
        GatewayConfig::updateOrCreate(
            [
                'team_id' => null,
                'gateway_id' => $gateway->id,
            ],
            [
                'is_enabled' => true,
                'is_sandbox' => (bool) $payfastConfig['is_sandbox'],
                'config' => [
                    'merchant_id' => $payfastConfig['merchant_id'],
                    'merchant_key' => $payfastConfig['merchant_key'],
                    'passphrase' => $payfastConfig['passphrase'],
                    'sandbox_url' => $payfastConfig['sandbox_url'],
                    'live_url' => $payfastConfig['live_url'],
                ],
            ]
        );
    }
}
