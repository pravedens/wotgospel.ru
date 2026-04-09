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
            $table->string('avatar')->nullable()->after('email'); // путь к аватару
            $table->string('phone')->nullable()->after('avatar');
            $table->string('city')->nullable()->after('phone');
            $table->string('church_name')->nullable()->after('city');
            $table->text('about')->nullable()->after('church_name');
            $table->date('birth_date')->nullable()->after('about');
            $table->string('last_name')->nullable()->after('name'); // фамилия
            $table->string('middle_name')->nullable()->after('last_name'); // отчество
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'phone',
                'city',
                'church_name',
                'about',
                'birth_date',
                'last_name',
                'middle_name',
            ]);
        });
    }
};
