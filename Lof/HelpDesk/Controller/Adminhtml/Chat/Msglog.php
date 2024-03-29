<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_HelpDesk
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */
namespace Lof\HelpDesk\Controller\Adminhtml\Chat;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Display Hello on screen
 */
class Msglog extends \Magento\Framework\App\Action\Action
{
    protected $_cacheTypeList;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Lof\HelpDesk\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    protected $_message;

    protected $chat;
    /**
     * @param Context                                             $context              
     * @param \Magento\Store\Model\StoreManager                   $storeManager         
     * @param \Magento\Framework\View\Result\PageFactory          $resultPageFactory    
     * @param \Lof\HelpDesk\Helper\Data                               $helper           
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory 
     * @param \Magento\Framework\Registry                         $registry             
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lof\HelpDesk\Helper\Data $helper,
        \Lof\HelpDesk\Model\ChatMessage $message,
        \Lof\HelpDesk\Model\Chat $chat,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList, 
        \Magento\Customer\Model\Session $customerSession
        ) {
        $this->chat = $chat;
        $this->resultPageFactory    = $resultPageFactory;
        $this->_helper              = $helper;
        $this->_message             = $message;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->_coreRegistry        = $registry;
        $this->_cacheTypeList       = $cacheTypeList;
        $this->_customerSession     = $customerSession;
        $this->_request             = $context->getRequest();
        parent::__construct($context);
    }

    /**
     * Default customer account page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    { 
        $id = $this->getRequest()->getparam('chat_id');
        $chat = $this->_objectManager->create('Lof\HelpDesk\Model\Chat')->load($id);

        if($this->_customerSession->getCustomer()->getEmail()) {
            $message = $this->_message->getCollection()->addFieldToFilter('customer_email',$this->_customerSession->getCustomer()->getEmail());
        } else {
           $message = $this->_message->getCollection()->addFieldToFilter('chat_id',$id); 
        }
    
        foreach ($message as $key => $_message) {

            $date_sent = $_message['created_at'];
            $day_sent = substr($date_sent, 8, 2); 
            $month_sent = substr($date_sent, 5, 2); 
            $year_sent = substr($date_sent, 0, 4); 
            $hour_sent = substr($date_sent, 11, 2); 
            $min_sent = substr($date_sent, 14, 2); 

            if ($_message['user_id'])
            {
                echo '
                    <div class="msg-user">
                        <p>'.$_message['body_msg'].'</p>
                        <div class="info-msg-user">
                            You
                        </div>
                    </div>
                    
                ';
            } else {
                echo '
                <div class="msg">
                    <p>'.$_message['body_msg'].'</p>
                    <div class="info-msg">';
                    if($chat->getData('ip')) {
                        echo 'Guest';
                    } else {
                        echo $_message['customer_name'];
                    }
                echo '</div>
                </div>
            ';
            }
        }
        exit;
          
    }
}