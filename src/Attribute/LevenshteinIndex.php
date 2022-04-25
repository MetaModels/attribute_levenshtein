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
use MetaModels\Attribute\IAttribute;
use Patchwork\Utf8;

/**
 * This class implements a general purpose search index for MetaModels to be searched with Levenshtein-Search algorithm.
 */
class LevenshteinIndex
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Add a text to the search index.
     *
     * @param string     $text      The text to index.
     * @param IAttribute $attribute The attribute the text originated from.
     * @param string     $itemId    The id of the item the text originates from.
     * @param string     $language  The language key.
     * @param Blacklist  $blacklist The blacklist to use.
     *
     * @return void
     */
    public function updateIndex($text, $attribute, $itemId, $language, Blacklist $blacklist)
    {
        $converter = new LevenshteinTextConverter($blacklist, $language);
        $words     = $converter->process($text);
        $entry     = $this->lookUpEntry($attribute, $itemId, $language);

        if (false === $entry) {
            $entry = $this->createEntry($attribute, $itemId, $language, $words->checksum());
        } elseif ($words->checksum() === $entry['checksum']) {
            return;
        }

        $this->addWords($entry, $words);
    }

    /**
     * Look up an entry in the database.
     *
     * @param IAttribute $attribute The attribute.
     * @param string     $itemId    The item id.
     * @param string     $language  The language code.
     *
     * @return array
     */
    private function lookUpEntry(IAttribute $attribute, string $itemId, string $language): array
    {
        return $this
            ->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('tl_metamodel_levensthein', 't')
            ->where('t.metamodel=:metamodel')
            ->andWhere('t.attribute=:attribute')
            ->andWhere('t.item=:item')
            ->andWhere('t.language=:language')
            ->setParameter('metamodel', $attribute->getMetaModel()->get('id'))
            ->setParameter('attribute', $attribute->get('id'))
            ->setParameter('item', $itemId)
            ->setParameter('language', $language)
            ->execute()
            ->fetchAllAssociative();
    }

    /**
     * Look up an entry in the database.
     *
     * @param IAttribute $attribute The attribute.
     * @param string     $itemId    The item id.
     * @param string     $language  The language code.
     * @param string     $checkSum  The checksum of the word list.
     *
     * @return array
     */
    private function createEntry(IAttribute $attribute, string $itemId, string $language, string $checkSum): array
    {
        $this
            ->connection
            ->createQueryBuilder()
            ->insert('tl_metamodel_levensthein')
            ->setValue('tl_metamodel_levensthein.metamodel', ':metamodel')
            ->setValue('tl_metamodel_levensthein.attribute', ':attribute')
            ->setValue('tl_metamodel_levensthein.item', ':item')
            ->setValue('tl_metamodel_levensthein.language', ':language')
            ->setValue('tl_metamodel_levensthein.checksum', ':checksum')
            ->setParameter('metamodel', $attribute->getMetaModel()->get('id'))
            ->setParameter('attribute', $attribute->get('id'))
            ->setParameter('item', $itemId)
            ->setParameter('language', $language)
            ->setParameter('checksum', $checkSum)
            ->execute();

        return $this->lookUpEntry($attribute, $itemId, $language);
    }

    /**
     * Add the words from the given list to the index.
     *
     * @param array    $entry The entry from the tl_metamodel_levensthein table.
     * @param WordList $words The word list.
     *
     * @return void
     */
    private function addWords(array $entry, WordList $words): void
    {
        // First: Delete all words in index.
        $this
            ->connection
            ->createQueryBuilder()
            ->delete('tl_metamodel_levensthein_index', 't')
            ->where('t.pid=:pid')
            ->setParameter('pid', $entry['id'])
            ->execute();

        if ($words->count() === 0) {
            return;
        }

        // Second: Rebuild index.
        $builder = $this
            ->connection
            ->createQueryBuilder()
            ->insert('tl_metamodel_levensthein_index')
            ->values([
                'tl_metamodel_levensthein_index.pid'            => ':pid',
                'tl_metamodel_levensthein_index.attribute'      => ':attribute',
                'tl_metamodel_levensthein_index.word'           => ':word',
                'tl_metamodel_levensthein_index.transliterated' => ':transliterated',
                'tl_metamodel_levensthein_index.relevance'      => ':relevance',
                'tl_metamodel_levensthein_index.language'       => ':language',
            ]);

        foreach ($words->getWords() as $word => $relevance) {
            $builder
                ->setParameter('pid', $entry['id'])
                ->setParameter('attribute', $entry['attribute'])
                ->setParameter('word', $word)
                ->setParameter('transliterated', $this->normalizeWord($word))
                ->setParameter('relevance', $relevance)
                ->setParameter('language', $words->getLanguage())
                ->execute();
        }
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
