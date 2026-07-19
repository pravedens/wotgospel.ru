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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_enrollment_rejected_email')->default(true);
            $table->boolean('notify_enrollment_rejected_webpush')->default(true);
            $table->boolean('notify_certificate_issued_email')->default(true);
            $table->boolean('notify_certificate_issued_webpush')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_enrollment_rejected_email',
                'notify_enrollment_rejected_webpush',
                'notify_certificate_issued_email',
                'notify_certificate_issued_webpush',
            ]);
        });
    }
};
