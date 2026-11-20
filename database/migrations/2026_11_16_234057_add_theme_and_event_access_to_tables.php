<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Apparence propre à chaque compte
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('clair');
        });

        // Les accès existants gardent l'accès à tous les événements
        Schema::table('organization_user', function (Blueprint $table) {
            $table->boolean('all_events')->default(true);
        });

        // Événements affectés à un collaborateur limité
        Schema::create('event_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'event_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_access');

        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn('all_events');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};