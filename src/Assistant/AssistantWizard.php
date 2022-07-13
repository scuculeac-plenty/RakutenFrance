<?php

namespace RakutenFrance\Assistant;

use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\System\Models\Webstore;
use Plenty\Modules\User\Contracts\UserRepositoryContract;
use Plenty\Modules\User\Models\User;
use Plenty\Modules\Wizard\Services\WizardProvider;
use RakutenFrance\Assistant\Handlers\AssistantWizardHandler;
use RakutenFrance\Catalogue\Helpers\CatalogPropertySelectionHelper;
use RakutenFrance\Configuration\PluginConfiguration;

class AssistantWizard extends WizardProvider
{
    //SETTINGS
    const ASSISTANT_KEY = 'rakuten_france_assistant';

    //Credentials
    const VALUE_RAKUTEN_USERNAME = 'rakutenUsername';
    const VALUE_RAKUTEN_TOKEN = 'rakutenToken';
    const VALUE_RAKUTEN_PROFILE_ID = 'rakutenProfileId';
    const VALUE_RAKUTEN_PROFILE_STOCK_ID = 'rakutenProfileStockId';
    const VALUE_RAKUTEN_PLENTY_ID = 'plentyId';

    //Order settings
    const VALUE_CANCELLATION_TEXT = 'defaultCancellationText';

    //Plenty settings for jobs and account information
    const VALUE_PLENTY_ACCOUNT_ID = 'plentyAccountId';
    const VALUE_JOB_SYNCHRONIZE_MARKETPLACE_ORDERS = 'synchronizeMarketplaceOrders';
    const VALUE_JOB_SYNCHRONIZE_STOCK_WITH_MARKETPLACE = 'synchronizeStockWithMarketplace';
    const VALUE_JOB_SYNCHRONIZE_MARKETPLACE_ORDER_STATUSES = 'synchronizeMarketplaceOrderStatuses';
    const VALUE_JOB_SYNCHRONIZE_FEED_TO_MARKETPLACE = 'synchronizeFeedToMarketplace';
    const VALUE_JOB_SYNCHRONIZE_EAN_MATCHING_FILE = 'synchronizeEanMatchingFile';

    private $webstoreRepository;
    private $userRepositoryContract;
    private $webstoreValues;
    private $mainWebstore;

    public function __construct(
        WebstoreRepositoryContract $webstoreRepository,
        UserRepositoryContract $userRepositoryContract
    ) {
        $this->webstoreRepository = $webstoreRepository;
        $this->userRepositoryContract = $userRepositoryContract;
    }

    protected function structure(): array
    {
        return [
            'key' => self::ASSISTANT_KEY,
            'translationNamespace' => PluginConfiguration::PLUGIN_NAME,
            'iconPath' => '',
            'title' => 'assistant.settings',
            'topics' => [],
            'settingsHandlerClass' => AssistantWizardHandler::class,
            'createOptionIdTitle' => 'assistant.createOptionIdTitle',
            'createOptionIdCardLabel' => 'assistant.createOptionIdCardLabel',
            'priority' => 500,
            'shortDescription' => 'assistant.shortDescription',
            'relevance' => 'essential',
            'keywords' => ['RakutenFrance', 'marketplace'],
            'steps' => [
                'step1' => [
                    'title' => 'assistant.step1Title',
                    'description' => 'assistant.step1Description',
                    'sections' => [
                        [
                            'title' => 'assistant.step1Section1Title',
                            'description' => 'assistant.step1Section1Description',
                            'form' => [
                                'rakutenUsername' => [
                                    'type' => 'text',
                                    'options' => [
                                        'name' => 'assistant.rakutenUsername',
                                        'required' => true,
                                    ],
                                ],
                                'rakutenToken' => [
                                    'type' => 'text',
                                    'options' => [
                                        'name' => 'assistant.rakutenToken',
                                        'isPassword' => true,
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'step2' => [
                    'title' => 'assistant.step2Title',
                    'description' => 'assistant.step2Description',
                    'sections' => [
                        [
                            'title' => 'assistant.step2Section1Title',
                            'description' => 'assistant.step2Section1Description',
                            'form' => [
                                'defaultCancellationText' => [
                                    'type' => 'text',
                                    'options' => [
                                        'name' => 'assistant.defaultCancellationText',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => 'assistant.step2Section2Title',
                            'description' => 'assistant.step2Section2Description',
                            'form' => [
                                'rakutenProfileId' => [
                                    'type' => 'number',
                                    'options' => [
                                        'name' => 'assistant.rakutenProfileId',
                                        'required' => false,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => 'assistant.step2Section3Title',
                            'description' => 'assistant.step2Section3Description',
                            'form' => [
                                'rakutenProfileStockId' => [
                                    'type' => 'number',
                                    'options' => [
                                        'name' => 'assistant.rakutenProfileStockId',
                                        'required' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'step3' => [
                    'title' => 'assistant.step3Title',
                    'description' => 'assistant.step3Description',
                    'sections' => [
                        [
                            'title' => 'assistant.step3Section1Title',
                            'description' => 'assistant.step3Section1Description',
                            'form' => [
                                'plentyAccountId' => [
                                    'type' => 'select',
                                    'options' => [
                                        'name' => 'assistant.plentyAccountId',
                                        'listBoxValues' => $this->getUserList(),
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => 'assistant.step3Section2Title',
                            'description' => 'assistant.step3Section2Description',
                            'form' => [
                                'synchronizeMarketplaceOrders' => [
                                    'type' => 'toggle',
                                    'options' => [
                                        'name' => 'assistant.synchronizeMarketplaceOrders',
                                    ],
                                ],
                                'synchronizeStockWithMarketplace' => [
                                    'type' => 'toggle',
                                    'options' => [
                                        'name' => 'assistant.synchronizeStockWithMarketplace',
                                    ],
                                ],
                                'synchronizeMarketplaceOrderStatuses' => [
                                    'type' => 'toggle',
                                    'options' => [
                                        'name' => 'assistant.synchronizeMarketplaceOrderStatuses',
                                    ],
                                ],
                                'synchronizeFeedToMarketplace' => [
                                    'type' => 'toggle',
                                    'options' => [
                                        'name' => 'assistant.synchronizeFeedToMarketplace',
                                    ],
                                ],
                                'synchronizeEanMatchingFile' => [
                                    'type' => 'toggle',
                                    'options' => [
                                        'name' => 'assistant.synchronizeEanMatchingFile',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'step4' => [
                    'title' => 'assistant.step4Title',
                    'description' => 'assistant.step4Description',
                    'sections' => [
                        [
                            'title' => 'assistant.step4Section1Title',
                            'description' => 'assistant.step4Section1Description',
                            'form' => [
                                'plentyId' => [
                                    'type' => 'select',
                                    'defaultValue' => $this->getMainWebstore(),
                                    'options' => [
                                        'name' => 'assistant.storeName',
                                        'required' => true,
                                        'listBoxValues' => $this->getWebstoreListForm(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getUserList(): array
    {
        $users = $this->userRepositoryContract->getAll();
        $userList = [];
        /** @var User $user */
        foreach ($users as $user) {
            $userList[] = [
                'caption' => $user->user,
                'value' => $user->id,
            ];
        }

        return $userList;
    }

    private function getMainWebstore()
    {
        if ($this->mainWebstore === null) {
            $this->mainWebstore = $this->webstoreRepository->findById(0)->storeIdentifier;
        }

        return $this->mainWebstore;
    }

    private function getWebstoreListForm(): array
    {
        if ($this->webstoreValues === null) {
            $webstores = $this->webstoreRepository->loadAll();
            /** @var Webstore $webstore */
            foreach ($webstores as $webstore) {
                $this->webstoreValues[] = [
                    'caption' => $webstore->name,
                    'value' => $webstore->storeIdentifier,
                ];
            }

            usort($this->webstoreValues, function ($a, $b) {
                return $a['value'] <=> $b['value'];
            });
        }

        return $this->webstoreValues;
    }
}
