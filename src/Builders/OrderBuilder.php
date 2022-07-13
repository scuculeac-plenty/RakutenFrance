<?php

namespace RakutenFrance\Builders;

use Plenty\Plugin\Application;

class OrderBuilder
{
    /** @var array */
    private $order = [];

    /**
     * Assign plenty ID and Order type
     *
     * @param int $type
     * @param int $plentyId
     *
     * @return OrderBuilder
     */
    public function prepare(int $type, int $plentyId): OrderBuilder
    {
        $this->order['typeId'] = $type;
        $this->order['plentyId'] = $plentyId;

        return $this;
    }

    /**
     * Add order item lines
     *
     * @param array $orderItems
     *
     * @return OrderBuilder
     */
    public function withOrderItems(array $orderItems): OrderBuilder
    {
        foreach ($orderItems as $orderItem) {
            $this->withOrderItem($orderItem);
        }

        return $this;
    }

    /**
     * Add order item line
     *
     * @param array $orderItem
     *
     * @return OrderBuilder
     */
    public function withOrderItem(array $orderItem): OrderBuilder
    {
        if ($this->order['orderItems'] === null) {
            $this->order['orderItems'] = [];
        }

        array_push($this->order['orderItems'], $orderItem);

        return $this;
    }


    /**
     * Add address relation
     *
     * @param int $addressId
     * @param int $type
     *
     * @return OrderBuilder
     */
    public function withAddressId(int $addressId, int $type): OrderBuilder
    {
        if ($this->order['addressRelations'] === null) {
            $this->order['addressRelations'] = [];
        }

        $address = [
            'typeId'    => $type,
            'addressId' => $addressId
        ];

        array_push($this->order['addressRelations'], $address);

        return $this;
    }

    /**
     * Add contact relation
     *
     * @param string $type
     * @param int $referenceId
     * @param string $relationType
     *
     * @return OrderBuilder
     */
    public function withRelation(string $type, int $referenceId, string $relationType): OrderBuilder
    {
        if ($this->order['relations'] === null) {
            $this->order['relations'] = [];
        }

        $relation = [
            'referenceType' => $type,
            'relation'      => $relationType,
            'referenceId'   => $referenceId
        ];

        array_push($this->order['relations'], $relation);

        return $this;
    }

    /**
     * Add property
     *
     * @param int $type
     * @param string $value
     *
     * @return OrderBuilder
     */
    public function withOrderProperty(int $type, string $value): OrderBuilder
    {
        if ($this->order['properties'] === null) {
            $this->order['properties'] = [];
        }

        $option =
        [
            'typeId' => $type,
            'value'  => $value
        ];

        array_push($this->order['properties'], $option);

        return $this;
    }

    /**
     * Return generated order array
     *
     * @return array
     */
    public function done(): array
    {
        return $this->order;
    }
}
