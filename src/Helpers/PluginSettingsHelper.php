<?php

namespace RakutenFrance\Helpers;

use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Modules\Wizard\Contracts\WizardDataRepositoryContract;
use Plenty\Plugin\Application;
use RakutenFrance\Assistant\AssistantWizard;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class PluginSettingsHelper
 *
 * @package RakutenFrance\Helpers
 */
class PluginSettingsHelper
{
    const USERNAME = AssistantWizard::VALUE_RAKUTEN_USERNAME;
    const TOKEN = AssistantWizard::VALUE_RAKUTEN_TOKEN;
    const PROFILE_ID = AssistantWizard::VALUE_RAKUTEN_PROFILE_ID;
    const CANCEL_TEXT = AssistantWizard::VALUE_CANCELLATION_TEXT;
    const PLENTY_ACCOUNT_ID = AssistantWizard::VALUE_PLENTY_ACCOUNT_ID;
    const PROFILE_STOCK_ID = AssistantWizard::VALUE_RAKUTEN_PROFILE_STOCK_ID;
    const APPLICATION_ID = AssistantWizard::VALUE_RAKUTEN_PLENTY_ID;
    const REFERRER_ID = 'referrerId';
    const METHOD_OF_PAYMENT_ID = 'methodOfPayment';

    const JOB_SYNCHRONIZE_MARKETPLACE_ORDERS = AssistantWizard::VALUE_JOB_SYNCHRONIZE_MARKETPLACE_ORDERS;
    const JOB_SYNCHRONIZE_STOCK_WITH_MARKETPLACE = AssistantWizard::VALUE_JOB_SYNCHRONIZE_STOCK_WITH_MARKETPLACE;
    const JOB_SYNCHRONIZE_MARKETPLACE_ORDER_STATUSES = AssistantWizard::VALUE_JOB_SYNCHRONIZE_MARKETPLACE_ORDER_STATUSES;
    const JOB_SYNCHRONIZE_FEED_TO_MARKETPLACE = AssistantWizard::VALUE_JOB_SYNCHRONIZE_FEED_TO_MARKETPLACE;
    const JOB_SYNCHRONIZE_EAN_MATCHING_FILE = AssistantWizard::VALUE_JOB_SYNCHRONIZE_EAN_MATCHING_FILE;

    private $settings;
    private $orderReferrerRepositoryContract;
    private $paymentMethodRepositoryContract;

    public function __construct(
        WizardDataRepositoryContract $wizardDataRepositoryContract,
        OrderReferrerRepositoryContract $orderReferrerRepositoryContract,
        PaymentMethodRepositoryContract $paymentMethodRepositoryContract
    ) {
        $this->settings = $wizardDataRepositoryContract->findByWizardKey(AssistantWizard::ASSISTANT_KEY)->data->default;
        $this->orderReferrerRepositoryContract = $orderReferrerRepositoryContract;
        $this->paymentMethodRepositoryContract = $paymentMethodRepositoryContract;
    }

    /**
     * Plugin settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        $this->settings['referrerId'] = $this->getReferrerId(PluginConfiguration::REFERRER_NAME);
        $this->settings['methodOfPayment'] = $this->getMethodOfPaymentId(PluginConfiguration::PLUGIN_NAME);

        return $this->settings;
    }

    /**
     * @param string $referrerName
     *
     * @return float
     */
    private function getReferrerId(string $referrerName): float
    {
        $listsOfReferrers = $this->orderReferrerRepositoryContract->getList(['backendName', 'id']);

        foreach ($listsOfReferrers as $referrer) {
            if ($referrer['backendName'] == $referrerName) {
                return $referrer['id'];
            }
        }

        return 0.0;
    }

    /**
     * @param string $paymentKey
     *
     * @return int
     */
    private function getMethodOfPaymentId(string $paymentKey): int
    {
        $paymentMethods = $this->paymentMethodRepositoryContract->allForPlugin(PluginConfiguration::PLUGIN_KEY);

        /** @var PaymentMethod $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->paymentKey === $paymentKey) {
                return $paymentMethod->id;
            }
        }

        return 0;
    }
}
