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
        Schema::table('bible_enrollment_requests', function (Blueprint $table) {
            $table->boolean('agreement_accepted')->default(false)->after('learning_expectations');
            $table->timestamp('agreement_accepted_at')->nullable()->after('agreement_accepted');
            $table->string('agreement_ip')->nullable()->after('agreement_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_enrollment_requests', function (Blueprint $table) {
            //
        });
    }
};
