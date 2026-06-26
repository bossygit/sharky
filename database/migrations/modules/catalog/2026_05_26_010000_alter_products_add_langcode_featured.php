<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compléments sur la table products :
 *  - DEFAULT gen_random_uuid() sur uuid (évite de devoir générer en PHP)
 *  - langcode CHAR(2) NOT NULL DEFAULT 'fr' (préparation i18n future)
 *  - featured BOOLEAN (Drupal field_featured_product)
 *  - title sur media (Drupal stocke un title séparé de alt)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN uuid SET DEFAULT gen_random_uuid()');
        }

        if (! Schema::hasColumn('products', 'langcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('langcode', 8)->default('fr')->after('slug');
            });
        }

        if (! Schema::hasColumn('products', 'featured')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('featured')->default(false)->after('status');
                $table->index('featured');
            });
        }

        if (! Schema::hasColumn('media', 'title')) {
            Schema::table('media', function (Blueprint $table) {
                $table->string('title')->nullable()->after('alt_text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'featured')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['featured']);
                $table->dropColumn(['langcode', 'featured']);
            });
        }

        if (Schema::hasColumn('media', 'title')) {
            Schema::table('media', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN uuid DROP DEFAULT');
        }
    }
};
