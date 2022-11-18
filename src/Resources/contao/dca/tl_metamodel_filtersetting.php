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

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['levensthein extends default'] = [
    '+config'   => ['attr_id'],
    '+fefilter' => [
        'urlparam',
        'label',
        'hide_label',
        'template',
        'placeholder',
        'cssID',
        'levenshtein_autocomplete',
        'levenshtein_minChar',
        'levenshtein_autoSubmit'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['fields']['levenshtein_autocomplete'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_autocomplete'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'default'   => '1',
    'eval'      => [
        'tl_class' => 'clr w50 m12 cbx',
    ],
    'sql'       => "char(1) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['fields']['levenshtein_minChar'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_minChar'],
    'exclude'   => true,
    'inputType' => 'text',
    'default'   => '3',
    'eval'      => [
        'tl_class' => 'w50',
        'rgxp'     => 'natural',
    ],
    'sql'       => "varchar(2) NOT NULL default '3'"
];

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['fields']['levenshtein_autoSubmit'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['levenshtein_autoSubmit'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50 cbx m12'],
    'sql'       => "char(1) NOT NULL default ''"
];
