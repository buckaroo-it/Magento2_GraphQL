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
            "data" => $this->getAdditionalOutputData()
        ];
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
            is_array($response->Services) &&
            count($response->Services) &&
            !empty($response->Services[0]->Parameters)
        ) {
            return $this->formatAdditionalOutput($response->Services[0]->Parameters);
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
                if (isset($item->Name) && isset($item->Value)) {
                    $additionalData[] = [
                        "key" => $item->Name,
                        "value" => $item->Value
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
