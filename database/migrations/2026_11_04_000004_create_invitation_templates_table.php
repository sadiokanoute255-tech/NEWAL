<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invitation_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->enum('category', ['familial', 'professionnel', 'institutionnel']);
            $t->string('event_type');                 // mariage, bapteme, gala, conference...
            $t->string('preview_path')->nullable();
            $t->json('design');                       // couleurs, polices, structure des blocs
            $t->boolean('is_active')->default(true);
            $t->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete(); // modèle perso entreprise
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('invitation_templates'); }
};
