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
            $records[] = [
                'first_name'      => $faker->firstName,
                'last_name'       => $faker->lastName,
                'email'           => $faker->unique()->safeEmail,
                'phone'           => $faker->numerify('##########'),
                'alt_phone'       => $faker->optional()->numerify('##########'),
                'address'         => $faker->address,
                'city'            => $faker->city,
                'state'           => strtoupper($faker->lexify('??')), // Random 2 letters
                'postal'          => $faker->postcode,
                'country'         => 'US',
                'ip'              => $faker->ipv4,
                'date_subscribed' => Carbon::now()->subDays(rand(0, 365)),
                'gender'          => $faker->randomElement(['male', 'female']),
                'offer_url'       => $faker->url,
                'dob'             => $faker->date('Y-m-d', '2000-01-01'),

                'tax_debt_amount' => $faker->randomFloat(2, 1000, 50000),
                'cc_debt_amount'  => $faker->randomFloat(2, 500, 20000),
                'type_of_debt'    => $faker->randomElement(['Tax', 'Credit Card', 'Student Loan', 'Mortgage']),
                'home_owner'      => $faker->randomElement(['Yes', 'No']),

                'import_date'     => Carbon::now(),
                'jornaya_id'      => Str::uuid(),
                'phone_type'      => $faker->randomElement(['Mobile', 'Landline']),
                'trusted_form_id' => Str::uuid(),
                'opt_in'          => $faker->randomElement(['Yes', 'No']),

                'sub_id_1'        => Str::random(5),
                'sub_id_2'        => Str::random(5),
                'sub_id_3'        => Str::random(5),
                'sub_id_4'        => Str::random(5),
                'sub_id_5'        => Str::random(5),
                'aff_id_1'        => 'aff' . rand(1, 10),
                'aff_id_2'        => 'aff' . rand(11, 20),

                'lead_id'         => Str::random(10),
                'ef_id'           => Str::random(10),
                'ck_id'           => Str::random(10),

                'page_url'        => $faker->url,
                'extra_fields'    => json_encode(['custom_field' => $faker->word]),

                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ];
        }

        DB::table('leads')->insert($records);
    }
}
