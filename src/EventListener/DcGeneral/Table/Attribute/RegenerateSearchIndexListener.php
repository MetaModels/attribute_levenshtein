<?php

/**
 * This file is part of MetaModels/attribute_levenshtein.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeLevenshteinBundle
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\EventListener\DcGeneral\Table\Attribute;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\GetReferrerEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Event\AbstractEnvironmentAwareEvent;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use Doctrine\DBAL\Connection;
use MetaModels\IFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This provides the attribute name options.
 */
class RegenerateSearchIndexListener extends AbstractListener
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

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
     * Regenerate the search index when 'rebuild_levensthein' action is called.
     *
     * @param ActionEvent $event The event.
     *
     * @return void
     */
    public function handle(ActionEvent $event)
    {
        if (!$this->wantToHandle($event)) {
            return;
        }

        $input     = $event->getEnvironment()->getInputProvider();
        $metaModel = $this->getMetaModel(ModelId::fromSerialized($input->getParameter('pid'))->getId());
        $attribute = $metaModel->getAttributeById(ModelId::fromSerialized($input->getParameter('id'))->getId());

        // FIXME: we prune the index hard here.
        $entries = $this->connection
            ->createQueryBuilder()
            ->select('id')
            ->from('tl_metamodel_levensthein')
            ->where('metamodel=:metamodel')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);

        $this->connection
            ->createQueryBuilder()
            ->delete('tl_metamodel_levensthein')
            ->where('metamodel=:metamodel')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->execute();
        if (!empty($entries)) {
            $this->connection
                ->createQueryBuilder()
                ->delete('tl_metamodel_levensthein_index')
                ->where('pid=:pid')
                ->setParameter('pid', $metaModel->get('id'))
                ->execute();

            $this->connection
                ->createQueryBuilder()
                ->delete('tl_metamodel_levensthein_index')
                ->where('pid IN(:pids)')
                ->setParameter('pids', array_fill(0, count($entries), $entries))
                ->execute();
        }

        $GLOBALS['WORDS'] = [];

        $languageBackup = $GLOBALS['TL_LANGUAGE'];
        foreach ($metaModel->getAvailableLanguages() as $language) {
            $GLOBALS['TL_LANGUAGE'] = $language;
            foreach ($metaModel->findByFilter(null) as $item) {
                $attribute->modelSaved($item);
            }
        }
        $GLOBALS['TL_LANGUAGE'] = $languageBackup;

        asort($GLOBALS['WORDS']);

        $refererEvent = new GetReferrerEvent(true, 'tl_metamodel_attribute');
        $this->dispatcher->dispatch(ContaoEvents::SYSTEM_GET_REFERRER, $refererEvent);

        $event->setResponse(
            sprintf(
                '<div id="tl_buttons">
    <a href="%1$s" class="header_back" title="%2$s" accesskey="b" onclick="Backend.getScrollOffset();">
        %2$s
    </a>
</div>
<div class="tl_listing_container levensthein_reindex">
            The search index now contains %3$d words.
</div>
',
                $refererEvent->getReferrerUrl(),
                $GLOBALS['TL_LANG']['MSC']['backBT'],
                count(array_values(array_unique($GLOBALS['WORDS'])))
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
    protected function wantToHandle(AbstractEnvironmentAwareEvent $event)
    {
        /** @var ActionEvent $event */
        return parent::wantToHandle($event)
               && 'rebuild_levensthein' === $event->getAction();
    }
}