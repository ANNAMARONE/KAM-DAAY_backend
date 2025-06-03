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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('prenom');
            $table->string('nom');
            $table->string('telephone')->nullable()->unique();
            $table->enum('type',['restaurateur','particulier','boutique'])->nullable();
            $table->string('adresse')->nullable(); 
            $table->enum('statut', ['actif', 'inactif'])->default('actif'); 
            $table->foreignId('user_id')
            ->constrained('users')
            ->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};