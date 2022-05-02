<?php

/**
 * This file is part of MetaModels/attribute_levenshtein.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_levenshtein
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\ITranslated;
use Patchwork\Utf8;

/**
 * This class implements a general purpose search index for MetaModels to be searched with Levenshtein-Search algorithm.
 */
class LevenshteinIndexLookup
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * The list of valid attributes.
     *
     * @var list<IAttribute>
     */
    private array $attributeList;

    /**
     * The maximum allowed levenshtein distance.
     *
     * @var array<string, int>
     */
    private array $maxDistance;

    /**
     * Minimum length of words to be added to the index.
     *
     * @var int
     */
    private int $minLength;

    /**
     * Maximum length of words to be added to the index.
     *
     * @var int
     */
    private int $maxLength;

    /**
     * Create a new instance.
     *
     * @param Connection         $connection    The connection instance to use.
     * @param IAttribute[]       $attributeList The list of valid attributes.
     * @param string             $language      The language key.
     * @param string             $pattern       The pattern to search.
     * @param array<string, int> $maxDistance   The maximum allowed levenshtein distance.
     * @param int                $minLength     The minimum length of words.
     * @param int                $maxLength     The maximum length of words.
     *
     * @return string[]
     */
    public static function filter(
        Connection $connection,
        array $attributeList,
        string $language,
        string $pattern,
        array $maxDistance = [0 => 2],
        int $minLength = 3,
        int $maxLength = 20
    ): array {
        $instance = new static($connection, $attributeList, $maxDistance, $minLength, $maxLength);

        return $instance->search($language, $pattern);
    }

    /**
     * Create a new instance.
     *
     * @param Connection   $connection    The database connection.
     * @param IAttribute[] $attributeList The list of valid attributes.
     * @param int[]        $maxDistance   The maximum allowed levenshtein distance.
     * @param int          $minLength     The minimum length of words.
     * @param int          $maxLength     The maximum length of words.
     */
    public function __construct(
        Connection $connection,
        array $attributeList,
        array $maxDistance = [0 => 2],
        int $minLength = 3,
        int $maxLength = 20
    ) {
        $this->connection    = $connection;
        $this->attributeList = $attributeList;
        $this->maxDistance   = $maxDistance;
        $this->minLength     = $minLength;
        $this->maxLength     = $maxLength;
    }

    /**
     * Search for occurrences and return the ids of matching items.
     *
     * @param string $language The language key.
     * @param string $pattern  The pattern to search.
     *
     * @return string[]
     */
    public function search(string $language, string $pattern): array
    {
        return $this->searchInternal($language, new SearchStringParser($pattern));
    }

    /**
     * Compile a list of best matching suggestions of words for all items that have been returned by the search.
     *
     * @param string $language The language key.
     * @param string $pattern  The pattern to search.
     *
     * @return string[]
     */
    public function getSuggestions(string $language, string $pattern): array
    {
        // Chop off the last word as it is the beginning of a new word.
        $parser    = new SearchStringParser($pattern, true);
        $items     = $this->searchInternal($language, $parser);
        $procedure = [];
        $params    = [];
        $partial   = $parser->getPartial();

        if (\in_array($partial[0], ['+', '-', '""'])) {
            $partial = \substr($partial, 1);
        }

        if (!empty($items)) {
            $procedure[] = \sprintf(
                'tl_metamodel_levensthein.item IN (%1$s)',
                \implode(',', \array_fill(0, \count($items), '?'))
            );
            $params      = \array_merge($params, $items);
        }

        $attributeIds = [];
        foreach ($this->attributeList as $attribute) {
            $attributeIds[] = $attribute->get('id');
        }

        $procedure[] = '(tl_metamodel_levensthein_index.language=?)';
        $procedure[] = \sprintf(
            '(tl_metamodel_levensthein_index.attribute IN (%1$s))',
            \implode(',', \array_fill(0, \count($attributeIds), '?'))
        );
        $procedure[] = '(tl_metamodel_levensthein_index.word LIKE ?)';
        $params      = \array_merge(
            $params,
            [$language],
            $attributeIds,
            [$partial . '%'],
            $attributeIds
        );
        $query       = \sprintf(
            'SELECT DISTINCT tl_metamodel_levensthein_index.word ' .
            'FROM tl_metamodel_levensthein_index ' .
            'LEFT JOIN tl_metamodel_levensthein ON (tl_metamodel_levensthein.id=tl_metamodel_levensthein_index.pid)' .
            'WHERE ' . \implode(' AND ', $procedure) .
            'ORDER BY FIELD(tl_metamodel_levensthein_index.attribute,%1$s),tl_metamodel_levensthein_index.word',
            \implode(',', \array_fill(0, \count($attributeIds), '?'))
        );

        $result = $this->connection->prepare($query);
        $result->execute($params);

        return $result->fetchFirstColumn();
    }

    /**
     * Search using the given search parser.
     *
     * @param string             $language The language key.
     * @param SearchStringParser $parser   The parser to use.
     *
     * @return string[]|null
     */
    private function searchInternal(string $language, SearchStringParser $parser): ?array
    {
        $results = new ResultSet();

        $attributeIds = [];
        foreach ($this->attributeList as $attribute) {
            $attributeIds[] = $attribute->get('id');
        }

        $this->getLiteralMatches($language, $parser, $results);
        $this->getMustIds($attributeIds, $language, $parser, $results);
        $this->getMatchingKeywords($attributeIds, $language, $parser, $results);
        $this->getMustNotIds($attributeIds, $language, $parser, $results);

        if (!($parser->getLiterals() || $parser->getMust() || $parser->getKeywords())) {
            $metaModel = $this->attributeList[0]->getMetaModel();
            $ids       = $metaModel->getIdsFromFilter($metaModel->getEmptyFilter());
            foreach ($this->attributeList as $attribute) {
                $results->addResults('-all-', $attribute, $ids);
            }
        }
        $items = \array_filter($results->getCombinedResults($this->attributeList));

        if ($items) {
            return $items;
        }

        // Try via levenshtein now.
        $this->getLevenshteinCandidates($attributeIds, $parser, $results);

        return \array_filter($results->getCombinedResults($this->attributeList));
    }

    /**
     * Retrieve the items matching the literal search patterns.
     *
     * @param string             $language  The language key.
     * @param SearchStringParser $parser    The parser to use.
     * @param ResultSet          $resultSet The result set to add to.
     *
     * @return void
     */
    private function getLiteralMatches(string $language, SearchStringParser $parser, ResultSet $resultSet)
    {
        $literals = $parser->getLiterals();
        foreach ($literals as $literal) {
            foreach ($this->attributeList as $attribute) {
                if ($attribute instanceof ITranslated) {
                    $results = $attribute->searchForInLanguages('*' . $literal . '*', [$language]);
                } else {
                    $results = $attribute->searchFor('*' . $literal . '*');
                }

                if (null === $results) {
                    $metaModel = $this->attributeList[0]->getMetaModel();
                    $results   = $metaModel->getIdsFromFilter($metaModel->getEmptyFilter());
                }

                $resultSet->addResults(
                    '"' . $literal . '"',
                    $attribute,
                    $results ?: []
                );
            }
        }
    }

    /**
     * Find exact matches of chunks and return the parent ids.
     *
     * @param list<string>       $attributeIds The attributes to search on.
     * @param string             $language     The language key.
     * @param SearchStringParser $parser       The chunks to search for.
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getMatchingKeywords(
        array $attributeIds,
        string $language,
        SearchStringParser $parser,
        ResultSet $resultSet
    ): void {
        if ($parser->getKeywords() == []) {
            return;
        }

        foreach ($parser->getKeywords() as $word) {
            $searchWord = \str_replace(
                              ['*', '?'],
                              ['%', '_'],
                              \str_replace(
                                  ['%', '_'],
                                  ['\%', '\_'],
                                  $word
                              )
                          ) . '%';

            $parameters   = \array_merge([$language], $attributeIds);
            $parameters[] = $this->normalizeWord($searchWord);
            $parameters[] = $searchWord;
            $parameters   = \array_merge($parameters, $attributeIds);
            $query        = \sprintf(
                'SELECT t.attribute,t.item FROM tl_metamodel_levensthein AS t
                            WHERE t.id IN (
                                SELECT tx.pid
                                FROM tl_metamodel_levensthein_index AS tx
                                WHERE tx.language=?
                                AND tx.attribute IN (%1$s)
                                AND (tx.transliterated LIKE ? OR tx.word LIKE ?)
                                )
                            ORDER BY FIELD(t.attribute,%1$s)',
                \implode(',', \array_fill(0, \count($attributeIds), '?'))
            );

            $query = $this
                ->connection
                ->prepare($query);
            $query->execute($parameters);

            if ($query->rowCount() === 0) {
                foreach ($attributeIds as $attribute) {
                    $resultSet->addResult($word, $attribute, 0);
                }
            }

            while ($result = $query->fetchAssociative()) {
                if (!empty($result['attribute']) && !empty($result['item'])) {
                    $resultSet->addResult($word, $result['attribute'], $result['item']);
                }
            }
        }
    }

    /**
     * Find exact matches of chunks and return the parent ids.
     *
     * @param list<string>       $attributeIds The attributes to search on.
     * @param string             $language     The language key.
     * @param SearchStringParser $parser       The chunks to search for.
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getMustIds(
        array $attributeIds,
        string $language,
        SearchStringParser $parser,
        ResultSet $resultSet
    ): void {
        if (($must = $parser->getMust()) === []) {
            return;
        }

        $parameters = \array_merge([$language], $attributeIds);
        $attributes = \implode(',', \array_fill(0, \count($attributeIds), '?'));
        $sql        = sprintf(
            'SELECT t.attribute, t.item FROM tl_metamodel_levensthein AS t
                WHERE t.id IN (
                    SELECT tx.pid
                        FROM tl_metamodel_levensthein_index AS tx
                        WHERE tx.language=?
                        AND tx.attribute IN (%1$s)
                        AND (%2$s)
                        ORDER BY FIELD(tx.attribute,%1$s), tx.word
                )',
            $attributes,
            'tx.transliterated=? OR tx.word=?'
        );

        foreach ($must as $word) {
            $query = $this->connection->prepare($sql);
            $query->execute(\array_merge($parameters, [$this->normalizeWord($word), $word], $attributeIds));

            // If no matches, add an empty array.
            if ($query->rowCount() === 0) {
                foreach ($attributeIds as $attribute) {
                    $resultSet->addMustResult($word, $attribute, 0);
                }
                continue;
            }

            while ($row = $query->fetchAssociative()) {
                if (!empty($row['attribute']) && !empty($row['item'])) {
                    $resultSet->addMustResult($word, $row['attribute'], $row['item']);
                }
            }
        }
    }

    /**
     * Find exact matches of chunks and return the parent ids.
     *
     * @param list<string>       $attributeIds The attributes to search on.
     * @param string             $language     The language key.
     * @param SearchStringParser $parser       The chunks to search for.
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getMustNotIds(
        array $attributeIds,
        string $language,
        SearchStringParser $parser,
        ResultSet $resultSet
    ): void {
        if (($must = $parser->getMustNot()) === []) {
            return;
        }

        $parameters = \array_merge([$language], $attributeIds);
        $attributes = \implode(',', \array_fill(0, \count($attributeIds), '?'));
        $sql        = \sprintf(
            'SELECT l.attribute, l.item FROM tl_metamodel_levensthein AS l
                WHERE l.id IN (
                    SELECT lx.pid
                        FROM tl_metamodel_levensthein_index AS lx
                        WHERE lx.language=?
                        AND lx.attribute IN (%1$s)
                        AND (%2$s)
                        ORDER BY FIELD(lx.attribute,%1$s),lx.word
                    )',
            $attributes,
            'lx.transliterated=? OR lx.word=?'
        );

        foreach ($must as $word) {
            $query = $this->connection->prepare($sql);
            $query->execute(\array_merge($parameters, [$this->normalizeWord($word), $word], $attributeIds));

            while ($row = $query->fetchAssociative()) {
                if (!empty($row['attribute']) && !empty($row['item'])) {
                    $resultSet->addNegativeResult($word, $row['attribute'], $row['item']);
                }
            }
        }
    }

    /**
     * Retrieve all words from the search index valid as levenshtein candidates.
     *
     * @param list<string>       $attributeIds The ids of the attributes to query.
     * @param SearchStringParser $parser       The chunks to search for.
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getLevenshteinCandidates(
        array $attributeIds,
        SearchStringParser $parser,
        ResultSet $resultSet
    ): void {
        $words = $parser->getKeywords($this->minLength, $this->maxLength);
        $query = \sprintf(
            'SELECT li.transliterated, li.word, li.pid, li.attribute, l.item
                FROM tl_metamodel_levensthein_index AS li
                RIGHT JOIN tl_metamodel_levensthein AS l ON (l.id=li.pid)
                WHERE
                li.attribute IN (%1$s)
                AND LENGTH(li.transliterated) BETWEEN ? AND ?
                ORDER BY li.word
                ',
            \implode(',', \array_fill(0, \count($attributeIds), '?'))
        );

        foreach ($words as $chunk) {
            if ($resultSet->hasResultsFor($chunk)) {
                continue;
            }

            $transChunk = $this->normalizeWord($chunk);
            $wordLength = \strlen($transChunk);
            $distance   = $this->getAllowedDistanceFor($transChunk);
            $minLen     = ($wordLength - $distance);
            $maxLen     = ($wordLength + $distance);

            // Easy out, word is too short or too long.
            if (($wordLength < $this->minLength) || ($wordLength > $this->maxLength)) {
                continue;
            }

            $results = $this->connection->prepare($query);
            $results->execute(\array_merge($attributeIds, [$minLen, $maxLen]));

            $this->processCandidates($resultSet, $chunk, $results, $distance);
        }
    }

    /**
     * Process the result list and add acceptable entries to the list.
     *
     * @param ResultSet $resultSet The result list to add to.
     * @param string    $chunk     The chunk being processed.
     * @param Statement $results   The results to process.
     * @param int       $distance  The acceptable distance.
     *
     * @return void
     */
    private function processCandidates(ResultSet $resultSet, string $chunk, Statement $results, int $distance): void
    {
        while ($result = $results->fetchAssociative()) {
            if (empty($result['attribute']) || empty($result['item'])) {
                continue;
            }

            if (!empty($result['transliterated'])) {
                $trans = $result['transliterated'];

                if ($this->isAcceptableByLevenshtein($chunk, $trans, $distance)) {
                    $resultSet->addResult($chunk, $result['attribute'], $result['item']);
                }
            }
        }
    }

    /**
     * Determine the allowed distance for the given word.
     *
     * @param string $word The word.
     *
     * @return int
     */
    private function getAllowedDistanceFor(string $word): int
    {
        $length   = \strlen($word);
        $distance = 0;

        foreach ($this->maxDistance as $minimumLength => $allowedDistance) {
            if ($minimumLength > $length) {
                break;
            }

            $distance = (int) $allowedDistance;
        }

        return $distance;
    }

    /**
     * Check if the passed value is an acceptable entry.
     *
     * @param string $chunk    Transliterated version of the chunk being searched.
     * @param string $trans    Transliterated version of the matched entry.
     * @param int    $distance The maximum levenshtein distance allowed.
     *
     * @return bool
     */
    private function isAcceptableByLevenshtein(string $chunk, string $trans, int $distance): bool
    {
        // Length too short.
        if (\strlen($trans) <= $this->minLength) {
            return false;
        }
        // Result has different Type (Prevent matches like XX = 01).
        if (\is_numeric($trans) && !\is_numeric($chunk)) {
            return false;
        }
        $lev = \levenshtein($chunk, $trans);
        if (0 === $lev) {
            return true;
        }
        // Distance too far.
        if ($lev > $distance) {
            return false;
        }

        return true;
    }

    /**
     * Normalize a word to plain ASCII representation.
     *
     * @param string $word The word to convert.
     *
     * @return string
     */
    private function normalizeWord(string $word): string
    {
        if (\mb_detect_encoding($word) == 'ASCII') {
            return $word;
        }

        return Utf8::toAscii($word);
    }
}
