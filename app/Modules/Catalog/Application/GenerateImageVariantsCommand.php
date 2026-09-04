<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Models\Media;
use Illuminate\Console\Command;

class GenerateImageVariantsCommand extends Command
{
    protected $signature = 'catalog:generate-image-variants {--limit=0 : Max media rows (0 = all)}';

    protected $description = 'Génère les dérivés 400w/800w pour le srcset catalogue';

    public function handle(ImageVariantService $variants): int
    {
        $query = Media::query()->where('disk', 'public')->where('is_available', true);
        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $count = 0;
        $query->orderBy('id')->each(function (Media $media) use ($variants, &$count): void {
            $variants->generate($media);
            $count++;
        });

        $this->info("Variantes générées pour {$count} média(s).");

        return self::SUCCESS;
    }
}
