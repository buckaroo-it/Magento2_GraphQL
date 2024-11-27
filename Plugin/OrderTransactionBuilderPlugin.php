<?php

namespace Buckaroo\Magento2Graphql\Plugin;

use Buckaroo\Magento2\Gateway\Http\TransactionBuilder\AbstractTransactionBuilder;
use Buckaroo\Magento2\Gateway\Http\TransactionBuilder\Order as OrderTransactionBuilder;
use Buckaroo\Magento2\Model\ConfigProvider\Account;
use Buckaroo\Magento2\Model\ConfigProvider\Factory;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;

class OrderTransactionBuilderPlugin
{
    public const DEFAULT_REDIRECT_PATH = 'checkout/onepage/succes/';

    public function __construct(
        private readonly Factory $configProviderFactory,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Overwrite the return url to include the order_id and maskedId,
     * There is an issue with `setReturnUrl` mutation not working, and we don't want to do extra mutations anyway.
     *
     * @param OrderTransactionBuilder $subject
     * @param string                  $result
     * @return string
     * @throws Exception
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetReturnUrl(
        OrderTransactionBuilder $subject,
        string $result
    ): string {
        $returnUrl = $this->getReturnUrl($subject);
        return $returnUrl . '?' . http_build_query([
                'orderId' => $subject->getOrder()->getIncrementId(),
            ]);
    }

    /**
     * When a specific returnURl is set on the payment, use that one.
     * Otherwise, use the default return url.
     *
     * @param OrderTransactionBuilder $subject
     * @return string
     */
    protected function getReturnUrl(OrderTransactionBuilder $subject): string
    {
        $payment = $subject->getOrder()->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        if (isset($additionalInfo[AbstractTransactionBuilder::ADDITIONAL_RETURN_URL])) {
            return $additionalInfo[AbstractTransactionBuilder::ADDITIONAL_RETURN_URL];
        }

        return $this->getDefaultUrl();

    }
    /**
     * Overwrite the return url to include the order_id and maskedId,
     * There is an issue with `setReturnUrl` mutation not working, and we don't want to do extra mutations anyway.
     *
     * @param OrderTransactionBuilder $subject
     * @param string                  $result
     * @return string
     * @throws Exception
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCancelUrl(
        OrderTransactionBuilder $subject,
        string $result
    ): string {
        $cancelUrl = $this->getCancelUrl($subject);
        return $cancelUrl . '?' . http_build_query([
                'orderId' => $subject->getOrder()->getIncrementId(),
            ]);
    }

    /**
     * When a specific returnURl is set on the payment, use that one.
     * Otherwise, use the default return url.
     *
     * @param OrderTransactionBuilder $subject
     * @return string
     */
    protected function getCancelUrl(OrderTransactionBuilder $subject): string
    {
        $payment = $subject->getOrder()->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        if (isset($additionalInfo[AbstractTransactionBuilder::ADDITIONAL_CANCEL_URL])) {
            return $additionalInfo[AbstractTransactionBuilder::ADDITIONAL_CANCEL_URL];
        }

        return $this->getDefaultUrl();
    }

    /**
     * Overwrite the return url to include the order_id and maskedId,
     * There is an issue with `setReturnUrl` mutation not working, and we don't want to do extra mutations anyway.
     *
     * @param OrderTransactionBuilder $subject
     * @param string                  $result
     * @return string
     * @throws Exception
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetErrorUrl(
        OrderTransactionBuilder $subject,
        string $result
    ): string {
        $errorUrl = $this->getErrorUrl($subject);
        return $errorUrl . '?' . http_build_query([
                'orderId' => $subject->getOrder()->getIncrementId(),
            ]);
    }

    /**
     * When a specific returnURl is set on the payment, use that one.
     * Otherwise, use the default return url.
     *
     * @param OrderTransactionBuilder $subject
     * @return string
     */
    protected function getErrorUrl(OrderTransactionBuilder $subject): string
    {
        $payment = $subject->getOrder()->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        if (isset($additionalInfo[AbstractTransactionBuilder::ADDITIONAL_ERROR_URL])) {
            return $additionalInfo[AbstractTransactionBuilder::ADDITIONAL_ERROR_URL];
        }

        return $this->getDefaultUrl();
    }

    /**
     * Overwrite the return url to include the order_id and maskedId,
     * There is an issue with `setReturnUrl` mutation not working, and we don't want to do extra mutations anyway.
     *
     * @param OrderTransactionBuilder $subject
     * @param string                  $result
     * @return string
     * @throws Exception
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetRejectUrl(
        OrderTransactionBuilder $subject,
        string $result
    ): string {
        $rejectUrl = $this->getRejectUrl($subject);
        return $rejectUrl . '?' . http_build_query([
                'orderId' => $subject->getOrder()->getIncrementId(),
            ]);
    }

    /**
     * When a specific returnURl is set on the payment, use that one.
     * Otherwise, use the default return url.
     *
     * @param OrderTransactionBuilder $subject
     * @return string
     */
    protected function getRejectUrl(OrderTransactionBuilder $subject): string
    {
        $payment = $subject->getOrder()->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        if (isset($additionalInfo[AbstractTransactionBuilder::ADDITIONAL_REJECT_URL])) {
            return $additionalInfo[AbstractTransactionBuilder::ADDITIONAL_REJECT_URL];
        }

        return $this->getDefaultUrl();
    }

    protected function getDefaultUrl(){
        $returnUrl = $this->scopeConfig->getValue('web/secure/frontend_base_link_url');
        try {
            /** @var Account $accountConfig */
            $accountConfig = $this->configProviderFactory->get('account');
            $returnUrl .= $accountConfig->getSuccessRedirect();
        } catch (Exception $e) {
            $returnUrl .= self::DEFAULT_REDIRECT_PATH;
        }
        return $returnUrl;
    }
}
