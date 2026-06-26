<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['creme', ['crème', 'cream']],
            ['bebe', ['bébé', 'baby', 'enfant']],
            ['chargeur', ['adaptateur', 'cable', 'câble']],
        ];

        foreach ($defaults as [$term, $synonyms]) {
            DB::table('search_synonyms')->updateOrInsert(
                ['term' => $term],
                ['synonyms' => json_encode($synonyms), 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        DB::table('search_synonyms')->whereIn('term', ['creme', 'bebe', 'chargeur'])->delete();
    }
};
