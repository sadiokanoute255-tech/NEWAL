<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('template_id')->nullable()->constrained('invitation_templates')->nullOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();

            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type');                       // mariage, bapteme, conference, gala, autre...
            $t->text('description')->nullable();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at')->nullable();
            $t->string('timezone')->default('Africa/Dakar');

            $t->string('venue_name')->nullable();
            $t->string('address')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('map_url')->nullable();
            $t->string('cover_path')->nullable();

            $t->string('dress_code')->nullable();
            $t->text('instructions')->nullable();
            $t->string('contact_name')->nullable();
            $t->string('contact_phone')->nullable();
            $t->string('contact_email')->nullable();
            $t->string('website')->nullable();
            $t->json('social_links')->nullable();

            $t->enum('visibility', ['prive', 'sur_invitation', 'public_inscription'])->default('sur_invitation');
            $t->enum('status', ['brouillon', 'publie', 'termine', 'archive'])->default('brouillon');

            // Paramétrage RSVP (§17) : {ask_party_size, ask_companion, ask_diet, ask_special_needs, ask_comment, deadline}
            $t->json('rsvp_settings')->nullable();
            // Personnalisation de l'invitation (§11) : couleurs, textes, message perso, logo
            $t->json('invitation_design')->nullable();

            $t->timestamps();
            $t->softDeletes();
            $t->index(['organization_id', 'status']);
        });

        Schema::create('event_program_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->time('time');
            $t->string('title');
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('event_tables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedSmallInteger('capacity')->default(10);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('event_tables');
        Schema::dropIfExists('event_program_items');
        Schema::dropIfExists('events');
    }
};
