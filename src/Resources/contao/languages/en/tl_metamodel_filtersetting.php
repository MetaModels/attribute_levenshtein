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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['typenames']['levensthein'] = 'Levenshtein assisted search';

$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_autocomplete'][0] = 'Activate autocomplete';
$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_autocomplete'][1] =
    'You can activate the autocomplete at search field.';
$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_minChar'][0]      = 'Min. characters for autocomplete';
$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_minChar'][1]      =
    'Number of characters from which the autocomplete should work.';
