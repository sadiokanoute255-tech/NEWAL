<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->string('first_name')->after('id')->nullable();
            $t->string('phone')->nullable()->unique();
            $t->enum('account_type', ['particulier', 'entreprise', 'organisation', 'agence'])->default('particulier');
            $t->boolean('is_admin')->default(false);
            $t->timestamp('phone_verified_at')->nullable();
            $t->string('locale', 5)->default('fr');
            $t->boolean('is_suspended')->default(false);
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['first_name','phone','account_type','is_admin','phone_verified_at','locale','is_suspended']);
        });
    }
};
