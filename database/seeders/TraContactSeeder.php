<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TraContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $records = [];
        $contactCount = 50; // Create 50 test contacts

        for ($i = 0; $i < $contactCount; $i++) {
            $email = $faker->unique()->safeEmail;
            $emailDomain = substr(strrchr($email, "@"), 1); // Extract domain from email

            $records[] = [
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'lead_time_stamp' => Carbon::now()->subDays(rand(0, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                'email' => $email,
                'email_domain' => $emailDomain,
                'phone' => $faker->numerify('##########'),
                'state' => $faker->stateAbbr,
                'zip_code' => $faker->postcode,
                'page' => $faker->optional()->url,
                'optin_domain' => $faker->optional()->domainName,
                'universal_leadid' => Str::uuid(),
                'cake_id' => 'CAKE' . Str::random(10),
                'ckm_campaign_id' => 'CKM' . $faker->numerify('####'),
                'ckm_key' => Str::random(32),
                'tax_debt' => $faker->numerify('########'), // Required field, always provide value (max 12 chars)
                'aff_id' => 'AFF' . rand(1, 100),
                'sub_id' => 'SUB' . Str::random(8),
                'ip_address' => $faker->ipv4,
                'offer_id' => $faker->optional()->numerify('####'),
                'response' => $faker->optional()->text(500),
                'created_at' => Carbon::now()->subDays(rand(0, 365)),
                'updated_at' => Carbon::now(),
            ];
        }

        // Insert in batches for better performance
        $chunks = array_chunk($records, 10);
        foreach ($chunks as $chunk) {
            DB::table('tra_contacts')->insert($chunk);
        }

        $this->command->info("Created " . count($records) . " TRA contacts");
    }
}
