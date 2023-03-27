<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use \Magento\Framework\Registry;

/**
 * Order output class
 */
class OrderOutput implements ResolverInterface
{

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        return [
            "redirect" => $this->getRedirect(),
            "data" => $this->getAdditionalOutputData(),
            "transaction_id" => $this->getTransactionId()
        ];
    }

    /**
     * Get it for transaction
     *
     * @return string|null
     */
    protected function getTransactionId()
    {
        $response = $this->getResponse();

        if (isset($response->Key) && !empty($response->Key)) {
            return $response->Key;
        }
    }
    /**
     * Get redirect url
     *
     * @return string|null
     */
    protected function getRedirect()
    {
        $response = $this->getResponse();
        if ($response !== null && !empty($response->RequiredAction->RedirectURL)) {
            return $response->RequiredAction->RedirectURL;
        }
    }
    /**
     * Get additional data from response
     *
     * @return array|null
     */
    protected function getAdditionalOutputData()
    {

        $response = $this->getResponse();
        if (
            $response !== null &&
            isset($response->Services) &&
            isset($response->Services->Service) &&
            is_array($response->Services->Service->ResponseParameter) &&
            count($response->Services->Service->ResponseParameter)
        ) {
            return $this->formatAdditionalOutput($response->Services->Service->ResponseParameter);
        }
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
            foreach ($data as $item) {
                if (isset($item->Name) && isset($item->_)) {
                    $additionalData[] = [
                        "key" => $item->Name,
                        "value" => $item->_
                    ];
                }
            }
        }
        return $additionalData;
    }
    /**
     * Get payment response
     *
     * @return stdClass|null
     */
    private function getResponse()
    {
        if ($this->registry && $this->registry->registry("buckaroo_response")) {
            return $this->registry->registry("buckaroo_response")[0];
        }
    }
}
