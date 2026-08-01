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
        Schema::table('posts', function (Blueprint $table) {
            // ✅ Индексы для внешних ключей (ускоряют JOIN и фильтрацию)
            $table->index('category_id', 'posts_category_id_index');
            $table->index('group_id', 'posts_group_id_index');
            $table->index('conference_id', 'posts_conference_id_index');

            // ✅ Индекс для сортировки по дате (новые сверху)
            $table->index('created_at', 'posts_created_at_index');

            // ✅ Составные индексы для частых фильтров
            $table->index(['category_id', 'group_id'], 'posts_category_group_index');
            $table->index(['category_id', 'conference_id'], 'posts_category_conference_index');

            // ✅ Полнотекстовый индекс для поиска по заголовку
            $table->fullText('title', 'posts_title_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Удаляем индексы при откате
            $table->dropIndex('posts_category_id_index');
            $table->dropIndex('posts_group_id_index');
            $table->dropIndex('posts_conference_id_index');
            $table->dropIndex('posts_created_at_index');
            $table->dropIndex('posts_category_group_index');
            $table->dropIndex('posts_category_conference_index');
            $table->dropFullText('posts_title_fulltext');
        });
    }
};
