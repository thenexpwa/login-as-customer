<?php
/**
 *
 * @package     nexpwa/login-as-customer
 * @author      Jayanka Ghosh <Codilar Technologies>
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v>
 * @link        http://www.codilar.com/
 */
namespace NexPWA\LoginAsCustomer\Plugin\Controller\Adminhtml\Login;

use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\LoginAsCustomerAdminUi\Controller\Adminhtml\Login\Login as Subject;
use Magento\LoginAsCustomerApi\Api\IsLoginAsCustomerEnabledForCustomerInterface;

class Login
{
    private JsonResultFactory $jsonResultFactory;
    private IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled;

    /**
     * @param JsonResultFactory $jsonResultFactory ;
     * @param IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled
     */
    public function __construct(
        JsonResultFactory $jsonResultFactory,
        IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled
    )
    {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->isLoginAsCustomerEnabled = $isLoginAsCustomerEnabled;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return JsonResult
     */
    public function aroundExecute(Subject $subject, callable $proceed): JsonResult
    {
        $messages = [];
        $redirectUrl = null;
        $customerId = $subject->getRequest()->getParam('customer_id');
        $isLoginAsCustomerEnabled = $this->isLoginAsCustomerEnabled->execute($customerId);
        if (!$isLoginAsCustomerEnabled->isEnabled()) {
            foreach ($isLoginAsCustomerEnabled->getMessages() as $message) {
                $messages[] = $message;
            }
        } else {
            $redirectUrl = $subject->getUrl('nexpwaloginascustomer/login/generateToken', [
                'customer_id' => $customerId
            ]);
        }

        return $this->prepareJsonResult($messages, $redirectUrl);
    }

    /**
     * Prepare JSON result
     *
     * @param array $messages
     * @param string|null $redirectUrl
     * @return JsonResult
     */
    private function prepareJsonResult(array $messages, ?string $redirectUrl = null)
    {
        $jsonResult = $this->jsonResultFactory->create();

        $jsonResult->setData([
            'redirectUrl' => $redirectUrl,
            'messages' => $messages,
        ]);

        return $jsonResult;
    }
}
