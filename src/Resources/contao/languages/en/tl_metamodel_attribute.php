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
 * @package    MetaModels
 * @subpackage AttributeLevenshtein
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance'][0] = 'Maximum Levenshtein distance';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance'][1] =
    'Please select for each minimum word length, the maximum distance for the Levenshtein algorithm.';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance_wordlength'][0] = 'Minimum word length';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance_distance'][0]   = 'Allowed distance';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_attributes'][0] = 'Attributes to index';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_attributes'][1] =
    'Please select all attributes that shall get indexed.';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['levensthein'] = 'Levenshtein assisted search';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['rebuild_levensthein'] = 'Rebuild search index.';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levenshtein_minLengthWords'][0] = 'Minimum length of words';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levenshtein_minLengthWords'][1] =
    'Please select minimum length of words that shall get indexed.';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levenshtein_maxLengthWords'][0] = 'Maximum length of words';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levenshtein_maxLengthWords'][1] =
    'Please select maximum length of words that shall get indexed.';
