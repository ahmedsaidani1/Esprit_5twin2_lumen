<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lecture et écriture de fichiers CSV.
 * Export : séparateur ";" et BOM UTF-8, pour qu'Excel (en français) ouvre le fichier correctement.
 * Import : séparateur ";" ou "," détecté, en-têtes insensibles à la casse et aux espaces.
 */
class CsvService
{
    public const MAX_LIGNES = 5000;

    /**
     * @return list<array<string, string>> une ligne = [en-tête => valeur] ; "_ligne" donne le n° de ligne du fichier
     */
    public function lire(UploadedFile $fichier): array
    {
        $contenu = file_get_contents($fichier->getRealPath());
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu); // BOM
        $lignes = preg_split('/\r\n|\n|\r/', trim($contenu));

        $separateur = substr_count($lignes[0] ?? '', ';') >= substr_count($lignes[0] ?? '', ',') ? ';' : ',';
        $entetes = array_map(fn ($h) => mb_strtolower(trim($h)), str_getcsv(array_shift($lignes) ?? '', $separateur));

        $resultat = [];
        foreach (array_slice($lignes, 0, self::MAX_LIGNES) as $i => $ligne) {
            if (trim($ligne) === '') {
                continue;
            }
            $valeurs = array_map('trim', str_getcsv($ligne, $separateur));
            $assoc = [];
            foreach ($entetes as $j => $h) {
                $assoc[$h] = $valeurs[$j] ?? '';
            }
            $resultat[] = $assoc + ['_ligne' => $i + 2];
        }

        return $resultat;
    }

    /** Convertit "12,5" (virgule décimale française) en "12.5". */
    public function nombre(string $valeur): string
    {
        return str_replace([' ', ','], ['', '.'], $valeur);
    }

    /** @param iterable<array> $lignes */
    public function exporter(string $nomFichier, array $entetes, iterable $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, $entetes, ';');
            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }
            fclose($sortie);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
