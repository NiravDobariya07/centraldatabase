<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\ConsumerInsiteContact;

class ConsumerInsiteContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // First, create categories
        $categoryNames = [
            'Credit Repair',
            'Debt Consolidation',
            'Mortgage',
            'Auto Loan',
            'Personal Loan',
            'Credit Card',
            'Student Loan',
            'Home Improvement',
            'Insurance',
            'Tax Relief'
        ];

        $categories = [];
        foreach ($categoryNames as $categoryName) {
            $categories[] = Category::create([
                'category_name' => $categoryName,
                'created_at' => Carbon::now(),
            ]);
        }

        // Create consumer insite contacts
        $contacts = [];
        $contactCount = 20; // Create 20 test contacts

        for ($i = 0; $i < $contactCount; $i++) {
            $email = $faker->unique()->safeEmail;

            $contact = ConsumerInsiteContact::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $email,
                'age' => (string) $faker->numberBetween(18, 80),
                'credit_score' => (string) $faker->numberBetween(300, 850),
                'location_name' => $faker->city . ', ' . $faker->stateAbbr,
                'is_email_duplicate' => $faker->boolean(20), // 20% chance of being true
                'eoapi_success' => $faker->boolean(70), // 70% chance of being true
                'result' => $faker->optional()->randomElement(['success', 'failed', 'pending']),
                'resultid' => $faker->optional()->numberBetween(1000, 9999),
                'response' => $faker->optional()->text(200),
                'is_ongage' => $faker->boolean(50), // 50% chance of being true
                'ongage_response' => $faker->optional()->text(150),
                'deleted_at' => 0, // Not deleted
                'created_at' => Carbon::now()->subDays(rand(0, 365)),
                'updated_at' => Carbon::now(),
            ]);

            $contacts[] = $contact;

            // Attach 1-3 random categories to each contact
            $randomCategories = $faker->randomElements($categories, $faker->numberBetween(1, 3));
            foreach ($randomCategories as $category) {
                DB::table('category_consumer_insite_contact')->insert([
                    'consumer_insite_contact_id' => $contact->id,
                    'category_id' => $category->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        $this->command->info("Created " . count($categories) . " categories");
        $this->command->info("Created " . count($contacts) . " consumer insite contacts");
        $this->command->info("Linked contacts to categories via pivot table");
    }
}
