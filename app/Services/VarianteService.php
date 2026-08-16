<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\VarianteProduit;
use Illuminate\Support\Str;

class VarianteService
{
    /**
     * Génère toutes les combinaisons de variantes pour un produit.
     */
    public function genererCombinaisons(Produit $produit, array $attributs): array
    {
        if (empty($attributs)) {
            return [];
        }

        $nomsAttributs = array_keys($attributs);
        $valeursParAttribut = array_values($attributs);

        $combinaisons = [[]];

        foreach ($valeursParAttribut as $indices => $valeurs) {
            $produitCourant = [];

            foreach ($combinaisons as $combinaisonExistante) {
                foreach ($valeurs as $valeur) {
                    $produitCourant[] = array_merge($combinaisonExistante, [$nomsAttributs[$indices] => $valeur]);
                }
            }

            $combinaisons = $produitCourant;
        }

        return array_values($combinaisons);
    }

    /**
     * Synchronise les variantes d'un produit.
     */
    public function synchroniserVariantes(Produit $produit, array $nouveauxAttributs): array
    {
        $combinaisons = $this->genererCombinaisons($produit, $nouveauxAttributs);

        $existantes = [];
        foreach ($produit->variantes()->get() as $variante) {
            $key = json_encode($variante->combinaison, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $existantes[$key] = $variante;
        }

        $resultat = [
            'creees' => 0,
            'maj' => 0,
            'supprimees' => 0,
            'combinaisons' => [],
        ];

        foreach ($combinaisons as $combinaison) {
            $cle = json_encode($combinaison, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $resultat['combinaisons'][] = $combinaison;

            if (!isset($existantes[$cle])) {
                $codeSku = $this->buildCodeSku($produit, $combinaison);
                VarianteProduit::create([
                    'produit_id' => $produit->id,
                    'code_sku' => $codeSku,
                    'combinaison' => $combinaison,
                    'quantite_stock' => 0,
                    'seuil_alerte' => 5,
                ]);
                $resultat['creees']++;
            } else {
                $resultat['maj']++;
            }
        }

        $cleCombinaisons = array_map(function ($combinaison) {
            return json_encode($combinaison, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }, $combinaisons);

        foreach ($existantes as $cle => $variante) {
            if (!in_array($cle, $cleCombinaisons, true)) {
                $variante->delete();
                $resultat['supprimees']++;
            }
        }

        $produit->has_variantes = !empty($combinaisons);
        $produit->save();

        return $resultat;
    }

    /**
     * Récupère le stock d'une combinaison spécifique.
     */
    public function getStockByCombinaison(Produit $produit, array $combinaison): int
    {
        $variante = $produit->variantes()->where('combinaison', json_encode($combinaison))->first();

        return (int) ($variante?->quantite_stock ?? 0);
    }

    /**
     * Met à jour le stock d'une variante.
     */
    public function mettreAJourStock(Produit $produit, array $combinaison, int $quantite, string $operation): bool
    {
        $variante = $produit->variantes()->where('combinaison', json_encode($combinaison))->first();

        if (!$variante) {
            return false;
        }

        $stockActuel = (int) $variante->quantite_stock;
        $nouveauStock = $operation === 'increment' ? $stockActuel + $quantite : $stockActuel - $quantite;
        $variante->quantite_stock = max(0, $nouveauStock);

        return $variante->save();
    }

    /**
     * Récupère les groupes visuels pour les photos.
     */
    public function getGroupesVisuels(Produit $produit): array
    {
        $templatesVisuels = $produit->categorie
            ?->attributsTemplates()
            ->where('est_visuel', true)
            ->with('attribut')
            ->get()
            ?? collect();

        if ($templatesVisuels->isEmpty()) {
            return [];
        }

        $nomsVisuels = $templatesVisuels->map(fn ($template) => $template->attribut?->nom)->filter()->all();

        $groupes = [];
        foreach ($produit->variantes()->get() as $variante) {
            $combinaison = $variante->combinaison ?? [];
            $groupe = [];

            foreach ($nomsVisuels as $nomAttribut) {
                if (array_key_exists($nomAttribut, $combinaison)) {
                    $groupe[$nomAttribut] = $combinaison[$nomAttribut];
                }
            }

            if (!empty($groupe)) {
                $groupes[] = $groupe;
            }
        }

        return $groupes;
    }

    protected function buildCodeSku(Produit $produit, array $combinaison): string
    {
        $base = $produit->code ?? 'PRO';

        $suffixes = [];
        foreach ($combinaison as $nomAttribut => $valeur) {
            $suffixes[] = Str::slug((string) $valeur);
        }

        return strtoupper($base . '-' . implode('-', $suffixes));
    }
}
