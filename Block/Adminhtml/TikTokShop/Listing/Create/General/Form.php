<?php

namespace M2E\TikTokShop\Block\Adminhtml\TikTokShop\Listing\Create\General;

use M2E\TikTokShop\Block\Adminhtml\StoreSwitcher;
use M2E\TikTokShop\Model\Listing;

class Form extends \M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \M2E\TikTokShop\Helper\Magento\Store $storeHelper;
    private \M2E\TikTokShop\Model\Shop\Repository $shopRepository;
    protected Listing $listing;
    private \M2E\TikTokShop\Helper\Data $dataHelper;
    private \M2E\TikTokShop\Helper\Data\Session $sessionDataHelper;
    private \M2E\TikTokShop\Model\Account\Repository $accountRepository;
    /** @var \M2E\TikTokShop\Model\Listing\Repository */
    private Listing\Repository $listingRepository;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Repository $listingRepository,
        \M2E\TikTokShop\Model\Account\Repository $accountRepository,
        \M2E\TikTokShop\Helper\Magento\Store $storeHelper,
        \M2E\TikTokShop\Model\Shop\Repository $shopRepository,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \M2E\TikTokShop\Helper\Data $dataHelper,
        \M2E\TikTokShop\Helper\Data\Session $sessionDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->storeHelper = $storeHelper;
        $this->shopRepository = $shopRepository;
        $this->dataHelper = $dataHelper;
        $this->sessionDataHelper = $sessionDataHelper;
        $this->accountRepository = $accountRepository;
        $this->listingRepository = $listingRepository;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => 'javascript:void(0)',
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $title = $this->getTitle();
        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'value' => $title,
                'required' => true,
                'class' => 'TikTokShop-listing-title',
                'tooltip' => __('Create a descriptive and meaningful Title for your M2E TikTok Shop Connect ' .
                    'Listing. <br/> This is used for reference within M2E TikTok Shop Connect and will not appear on ' .
                    'your TikTok Shop Listings.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'tiktokshop_settings_fieldset',
            [
                'legend' => __('TikTok Shop Settings'),
                'collapsable' => false,
            ]
        );

        $accountsData = $this->getAccountData();
        if ($accountsData['select_account_is_disabled']) {
            $fieldset->addField(
                'account_id_hidden',
                'hidden',
                [
                    'name' => 'account_id',
                    'value' => $accountsData['active_account_id'],
                ]
            );
        }

        $accountSelect = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'html_id' => 'account_id',
                    'name' => 'account_id',
                    'style' => 'width: 50%;',
                    'value' => $accountsData['active_account_id'],
                    'values' => $accountsData['accounts'],
                    'required' => $accountsData['is_required'],
                    'disabled' => $accountsData['select_account_is_disabled'],
                ],
            ]
        );
        $accountSelect->setForm($form);

        $isAddAccountButtonHidden = $this->getRequest()->getParam('wizard', false)
            || $accountsData['select_account_is_disabled'];

        $addAnotherAccountButton = $this
            ->getLayout()
            ->createBlock(
                \M2E\TikTokShop\Block\Adminhtml\Magento\Button\SplitButton::class
            );

        $addAnotherAccountButton->setData([
            'id' => 'add_account_button',
            'label' => __('Add Another'),
            'style' => 'pointer-events: none',
            'class' => 'primary',
            'class_name' => \M2E\TikTokShop\Block\Adminhtml\Magento\Button\SplitButton::class,
            'options' => [
                'gb' => [
                    'label' => __('United Kingdom'),
                    'id' => 'gb',
                    'data_attribute' => [
                        'add-account-btn' => true,
                        'url' =>
                            $this->getUrl(
                                '*/tiktokshop_account/beforeGetToken',
                                ['_current' => true, 'region' => \M2E\TikTokShop\Model\Shop::REGION_GB]
                            ),
                    ],
                ],
                'us' => [
                    'label' => __('United States'),
                    'id' => 'us',
                    'data_attribute' => [
                        'add-account-btn' => true,
                        'url' =>
                            $this->getUrl(
                                '*/tiktokshop_account/beforeGetToken',
                                ['_current' => true, 'region' => \M2E\TikTokShop\Model\Shop::REGION_US]
                            ),
                    ],
                ],
                'es' => [
                    'label' => __('Spain'),
                    'id' => 'es',
                    'data_attribute' => [
                        'add-account-btn' => true,
                        'url' =>
                            $this->getUrl(
                                '*/tiktokshop_account/beforeGetToken',
                                ['_current' => true, 'region' => \M2E\TikTokShop\Model\Shop::REGION_ES]
                            ),
                    ],
                ],
            ],
        ]);

        $fieldset->addField(
            'account_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => __('Account'),
                'style' => 'line-height: 32px; display: initial;',
                'required' => $accountsData['is_required'],
                'text' => <<<HTML
    <span id="account_label"></span>
    {$accountSelect->toHtml()}
HTML
                ,
                'after_element_html' => sprintf(
                    '<div style="margin-left:5px; display: inline-block; position:absolute;%s">%s</div>',
                    $isAddAccountButtonHidden ? 'display: none;' : '',
                    $addAnotherAccountButton->toHtml()
                ),
            ]
        );

        $shopData = $this->getShopsData((int)$accountsData['active_account_id']);
        $fieldset->addField(
            'shop_id',
            self::SELECT,
            [
                'name' => 'shop_id',
                'label' => __('Shop'),
                'value' => $shopData['active_shop_id'],
                'values' => $shopData['shops'],
                'tooltip' => __('Choose the Shop you want to list on using this M2E TikTok Shop Connect ' .
                    'Listing. Currency will be set automatically based on the Shop you choose.'),
                'field_extra_attributes' => 'style="margin-bottom: 0px"',
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_fieldset',
            [
                'legend' => __('Magento Settings'),
                'collapsable' => false,
            ]
        );

        $storeId = $this->getSessionData('store_id') ?? $this->storeHelper->getDefaultStoreId();
        $fieldset->addField(
            'store_id',
            self::STORE_SWITCHER,
            [
                'name' => 'store_id',
                'label' => __('Magento Store View'),
                'value' => $storeId,
                'required' => true,
                'has_empty_option' => true,
                'tooltip' => __('Choose the Magento Store View you want to use for this M2E TikTok Shop Connect ' .
                    'Listing. Please remember that Attribute values from the selected Store View will ' .
                    'be used in the Listing.'),
                'display_default_store_mode' => StoreSwitcher::DISPLAY_DEFAULT_STORE_MODE_DOWN,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function getTitle(): string
    {
        if ($fromSession = $this->getSessionData('title')) {
            return $fromSession;
        }

        return $this->listingRepository->getListingsCount() === 0
            ? (string)__('Default')
            : '';
    }

    /**
     * @return array{
     *     account_is_disabled: bool,
     *     is_required: bool,
     *     active_account_id: int,
     *     accounts: array
     * }
     */
    private function getAccountData(): array
    {
        $accounts = $this->accountRepository->getAll();

        if ($accounts === []) {
            return [
                'select_account_is_disabled' => false,
                'is_required' => 0,
                'active_account_id' => 0,
                'accounts' => [],
            ];
        }

        $data = [
            'select_account_is_disabled' => false,
            'is_required' => count($accounts) > 1,
            'active_account_id' => reset($accounts)->getId(),
            'accounts' => array_map(
                static function (\M2E\TikTokShop\Model\Account $account) {
                    return [
                        'value' => $account->getId(),
                        'label' => $account->getTitle(),
                    ];
                },
                $accounts
            ),
        ];

        if ($sessionAccountId = $this->getSessionData('account_id')) {
            $data['active_account_id'] = $sessionAccountId;

            return $data;
        }

        if ($requestAccountId = $this->getRequest()->getParam('account_id')) {
            $data['select_account_is_disabled'] = true;
            $data['active_account_id'] = (int)$requestAccountId;
        }

        return $data;
    }

    /**
     * @return array{
     *    active_shop_id: int,
     *    shops: array
     * }
     */
    private function getShopsData(int $accountId): array
    {
        $shops = $this->getShops($accountId);

        if ($shops === []) {
            return [
                'active_shop_id' => 0,
                'shops' => [],
            ];
        }

        $data = [
            'active_shop_id' => reset($shops)['value'],
            'shops' => $shops,
        ];

        if ($sessionShopId = $this->getSessionData('shop_id')) {
            $data['active_shop_id'] = $sessionShopId;
        }

        return $data;
    }

    private function getShops(int $accountId): array
    {
        $shops = [];
        $entities = $this->shopRepository->findForAccount($accountId);
        foreach ($entities as $entity) {
            $shops[$entity->getId()] = [
                'label' => $entity->getShopName(),
                'value' => $entity->getId(),
            ];
        }

        return $shops;
    }

    protected function _prepareLayout()
    {
        $this->jsPhp->addConstants(
            \M2E\TikTokShop\Helper\Data::getClassConstants(\M2E\TikTokShop\Helper\Component\TikTokShop::class)
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('TikTokShop\Account'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('TikTokShop\Shop'));

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('TikTokShop_Listing_Create', ['_current' => true])
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/tiktokshop_account/beforeGetToken',
                [
                    'close_on_save' => true,
                    'wizard' => (bool)$this->getRequest()->getParam('wizard', false),
                ]
            ),
            'tiktokshop_account/newAction'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/tiktokshop_synchronization_log/index',
                [
                    'wizard' => (bool)$this->getRequest()->getParam('wizard', false),
                ]
            ),
            'logViewUrl'
        );

        $this->jsTranslator->addTranslations(
            [
                'The specified Title is already used for other Listing. Listing Title must be unique.'
                => __(
                    'The specified Title is already used for other Listing. Listing Title must be unique.'
                ),
                'Account not found, please create it.'
                => __('Account not found, please create it.'),
                'Add Another' => __('Add Another'),
                'Please wait while Synchronization is finished.'
                => __('Please wait while Synchronization is finished.'),
            ]
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'TikTokShop/TikTokShop/Listing/Create/General'
    ], function(){
        TikTokShop.formData.wizard = {$this->getRequest()->getParam('wizard', 0)};

        window.TikTokShopListingCreateGeneralObj = new TikTokShopListingCreateGeneral();
    });
JS
        );

        return parent::_prepareLayout();
    }

    private function getSessionData(string $key): ?string
    {
        $sessionData = $this->sessionDataHelper->getValue(Listing::CREATE_LISTING_SESSION_DATA);

        return $sessionData[$key] ?? null;
    }
}