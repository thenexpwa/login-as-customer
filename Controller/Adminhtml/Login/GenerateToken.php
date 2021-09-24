<?php
/**
 *
 * @package     nexpwa/login-as-customer
 * @author      Jayanka Ghosh <Codilar Technologies>
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v>
 * @link        http://www.codilar.com/
 */
namespace NexPWA\LoginAsCustomer\Controller\Adminhtml\Login;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use NexPWA\LoginAsCustomer\Model\GenerateCustomerToken;
use Magento\Framework\View\Element\TemplateFactory;
use Magento\Backend\Model\Auth\Session;

class GenerateToken extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_LoginAsCustomer::login';

    protected array $_publicActions = ['generateToken'];

    private GenerateCustomerToken $generateCustomerToken;
    private TemplateFactory $templateFactory;
    private Session $authSession;

    /**
     * @param Context $context
     * @param GenerateCustomerToken $generateCustomerToken
     * @param TemplateFactory $templateFactory
     * @param Session $authSession
     */
    public function __construct(
        Context $context,
        GenerateCustomerToken $generateCustomerToken,
        TemplateFactory $templateFactory,
        Session $authSession
    )
    {
        parent::__construct($context);
        $this->generateCustomerToken = $generateCustomerToken;
        $this->templateFactory = $templateFactory;
        $this->authSession = $authSession;
    }

    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        try {
            $token = $this->generateCustomerToken->execute($customerId, $this->authSession->getUser()->getId());
            $redirectUrl = $this->_url->getBaseUrl() . 'my-account';
            /** @var Raw $result */
            $block = $this->templateFactory->create();
            $block->addData([
                'token' => $token,
                'redirect_url' => $redirectUrl
            ]);
            $block->setTemplate('NexPWA_LoginAsCustomer::generate_token.phtml');
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setContents($block->toHtml());
            return $result;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('customer/index/edit', ['id' => $customerId]);
        }
    }
}
