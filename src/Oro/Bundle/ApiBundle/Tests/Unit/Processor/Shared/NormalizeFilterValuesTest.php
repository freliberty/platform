<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeFilterValues;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizeFilterValuesTest extends GetListProcessorTestCase
{
    /** @var NormalizeFilterValues */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeFilterValues($this->valueNormalizer);
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcess()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('id=1&label=test');
        $filterValues = new RestFilterValueAccessor($request);

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['1', 'integer', $this->context->getRequestType(), false, false, 1],
                    ['test', 'string', $this->context->getRequestType(), false, false, 'test'],
                ]
            );

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        $this->assertTrue(is_int($filterValues->get('id')->getValue()));
        $this->assertEquals(1, $filterValues->get('id')->getValue());
        $this->assertTrue(is_string($filterValues->get('label')->getValue()));
        $this->assertEquals('test', $filterValues->get('label')->getValue());

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessForInvalidDataType()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $filters->add('id', $integerFilter);

        $exception = new \UnexpectedValueException('invalid data type');

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('id=invalid');
        $filterValues = new RestFilterValueAccessor($request);

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('invalid', 'integer', $this->context->getRequestType(), false, false)
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        $this->assertEquals('invalid', $filterValues->get('id')->getValue());

        $this->assertEquals(
            [
                Error::createByException($exception)
                    ->setStatusCode(Response::HTTP_BAD_REQUEST)
                    ->setSource(ErrorSource::createByParameter('id'))
            ],
            $this->context->getErrors()
        );
    }
}
