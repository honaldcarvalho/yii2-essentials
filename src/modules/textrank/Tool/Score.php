<?php

/**
 * CroacWorks Essentials — TextRank module
 *
 * Based on the original PHP-Science-TextRank (MIT License)
 * https://github.com/DavidBelicza/PHP-Science-TextRank
 *
 * Copyright (c) 2016 David Belicza
 * Modified & maintained by CroacWorks (Honald Carvalho)
 *
 * Licensed under the MIT License.
 */

namespace croacworks\essentials\modules\textrank\Tool;

use croacworks\essentials\modules\textrank\Tool\Text;

class Score
{

    /**
     * Normaliza a entrada ($text) em array de tokens.
     * Aceita Tool\Text, array ou string.
     *
     * @param mixed $text
     * @return string[]
     */
    private static function toTokenArray(Text|array|string $text): array
    {
        if ($text instanceof Text) {
            // Tente métodos do seu fork; fallback para propriedades públicas
            if (method_exists($text, 'getWords')) {
                return (array) $text->getWords();
            }
            if (method_exists($text, 'getArray')) {
                return (array) $text->getArray();
            }
            if (property_exists($text, 'words')) {
                return (array) $text->words;
            }
        }

        if (is_array($text)) {
            return $text;
        }

        // Fallback: tokeniza string
        $str = trim((string) $text);
        if ($str === '') return [];
        return preg_split('/\s+/u', $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * TextRank word scoring with semantic boosts and adaptive cache.
     */
    public function calculates(Graph $graph, array $text): array
    {

        if ($text instanceof \croacworks\essentials\modules\textrank\Tool\Text) {
            $text = $text->getSentences();
        }

        if (!is_array($text)) {
            throw new \InvalidArgumentException('Expected array or Text instance');
        }

        $tokens = self::toTokenArray($text);

        $scores = [];
        $vertices = $graph->getVertices();

        // Caminho do cache adaptativo
        $cacheFile = \Yii::getAlias('@runtime/keywords_boost.json');
        $cache = [];

        if (file_exists($cacheFile)) {
            $json = file_get_contents($cacheFile);
            $cache = json_decode($json, true) ?: [];
        }

        // Combina o texto em minúsculas (para análise semântica)
        $joined = mb_strtolower(implode(' ', $tokens), 'UTF-8');

        foreach ($vertices as $word) {
            $w = trim($word);
            if ($w === '') continue;

            // Peso base
            $weight = 1.0;

            // 🔹 Nome próprio (inicial maiúscula dentro do texto)
            if (preg_match('/\b[A-ZÁÀÂÃÉÊÍÓÔÕÚÇ][a-záàâãéêíóôõúç]+/', $word)) {
                $weight *= 2.0;
            }

            // 🔹 Sigla (tudo em maiúsculas)
            elseif (mb_strtoupper($word, 'UTF-8') === $word && mb_strlen($word, 'UTF-8') > 1) {
                $weight *= 1.8;
            }

            // 🔹 Termos técnicos ou de tecnologia
            elseif (preg_match('/\b(linux|fedora|ubuntu|windows|microsoft|apple|google|intel|amd|gnome|kde|php|yii|javascript|node|docker|api)\b/i', $word)) {
                $weight *= 1.6;
            }

            // 🔹 Palavras longas (provável substantivo)
            elseif (mb_strlen($word, 'UTF-8') > 8) {
                $weight *= 1.3;
            }

            // 🔹 Frequência histórica (cache adaptativo)
            $lower = mb_strtolower($word, 'UTF-8');
            if (isset($cache[$lower])) {
                $weight *= min(3.0, 1.0 + log(1 + $cache[$lower]) / 2); // aumenta de forma logarítmica
            }

            $scores[$word] = $weight;
        }

        // 🔸 Cálculo base do TextRank (PageRank-like)
        $ranks = [];
        $d = 0.85; // fator de amortecimento
        $min_diff = 0.0001;
        $steps = 100;

        // Inicializa pontuações
        foreach ($vertices as $v) {
            $ranks[$v] = $scores[$v] ?? 1.0;
        }

        for ($i = 0; $i < $steps; $i++) {
            $prev_ranks = $ranks;
            $diff = 0.0;

            foreach ($vertices as $v) {
                $neighbors = $graph->getEdges($v);
                $rank_sum = 0.0;
                foreach ($neighbors as $n) {
                    $n_neighbors = $graph->getEdges($n);
                    $rank_sum += ($ranks[$n] ?? 0) / (count($n_neighbors) ?: 1);
                }
                $ranks[$v] = (1 - $d) + $d * $rank_sum * ($scores[$v] ?? 1.0);
                $diff += abs($ranks[$v] - ($prev_ranks[$v] ?? 0));
            }

            if ($diff < $min_diff) break;
        }

        // 🔸 Normaliza pesos finais (0–1)
        $maxRank = max($ranks);
        if ($maxRank > 0) {
            foreach ($ranks as $k => $v) {
                $ranks[$k] = $v / $maxRank;
            }
        }

        // 🔸 Atualiza o cache adaptativo
        foreach ($ranks as $word => $val) {
            $lower = mb_strtolower($word, 'UTF-8');
            $cache[$lower] = ($cache[$lower] ?? 0) + 1;
        }

        // Salva cache
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0775, true);
        }
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $ranks;
    }
}
