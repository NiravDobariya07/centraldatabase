<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Dhananjay Choksi',
                'email' => 'dhananjay.choksi@bytestechnolab.com',
            ],
            [
                'name' => 'Nivedita Nadgonde',
                'email' => 'nivedita.nadgonde@bytestechnolab.com',
            ],
            [
                'name' => 'Mitul Patel',
                'email' => 'mitul@bytestechnolab.com',
            ],
            [
                'name' => 'Chintan Fadia',
                'email' => 'chintan.fadia@bytestechnolab.com',
            ],
            [
                'name' => 'Christina MacKinney',
                'email' => 'christina@kinetiqmedia.com',
            ],
            [
                'name' => 'Austin Walker',
                'email' => 'austin@kinetiqmedia.com',
            ],
            [
                'name' => 'Khushal Dayala',
                'email' => 'khushal.dayala@bytestechnolab.com',
            ],
        ];

        foreach ($users as $user) {
            $existingUser = User::where('email', $user['email'])->exists();

            if (empty($existingUser)) {
                User::create([
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make('TraDB@1234'), // Make sure to hash the password
                    'two_fa_enabled' => true,
                    'two_fa_method' => 'email', // Default 2FA method is email
                ]);

                echo "User created: {$user['name']} ({$user['email']})\n";
            } else {
                echo "User already exists: {$user['email']}, skipping...\n";
            }
        }

        echo "User seeding completed successfully!\n";
    }
}
