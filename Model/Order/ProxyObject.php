<?php

namespace M2E\TikTokShop\Model\Order;

use M2E\TikTokShop\Model\Magento\Payment as TikTokShopPayment;

class ProxyObject
{
    public const CHECKOUT_GUEST = 'guest';
    public const CHECKOUT_REGISTER = 'register';

    public const USER_ID_ATTRIBUTE_CODE = 'tiktok_user_id';

    protected \M2E\TikTokShop\Model\Currency $currency;
    protected TikTokShopPayment $payment;
    protected \M2E\TikTokShop\Model\Order $order;
    protected \Magento\Customer\Model\CustomerFactory $customerFactory;
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    /** @var \M2E\TikTokShop\Model\Order\Item\ProxyObject[] */
    protected ?array $items = null;
    protected \Magento\Store\Api\Data\StoreInterface $store;
    protected array $addressData = [];

    private UserInfoFactory $userInfoFactory;
    protected \Magento\Tax\Model\Calculation $taxCalculation;
    private \M2E\Core\Model\Magento\CustomerFactory $magentoCustomerFactory;
    private \M2E\TikTokShop\Model\Config\Manager $config;
    private \Magento\Customer\Helper\Address $addressHelper;

    public function __construct(
        \M2E\TikTokShop\Model\Order $order,
        \M2E\TikTokShop\Model\Config\Manager $config,
        \M2E\Core\Model\Magento\CustomerFactory $magentoCustomerFactory,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \M2E\TikTokShop\Model\Currency $currency,
        TikTokShopPayment $payment,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \M2E\TikTokShop\Model\Order\UserInfoFactory $userInfoFactory,
        \Magento\Customer\Helper\Address $addressHelper
    ) {
        $this->order = $order;
        $this->config = $config;
        $this->currency = $currency;
        $this->payment = $payment;
        $this->userInfoFactory = $userInfoFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->taxCalculation = $taxCalculation;
        $this->magentoCustomerFactory = $magentoCustomerFactory;
        $this->addressHelper = $addressHelper;
    }

    public function createUserInfoFromRawName(string $rawName): UserInfo
    {
        return $this->userInfoFactory->create($rawName, $this->getStore());
    }

    /**
     * @return \M2E\TikTokShop\Model\Order\Item\ProxyObject[]
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getItems(): array
    {
        if ($this->items === null) {
            $items = [];

            foreach ($this->order->getItems() as $item) {
                $proxyItem = $item->getProxy();
                if ($proxyItem->getQty() <= 0) {
                    continue;
                }

                $items[] = $proxyItem;
            }

            $this->items = $this->mergeItems($items);
        }

        return $this->items;
    }

    /**
     * Order may have multiple items ordered, but some of them may be mapped to single product in magento.
     * We have to merge them to avoid qty and price calculation issues.
     *
     * @param \M2E\TikTokShop\Model\Order\Item\ProxyObject[] $items
     *
     * @return \M2E\TikTokShop\Model\Order\Item\ProxyObject[]
     */
    protected function mergeItems(array $items)
    {
        $unsetItems = [];

        foreach ($items as $key => &$item) {
            if (in_array($key, $unsetItems)) {
                continue;
            }

            foreach ($items as $nestedKey => $nestedItem) {
                if ($key == $nestedKey) {
                    continue;
                }

                if (!$item->equals($nestedItem)) {
                    continue;
                }

                $item->merge($nestedItem);

                $unsetItems[] = $nestedKey;
            }
        }

        foreach ($unsetItems as $key) {
            unset($items[$key]);
        }

        return $items;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return $this
     */
    public function setStore(\Magento\Store\Api\Data\StoreInterface $store): self
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @return \Magento\Store\Model\Store
     * @throws \M2E\TikTokShop\Model\Exception
     */
    public function getStore()
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck  */
        if (!isset($this->store)) {
            throw new \M2E\TikTokShop\Model\Exception('Store is not set.');
        }

        /** @psalm-suppress NoValue  */
        return $this->store;
    }

    public function getCheckoutMethod(): string
    {
        if (
            $this->order->getAccount()->getOrdersSettings()->isCustomerPredefined()
            || $this->order->getAccount()->getOrdersSettings()->isCustomerNew()
        ) {
            return self::CHECKOUT_REGISTER;
        }

        return self::CHECKOUT_GUEST;
    }

    /**
     * @return bool
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function isCheckoutMethodGuest()
    {
        return $this->getCheckoutMethod() == self::CHECKOUT_GUEST;
    }

    public function isOrderNumberPrefixSourceMagento(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isMagentoOrdersNumberSourceMagento();
    }

    public function isOrderNumberPrefixSourceChannel(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isMagentoOrdersNumberSourceChannel();
    }

    public function getOrderNumberPrefix(): string
    {
        return $this->order->getAccount()->getOrdersSettings()->getMagentoOrdersNumberRegularPrefix();
    }

    public function getChannelOrderNumber()
    {
        return $this->order->getTtsOrderId();
    }

    public function isMagentoOrdersCustomerNewNotifyWhenOrderCreated(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isCustomerNewNotifyWhenOrderCreated();
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomer(): ?\Magento\Customer\Api\Data\CustomerInterface
    {
        $accountModel = $this->order->getAccount();

        if ($accountModel->getOrdersSettings()->isCustomerPredefined()) {
            $customerDataObject = $this->customerRepository->getById(
                $accountModel->getOrdersSettings()->getCustomerPredefinedId()
            );

            if ($customerDataObject->getId() === null) {
                throw new \M2E\TikTokShop\Model\Exception(
                    sprintf(
                        'Customer with ID specified in %s Account Settings does not exist.',
                        \M2E\TikTokShop\Helper\Module::getChannelTitle()
                    )
                );
            }

            return $customerDataObject;
        }

        $customerBuilder = $this->magentoCustomerFactory->create();

        if ($accountModel->getOrdersSettings()->isCustomerNew()) {
            $customerInfo = $this->getAddressData();

            /** @var \Magento\Customer\Model\Customer $customerObject */
            $customerObject = $this->customerFactory->create();
            $customerObject->setWebsiteId($accountModel->getOrdersSettings()->getCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if ($customerObject->getId() !== null) {
                $customerBuilder->setData($customerInfo);
                $customerBuilder->updateAddress($customerObject);
                $this->updateCustomerVatId($customerObject, $customerInfo);

                return $customerObject->getDataModel();
            }

            $customerInfo['website_id'] = $accountModel->getOrdersSettings()->getCustomerNewWebsiteId();
            $customerInfo['group_id'] = $accountModel->getOrdersSettings()->getCustomerNewGroupId();

            $customerBuilder->setData($customerInfo);
            $customerBuilder->buildCustomer();
            $customerObject = $customerBuilder->getCustomer();
            $customerObject->save();

            $this->updateCustomerVatId($customerObject, $customerInfo);

            return $customerObject->getDataModel();
        }

        return null;
    }

    public function getCustomerFirstName()
    {
        $addressData = $this->getAddressData();

        return $addressData['firstname'];
    }

    public function getCustomerLastName()
    {
        $addressData = $this->getAddressData();

        return $addressData['lastname'];
    }

    public function getBuyerEmail()
    {
        $addressData = $this->getAddressData();

        return $addressData['email'];
    }

    /**
     * @return array
     */
    public function getAddressData(): array
    {
        if (empty($this->addressData)) {
            $rawAddressData = $this->order->getShippingAddress()->getRawData();

            $recipientUserInfo = $this->createUserInfoFromRawName($rawAddressData['recipient_name']);
            $this->addressData['prefix'] = $recipientUserInfo->getPrefix();
            $this->addressData['firstname'] = $recipientUserInfo->getFirstName();
            $this->addressData['middlename'] = $recipientUserInfo->getMiddleName();
            $this->addressData['lastname'] = $recipientUserInfo->getLastName();
            $this->addressData['suffix'] = $recipientUserInfo->getSuffix();

            $customerUserInfo = $this->createUserInfoFromRawName($rawAddressData['buyer_name']);
            $this->addressData['customer_prefix'] = $customerUserInfo->getPrefix();
            $this->addressData['customer_firstname'] = $customerUserInfo->getFirstName();
            $this->addressData['customer_middlename'] = $customerUserInfo->getMiddleName();
            $this->addressData['customer_lastname'] = $customerUserInfo->getLastName();
            $this->addressData['customer_suffix'] = $customerUserInfo->getSuffix();

            $this->addressData['email'] = $rawAddressData['email'];
            $this->addressData['country_id'] = $rawAddressData['country_id'];
            $this->addressData['region'] = $rawAddressData['region'];
            $this->addressData['region_id'] = $this->order->getShippingAddress()->getRegionId();
            $this->addressData['city'] = $rawAddressData['city'];
            $this->addressData['postcode'] = $rawAddressData['postcode'];
            $this->addressData['telephone'] = $rawAddressData['telephone'];

            $rawStreets = array_filter($rawAddressData['street'] ?? []);
            $rawStreets = $this->sortStreetLinesForBrazil($rawStreets);
            if (count($rawStreets) > $this->addressHelper->getStreetLines($this->order->getStore())) {
                $rawStreets = [implode(', ', $rawStreets)];
            }

            $this->addressData['street'] = $rawStreets;
            $this->addressData['company'] = !empty($rawAddressData['company']) ? $rawAddressData['company'] : '';
            $this->addressData['save_in_address_book'] = 0;
            $this->addressData['vat_id'] = $this->order->getCpf();
        }

        return $this->addressData;
    }

    /**
     * @return array
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getBillingAddressData()
    {
        return $this->getAddressData();
    }

    /**
     * @return bool
     */
    public function shouldIgnoreBillingAddressValidation()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->order->getCurrency();
    }

    public function convertPrice($price)
    {
        return $this->currency->convertPrice($price, $this->getCurrency(), $this->getStore());
    }

    public function convertPriceToBase($price)
    {
        return $this->currency->convertPriceToBaseCurrency($price, $this->getCurrency(), $this->getStore());
    }

    public function getPaymentData(): array
    {
        return [
            \Magento\Quote\Api\Data\PaymentInterface::KEY_METHOD => $this->payment->getCode(),
            \Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA => [
                TikTokShopPayment::ADDITIONAL_DATA_KEY_PAYMENT_METHOD => $this->order->getPaymentMethod(),
                TikTokShopPayment::ADDITIONAL_DATA_KEY_CHANNEL_ORDER_ID => $this->order->getTtsOrderId(),
            ],
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getShippingData(): array
    {
        $additionalData = '';

        $shipByDate = $this->order->getShipByDate();
        $isImportShipByDate = $this
            ->order
            ->getAccount()
            ->getOrdersSettings()
            ->isImportShipByDate();

        if (!empty($shipByDate) && $isImportShipByDate) {
            $shippingDate = \M2E\Core\Helper\Date::createDateInCurrentZone($shipByDate);
            $additionalData .= sprintf('Ship By Date: %s | ', $shippingDate->format('M d, Y, H:i:s'));
        }

        if (!empty($additionalData)) {
            $additionalData = ' | ' . $additionalData;
        }

        $shippingMethod = $this->order->getShippingService();

        return [
            'carrier_title' => (string)__(
                '%channel_title Delivery Option',
                [
                    'channel_title' => \M2E\TikTokShop\Helper\Module::getChannelTitle(),
                ]
            ),
            'shipping_method' => $shippingMethod . $additionalData,
            'shipping_price' => $this->getBaseShippingPrice(),
        ];
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        $price = $this->order->getShippingPrice();

        if ($this->isTaxModeNone() && !$this->isShippingPriceIncludeTax()) {
            $taxAmount = $this->taxCalculation->calcTaxAmount(
                $price,
                $this->getShippingPriceTaxRate(),
                false,
                false
            );

            $price += $taxAmount;
        }

        return $price;
    }

    protected function getBaseShippingPrice()
    {
        return $this->convertPriceToBase($this->getShippingPrice());
    }

    /**
     * @return bool
     */
    public function hasTax()
    {
        return $this->order->hasTax();
    }

    /**
     * @return bool
     */
    public function isSalesTax()
    {
        return $this->order->isSalesTax();
    }

    /**
     * @return bool
     */
    public function isVatTax()
    {
        return $this->order->isVatTax();
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        if (!$this->hasTax()) {
            return 0;
        }

        if ($this->isTaxModeNone() || $this->isTaxModeMagento()) {
            return 0;
        }

        return $this->order->getTaxRate();
    }

    /**
     * @return \M2E\TikTokShop\Model\Order\Tax\PriceTaxRateInterface|null
     */
    public function getProductPriceTaxRateObject(): ?\M2E\TikTokShop\Model\Order\Tax\PriceTaxRateInterface
    {
        return null;
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        if (!$this->hasTax()) {
            return 0;
        }

        if ($this->isTaxModeNone() || $this->isTaxModeMagento()) {
            return 0;
        }

        if (!$this->order->isShippingPriceHasTax()) {
            return 0;
        }

        return $this->getProductPriceTaxRate();
    }

    /**
     * @return \M2E\TikTokShop\Model\Order\Tax\PriceTaxRateInterface|null
     */
    public function getShippingPriceTaxRateObject(): ?\M2E\TikTokShop\Model\Order\Tax\PriceTaxRateInterface
    {
        return null;
    }

    // ---------------------------------------

    /**
     * @return bool|null
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function isProductPriceIncludeTax(): ?bool
    {
        return $this->isPriceIncludeTax('product');
    }

    /**
     * @return bool|null
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function isShippingPriceIncludeTax(): ?bool
    {
        return $this->isPriceIncludeTax('shipping');
    }

    /**
     * @param $priceType
     *
     * @return bool|null
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    protected function isPriceIncludeTax(string $priceType): ?bool
    {
        $configValue = $this->config->getGroupValue("/order/tax/{$priceType}_price/", 'is_include_tax');
        if ($configValue !== null) {
            return (bool)$configValue;
        }

        if ($this->isTaxModeChannel() || ($this->isTaxModeMixed() && $this->hasTax())) {
            return $this->isVatTax();
        }

        return null;
    }

    public function isTaxModeNone(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isTaxModeNone();
    }

    public function isTaxModeChannel(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isTaxModeChannel();
    }

    public function isTaxModeMagento(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isTaxModeMagento();
    }

    public function isTaxModeMixed(): bool
    {
        return !$this->isTaxModeNone() &&
            !$this->isTaxModeChannel() &&
            !$this->isTaxModeMagento();
    }

    public function getComments(): array
    {
        return array_merge($this->getGeneralComments(), $this->getChannelComments());
    }

    /**
     * @return array
     */
    public function getChannelComments()
    {
        return array_merge($this->getGiftItemsComments(), $this->getSampleOrderComment());
    }

    /**
     * @return array
     * @throws \M2E\TikTokShop\Model\Exception
     */
    public function getGeneralComments()
    {
        $store = $this->getStore();

        $currencyConvertRate = $this->currency->getConvertRateFromBase($this->getCurrency(), $store, 4);

        if ($this->currency->isBase($this->getCurrency(), $store)) {
            return [];
        }

        $comments = [];

        if (!$this->currency->isAllowed($this->getCurrency(), $store)) {
            $comments[] = (string)__(
                '<b>Attention!</b> The Order Prices are incorrect. Conversion was not ' .
                'performed as "%order_currency" Currency is not enabled. Default ' .
                'Currency "%store_currency" was used instead. Please, ' .
                'enable Currency in System > Configuration > Currency Setup.',
                [
                    'order_currency' => $this->getCurrency(),
                    'store_currency' => $store->getBaseCurrencyCode(),
                ]
            );
        } elseif ($currencyConvertRate == 0) {
            $comments[] = __(
                '<b>Attention!</b> The Order Prices are incorrect. Conversion was not ' .
                'performed as there\'s no rate for "%order_currency". Default Currency ' .
                '"%store_currency" was used instead. Please, add Currency convert ' .
                'rate in System > Manage Currency > Rates.',
                [
                    'order_currency' => $this->getCurrency(),
                    'store_currency' => $store->getBaseCurrencyCode(),
                ]
            );
        } else {
            $comments[] = __(
                'Because the Order Currency is different from the Store Currency, the conversion ' .
                'from <b>"%order_currency" to "%store_currency"</b> was performed ' .
                'using <b>%currency_rate</b> as a rate.',
                [
                    'order_currency' => $this->getCurrency(),
                    'store_currency' => $store->getBaseCurrencyCode(),
                    'currency_rate' => $currencyConvertRate,
                ]
            );
        }

        return $comments;
    }

    public function getGiftItemsComments(): array
    {
        $giftItems = array_filter($this->order->getItems(), fn($item) => $item->isGiftItem());

        if (empty($giftItems)) {
            return [];
        }

        $comments = [];
        /** @var \M2E\TikTokShop\Model\Order\Item $giftItem */
        foreach ($giftItems as $giftItem) {
            $comments[] = (string) __(
                "<b>SKU</b> %sku <b>is a gift product</b>",
                ['sku' => $giftItem->getSku()]
            );
        }

        return $comments;
    }

    public function getSampleOrderComment(): array
    {
        return $this->order->isSample() ? [(string)__('This Order contains a free Sample')] : [];
    }

    private function updateCustomerVatId(\Magento\Customer\Model\Customer $customer, array $customerInfo)
    {
        if (empty($customerInfo['vat_id'] ?? null)) {
            return;
        }

        foreach ($customer->getPrimaryAddresses() as $addressModel) {
            $addressModel->setVatId($customerInfo['vat_id']);
            $addressModel->save();
        }
    }

    private function sortStreetLinesForBrazil(array $rawStreets): array
    {
        if (!$this->order->getShop()->getRegion()->isRegionCodeBR()) {
            return $rawStreets;
        }

        return [
            $rawStreets[1] ?? null,
            $rawStreets[2] ?? null,
            $rawStreets[0] ?? null,
            $rawStreets[3] ?? null,
        ];
    }
}
