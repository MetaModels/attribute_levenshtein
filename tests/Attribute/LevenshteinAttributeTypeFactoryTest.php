<?php

/**
 * This file is part of MetaModels/attribute_levensthein.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_levensthein
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\AttributeLevenshteinBundle\Attribute\AttributeLevenshtein;
use MetaModels\AttributeLevenshteinBundle\Attribute\LevenshteinAttributeTypeFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\AttributeLevenshteinBundle\Attribute\LevenshteinAttributeTypeFactory
 */
class LevenshteinAttributeTypeFactoryTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel|MockObject
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder(IMetaModel::class)->getMockForAbstractClass();

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn($tableName);

        $metaModel
            ->expects(self::any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects(self::any())
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Override the method to run the tests on the attribute factories to be tested.
     *
     * @return IAttributeTypeFactory[]
     */
    protected function getAttributeFactories()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        return [new LevenshteinAttributeTypeFactory($connection, $manipulator)];
    }

    /**
     * Test creation of a decimal attribute.
     *
     * @return void
     */
    public function testCreateSelect()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $factory   = new LevenshteinAttributeTypeFactory($connection, $manipulator);
        $values    = [
        ];
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        self::assertInstanceOf(AttributeLevenshtein::class, $attribute);

        foreach ($values as $key => $value) {
            self::assertEquals($value, $attribute->get($key), $key);
        }
    }
}
