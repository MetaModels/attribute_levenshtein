<?php

/**
 * This file is part of MetaModels/attribute_levenshtein.
 *
 * (c) 2012-2024 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_levensthein
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\Attribute;

use MetaModels\Attribute\IAttribute;

/**
 * This class implements a general purpose search result container.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ResultSet
{
    /**
     * The list of results.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $results = [];

    /**
     * The list of results.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $mustResults = [];

    /**
     * The list of results tha must not be returned.
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $negativeResults = [];

    /**
     * Add some results for a search expression on an attribute.
     *
     * @param string            $searchExpression The expression that was searched.
     * @param IAttribute|string $attribute        The attribute that was searched.
     * @param list<string>      $results          The resulting ids.
     *
     * @return void
     */
    public function addResults($searchExpression, $attribute, $results)
    {
        $attributeId = ($attribute instanceof IAttribute) ? $attribute->get('id') : $attribute;
        foreach ($results as $result) {
            $this->addResult($searchExpression, $attributeId, $result);
        }
    }

    /**
     * Add a single result match.
     *
     * @param string $searchExpression The search expression.
     * @param string $attributeId      The attribute id.
     * @param string $result           The matching id.
     *
     * @return void
     */
    public function addResult($searchExpression, $attributeId, $result)
    {
        if (!isset($this->results[$attributeId])) {
            $this->results[$attributeId] = [];
        }

        if (!isset($this->results[$attributeId][$searchExpression])) {
            $this->results[$attributeId][$searchExpression] = [];
        }

        $this->results[$attributeId][$searchExpression][] = $result;
    }

    /**
     * Add a single result match.
     *
     * @param string $searchExpression The search expression.
     * @param string $attributeId      The attribute id.
     * @param string $result           The matching id.
     *
     * @return void
     */
    public function addMustResult($searchExpression, $attributeId, $result)
    {
        if (!isset($this->mustResults[$attributeId])) {
            $this->mustResults[$attributeId] = [];
        }

        if (!isset($this->mustResults[$attributeId][$searchExpression])) {
            $this->mustResults[$attributeId][$searchExpression] = [];
        }

        $this->mustResults[$attributeId][$searchExpression][] = $result;
    }

    /**
     * Add multiple result matches.
     *
     * @param string   $searchExpression The search expression.
     * @param string   $attributeId      The attribute id.
     * @param string[] $results          The resulting ids.
     *
     * @return void
     */
    public function addMustResults($searchExpression, $attributeId, $results)
    {
        if (!isset($this->mustResults[$attributeId])) {
            $this->mustResults[$attributeId] = [];
        }

        if (!isset($this->mustResults[$attributeId][$searchExpression])) {
            $this->mustResults[$attributeId][$searchExpression] = [];
        }

        foreach ($results as $result) {
            $this->mustResults[$attributeId][$searchExpression][] = $result;
        }
    }

    /**
     * Add a single negative result match.
     *
     * @param string $searchExpression The search expression.
     * @param string $attributeId      The attribute id.
     * @param string $result           The matching id.
     *
     * @return void
     */
    public function addNegativeResult($searchExpression, $attributeId, $result)
    {
        if (!isset($this->negativeResults[$attributeId])) {
            $this->negativeResults[$attributeId] = [];
        }

        if (!isset($this->negativeResults[$attributeId][$searchExpression])) {
            $this->negativeResults[$attributeId][$searchExpression] = [];
        }

        $this->negativeResults[$attributeId][$searchExpression][] = $result;
    }

    /**
     * Add multiple negative result matches.
     *
     * @param string   $searchExpression The search expression.
     * @param string   $attributeId      The attribute id.
     * @param string[] $results          The matching id.
     *
     * @return void
     */
    public function addNegativeResults($searchExpression, $attributeId, $results)
    {
        if (!isset($this->negativeResults[$attributeId])) {
            $this->negativeResults[$attributeId] = [];
        }

        if (!isset($this->negativeResults[$attributeId][$searchExpression])) {
            $this->negativeResults[$attributeId][$searchExpression] = [];
        }

        foreach ($results as $result) {
            $this->negativeResults[$attributeId][$searchExpression][] = $result;
        }
    }

    /**
     * Retrieve the combined results.
     *
     * @param IAttribute[] $sortOrder            The attribute to sort the results by.
     * @param bool         $addMissingAttributes If true, missing attributes will be appended to the sort list.
     *
     * @return \string[]
     */
    public function getCombinedResults($sortOrder, $addMissingAttributes = true)
    {
        $attributes = [];
        foreach ($sortOrder as $attribute) {
            $attributes[] = $attribute->get('id');
        }

        // add missing ids.
        if ($addMissingAttributes) {
            $attributes = \array_values(\array_unique(\array_merge($attributes, \array_keys($this->results))));
        }

        // determine the negative result set.
        $negatives = $this->getNegatives($attributes);

        $matches = \array_diff($this->getPositives($attributes), $negatives);

        if (null !== ($must = $this->getMust($attributes))) {
            return \array_values(\array_intersect($matches, $must));
        }

        return \array_values($matches);
    }

    /**
     * Check if there are any results for the given word.
     *
     * @param string $word The word to check.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) - complex method but readable, so we accept a complexity of 10.
     */
    public function hasResultsFor($word)
    {
        $temp = new self();

        foreach ($this->results as $attribute => $attributeResults) {
            foreach ($attributeResults as $expression => $exprMatches) {
                if ($expression === $word) {
                    $temp->addResults($expression, $attribute, $exprMatches);
                    break;
                }
            }
        }

        foreach ($this->mustResults as $attribute => $attributeResults) {
            foreach ($attributeResults as $expression => $exprMatches) {
                if ($expression === $word) {
                    $temp->addMustResults($expression, $attribute, $exprMatches);
                    break;
                }
            }
        }

        foreach ($this->negativeResults as $attribute => $attributeResults) {
            foreach ($attributeResults as $expression => $exprMatches) {
                if ($expression === $word) {
                    $temp->addNegativeResults($expression, $attribute, $exprMatches);
                    break;
                }
            }
        }

        // Filtering because we have to kill any optional placeholder '0' in the results.
        return [] !== \array_filter($temp->getCombinedResults([]));
    }

    /**
     * Retrieve the mandatory ids as array.
     *
     * @param list<string> $attributeIds The list of attributes.
     *
     * @return string[]|null
     */
    private function getMust(array $attributeIds): ?array
    {
        if ([] === $this->mustResults) {
            return null;
        }

        $must = [];
        foreach ($attributeIds as $attributeId) {
            if ([] === ($this->mustResults[$attributeId] ?? [])) {
                continue;
            }

            foreach ($this->mustResults[$attributeId] as $expression => $exprMatches) {
                if (!isset($must[$expression])) {
                    $must[$expression] = [];
                }

                $must[$expression] = \array_unique(\array_merge($must[$expression], $exprMatches));
            }
        }

        $results = \current($must);

        if (false === $results) {
            return [];
        }

        foreach ($must as $result) {
            $results = \array_intersect($results, $result);
        }

        return \array_values(\array_unique($results));
    }

    /**
     * Retrieve the negative ids as array.
     *
     * @param string[] $attributeIds The list of attributes.
     *
     * @return string[]
     */
    private function getNegatives(array $attributeIds): array
    {
        // determine the negative result set.
        $negatives = [];
        foreach ($attributeIds as $attributeId) {
            if ([] === ($this->negativeResults[$attributeId] ?? [])) {
                continue;
            }

            foreach ($this->negativeResults[$attributeId] as $exprMatches) {
                $negatives = \array_merge($negatives, $exprMatches);
            }
        }

        return \array_values(\array_unique($negatives));
    }

    /**
     * Retrieve the positive ids as array.
     *
     * @param string[] $attributeIds The list of attributes.
     *
     * @return string[]
     */
    private function getPositives(array $attributeIds): array
    {
        $positives = [];
        $points    = [];
        $point     = $this->scoreMatrix($attributeIds);
        foreach ($attributeIds as $attributeId) {
            if ([] === ($this->results[$attributeId] ?? [])) {
                continue;
            }

            foreach ($this->results[$attributeId] as $expression => $exprMatches) {
                if (!isset($positives[$expression])) {
                    $positives[$expression] = [];
                }

                $positives[$expression] = \array_unique(array_merge($positives[$expression], $exprMatches));

                foreach ($exprMatches as $match) {
                    if (!\array_key_exists($match, $points)) {
                        $points[$match] = $point[$attributeId];
                    }
                    $points[$match] |= $point[$attributeId];
                }
            }
        }

        $results = \current($positives);

        if (false === $results) {
            return [];
        }

        if (\count($positives) > 1) {
            $results = array_intersect(...\array_values($positives));
        }

        $results = \array_values(\array_unique($results));
        \arsort($points);

        $results = \array_intersect(\array_keys($points), $results);

        return \array_values($results);
    }

    /**
     * Create a bit mask to allow setting of bits for sorting.
     *
     * @param string[] $attributeIds List of attribute ids.
     *
     * @return int[]
     */
    private function scoreMatrix(array $attributeIds): array
    {
        $point = [];

        foreach (\array_values(\array_reverse($attributeIds)) as $index => $id) {
            $point[$id] = (1 << $index);
        }

        return $point;
    }
}
