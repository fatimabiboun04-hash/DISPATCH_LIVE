<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nom'       => 'Administrateur',
            'email'     => 'admin@dispatch.com',
            'password'  => Hash::make('admin123'),
            'role'      => 'Admin',
            'is_active' => true,
        ]);
    }
}
