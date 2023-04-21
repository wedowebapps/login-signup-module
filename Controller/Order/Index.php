<?php
namespace Lordhair\Customizations\Controller\Order;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Message\ManagerInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
    * @var \Magento\Framework\App\Action\Context $context
    */
    protected $_context;
    /**
    * @var \Magento\Framework\View\Result\PageFactory $resultPageFactory
    */
    protected $_resultPageFactory;
    /**
    * @var \Magento\Framework\Message\ManagerInterface $messageManager
    */
    protected $_messageManager;
    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        \Magento\Framework\View\Page\Config $pageConfig
    ) {
        $this->_context = $context;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_messageManager = $messageManager;
        $this->_pageConfig = $pageConfig;
        parent::__construct($context);
    }
    /**
     * Takes the place of the M1 indexAction.
     * Now, every action has an execute
     *
     **/
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        $this->_setMetaDetails();
        if (isset($_GET['pid'])) {
            try {
            } catch (\Exception $e) {
                $this->_messageManager->addError(__($e->getMessage()));
            }
        } else {
            $this->_messageManager->addError(__('Something went wrong please try again after sometime'));
        }
        return $resultPage;
    }

    public function _setMetaDetails()
    {
        $this->_pageConfig->getTitle()->set(__('Customizations for Order a New Hair System'));
    }
}