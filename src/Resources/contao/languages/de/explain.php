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
 * Translations are managed automatically using Transifex. To create a new translation
 * or to help to maintain an existing one, please register at transifex.com.
 *
 * Last-updated: 2022-11-30T08:07:42+00:00
 *
 * @copyright 2012-2022 The MetaModels team.
 * @license   https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0-or-later
 * @link      https://www.transifex.com/metamodels/public/
 * @link      https://www.transifex.com/signup/?join_project=metamodels
 */


$GLOBALS['TL_LANG']['XPL']['levenshtein_distance']['0']['0'] = 'Levenshtein-Abstand';
$GLOBALS['TL_LANG']['XPL']['levenshtein_distance']['0']['1'] = 'Der Levenshtein-Abstand zwischen zwei Zeichenketten ist die Mindestanzahl von Einfüge-, Lösch- und Ersetzungsvorgängen,um die erste Zeichenkette in die zweite umzuwandeln.<br /><br /> 
So ist beispielsweise eine Operation erforderlich, um aus dem Wort "fame" das Wort "flame" zu machen ("l" einfügen), oder
drei Operrationen von "kitten" in das Wort "sitting" (Ersetzung von "k" und "e" und
Einfügung "g" am Ende).<br /><br />
Wird der Abstand auf 0 gesetzt, findet kein Austausch statt und es wird nach der genauen Zeichenfolge gesucht.';
$GLOBALS['TL_LANG']['XPL']['levenshtein_distance']['1']['0'] = 'Beispiel';
$GLOBALS['TL_LANG']['XPL']['levenshtein_distance']['1']['1'] = 'Eine mögliche Einstellung wäre:<br /><br />
<table>
<tr><th style="padding-right: 5px">Wortlänge</th><th>Abstand</th></tr>
<tr><td style="text-align:center;">3</td><td style="text-align:center;">1</td></tr>
<tr><td style="text-align:center;">5</td><td style="text-align:center;">2</td></tr>
<tr><td style="text-align:center;">9</td><td style="text-align:center;">3</td></tr>
</table><br /><br />
Die jeweilige Mindestwortlänge sollte zwischen der Mindest- und der Höchstlänge
der Wörter liegen, wie sie aus dem Index gelesen werden.<br /><br />
Legen Sie die Werte für den Abstand in aufsteigender Reihenfolge fest.';

