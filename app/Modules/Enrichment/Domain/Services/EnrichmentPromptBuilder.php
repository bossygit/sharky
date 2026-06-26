<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Services;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Domain\Contracts\WebSearchResult;

class EnrichmentPromptBuilder
{
    /** @param  list<WebSearchResult>  $sources */
    public function build(Product $product, array $sources): string
    {
        $brand = $product->brand?->name ?? 'Non renseignée';
        $price = $product->formattedPrice() ?? 'Non renseigné';
        $description = trim(strip_tags((string) ($product->description_html ?: $product->short_description_html ?: '')));

        $sourcesBlock = '';
        foreach ($sources as $i => $source) {
            $sourcesBlock .= sprintf(
                "[%d] %s\n%s\n---\n",
                $i,
                $source->url,
                $source->excerpt ?: $source->title,
            );
        }

        return <<<PROMPT
PRODUIT : {$product->title}
MARQUE  : {$brand}
PRIX    : {$price}
DESCRIPTION_ACTUELLE: {$description}

SOURCES COLLECTÉES (extraits) :
{$sourcesBlock}

SCHÉMA DE SORTIE (JSON uniquement) :
{
  "description_html": string|null,
  "features": [string],
  "faqs": [{"q": string, "a": string}],
  "meta_title": string,
  "meta_description": string,
  "evidence": [{"field": string, "source_index": number, "confidence": number}]
}

Règles : français, Congo (XAF), pas d'invention de specs numériques, evidence obligatoire par champ renseigné.
PROMPT;
    }

    public function systemPrompt(): string
    {
        return <<<'SYSTEM'
Tu es un assistant qui structure des informations produit pour un site e-commerce
en français au Congo (devise XAF). Tu réponds UNIQUEMENT en JSON conforme au schéma
fourni. Chaque champ enrichi doit s'appuyer sur au moins une source citée dans
"evidence". Si tu n'as pas d'information fiable, mets null. N'invente JAMAIS de
caractéristique numérique, de poids, de dimension ou d'ingrédient.
SYSTEM;
    }
}
