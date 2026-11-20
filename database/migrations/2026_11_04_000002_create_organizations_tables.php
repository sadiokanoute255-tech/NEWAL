<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->enum('type', ['particulier', 'entreprise', 'organisation', 'agence'])->default('particulier');
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->string('logo_path')->nullable();
            $t->string('primary_color', 9)->nullable();
            $t->string('secondary_color', 9)->nullable();
            $t->string('subdomain')->nullable()->unique();
            $t->timestamps();
        });

        // Collaborateurs : un user peut appartenir à plusieurs organisations avec un rôle par organisation
        Schema::create('organization_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->enum('role', ['proprietaire', 'gestionnaire', 'communication', 'accueil', 'lecture_seule']);
            $t->timestamps();
            $t->unique(['organization_id', 'user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
