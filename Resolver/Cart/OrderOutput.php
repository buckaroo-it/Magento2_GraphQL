<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Buckaroo\Magento2\Api\Data\BuckarooResponseDataInterface;
use Buckaroo\Transaction\Response\TransactionResponse;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Order output class
 */
class OrderOutput implements ResolverInterface
{
    /**
     * @var BuckarooResponseDataInterface
     */
    private BuckarooResponseDataInterface $buckarooResponseData;

    /**
     * @var TransactionResponse|null
     */
    private ?TransactionResponse $buckarooResponse = null;

    public function __construct(BuckarooResponseDataInterface $buckarooResponseData)
    {
        $this->buckarooResponseData = $buckarooResponseData;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        return [
            "redirect"       => $this->getRedirect(),
            "data"           => $this->getAdditionalOutputData(),
            "transaction_id" => $this->getTransactionId()
        ];
    }

    /**
     * Get redirect url
     *
     * @return string|null
     */
    protected function getRedirect()
    {
        if ($this->getResponse()->hasRedirect()) {
            return $this->getResponse()->getRedirectUrl();
        }

        return null;
    }

    /**
     * Get payment response
     *
     * @return \Buckaroo\Transaction\Response\TransactionResponse|void
     */
    private function getResponse()
    {
        if (!$this->buckarooResponse) {
            $this->buckarooResponse = $this->buckarooResponseData->getResponse();
        }
        return $this->buckarooResponse;
    }

    /**
     * Get additional data from response
     *
     * @return array|null
     */
    protected function getAdditionalOutputData()
    {
        return $this->formatAdditionalOutput($this->getResponse()->getAdditionalParameters());
    }

    /**
     * Format data in the graphQl format
     *
     * @param array $data
     *
     * @return array
     */
    private function formatAdditionalOutput($data)
    {
        $additionalData = [];
        if (count($data)) {
            foreach ($data as $key => $value) {
                $additionalData[] = [
                    "key"   => $key,
                    "value" => $value
                ];

            }
        }
        return $additionalData;
    }

    /**
     * Get it for transaction
     *
     * @return string|null
     */
    protected function getTransactionId()
    {
        return $this->getResponse()->getTransactionKey();
    }
}
