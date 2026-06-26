<?php

declare(strict_types=1);

namespace App\Modules\Demand\Application;

use Illuminate\Console\Command;

class ProjectDemandClustersCommand extends Command
{
    protected $signature = 'demand:project-clusters';

    protected $description = 'Agrège les recherches sans résultat et re-clusterise la demande (n-gram + Jaccard)';

    public function handle(DemandProjectionService $projection): int
    {
        $result = $projection->project();

        $this->info(sprintf(
            'Projection terminée : %d signal(s) synchronisé(s), %d cluster(s) actif(s).',
            $result['signals_synced'],
            $result['clusters_updated'],
        ));

        return self::SUCCESS;
    }
}
