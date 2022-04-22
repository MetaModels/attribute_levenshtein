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
use MetaModels\Attribute\AbstractAttributeTypeFactory;

/**
 * Attribute type factory for levenshtein attributes.
 */
class LevenshteinAttributeTypeFactory extends AbstractAttributeTypeFactory
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->typeName   = 'levensthein';
        $this->typeIcon   = 'bundles/metamodelsattributelevenshtein/levensthein.png';
        $this->typeClass  = AttributeLevenshtein::class;
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->connection);
    }
}
