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

declare(strict_types=1);

namespace croacworks\essentials\modules\textrank;

use croacworks\essentials\modules\textrank\Tool\Graph;
use croacworks\essentials\modules\textrank\Tool\Parser;
use croacworks\essentials\modules\textrank\Tool\Score;
use croacworks\essentials\modules\textrank\Tool\StopWords\StopWordsAbstract;
use croacworks\essentials\modules\textrank\Tool\Summarize;

/**
 * Class TextRankFacade
 *
 * This Facade class is capable to find the keywords in a raw text, weigh them
 * and retrieve the most important sentences from the whole text. It is an
 * implementation of the TextRank algorithm.
 *
 * <code>
 * $stopWords = new English();
 *
 * $textRank = new TextRankFacade();
 * $textRank->setStopWords($stopWords);
 *
 * $sentences = $textRank->summarizeTextFreely(
 * $rawText,
 * 5,
 * 2,
 * Summarize::GET_ALL_IMPORTANT
 * );
 * </code>
 *
 * @package croacworks\essentials\modules\textrank
 */
class TextRankFacade
{
    /**
     * Stop Words
     *
     * Stop Words to ignore because of dummy words. These words will not be Key
     * Words. A, like, no yes, one, two, I, you for example.
     *
     * @see \croacworks\essentials\modules\textrank\Tool\StopWords\English
     *
     * @var StopWordsAbstract
     */
    protected $stopWords;

    /**
     * Set Stop Words.
     *
     * @param StopWordsAbstract $stopWords Stop Words to ignore because of
     * dummy words.
     */
    public function setStopWords(StopWordsAbstract $stopWords)
    {
        $this->stopWords = $stopWords;
    }

    /**
     * Only Keywords
     *
     * It retrieves the possible keywords with their scores from a text.
     *
     * @param string $rawText A single raw text.
     *
     * @return array Array from Keywords. Key is the parsed word, value is the
     * word score.
     */
    public function getOnlyKeyWords(string $rawText): array
    {
        $parser = new Parser();
        $parser->setMinimumWordLength(3);
        $parser->setRawText($rawText);

        if ($this->stopWords) {
            $parser->setStopWords($this->stopWords);
        }

        $text = $parser->parse();
        if (is_array($text)) {
            shuffle($text);
        }
        $graph = new Graph();
        $graph->createGraph($text);

        $score = new Score();

        // 1. Calcula scores para palavras singulares (já normalizadas e ordenadas)
        $results = $score->calculate($graph, $text); 
        
        // --- INÍCIO DA NOVA LÓGICA: EXTRAÇÃO DE KEYWORDS COMPOSTAS (BI-GRAMS) ---
        
        $wordMatrix = $text->getWordMatrix();
        $phraseScores = [];
        $phraseLength = 2; // Foco em bi-grams
        
        foreach ($wordMatrix as $sentenceIdx => $words) {
            $count = count($words);
            
            // Itera sobre as palavras para encontrar bi-grams
            for ($i = 0; $i < $count - $phraseLength + 1; $i++) {
                $phraseWords = array_slice($words, $i, $phraseLength);
                $phrase = implode(' ', $phraseWords);
                
                $totalScore = 0.0;
                $scoredWordCount = 0;
                $validPhrase = true;
                
                foreach ($phraseWords as $word) {
                    if (isset($results[$word])) {
                        $totalScore += $results[$word];
                        $scoredWordCount++;
                    } else {
                        // Se alguma palavra não foi pontuada pelo TextRank (stop word, muito curta), ignora o bi-gram
                        $validPhrase = false;
                        break; 
                    }
                }
                
                if ($validPhrase && $scoredWordCount === $phraseLength) {
                    // Pontua a frase com a média dos scores normalizados de suas palavras constituintes.
                    $phraseScore = ($totalScore / $scoredWordCount) * 1.0; 
                    
                    // Armazena a maior pontuação encontrada para esta frase
                    if (!isset($phraseScores[$phrase]) || $phraseScore > $phraseScores[$phrase]) {
                        $phraseScores[$phrase] = $phraseScore;
                    }
                }
            }
        }
        
        // 2. Mescla os scores de palavras singulares com os scores de frases
        $mergedResults = array_merge($results, $phraseScores);
        
        // 3. Re-ordena (do maior score para o menor) para garantir que o filtro de duplicatas pegue o melhor termo
        arsort($mergedResults);
        
        $results = $mergedResults;
        
        // --- FIM DA NOVA LÓGICA ---

        $normalized = [];
        foreach ($results as $word => $value) {
            if($word === null && !is_string($word) )
                continue;

            $stem = preg_replace('/(s|es|os|as|mente|ções|ção|mento|mentos|dade|dades)$/u', '', mb_strtolower($word, 'UTF-8'));
            $skip = false;
            foreach ($normalized as $n => $v) {
                // Checa se o stem atual é uma forma flexionada de um termo já aceito
                if (str_starts_with($stem, mb_strtolower($n, 'UTF-8'))) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $normalized[$word] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Highlighted Texts
     *
     * It finds the most important sentences from a text by the most important
     * keywords and these keywords also found by automatically. It retrieves
     * the most important sentences what are 20 percent of the full text.
     *
     * @param string $rawText A single raw text.
     *
     * @return array An array from sentences.
     */
    public function getHighlights(string $rawText): array
    {
        $parser = new Parser();
        $parser->setMinimumWordLength(3);
        $parser->setRawText($rawText);

        if ($this->stopWords) {
            $parser->setStopWords($this->stopWords);
        }

        $text = $parser->parse();
        $maximumSentences = (int) (count($text->getSentences()) * 0.2);

        $graph = new Graph();
        $graph->createGraph($text);

        $score = new Score();
        $scores = $score->calculate($graph, $text);

        $summarize = new Summarize();

        return $summarize->getSummarize(
            $scores,
            $graph,
            $text,
            12,
            $maximumSentences,
            Summarize::GET_ALL_IMPORTANT
        );
    }

    /**
     * Compounds a Summarized Text
     *
     * It finds the three most important sentences from a text by the most
     * important keywords and these keywords also found by automatically. It
     * retrieves these important sentences.
     *
     * @param string $rawText A single raw text.
     *
     * @return array An array from sentences.
     */
    public function summarizeTextCompound(string $rawText): array
    {
        $parser = new Parser();
        $parser->setMinimumWordLength(3);
        $parser->setRawText($rawText);

        if ($this->stopWords) {
            $parser->setStopWords($this->stopWords);
        }

        $text = $parser->parse();

        $graph = new Graph();
        $graph->createGraph($text);

        $score = new Score();
        $scores = $score->calculate($graph, $text);

        $summarize = new Summarize();

        return $summarize->getSummarize(
            $scores,
            $graph,
            $text,
            10,
            3,
            Summarize::GET_ALL_IMPORTANT
        );
    }

    /**
     * Summarized Text
     *
     * It finds the most important sentence from a text by the most important
     * keywords and these keywords also found by automatically. It retrieves
     * the most important sentence and its following sentences.
     *
     * @param string $rawText A single raw text.
     *
     * @return array An array from sentences.
     */
    public function summarizeTextBasic(string $rawText): array
    {
        $parser = new Parser();
        $parser->setMinimumWordLength(3);
        $parser->setRawText($rawText);

        if ($this->stopWords) {
            $parser->setStopWords($this->stopWords);
        }

        $text = $parser->parse();

        $graph = new Graph();
        $graph->createGraph($text);

        $score = new Score();
        $scores = $score->calculate($graph, $text);

        $summarize = new Summarize();

        return $summarize->getSummarize(
            $scores,
            $graph,
            $text,
            10,
            3,
            Summarize::GET_FIRST_IMPORTANT_AND_FOLLOWINGS
        );
    }

    /**
     * Freely Summarized Text.
     *
     * It retrieves the most important sentences from a text by the most important
     * keywords and these keywords also found by automatically.
     *
     * @param string $rawText           A single raw text.
     * @param int    $analyzedKeyWords  Maximum number of the most important
     * Key Words to analyze the text.
     * @param int    $expectedSentences How many sentence should be retrieved.
     * @param int    $summarizeType     Highlights from the text or a part of
     * the text.
     *
     * @return array An array from sentences.
     */
    public function summarizeTextFreely(
        string $rawText,
        int $analyzedKeyWords,
        int $expectedSentences,
        int $summarizeType
    ): array {
        $parser = new Parser();
        $parser->setMinimumWordLength(3);
        $parser->setRawText($rawText);

        if ($this->stopWords) {
            $parser->setStopWords($this->stopWords);
        }

        $text = $parser->parse();

        $graph = new Graph();
        $graph->createGraph($text);

        $score = new Score();
        $scores = $score->calculate($graph, $text);

        $summarize = new Summarize();

        return $summarize->getSummarize(
            $scores,
            $graph,
            $text,
            $analyzedKeyWords,
            $expectedSentences,
            $summarizeType
        );
    }
}