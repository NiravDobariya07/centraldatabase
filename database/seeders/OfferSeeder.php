<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate the table first
        DB::table('offers')->truncate();

        $faker = Faker::create();

        $records = [];
        $offerCount = 20; // Create 20 test offers

        // Sample domain names for variety (with https://www. prefix)
        $domains = [
            'https://www.example.com',
            'https://www.testdomain.com',
            'https://www.sample.org',
            'https://www.demo.net',
            'https://www.mydomain.io',
            'https://www.sitedomain.com',
            'https://www.webdomain.org',
            'https://www.newdomain.net',
            'https://www.testdomain.io',
            'https://www.exampledomain.com',
            'https://www.nirav.com',
            'https://www.empiretaxrelief.com',
            'https://www.capitaltaxrelief.com',
            'https://www.seniortaxdefense.com',
            'https://www.premier-taxrelief.com',
            'https://www.topmoneyprogram.com',
            'https://www.fresh-tax-help.net'
        ];

        // Sample domain abbreviations
        $abbreviations = [
            'EC', 'TD', 'SO', 'DN', 'MD', 'SD', 'WD', 'ND', 'TI', 'ED',
            'NIR', 'EPTR', 'CTR', 'STD', 'PTR', 'TMP', 'FTH'
        ];

        for ($i = 0; $i < $offerCount; $i++) {
            // Generate a base64-like token (similar to the image format)
            $tokenBytes = random_bytes(32);
            $authToken = base64_encode($tokenBytes);
            // Remove padding and limit to 100 chars as per database
            $authToken = substr(rtrim($authToken, '='), 0, 100);

            // Get domain and abbreviation (use same index for consistency)
            $domainIndex = $i % count($domains);
            $domainName = $domains[$domainIndex];
            $offerName = $abbreviations[$domainIndex] ?? strtoupper(Str::random(3) . rand(100, 999));

            $records[] = [
                'offer_name' => $offerName,
                'domain_abt' => $domainName,
                'auth_token' => $authToken,
                'created_at' => Carbon::now()->subDays(rand(0, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                'updated_at' => Carbon::now(),
            ];
        }

        // Insert in batches for better performance
        $chunks = array_chunk($records, 10);
        foreach ($chunks as $chunk) {
            DB::table('offers')->insert($chunk);
        }

        $this->command->info("Created " . count($records) . " offers");
    }
}
