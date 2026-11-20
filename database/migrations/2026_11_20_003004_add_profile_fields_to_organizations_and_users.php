<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('subtype', 30)->nullable();   // institution, ong, association, autre
            $table->string('sector', 100)->nullable();   // secteur d'activité (entreprise)
            $table->string('legal_id', 60)->nullable();  // NINEA, RCCM, récépissé
            $table->string('website', 255)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title', 100)->nullable(); // fonction du responsable
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('job_title');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['subtype', 'sector', 'legal_id', 'website']);
        });
    }
};