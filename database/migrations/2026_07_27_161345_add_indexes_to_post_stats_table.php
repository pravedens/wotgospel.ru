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
        Schema::table('post_stats', function (Blueprint $table) {
            if (! Schema::hasIndex('post_stats', 'post_stats_post_id_index')) {
                $table->index('post_id', 'post_stats_post_id_index');
            }

            if (! Schema::hasIndex('post_stats', 'post_stats_fingerprint_index')) {
                $table->index('fingerprint', 'post_stats_fingerprint_index');
            }

            // Составной индекс
            if (! Schema::hasIndex('post_stats', 'post_stats_post_id_fingerprint_index')) {
                $table->index(['post_id', 'fingerprint'], 'post_stats_post_id_fingerprint_index');
            }

            if (! Schema::hasIndex('post_stats', 'post_stats_viewed_at_index')) {
                $table->index('viewed_at', 'post_stats_viewed_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_stats', function (Blueprint $table) {
            $table->dropIndex('post_stats_post_id_index');
            $table->dropIndex('post_stats_fingerprint_index');
            $table->dropIndex('post_stats_post_id_fingerprint_index');
            $table->dropIndex('post_stats_viewed_at_index');
        });
    }
};
