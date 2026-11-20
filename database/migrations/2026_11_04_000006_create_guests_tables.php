<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Cercles d'invités (§9) : Famille, VIP, Collègues... personnalisables par événement
        Schema::create('guest_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('color', 9)->nullable();
            $t->timestamps();
            $t->unique(['event_id', 'name']);
        });

        Schema::create('guests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guest_group_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('event_table_id')->nullable()->constrained()->nullOnDelete();

            $t->string('first_name')->nullable();
            $t->string('last_name');                  // ou nom de famille : "Famille Ndiaye"
            $t->string('phone')->nullable();
            $t->string('whatsapp')->nullable();
            $t->string('email')->nullable();
            $t->string('organization')->nullable();
            $t->string('job_title')->nullable();
            $t->unsignedSmallInteger('allowed_seats')->default(1);   // §10 : 1, couple=2, famille max 4...
            $t->text('comment')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['event_id', 'last_name']);
            $t->index(['event_id', 'phone']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('guests');
        Schema::dropIfExists('guest_groups');
    }
};
