<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // ou User::find(1);

        if (!$user) {
            $this->command->error('Aucun utilisateur trouvé. Créez d\'abord un utilisateur.');
            return;
        }

        for ($i = 1; $i <= 10; $i++) {
            $telephone = "77000000$i";

            $client = Client::firstOrCreate(
                ['telephone' => $telephone], // condition d'unicité
                [ // données à remplir si non existant
                    'prenom' => "ClientPrenom$i",
                    'nom' => "Nom$i",
                    'adresse' => "Adresse $i",
                    'statut' => $i % 2 === 0 ? 'actif' : 'inactif',
                    'type' => $i % 2 === 0 ? 'restaurateur' : 'particulier',
                    'user_id' => $user->id,
                ]
            );

            // Créer une vente uniquement si elle n'existe pas déjà
            if ($client->ventes()->count() === 0) {
                $vente = $client->ventes()->create([
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                ]);

                for ($j = 1; $j <= rand(1, 3); $j++) {
                    // Créer ou récupérer un produit
                    $produit = \App\Models\Produit::firstOrCreate(
                        ['nom' => "Produit $j"],
                        ['image' => "image_produit_$j.jpg"]
                    );

                    // Calcul des quantités et prix
                    $quantite = rand(1, 10);
                    $prixUnitaire = rand(500, 5000);
                    $montantTotal = $quantite * $prixUnitaire;

                    // Attacher au pivot avec données
                    $vente->produits()->attach($produit->id, [
                        'quantite' => $quantite,
                        'prix_unitaire' => $prixUnitaire,
                        'montant_total' => $montantTotal,
                        'date_vente' => $vente->created_at->toDateString(),
                    ]);
                }
            }
        }
        $this->command->info('10 clients générés (sans doublons de téléphone).');
    }
}