<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
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

namespace Lof\HelpDesk\Controller\Ticket;
use Magento\Framework\App\Filesystem\DirectoryList;
class Saveticket  extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    /**
     *
     * @var Magento\Framework\App\Action\Session
     */
    protected $session;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * @var \Lof\HelpDesk\Model\Sender
     */
    protected $sender;
     /**
     * @var  \Lof\HelpDesk\Helper\Data
     */
    protected $helper;
    /**
     * @var  \Lof\HelpDesk\Model\Spam
     */
    protected $spam;

    /**
     * @param \Magento\Framework\App\Action\Context      $context           
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory    
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
         \Magento\Customer\Model\Session $customerSession, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lof\HelpDesk\Model\Sender $sender,
        \Lof\HelpDesk\Helper\Data $helper,
        \Lof\HelpDesk\Model\Spam $spam,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->spam = $spam;
        $this->helper = $helper;
        $this->resultPageFactory = $resultPageFactory;
        $this->session           = $customerSession;
        $this->_fileSystem = $filesystem;
        $this->sender = $sender;
        parent::__construct($context);
    }

    public function execute()
    { 

        $customerSession = $this->session;
        $customerId = $customerSession->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            

        $data = $this->getRequest()->getPostValue();
 
        if ($data) {
            //$id = $this->getRequest()->getParam('seller_id');
            $data['customer_id'] = $customerId;
            $data['customer_name'] = $customerSession->getCustomer()->getName();
            $data['customer_email'] = $customerSession->getCustomer()->getEmail();
            $ticketModel = $objectManager->get('Lof\HelpDesk\Model\Ticket');
            $category = $objectManager->get('Lof\HelpDesk\Model\Category')->load($data['category_id'],'category_id');
            
            $user = $objectManager->create('\Magento\User\Model\User');
            $store = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $data['store_id'] = $store->getStore()->getId();
            $data['status_id'] = 1;
            $data['last_reply_name'] = $data['customer_name'];
            $data['reply_cnt'] = 0;
            $data['category'] = $category->getTile();
            $data['namestore'] = $this->helper->getStoreName();
            $data['urllogin'] = $this->helper->getCustomerLoginUrl();
            $data['department_id'] = $this->helper->getDepartmentByCategory($data['category_id']);

            $department = $objectManager->create('Lof\HelpDesk\Model\Department')->load($data['department_id']);
            $data['email_to'] = [];
            if(count($department->getData()) > 0) {
                foreach ($department->getData('users') as $key => $_user) {
                    $user->load($_user,'user_id');
                    $data['email_to'][]=$user->getEmail();
                }
            }    
           
        
            $mediaDirectory =  $this->_objectManager->get('Magento\Framework\Filesystem')
                    ->getDirectoryRead(DirectoryList::MEDIA);
            $mediaFolder = 'lof/helpdesk/';
            $path = $mediaDirectory->getAbsolutePath($mediaFolder);

            // Delete, Upload Image
            
            $imagePath = $mediaDirectory->getAbsolutePath($mediaFolder);
            if(isset($data['attachment']['delete']) && file_exists($imagePath.$mediaFolder)){
                //unlink($imagePath.$mediaFolder);
                $data['attachment'] = '';
            }
            if(isset($data['attachment']) && is_array($data['attachment'])){
                unset($data['attachment']);
            }
            if($image = $this->uploadImage('attachment')){
                
                $data['attachment'] = $image['attachment'];
                $data['attachment_name'] = $image['attachment_name'];
            }
            foreach ($this->spam->getCollection()->addFieldToFilter('is_active',1) as $key => $spam) {

                if($this->helper->checkSpam($spam,$data)) {
                    $this->messageManager->addError(__('You are spamer'));
                    $this->_redirect('lofhelpdesk/ticket');
                    return;
                }
            }
            $ticketModel->setData($data)->save();
            if(count($data['email_to'])) {
                $this->sender->newTicket($data);
            }
           
            $this->_redirect('lofhelpdesk/ticket');
        }
    }

     public function uploadImage($fieldId = 'file')
    {                

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (isset($_FILES[$fieldId]) && $_FILES[$fieldId]['name']!='') 
        {
            $uploader = $this->_objectManager->create(
                'Magento\Framework\File\Uploader',
                array('fileId' => $fieldId)
                );
            $path = $this->_fileSystem->getDirectoryRead(
            DirectoryList::MEDIA
            )->getAbsolutePath(
            'catalog/category/'
            );

            /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
            ->getDirectoryRead(DirectoryList::MEDIA);
            $mediaFolder = 'lof/helpdesk/';
            try {

                $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png')); 
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);
                $result = $uploader->save($mediaDirectory->getAbsolutePath($mediaFolder)
                    );
                $image['attachment'] = $mediaFolder.str_replace(' ','_',$result['name']);
                $image['attachment_name'] = str_replace(' ','_',$result['name']);
            return $image;
            } catch (\Exception $e) {

                $this->_logger->critical($e);
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['ticket_id' => $this->getRequest()->getParam('ticket_id')]);
            }
        }
        return;
    }
}