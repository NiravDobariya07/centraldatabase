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
                'name' => 'Nirav',
                'email' => 'nirav.dobariya@bytestechnolab.com',
                'password' => 'Nirav@123',
            ],
            [
                'name' => 'Chintan',
                'email' => 'chintan.fadia@bytestechnolab.com',
                'password' => 'qLXjWFa8iMxabay',
            ],
            [
                'name' => 'Christina',
                'email' => 'christina@forwardleapmarketing.com',
                'password' => 'weG6LwxqgFFnYMscqVda',
            ],
            [
                'name' => 'Ashly',
                'email' => 'ashley@forwardleapmarketing.com',
                'password' => 'bKDB92w3L9quffx',
            ],
            [
                'name' => 'Sushana',
                'email' => 'susanna@forwardleapmarketing.com',
                'password' => 'qruNQ5ULzFA6ffC',
            ],
            [
                'name' => 'Nivedita',
                'email' => 'nivedita.nadgonde@bytestechnolab.com',
                'password' => 'CQG9YVjfVgNlqgm',
            ],
            [
                'name' => 'Urvish',
                'email' => 'urvish.patel@bytestechnolab.com',
                'password' => 'rdUJ8B7X1A20ET5U',
            ],
            [
                'name' => 'Nilesh',
                'email' => 'nilesh.kanzariya@bytestechnolab.com',
                'password' => '4X2mcikqhyUDGvZR',
            ],
            [
                'name' => 'Datta',
                'email' => 'datta.pandya@bytestechnolab.com',
                'password' => '2S8cS11nRMPWwirB',
            ],
            [
                'name' => 'Darshak',
                'email' => 'darshak.mehta@bytestechnolab.com',
                'password' => '6lWkFKCf6VU2sF98',
            ],
            [
                'name' => 'Apexa',
                'email' => 'apexa.dave@magnetoitsolutions.com',
                'password' => 'bdU3B8W9k158SMq8',
            ],
        ];

        foreach ($users as $user) {
            $existingUser = User::where('email', $user['email'])->first();

            if (!$existingUser) {
                User::create([
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'two_fa_enabled' => true,
                    'two_fa_method' => 'email', // Default 2FA method is email
                ]);

                echo "User created: {$user['name']} ({$user['email']})\n";
            } else {
                // Update existing user with new name and password
                $existingUser->update([
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                ]);

                echo "User updated: {$user['name']} ({$user['email']})\n";
            }
        }

        echo "User seeding completed successfully!\n";
    }
}
