<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Services;

class ShelfPositioningService
{
    private const MIN_BASE_HEIGHT = 0;

    /**
     * Distribui as prateleiras uniformemente dentro dos limites dos furos disponíveis
     *
     * @param  array  $holes  Array de posições Y dos furos disponíveis
     * @param  int  $shelfCount  Número de prateleiras desejadas
     * @return array Posições Y selecionadas para as prateleiras
     */
    public function distributeShelvesEvenly(array $holes, int $shelfCount): array
    {
        if ($shelfCount <= 0 || empty($holes)) {
            return [];
        }

        // Ordena os furos de baixo para cima e remove duplicatas
        $holes = array_unique($holes);
        sort($holes);

        // Encontra o primeiro furo válido acima da altura mínima da base
        $baseHoleIndex = 0;
        foreach ($holes as $index => $holePosition) {
            if ($holePosition >= self::MIN_BASE_HEIGHT) {
                $baseHoleIndex = $index;
                break;
            }
        }

        // Filtra os furos utilizáveis
        $usableHoles = array_slice($holes, $baseHoleIndex);

        if (empty($usableHoles)) {
            return [];
        }

        if ($shelfCount === 1) {
            // Uma única prateleira vai no meio dos furos disponíveis
            $middleIndex = floor(count($usableHoles) / 2);

            return [$usableHoles[$middleIndex]];
        }

        $positions = [];
        $lastIndex = count($usableHoles) - 1;

        // Primeira prateleira
        $positions[] = $usableHoles[0];

        if ($shelfCount > 2) {
            // Calcula o espaço disponível entre a primeira e a última posição
            $availableSpace = $lastIndex;
            $spacing = $availableSpace / ($shelfCount - 1);

            // Distribui as prateleiras intermediárias
            for ($i = 1; $i < $shelfCount - 1; $i++) {
                $targetIndex = (int) round($i * $spacing);
                // Garante que o índice está dentro dos limites
                $targetIndex = min($targetIndex, $lastIndex);
                $positions[] = $usableHoles[$targetIndex];
            }
        }

        // Última prateleira sempre no último furo disponível
        if ($shelfCount > 1) {
            $positions[] = $usableHoles[$lastIndex];
        }

        // Remove possíveis duplicatas e mantém a ordem
        return array_values(array_unique($positions));
    }

    /**
     * Verifica se as posições das prateleiras respeitam o espaçamento mínimo
     *
     * @param  array  $positions  Posições Y das prateleiras
     * @param  float  $minSpacing  Espaçamento mínimo necessário
     */
    public function validateSpacing(array $positions, float $minSpacing): bool
    {
        // sort($positions);
        for ($i = 1; $i < count($positions); $i++) {
            if (($positions[$i] - $positions[$i - 1]) < $minSpacing) {
                return false;
            }
        }

        return true;
    }
}
