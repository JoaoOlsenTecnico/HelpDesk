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
namespace Lof\HelpDesk\Controller\Adminhtml\Ticket;

class NewAction extends \Lof\HelpDesk\Controller\Adminhtml\Ticket
{
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Create new formbuilder form
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        $id = $this->getRequest()->getParam('ticket_id');
        $model = $this->_objectManager->create('Lof\HelpDesk\Model\Ticket');
        if ($id) { 
             $model->load($id);

             $category = $model->getCategoryId();
             $store = $model->getStoreId();
             $department = $this->_objectManager->create('Lof\HelpDesk\Model\Department');
             
            foreach ($department->getCollection() as $key => $_department) {
                $data = $department->load($_department->getDepartmentId())->getData();
                if(in_array($category, $data['category_id']) && $data['is_active'] == 1 && in_array($store, $data['store_id'])) {
                    return 1;
                }   
            }
        } 
        return $this->_authorization->isAllowed('Lof_HelpDesk::ticket_edit');
    }
}
