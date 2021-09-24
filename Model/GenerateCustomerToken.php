<?php
/**
 *
 * @package     nexpwa/login-as-customer
 * @author      Jayanka Ghosh <Codilar Technologies>
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v>
 * @link        http://www.codilar.com/
 */
namespace NexPWA\LoginAsCustomer\Model;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\LoginAsCustomerApi\Api\IsLoginAsCustomerEnabledForCustomerInterface;
use Magento\LoginAsCustomerLog\Api\Data\LogInterfaceFactory;
use Magento\LoginAsCustomerLog\Api\SaveLogsInterface;
use Magento\User\Model\UserFactory as AdminUserFactory;
use Magento\User\Model\User as AdminUser;
use Magento\User\Model\ResourceModel\User as AdminUserResource;

class GenerateCustomerToken
{
    private TokenModelFactory $tokenFactory;
    private IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled;
    private LogInterfaceFactory $logFactory;
    private SaveLogsInterface $saveLogs;
    private CustomerRepositoryInterface $customerRepository;
    private AdminUserFactory $adminUserFactory;
    private AdminUserResource $adminUserResource;

    /**
     * @param TokenModelFactory $tokenFactory
     * @param IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled
     * @param LogInterfaceFactory $logFactory
     * @param SaveLogsInterface $saveLogs
     * @param CustomerRepositoryInterface $customerRepository
     * @param AdminUserFactory $adminUserFactory
     * @param AdminUserResource $adminUserResource
     */
    public function __construct(
        TokenModelFactory $tokenFactory,
        IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled,
        LogInterfaceFactory $logFactory,
        SaveLogsInterface $saveLogs,
        CustomerRepositoryInterface $customerRepository,
        AdminUserFactory $adminUserFactory,
        AdminUserResource $adminUserResource
    )
    {
        $this->tokenFactory = $tokenFactory;
        $this->isLoginAsCustomerEnabled = $isLoginAsCustomerEnabled;
        $this->logFactory = $logFactory;
        $this->saveLogs = $saveLogs;
        $this->customerRepository = $customerRepository;
        $this->adminUserFactory = $adminUserFactory;
        $this->adminUserResource = $adminUserResource;
    }

    /**
     * @param int $customerId
     * @param int $adminId
     * @return string
     * @throws LocalizedException
     */
    public function execute(int $customerId, int $adminId): string
    {
        $customer = $this->customerRepository->getById($customerId);
        $admin = $this->getAdminById($adminId);
        $isLoginAsCustomerEnabled = $this->isLoginAsCustomerEnabled->execute($customer->getId());
        if (!$isLoginAsCustomerEnabled->isEnabled()) {
            foreach ($isLoginAsCustomerEnabled->getMessages() as $message) {
                throw new LocalizedException(__($message));
            }
        }
        $token = $this->tokenFactory->create()->createCustomerToken($customerId);
        $this->log($customer, $admin);
        return $token->getToken();
    }

    /**
     * @param int $adminId
     * @return AdminUser
     * @throws NoSuchEntityException
     */
    protected function getAdminById(int $adminId): AdminUser
    {
        $admin = $this->adminUserFactory->create();
        $this->adminUserResource->load($admin, $adminId);
        if (!$admin->getId()) {
            throw NoSuchEntityException::singleField('id', $adminId);
        }
        return $admin;
    }

    protected function log(CustomerInterface $customer, AdminUser $adminUser)
    {
        $log = $this->logFactory->create(
            [
                'data' => [
                    'customer_id' => $customer->getId(),
                    'customer_email' => $customer->getEmail(),
                    'user_id' => $adminUser->getId(),
                    'user_name' => $adminUser->getUserName(),
                ],
            ]
        );
        $this->saveLogs->execute([$log]);
    }
}
