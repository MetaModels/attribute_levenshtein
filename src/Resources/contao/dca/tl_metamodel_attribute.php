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

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['levensthein extends _complexattribute_'] = [
    '+advanced' => [
        '-isvariant',
        '-isunique',
        'levensthein_distance',
        'levensthein_attributes'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['levensthein_distance'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'disableSorting' => true,
        'columnFields'   => [
            'wordLength' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance_wordlength'],
                'inputType' => 'text',
                'default'   => '3',
                'eval'      => [
                    'rgxp'  => 'digit',
                    'style' => 'width:115px',
                ]
            ],
            'distance'   => [
                'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance_distance'],
                'inputType' => 'select',
                'options'   => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'default'   => '1',
                'eval'      => [
                    'includeBlankOption' => true,
                    'style'              => 'width:115px',
                ]
            ],
        ],
        'tl_class'       => 'w50 w50x',
        'helpwizard'     => true,
    ],
    'explanation' => 'levenshtein_distance',
    'sql'         => 'mediumtext NULL',
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['levensthein_attributes'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_attributes'],
    'exclude'   => true,
    'inputType' => 'checkboxWizard',
    'eval'      => ['multiple' => true, 'tl_class' => 'w50 w50x'],
    'sql'       => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['list']['operations']['rebuild_levensthein'] = [
    'label' => $GLOBALS['TL_LANG']['tl_metamodel_attribute']['rebuild_levensthein'],
    'href'  => 'act=rebuild_levensthein',
    'icon'  => 'bundles/metamodelsattributelevenshtein/levensthein.png'
];
