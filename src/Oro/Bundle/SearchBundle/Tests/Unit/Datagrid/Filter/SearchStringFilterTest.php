<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Doctrine\Common\Collections\Expr\Comparison as DoctrineComparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;

class SearchStringFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchStringFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /* @var $formFactory FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
        $formFactory = $this->createMock(FormFactoryInterface::class);
        /* @var $filterUtility FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
        $filterUtility = $this->createMock(FilterUtility::class);

        $this->filter = new SearchStringFilter($formFactory, $filterUtility);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->filter->apply($ds, ['type' => TextFilterType::TYPE_EQUAL, 'value' => 'bar']);
    }

    /**
     * @param string $filterType
     * @param string $comparisonOperator
     * @param array  $filterParams
     * @dataProvider applyDataProvider
     */
    public function testApply($filterType, $comparisonOperator, array $filterParams = [])
    {
        $fieldName = 'field';
        $fieldValue = 'value';

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($this->isInstanceOf('Doctrine\Common\Collections\Expr\Comparison'), FilterUtility::CONDITION_AND)
            ->willReturnCallback(
                function (DoctrineComparison $comparison) use ($fieldName, $comparisonOperator, $fieldValue) {
                    $this->assertEquals($fieldName, $comparison->getField());
                    $this->assertEquals($comparisonOperator, $comparison->getOperator());
                    $this->assertEquals($fieldValue, $comparison->getValue()->getValue());
                }
            );

        $this->filter->init('test', array_merge([
            FilterUtility::FORCE_LIKE_KEY => false,
            FilterUtility::MIN_LENGTH_KEY => 0,
            FilterUtility::DATA_NAME_KEY => $fieldName
        ], $filterParams));
        $this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]);
    }

    /**
     * @param string $fieldValue
     * @param array  $filterParams
     * @dataProvider applyWithMinLengthViolatedDataProvider
     */
    public function testApplyWithMinLengthViolated($fieldValue, array $filterParams = [])
    {
        $filterType = 'anyCustomFilterType';
        $fieldName = 'field';

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ds->expects($this->never())->method('addRestriction');

        $this->filter->init('test', array_merge([
            FilterUtility::FORCE_LIKE_KEY => false,
            FilterUtility::MIN_LENGTH_KEY => 0,
            FilterUtility::DATA_NAME_KEY => $fieldName
        ], $filterParams));
        $this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]);
    }

    /**
     * @return array
     */
    public function applyDataProvider()
    {
        return [
            'contains' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::CONTAINS,
                [
                    FilterUtility::FORCE_LIKE_KEY => false,
                ]
            ],
            'contains force like' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::LIKE,
                [
                    FilterUtility::FORCE_LIKE_KEY => true,
                ]
            ],
            'contains like min_length' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::LIKE,
                [
                    FilterUtility::FORCE_LIKE_KEY => true,
                    FilterUtility::MIN_LENGTH_KEY => 5,
                ]
            ],
            'not contains' => [
                'filterType' => TextFilterType::TYPE_NOT_CONTAINS,
                'comparisonOperator' => Comparison::NOT_CONTAINS,
            ],
            'equal' => [
                'filterType' => TextFilterType::TYPE_EQUAL,
                'comparisonOperator' => Comparison::EQ,
            ],
        ];
    }

    /**
     * @return array
     */
    public function applyWithMinLengthViolatedDataProvider()
    {
        return [
            ['abc', [FilterUtility::MIN_LENGTH_KEY => 4]],
        ];
    }
}
