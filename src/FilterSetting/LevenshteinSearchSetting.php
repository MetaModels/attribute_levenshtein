<?php

/**
 * This file is part of MetaModels/attribute_levenshtein.
 *
 * (c) 2012-2024 The MetaModels team.
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
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\FilterSetting;

use Contao\StringUtil;
use MetaModels\Attribute\IAttribute;
use MetaModels\AttributeLevenshteinBundle\Attribute\AttributeLevenshtein;
use MetaModels\AttributeLevenshteinBundle\FilterRule\LevenstheinSearchRule;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\SimpleLookup;
use MetaModels\FrontendIntegration\FrontendFilterOptions;

/**
 * Filter attributes for keywords using the LevenshteinSearch algorithm.
 */
class LevenshteinSearchSetting extends SimpleLookup
{
    /**
     * Overrides the parent implementation to always return true, as this setting is always optional.
     *
     * @return bool true if all matches shall be returned, false otherwise.
     */
    public function allowEmpty()
    {
        return true;
    }

    /**
     * Overrides the parent implementation to always return true, as this setting is always available for FE filtering.
     *
     * @return bool true as this setting is always available.
     */
    public function enableFEFilterWidget()
    {
        return true;
    }

    /**
     * Retrieve the filter parameter name to react on.
     *
     * @return string|null
     */
    protected function getParamName()
    {
        if ($this->get('urlparam')) {
            return $this->get('urlparam');
        }

        $objAttribute = $this->getMetaModel()->getAttributeById((int) $this->get('attr_id'));
        if ($objAttribute instanceof IAttribute) {
            return $objAttribute->getColName();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function prepareRules(IFilter $objFilter, $arrFilterUrl)
    {
        $metaModel = $this->getMetaModel();
        $attribute = $metaModel->getAttributeById((int) $this->get('attr_id'));
        $paramName = $this->getParamName();
        $value     = $arrFilterUrl[$paramName] ?? '';

        if ($attribute && $paramName && $value) {
            /** @var AttributeLevenshtein $attribute */
            $objFilter->addFilterRule(new LevenstheinSearchRule($attribute, $value));
            return;
        }

        $objFilter->addFilterRule(new StaticIdList([]));
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return ($strParamName = $this->getParamName()) ? [$strParamName] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterNames()
    {
        if (($strParamName = $this->getParamName())) {
            return [
                $strParamName => ($this->getLabel() ?: $this->getParamName())
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function getParameterFilterWidgets(
        $arrIds,
        $arrFilterUrl,
        $arrJumpTo,
        FrontendFilterOptions $objFrontendFilterOptions
    ) {
        if (!$this->enableFEFilterWidget()) {
            return [];
        }

        if (!$this->getFilteredAttribute()) {
            return [];
        }

        $paramName = $this->getParamName();
        assert(\is_string($paramName));

        $this->addFilterParam($paramName);
        $arrReturn = [];
        $attrId    = $this->get('attr_id');
        $cssID     = StringUtil::deserialize($this->get('cssID'), true);

        $attribute = $this->getMetaModel()->getAttributeById((int) $attrId);
        assert($attribute instanceof IAttribute);

        /** @psalm-suppress DeprecatedMethod */
        $arrWidget = [
            'label'     => [
                $this->getLabel(),
                'GET: ' . $paramName
            ],
            'inputType' => 'text',
            'count'     => [],
            'showCount' => $objFrontendFilterOptions->isShowCountValues(),
            'eval'      => [
                'colname'      => $attribute->getColName(),
                'urlparam'     => $paramName,
                'template'     => $this->get('template'),
                'hide_label'   => $this->get('hide_label'),
                'cssID'        => \sprintf(
                    ' id="%s"',
                    !empty($cssID[0]) ? $cssID[0] : 'autocomplete__container_' . $attrId
                ),
                'class'        => !empty($cssID[1]) ? ' ' . $cssID[1] : '',
                'placeholder'  => $this->get('placeholder'),
                'tableName'    => $this->getMetaModel()->getTableName(),
                'attrId'       => $attrId,
                'language'     => $this->getMetaModel()->getActiveLanguage(),
                'selector'     => !empty($cssID[0]) ? $cssID[0] : 'autocomplete__container_' . $attrId,
                'autocomplete' => $this->get('levenshtein_autocomplete'),
                'minChar'      => (int) $this->get('levenshtein_minChar'),
                'autoSubmit'   => (int) $this->get('levenshtein_autoSubmit'),
            ]
        ];

        $arrReturn[$paramName] =
            $this->prepareFrontendFilterWidget($arrWidget, $arrFilterUrl, $arrJumpTo, $objFrontendFilterOptions);

        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterDCA()
    {
        return [];
    }

    /**
     * Add Param to global filter params array.
     *
     * @param string $strParam Name of filter param.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function addFilterParam(string $strParam): void
    {
        $GLOBALS['MM_FILTER_PARAMS'][] = $strParam;
    }
}
