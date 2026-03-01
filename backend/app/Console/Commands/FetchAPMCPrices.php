<?php

namespace App\Console\Commands;

use App\Models\APMCPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

// ============================================================
// APMC PRICE FETCH CRON JOB
//
// This command runs every day at midnight automatically.
// It fetches fresh crop prices from the government API
// (data.gov.in) or falls back to mock data for testing.
//
// HOW TO REGISTER THIS CRON:
// In app/Console/Kernel.php add:
//   $schedule->command('apmc:fetch-prices')->daily();
//
// HOW TO RUN MANUALLY:
//   php artisan apmc:fetch-prices
// ============================================================
class FetchAPMCPrices extends Command
{
    /**
     * The command name (used in artisan)
     */
    protected $signature = 'apmc:fetch-prices {--demo : Use demo data instead of real API}';

    protected $description = 'Fetch latest APMC market prices and store in database';

    public function handle()
    {
        $this->info('🌾 Starting APMC price fetch...');

        if ($this->option('demo') || !env('APMC_API_KEY')) {
            $this->insertDemoData();
            return;
        }

        $this->fetchFromAPI();
    }

    /**
     * Fetch from data.gov.in Agmarknet API
     * API Docs: https://data.gov.in/resource/9ef84268-d588-465a-a308-a864a43d0070
     */
    private function fetchFromAPI(): void
    {
        $today  = Carbon::today()->toDateString();
        $offset = 0;
        $limit  = 100;
        $total  = null;
        $count  = 0;

        do {
            $response = Http::get(env('APMC_API_URL'), [
                'api-key'    => env('APMC_API_KEY'),
                'format'     => 'json',
                'offset'     => $offset,
                'limit'      => $limit,
                'filters[Arrival_Date]' => $today,
            ]);

            if (!$response->successful()) {
                $this->error('API call failed: ' . $response->status());
                break;
            }

            $data = $response->json();

            if (!$total) {
                $total = $data['total'] ?? 0;
                $this->info("Total records available: {$total}");
            }

            foreach ($data['records'] ?? [] as $record) {
                APMCPrice::updateOrCreate(
                    [
                        'crop_name'   => $record['Commodity'],
                        'district'    => $record['District'],
                        'market_name' => $record['Market'],
                        'price_date'  => $today,
                    ],
                    [
                        'state'       => $record['State'],
                        'min_price'   => (float) str_replace(',', '', $record['Min_x0020_Price'] ?? 0),
                        'max_price'   => (float) str_replace(',', '', $record['Max_x0020_Price'] ?? 0),
                        'modal_price' => (float) str_replace(',', '', $record['Modal_x0020_Price'] ?? 0),
                    ]
                );
                $count++;
            }

            $offset += $limit;
        } while ($offset < $total);

        $this->info("✅ Fetched and stored {$count} records for {$today}");
    }

    /**
     * Insert demo/mock data when real API is unavailable
     * Run with: php artisan apmc:fetch-prices --demo
     */
    private function insertDemoData(): void
    {
        $today = Carbon::today()->toDateString();
        $crops = ['Wheat', 'Rice', 'Tomato', 'Onion', 'Potato', 'Cotton', 'Soybean'];
        $markets = [
            ['Maharashtra', 'Pune',    'Pune APMC'],
            ['Maharashtra', 'Nashik',  'Nashik APMC'],
            ['Punjab',      'Ludhiana','Ludhiana Grain Market'],
            ['UP',          'Agra',    'Agra Mandi'],
        ];

        $count = 0;
        foreach ($crops as $crop) {
            foreach ($markets as [$state, $district, $market]) {
                $base = rand(800, 4500);
                APMCPrice::updateOrCreate(
                    ['crop_name'=>$crop,'district'=>$district,'market_name'=>$market,'price_date'=>$today],
                    [
                        'state'       => $state,
                        'min_price'   => $base - rand(100,300),
                        'max_price'   => $base + rand(100,400),
                        'modal_price' => $base,
                    ]
                );
                $count++;
            }
        }
        $this->info("✅ Inserted {$count} demo price records for {$today}");
    }
}
