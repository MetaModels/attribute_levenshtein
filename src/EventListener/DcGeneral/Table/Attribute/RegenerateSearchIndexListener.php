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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\EventListener\DcGeneral\Table\Attribute;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\GetReferrerEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Event\AbstractEnvironmentAwareEvent;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use ContaoCommunityAlliance\DcGeneral\InputProviderInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttribute;
use MetaModels\Helper\LocaleUtil;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use MetaModels\ITranslatedMetaModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This provides the attribute name options.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegenerateSearchIndexListener extends AbstractListener
{
    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * Create a new instance.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The scope determinator.
     * @param IFactory                 $factory           The MetaModel factory.
     * @param Connection               $connection        The database connection.
     * @param EventDispatcherInterface $dispatcher        The event dispatcher.
     */
    public function __construct(
        RequestScopeDeterminator $scopeDeterminator,
        IFactory $factory,
        Connection $connection,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($scopeDeterminator, $factory, $connection);

        $this->dispatcher = $dispatcher;
    }

    /**
     * Regenerate the search index when 'rebuild_levenshtein' action is called.
     *
     * @param ActionEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function handle(ActionEvent $event)
    {
        if (!$this->wantToHandle($event)) {
            return;
        }

        $inputProvider = $event->getEnvironment()->getInputProvider();
        assert($inputProvider instanceof InputProviderInterface);
        $metaModel = $this->getMetaModel(ModelId::fromSerialized($inputProvider->getParameter('pid'))->getId());
        assert($metaModel instanceof IMetaModel);
        $attribute =
            $metaModel->getAttributeById((int) ModelId::fromSerialized($inputProvider->getParameter('id'))->getId());
        assert($attribute instanceof IAttribute);

        $entries = $this->connection
            ->createQueryBuilder()
            ->select('t.id')
            ->from('tl_metamodel_levensthein', 't')
            ->where('t.metamodel=:metamodel')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->executeQuery()
            ->fetchFirstColumn();

        $this->connection
            ->createQueryBuilder()
            ->delete('tl_metamodel_levensthein')
            ->where('tl_metamodel_levensthein.metamodel=:metamodel')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->executeQuery();

        if (!empty($entries)) {
            $this->connection
                ->createQueryBuilder()
                ->delete('tl_metamodel_levensthein_index')
                ->where('tl_metamodel_levensthein_index.pid IN(:pids)')
                ->setParameter('pids', $entries, ArrayParameterType::STRING)
                ->executeQuery();
        }

        if ($metaModel instanceof ITranslatedMetaModel) {
            $languageBackup = $metaModel->getLanguage();
            foreach ($metaModel->getLanguages() as $language) {
                $metaModel->selectLanguage($language);
                // @deprecated usage of TL_LANGUAGE - remove for Contao 5.0.
                $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($language);
                foreach ($metaModel->findByFilter(null) as $item) {
                    $attribute->modelSaved($item);
                }
            }
            $metaModel->selectLanguage($languageBackup);
        } else {
            // @deprecated usage of TL_LANGUAGE - remove for Contao 5.0.
            $languageBackup = $GLOBALS['TL_LANGUAGE'];
            /**
             * @psalm-suppress DeprecatedMethod
             * @psalm-suppress TooManyArguments
             */
            $languages = $metaModel->isTranslated(false)
                ? $metaModel->getAvailableLanguages()
                : [$languageBackup];
            foreach ($languages as $language) {
                // @deprecated usage of TL_LANGUAGE - remove for Contao 5.0.
                $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($language);
                foreach ($metaModel->findByFilter(null) as $item) {
                    $attribute->modelSaved($item);
                }
            }
            // @deprecated usage of TL_LANGUAGE - remove for Contao 5.0.
            $GLOBALS['TL_LANGUAGE'] = $languageBackup;
        }

        $count = $this->connection
            ->createQueryBuilder()
            ->select('COUNT(DISTINCT tx.word)')
            ->from('tl_metamodel_levensthein_index', 'tx')
            ->where('tx.pid IN (SELECT t.id FROM tl_metamodel_levensthein AS t WHERE t.metamodel=:metamodel)')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->executeQuery()
            ->fetchOne();

        $refererEvent = new GetReferrerEvent(true, 'tl_metamodel_attribute');
        $this->dispatcher->dispatch($refererEvent, ContaoEvents::SYSTEM_GET_REFERRER);

        $event->setResponse(
            \sprintf(
                '
<div id="tl_buttons">
    <a href="%1$s" class="header_back" title="%2$s" accesskey="b" onclick="Backend.getScrollOffset();">%2$s</a>
</div>
<div class="tl_listing_container levenshtein_reindex">
            The search index now contains %3$d words.
</div>
',
                $refererEvent->getReferrerUrl(),
                $GLOBALS['TL_LANG']['MSC']['backBT'],
                (string) $count
            )
        );
    }

    /**
     * Test if the event is for the correct table and in backend scope.
     *
     * @param AbstractEnvironmentAwareEvent $event The event to test.
     *
     * @return bool
     */
    protected function wantToHandle(AbstractEnvironmentAwareEvent $event): bool
    {
        /** @var ActionEvent $event */
        return parent::wantToHandle($event)
               && 'rebuild_levensthein' === $event->getAction()->getName();
    }
}
