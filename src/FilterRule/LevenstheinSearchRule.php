<?php

/**
 * This file is part of MetaModels/attribute_levensthein.
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
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\FilterRule;

use MetaModels\AttributeLevenshteinBundle\Attribute\AttributeLevenshtein;
use MetaModels\Filter\FilterRule;

/**
 * Filter attributes for keywords using the LevenshteinSearch algorithm.
 */
class LevenstheinSearchRule extends FilterRule
{
    /**
     * The attribute to search in.
     *
     * @var AttributeLevenshtein
     */
    protected $attribute;

    /**
     * The value to search for.
     *
     * @var string
     */
    protected $value = '';

    /**
     * Creates an instance of a simple query filter rule.
     *
     * @param AttributeLevenshtein $attribute The attribute to be searched.
     * @param string               $value     The value to be searched for. Wildcards (* and ? allowed).
     */
    public function __construct($attribute, $value = '')
    {
        parent::__construct();
        $this->attribute = $attribute;
        $this->value     = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingIds()
    {
        return $this->attribute->searchFor($this->value);
    }
}
