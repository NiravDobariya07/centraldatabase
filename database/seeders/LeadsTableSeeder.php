<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        $records = [];

        // Generate 15 leads dynamically
        for ($i = 0; $i < 15; $i++) {
            $email = $faker->unique()->safeEmail;
            $emailDomain = substr(strrchr($email, "@"), 1); // Extract domain from email

            $records[] = [
                'first_name'          => $faker->firstName,
                'last_name'           => $faker->lastName,
                'email'               => $email,
                'phone'               => $faker->numerify('##########'),
                'email_domain'        => $emailDomain,
                'optin_domain'        => $faker->optional()->domainName,
                'domain_abt'          => $faker->optional()->domainName,
                'aff_id'              => 'aff' . rand(1, 20),
                'sub_id'              => Str::random(10),
                'cake_leadid'         => Str::random(10),
                'result'              => $faker->optional()->randomElement(['success', 'failed', 'pending']),
                'resultid'            => $faker->optional()->numerify('#########'),
                'response'            => $faker->optional()->text(200),
                'journya'             => Str::uuid(),
                'trusted_form'        => Str::uuid(),
                'ip_address'          => $faker->ipv4,
                'esp'                 => $faker->optional()->randomElement(['Mailchimp', 'SendGrid', 'Constant Contact']),
                'offer_id'            => $faker->optional()->numerify('####'),
                'is_email_duplicate'  => $faker->boolean(20), // 20% chance of being true
                'eoapi_success'       => $faker->boolean(70), // 70% chance of being true
                'is_ongage'           => $faker->boolean(50), // 50% chance of being true
                'ongage_response'     => $faker->optional()->text(150),
                'ongage_at'           => $faker->optional()->dateTimeBetween('-30 days', 'now'),
                'created_at'          => Carbon::now()->subDays(rand(0, 365)),
                'updated_at'          => Carbon::now(),
            ];
        }

        DB::table('all_contacts')->insert($records);
    }
}
