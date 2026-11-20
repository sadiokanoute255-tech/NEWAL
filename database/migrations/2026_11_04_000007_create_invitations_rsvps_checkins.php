<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invitations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guest_id')->unique()->constrained()->cascadeOnDelete(); // 1 invitation par invite
            $t->string('code', 12)->unique();          // code court saisissable
            $t->string('token', 64)->unique();         // token du lien public
            $t->string('qr_token', 64)->unique();      // token du QR
            $t->enum('status', [
                'creee', 'envoyee', 'distribuee', 'ouverte',
                'confirmee', 'refusee', 'sans_reponse', 'arrivee',
            ])->default('creee');
            $t->string('last_channel')->nullable();    // whatsapp, sms, email, lien
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('first_opened_at')->nullable();
            $t->unsignedInteger('open_count')->default(0);
            $t->timestamp('last_reminder_at')->nullable();
            $t->timestamps();
            $t->index(['event_id', 'status']);
        });

        Schema::create('rsvps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invitation_id')->unique()->constrained()->cascadeOnDelete();
            $t->enum('response', ['accepte', 'refuse']);
            $t->unsignedSmallInteger('party_size')->default(1);
            $t->string('companion_name')->nullable();
            $t->string('dietary_preferences')->nullable();
            $t->string('special_needs')->nullable();
            $t->text('comment')->nullable();
            $t->timestamp('responded_at');
            $t->timestamps();
        });

        // Un check-in par passage (validation, annulation, historique)
        Schema::create('check_ins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedSmallInteger('persons_entered');
            $t->timestamp('checked_in_at');
            $t->timestamp('cancelled_at')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('cancel_reason')->nullable();
            $t->timestamps();
            $t->index(['event_id', 'checked_in_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('rsvps');
        Schema::dropIfExists('invitations');
    }
};