<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class LectorPdf
{
    // Páginas numeradas desde 1, no desde 0.
    public function extraerPaginas(string $rutaAbsoluta): array
    {
        $documento = (new Parser())->parseFile($rutaAbsoluta);
        $paginas = [];

        foreach ($documento->getPages() as $indice => $pagina) {
            $paginas[$indice + 1] = $this->limpiar($pagina->getText());
        }

        return $paginas;
    }

    private function limpiar(string $texto): string
    {
        $texto = preg_replace('/[ \t]+/', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\n{3,}/', "\n\n", $texto) ?? $texto;

        return trim($texto);
    }
}
