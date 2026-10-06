<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@novamart.test'],
            [
                'name' => 'Demo Shopper',
                'password' => 'password',
            ],
        );
    }
}
