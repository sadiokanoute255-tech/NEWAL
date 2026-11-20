<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Plans modifiables depuis l'admin (§32)
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');                       // Essentiel, Standard, Premium, Prestige, Entreprise
            $t->unsignedInteger('max_guests');
            $t->unsignedInteger('price');             // en XOF (pas de décimales)
            $t->string('currency', 3)->default('XOF');
            $t->enum('billing_period', ['event', 'monthly', 'yearly'])->default('event');
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->enum('discount_type', ['percent', 'fixed']);
            $t->unsignedInteger('discount_value');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->unsignedInteger('max_uses')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained();
            $t->foreignId('event_id')->nullable();    // abonnement lié à un événement (offre ponctuelle) ou null (agence)
            $t->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider');                   // wave, orange_money, card, ...
            $t->string('provider_reference')->nullable()->unique();
            $t->unsignedInteger('amount');
            $t->string('currency', 3)->default('XOF');
            $t->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $t->json('payload')->nullable();          // réponse brute de l'agrégateur
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('plans');
    }
};
