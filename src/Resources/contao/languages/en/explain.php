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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

/* levenshtein distance */
$GLOBALS['TL_LANG']['XPL']['levenshtein_distance'][0][0] = 'Levenshtein distance';
$GLOBALS['TL_LANG']['XPL']['levenshtein_distance'][0][1] =
    'The Levenshtein distance between two strings is the minimum number of inserting,
deleting and replacing operations to convert the first string into the second.<br /><br /> 
For example, it takes one operation to turn the word "fame" into the word "flame" (add "l"), or
three operation from "kitten" into the word "sitting" (substitution of "k" and "e" and
insertion "g" at end).<br /><br />
If the distance is set to 0, no exchange takes place and the exact string is searched for.';

$GLOBALS['TL_LANG']['XPL']['levenshtein_distance'][1][0] = 'Example';
$GLOBALS['TL_LANG']['XPL']['levenshtein_distance'][1][1] =
    'A possible setting would be:<br /><br />
<table>
<tr><th style="padding-right: 5px">Word length</th><th>Distance</th></tr>
<tr><td style="text-align:center;">3</td><td style="text-align:center;">1</td></tr>
<tr><td style="text-align:center;">5</td><td style="text-align:center;">2</td></tr>
<tr><td style="text-align:center;">9</td><td style="text-align:center;">3</td></tr>
</table><br /><br />
Configure the values for the distance in ascending order.';
