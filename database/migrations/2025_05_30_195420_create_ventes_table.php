<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
              $table->id();
              $table->foreignId('user_id')
              ->constrained('users')
              ->onDelete('cascade');

        $table->foreignId('client_id')
              ->nullable()
              ->constrained('clients')
              ->onDelete('set null');

        $table->string('produit'); 
        $table->integer('quantite');
        $table->decimal('prix_unitaire', 10, 2);
        $table->decimal('montant_total', 10, 2);
        $table->date('date_vente')->default(now());
        $table->timestamps();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};