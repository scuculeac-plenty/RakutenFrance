<?php

namespace RakutenFrance\Services;

use Exception;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Order\Models\Order;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Payment\Models\Payment;
use RakutenFrance\Builders\PaymentBuilder;
use Plenty\Modules\Payment\Models\PaymentProperty;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Payment\Models\PaymentOrderRelation;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;

class PaymentService
{
    use Loggable;

    private $paymentRepository;
    private $authHelper;
    private $paymentOrderRelationRepositoryContract;

    public function __construct(
        PaymentRepositoryContract $paymentRepository,
        AuthHelper $authHelper,
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepositoryContract
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->authHelper = $authHelper;
        $this->paymentOrderRelationRepositoryContract = $paymentOrderRelationRepositoryContract;
    }

    /**
     * Create plentymarkets payment
     *
     * @param float  $amount
     * @param string $date
     * @param string $transactionId
     * @param string $currency
     * @param int    $mop
     *
     * @return Payment|null
     */
    public function placePayment(float $amount, string $date, string $transactionId, string $currency, int $mop)
    {
        try {
            /** @var PaymentBuilder $paymentBuilder */
            $paymentBuilder = pluginApp(PaymentBuilder::class);

            $paymentBuilder = $paymentBuilder
                ->withAmount($amount)
                ->withDate($date)
                ->withTransactionType(Payment::TRANSACTION_TYPE_PROVISIONAL_POSTING)
                ->withCurrency($currency)
                ->withOrigin(Payment::ORIGIN_PLUGIN)
                ->withStatus(Payment::STATUS_APPROVED)
                ->withMethodOfPayment($mop)
                ->withPaymentProperty(PaymentProperty::TYPE_TRANSACTION_ID, $transactionId)
                ->withPaymentProperty(PaymentProperty::TYPE_BOOKING_TEXT, 'Purchase ID: '.$transactionId);

            return $this->authHelper->processUnguarded(
                function () use ($paymentBuilder) {
                    return $this->paymentRepository->createPayment($paymentBuilder->done());
                }
            );
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME.'::log.exception', [
                'error' => $e->getMessageBag(),
            ]);
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME.'::log.exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add payment and order relation.
     *
     * @param Payment $payment
     * @param Order   $order
     *
     * @return PaymentOrderRelation|null
     */
    public function addPaymentOrderRelation(Payment $payment, Order $order)
    {
        try {
            return $this->authHelper->processUnguarded(
                function () use ($payment, $order) {
                    return $this->paymentOrderRelationRepositoryContract->createOrderRelation($payment, $order);
                }
            );
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME.'::log.exception', [
                'error' => $e->getMessageBag(),
            ]);
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME.'::log.exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
