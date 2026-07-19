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
        Schema::table('bible_courses', function (Blueprint $table) {
            $table->text('what_you_will_learn')->nullable()->after('description');
            $table->text('skills')->nullable()->after('what_you_will_learn');
            $table->string('price')->default('Бесплатно')->after('skills');
            $table->json('statuses')->nullable()->after('price'); // статусы обучения
            $table->string('certificate_text')->nullable()->after('statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_courses', function (Blueprint $table) {
            $table->dropColumn(['what_you_will_learn', 'skills', 'price', 'statuses', 'certificate_text']);
        });
    }
};
