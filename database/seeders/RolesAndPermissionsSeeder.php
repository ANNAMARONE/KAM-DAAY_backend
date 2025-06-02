<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $roleAdmin = Role::create(['name' => 'admin']);
        $rolevendeuse = Role::create(['name' => 'vendeuse']);
       

    // Permissions pour les signalements
Permission::create(['name' => 'ajouter client']);
Permission::create(['name' => 'voir client']);
Permission::create(['name' => 'modifier client']);
Permission::create(['name' => 'supprimer client']);
Permission::create(['name' => 'ajouter vente']);
Permission::create(['name' => 'voir ventes']);
Permission::create(['name' => 'supprimer vente']);
Permission::create(['name'=>'voir feedback']);
Permission::create(['name'=>'exporter données']);
Permission::create(['name'=>'gérer utilisateurs']);
Permission::create(['name'=>'voir statistiques']);
Permission::create(['name'=>'gestion profile']);
      
$rolevendeuse->givePermissionTo([
    'ajouter client',
    'voir client',
    'modifier client',
    'ajouter vente',
    'voir ventes',
    'supprimer vente',
    'gestion profile',
    'voir feedback',
    'exporter données',
]);

$roleAdmin->givePermissionTo(Permission::all()); 
    }
    
}