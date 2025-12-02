<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExtLeadContactSeeder extends Seeder
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
            $createdDate = Carbon::now()->subDays(rand(0, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $records[] = [
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $email,
                'phone' => $faker->numerify('##########'),
                'alt_phone' => $faker->optional(0.3)->numerify('##########'),
                'address' => $faker->optional(0.7)->streetAddress,
                'city' => $faker->optional(0.7)->city,
                'state' => $faker->optional(0.7)->stateAbbr,
                'postal' => $faker->optional(0.7)->postcode,
                'country' => $faker->optional(0.5)->country,
                'ip' => $faker->optional(0.8)->ipv4,
                'date_subscribed' => $faker->optional(0.6)->date('Y-m-d'),
                'gender' => $faker->optional(0.5)->randomElement(['M', 'F', 'Other']),
                'offer_url' => $faker->optional(0.6)->url,
                'dob' => $faker->optional(0.4)->date('Y-m-d'),
                'list_id' => $faker->optional(0.6)->numerify('LIST####'),
                'import_date' => $faker->optional(0.5)->date('Y-m-d'),
                'phone_type' => $faker->optional(0.4)->randomElement(['Mobile', 'Home', 'Work']),
                'tax_debt_amount' => $faker->optional(0.5)->numerify('#######'),
                'type_of_debt' => $faker->optional(0.4)->randomElement(['Tax', 'Credit Card', 'Medical', 'Student Loan']),
                'homeowner' => $faker->optional(0.5)->randomElement(['Yes', 'No']),
                'jornaya_id' => $faker->optional(0.6)->uuid(),
                'trusted_form_id' => $faker->optional(0.6)->uuid(),
                'opt_in' => $faker->optional(0.5)->randomElement(['Yes', 'No']),
                'subid1' => $faker->optional(0.6)->bothify('SUB?##??'),
                'subid2' => $faker->optional(0.5)->bothify('SUB?##??'),
                'subid3' => $faker->optional(0.4)->bothify('SUB?##??'),
                'subid4' => $faker->optional(0.3)->bothify('SUB?##??'),
                'subid5' => $faker->optional(0.2)->bothify('SUB?##??'),
                'aff_id_1' => $faker->optional(0.6)->bothify('AFF?##??'),
                'aff_id_2' => $faker->optional(0.5)->bothify('AFF?##??'),
                'lead_id' => $faker->optional(0.7)->bothify('LEAD#######'),
                'page_url' => $faker->optional(0.6)->url,
                'ef_id' => $faker->optional(0.4)->bothify('EF####'),
                'ck_id' => $faker->optional(0.4)->bothify('CK####'),
                'source' => $faker->optional(0.7)->randomElement(['Website', 'Facebook', 'Google', 'Email Campaign', 'Referral']),
                'affid' => $faker->optional(0.6)->bothify('AFF?##??'),
                'subid' => $faker->optional(0.6)->bothify('SUB?##??'),
                'result' => $faker->optional(0.6)->randomElement(['success', 'failed', 'pending', 'invalid']),
                'resultid' => $faker->optional(0.5)->numerify('#########'),
                'response' => $faker->optional(0.4)->text(500),
                'is_email_duplicate' => $faker->boolean(20), // 20% chance of being true
                'eoapi_success' => $faker->boolean(70), // 70% chance of being true
                'is_ongage' => $faker->boolean(50), // 50% chance of being true
                'ongage_response' => $faker->optional(0.4)->text(300),
                'ongage_at' => $faker->optional(0.3)->dateTimeBetween('-30 days', 'now'),
                'created_date' => $createdDate,
                'updated_date' => $faker->optional(0.3)->dateTimeBetween($createdDate, 'now'),
                'deleted_date' => null,
            ];
        }

        // Insert in batches for better performance
        $chunks = array_chunk($records, 10);
        foreach ($chunks as $chunk) {
            DB::table('ext_lead_contact')->insert($chunk);
        }

        $this->command->info("Created " . count($records) . " Ext Lead contacts");
    }
}
