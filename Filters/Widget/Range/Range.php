<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\FilterManagerBundle\Filters\Widget\Range;

use ONGR\ElasticsearchBundle\DSL\Aggregation\StatsAggregation;
use ONGR\ElasticsearchBundle\DSL\Filter\RangeFilter;
use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\FilterManagerBundle\Filters\FilterState;
use ONGR\FilterManagerBundle\Filters\Helper\FieldAwareInterface;
use ONGR\FilterManagerBundle\Filters\Helper\FieldAwareTrait;
use ONGR\FilterManagerBundle\Filters\Helper\ViewDataFactoryInterface;
use ONGR\FilterManagerBundle\Filters\ViewData;
use ONGR\FilterManagerBundle\Filters\Widget\AbstractSingleRequestValueFilter;
use ONGR\FilterManagerBundle\Search\SearchRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Range filter, selects documents from lower limit to upper limit.
 */
class Range extends AbstractSingleRequestValueFilter implements FieldAwareInterface, ViewDataFactoryInterface
{
    use FieldAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getState(Request $request)
    {
        $state = parent::getState($request);

        if (!$state->isActive()) {
            return $state;
        }

        $values = explode(';', $state->getValue(), 2);

        if (count($values) < 2) {
            $state->setActive(false);

            return $state;
        }

        $normalized['gt'] = floatval($values[0]);
        $normalized['lt'] = floatval($values[1]);

        $state->setValue($normalized);

        return $state;
    }

    /**
     * {@inheritdoc}
     */
    public function modifySearch(Search $search, FilterState $state = null, SearchRequest $request = null)
    {
        if ($state && $state->isActive()) {
            $filter = new RangeFilter($this->getField(), $state->getValue());
            $search->addPostFilter($filter);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preProcessSearch(Search $search, Search $relatedSearch, FilterState $state = null)
    {
        $stateAgg = new StatsAggregation('range_agg');
        $stateAgg->setField($this->getField());
        $search->addAggregation($stateAgg);
    }

    /**
     * {@inheritdoc}
     */
    public function createViewData()
    {
        return new ViewData\RangeAwareViewData();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(DocumentIterator $result, ViewData $data)
    {
        /** @var $data ViewData\RangeAwareViewData */

        $data->setMinBounds($result->getAggregations()['range_agg']->getValue()['min']);
        $data->setMaxBounds($result->getAggregations()['range_agg']->getValue()['max']);

        return $data;
    }
}
