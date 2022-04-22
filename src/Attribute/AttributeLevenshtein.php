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
use MetaModels\IMetaModel;
use MetaModels\Attribute\BaseComplex;
use MetaModels\Attribute\IAttribute;

/**
 * This class implements a general purpose search index for MetaModels to be searched with Levenshtein-Search algorithm.
 */
class AttributeLevenshtein extends BaseComplex
{
    /**
     * The index to work with.
     *
     * @var LevenshteinIndex
     */
    private LevenshteinIndex $index;

    /**
     * The index to work with.
     *
     * @var LevenshteinIndexLookup
     */
    private LevenshteinIndexLookup $indexLookup;

    /**
     * Database connection.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel      $objMetaModel The MetaModel instance this attribute belongs to.
     * @param array           $arrData      The information array, for attribute information, refer to
     *                                      documentation of table tl_metamodel_attribute and documentation of the
     *                                      certain attribute classes for information what values are understood.
     * @param Connection|null $connection   The database connection.
     */
    public function __construct(
        IMetaModel $objMetaModel,
        $arrData = [],
        Connection $connection = null
    ) {
        parent::__construct($objMetaModel, $arrData);

        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
        }

        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(parent::getAttributeSettingNames(), ['levensthein_distance', 'levensthein_attributes']);
    }

    /**
     * {@inheritdoc}
     *
     * This method is a no-op in this class.
     *
     * @codeCoverageIgnore
     */
    public function parseValue($arrRowData, $strOutputFormat = 'text', $objSettings = null)
    {
        return [$strOutputFormat => null];
    }

    /**
     * {@inheritdoc}
     *
     * This method is a no-op in this class.
     *
     * @codeCoverageIgnore
     */
    public function getFilterOptions($idList, $usedOnly, &$count = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * This method is a no-op in this class.
     *
     * @codeCoverageIgnore
     */
    public function getDataFor($idList)
    {
        // No op - this attribute is not meant to be manipulated.
        return null;
    }

    /**
     * This method is a no-op in this class.
     *
     * @param mixed[int] $values Unused.
     *
     * @return void
     *
     * @SuppressWarnings("unused")
     * @codeCoverageIgnore
     */
    public function setDataFor($values)
    {
        // No op - this attribute is not meant to be manipulated.
    }

    /**
     * Delete all values for the given items.
     *
     * @param int[] $idList The ids of the items to remove votes for.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function unsetDataFor($idList)
    {
        // No op - this attribute is not meant to be manipulated.
    }

    /**
     * Search the index with levenshtein algorithm.
     *
     * The standard wildcards * (many characters) and ? (a single character) are supported.
     *
     * @param string $pattern The search pattern to search.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     */
    public function searchFor($pattern)
    {
        $index = $this->getLookup();

        return $index->search(
            $this->getMetaModel()->getActiveLanguage(),
            $pattern
        );
    }

    /**
     * {@inheritdoc}
     */
    public function modelSaved($item)
    {
        $indexer   = $this->getIndex();
        $blacklist = $this->getBlackList();
        $metaModel = $this->getMetaModel();
        $language  = $metaModel->getActiveLanguage();
        // Parse the value as text representation for each attribute.
        foreach ($this->getIndexedAttributes() as $attribute) {
            $value = $item->parseAttribute($attribute->getColName());
            $indexer->updateIndex(
                $value['text'],
                $attribute,
                $item->get('id'),
                $language,
                $blacklist
            );
        }
    }

    /**
     * Search the index with levenshtein algorithm.
     *
     * The standard wildcards * (many characters) and ? (a single character) are supported.
     *
     * @param string $pattern The search pattern to search.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     */
    public function getSuggestions($pattern)
    {
        $index = $this->getLookup();

        return $index->getSuggestions(
            $this->getMetaModel()->getActiveLanguage(),
            $pattern
        );
    }

    /**
     * Retrieve the list of attributes we index.
     *
     * @return IAttribute[]
     */
    private function getIndexedAttributes(): array
    {
        $metaModel  = $this->getMetaModel();
        $attributes = [];
        foreach ($this->get('levensthein_attributes') as $attributeName) {
            $attribute = $metaModel->getAttribute($attributeName);
            if ($attribute) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Retrieve the index instance.
     *
     * @return LevenshteinIndex
     */
    private function getIndex(): LevenshteinIndex
    {
        if (!isset($this->index)) {
            $this->index = new LevenshteinIndex($this->connection);
        }

        return $this->index;
    }

    /**
     * Retrieve the index lookup instance.
     *
     * @return LevenshteinIndexLookup
     */
    private function getLookup(): LevenshteinIndexLookup
    {
        if (!isset($this->indexLookup)) {
            $this->indexLookup = new LevenshteinIndexLookup(
                $this->connection,
                $this->getIndexedAttributes(),
                $this->get('levensthein_distance')
            );
        }

        return $this->indexLookup;
    }

    /**
     * Retrieve the blacklist.
     *
     * @return Blacklist
     */
    private function getBlackList(): Blacklist
    {
        $blacklist = new Blacklist();
        $blacklist->addLanguage(
            'en',
            [
                'a',
                'an',
                'any',
                'are',
            ]
        );

        return $blacklist;
    }
}
