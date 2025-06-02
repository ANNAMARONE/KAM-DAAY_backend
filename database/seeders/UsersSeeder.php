<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Admin
         $admin = User::create([
            'profile' => 'admin.jpg',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'telephone' => '771234567',
            'role' => 'admin',
            'localite' => 'Mbour',
            'statut' => 'actif',
            'domaine_activite' => 'halieutique',
        ]);
        $admin->assignRole('admin');

        // Vendeuse
        $vendeuse = User::create([
            'profile' => 'vendeuse.jpg',
            'username' => 'amy',
            'password' => Hash::make('vendeuse123'),
            'telephone' => '781234567',
            'role' => 'vendeuse',
            'localite' => 'Joal',
            'statut' => 'actif',
            'domaine_activite' => 'Agroalimentaire',
        ]);
        $vendeuse->assignRole('vendeuse');
    }
    
}