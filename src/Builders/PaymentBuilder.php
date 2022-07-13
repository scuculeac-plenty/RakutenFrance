<?php

namespace RakutenFrance\Builders;

use DateTime;
use Plenty\Plugin\Log\Loggable;

class PaymentBuilder
{
    use Loggable;

    /** @var array */
    private $payment = [];

    public function __construct()
    {
    }

    /**
     * Add payment amount
     *
     * @param float $amount
     *
     * @return PaymentBuilder
     */
    public function withAmount(float $amount): PaymentBuilder
    {
        $this->payment['amount'] =  $amount;

        return $this;
    }

    /**
     * Add payment date
     *
     * @param string $date
     *
     * @return PaymentBuilder
     */
    public function withDate(string $date): PaymentBuilder
    {
        $time = DateTime::createFromFormat('j/m/Y-H:i', $date);
        $this->payment['receivedAt'] = $time->format(DateTime::W3C);
        $this->payment['importedAt'] = $time->format(DateTime::W3C);

        return $this;
    }

    /**
     * Add payment transaction type
     *
     * @param int $transactionType
     *
     * @return PaymentBuilder
     */
    public function withTransactionType(int $transactionType): PaymentBuilder
    {
        $this->payment['transactionType'] = $transactionType;

        return $this;
    }

    /**
     * Add payment currency
     *
     * @param string $currency
     *
     * @return PaymentBuilder
     */
    public function withCurrency(string $currency): PaymentBuilder
    {
        $this->payment['currency'] = $currency;
        return $this;
    }

    /**
     * Add method of pay
     *
     * @param int $mopId
     *
     * @return PaymentBuilder
     */
    public function withMethodOfPayment(int $mopId): PaymentBuilder
    {
        $this->payment['mopId'] = $mopId;

        return $this;
    }

    /**
     * Add payment origin
     *
     * @param int $originId
     *
     * @return PaymentBuilder
     */
    public function withOrigin(int $originId): PaymentBuilder
    {
        $this->payment['origin'] = $originId;

        return $this;
    }

    /**
     * Add payment property
     *
     * @param int $type
     * @param string $value
     *
     * @return PaymentBuilder
     */
    public function withPaymentProperty(int $type, string $value): PaymentBuilder
    {
        if ($this->payment['properties'] === null) {
            $this->payment['properties'] = [];
        }

        $option = [
            'typeId' => $type,
            'value'  => $value
        ];

        array_push($this->payment['properties'], $option);

        return $this;
    }

    /**
     * Add payment status
     *
     * @param int $statusId
     *
     * @return PaymentBuilder
     */
    public function withStatus(int $statusId): PaymentBuilder
    {
        $this->payment['status'] = $statusId;

        return $this;
    }

    /**
     * Return payment array
     *
     * @return array
     */
    public function done(): array
    {
        return $this->payment;
    }
}
