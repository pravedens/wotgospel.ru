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
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('recipient_role')->nullable()->after('user_agent');
            $table->string('recipient_email')->nullable()->after('recipient_role');
            $table->text('recipient_emails_list')->nullable()->after('recipient_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['recipient_role', 'recipient_email', 'recipient_emails_list']);
        });
    }
};
