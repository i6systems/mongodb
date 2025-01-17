<?php

namespace Doctrine\MongoDB\Aggregation\Stage;

use Doctrine\MongoDB\Aggregation\Builder;
use Doctrine\MongoDB\Aggregation\Expr;
use Doctrine\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $graphLookup stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
class GraphLookup extends Stage
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string|Expr|array
     */
    private $startWith;

    /**
     * @var string
     */
    private $connectFromField;

    /**
     * @var string
     */
    private $connectToField;

    /**
     * @var string
     */
    private $as;

    /**
     * @var int
     */
    private $maxDepth;

    /**
     * @var string
     */
    private $depthField;

    /**
     * @var Stage\GraphLookup\MatchStage
     */
    private $restrictSearchWithMatch;

    /**
     * Lookup constructor.
     *
     * @param Builder $builder
     * @param string $from Target collection for the $graphLookup operation to
     * search, recursively matching the connectFromField to the connectToField.
     */
    public function __construct(Builder $builder, $from)
    {
        parent::__construct($builder);

        $this->from($from);
        $this->restrictSearchWithMatch = $this->createMatchObject();
    }

    /**
     * @return GraphLookup\MatchStage
     */
    protected function createMatchObject()
    {
        return new Stage\GraphLookup\MatchStage($this->builder, $this);
    }

    /**
     * Target collection for the $graphLookup operation to search, recursively
     * matching the connectFromField to the connectToField.
     *
     * The from collection cannot be sharded and must be in the same database as
     * any other collections used in the operation.
     *
     * @param string $from
     *
     * @return $this
     */
    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Expression that specifies the value of the connectFromField with which to
     * start the recursive search.
     *
     * Optionally, startWith may be array of values, each of which is
     * individually followed through the traversal process.
     *
     * @param string|array|Expr $expression
     *
     * @return $this
     */
    public function startWith($expression)
    {
        $this->startWith = $expression;

        return $this;
    }

    /**
     * Field name whose value $graphLookup uses to recursively match against the
     * connectToField of other documents in the collection.
     *
     * Optionally, connectFromField may be an array of field names, each of
     * which is individually followed through the traversal process.
     *
     * @param string $connectFromField
     *
     * @return $this
     */
    public function connectFromField($connectFromField)
    {
        $this->connectFromField = $connectFromField;

        return $this;
    }

    /**
     * Field name in other documents against which to match the value of the
     * field specified by the connectFromField parameter.
     *
     * @param string $connectToField
     *
     * @return $this
     */
    public function connectToField($connectToField)
    {
        $this->connectToField = $connectToField;

        return $this;
    }

    /**
     * Name of the array field added to each output document.
     *
     * Contains the documents traversed in the $graphLookup stage to reach the
     * document.
     *
     * @param string $alias
     *
     * @return $this
     */
    public function alias($alias)
    {
        $this->as = $alias;

        return $this;
    }

    /**
     * Non-negative integral number specifying the maximum recursion depth.
     *
     * @param int $maxDepth
     *
     * @return $this
     */
    public function maxDepth($maxDepth)
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }

    /**
     * Name of the field to add to each traversed document in the search path.
     *
     * The value of this field is the recursion depth for the document,
     * represented as a NumberLong. Recursion depth value starts at zero, so the
     * first lookup corresponds to zero depth.
     *
     * @param string $depthField
     *
     * @return $this
     */
    public function depthField($depthField)
    {
        $this->depthField = $depthField;

        return $this;
    }

    /**
     * A document specifying additional conditions for the recursive search.
     *
     * @return GraphLookup\MatchStage
     */
    public function restrictSearchWithMatch()
    {
        return $this->restrictSearchWithMatch;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        $graphLookup = [
            'from' => $this->from,
            'startWith' => $this->convertExpression($this->startWith),
            'connectFromField' => $this->connectFromField,
            'connectToField' => $this->connectToField,
            'as' => $this->as,
            'restrictSearchWithMatch' => $this->restrictSearchWithMatch->getExpression(),
        ];

        foreach (['maxDepth', 'depthField'] as $field) {
            if ($this->$field === null) {
                continue;
            }

            $graphLookup[$field] = $this->$field;
        }

        return ['$graphLookup' => $graphLookup];
    }

    /**
     * Converts an expression object into an array, recursing into nested items
     *
     * This method is meant to be overwritten by extending classes to apply
     * custom conversions (e.g. field name translation in MongoDB ODM) to the
     * expression object.
     *
     * @param mixed|self $expression
     * @return string|array
     */
    protected function convertExpression($expression)
    {
        return Expr::convertExpression($expression);
    }
}
