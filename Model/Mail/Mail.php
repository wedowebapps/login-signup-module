<?php
namespace Lordhair\Customizations\Model\Mail;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\TransportInterface;
use Zend_Mime;
use Zend\Mime\Part;
use Magento\Framework\Filesystem\Io\File;
use Zend\Mime\PartFactory as MimePartFactory;
use Zend\Mime\Mime;

class Mail implements MailInterface
{

    const SENDER_EMAIL='trans_email/ident_general/email';
    const SENDER_NAME = 'trans_email/ident_general/name';

    private $transportBuilder;
    private $inlineTranslation;
    private $storeManager;
    protected $scopeConfig;
    protected $mimePartFactory;

    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager = null,
        File $file,
        MimePartFactory $mimePartFactory
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->file = $file;
        $this->mimePartFactory = $mimePartFactory;
    }

    /**
     * Send email from contact form
     *
     * @param string $replyTo
     * @param array $variables
     * @return void
     */
    public function send($replyTo, array $variables)
    {
        /** @see \Magento\Contact\Controller\Index\Post::validatedParams() */
        $replyToName = !empty($variables['customer']) ? $variables['customer'] : null;

        $this->inlineTranslation->suspend();

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($variables['tempId'])
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFromByScope($this->senderEmail())
                ->addTo($replyTo)
                ->setReplyTo($replyTo, $replyToName);
/*            foreach($variables['attchments'] as $file) {
                if($file['filename']){
                    $transport->addAttachment(file_get_contents($file['file']), $file['filename'], $file['type']);
                }
            }*/
            $transport->getTransport()->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    public function addSingleAttachment(array $file)
    {
        $filePath = $file['file'];
        $fileName = $file['filename'];
        $fileType = $file['type'];
        $fileContents = fopen($filePath, 'r');
        $attachment = new \Zend\Mime\Part($fileContents);
        $attachment->type = $fileType;
        $attachment->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = \Zend_Mime::ENCODING_BASE64;
        $attachment->filename = $fileName;
        return $attachment;
    }

    public function senderEmail($type = null, $storeId = null)
    {
        $sender ['email'] = $this->scopeConfig->getValue(self::SENDER_EMAIL, ScopeInterface::SCOPE_STORE, $storeId);
        $sender['name'] = $this->scopeConfig->getValue(self::SENDER_NAME, ScopeInterface::SCOPE_STORE);
        return $sender;
    }

    public function emailAdmin()
    {
        return $this->scopeConfig->getValue(
            self::SENDER_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }
}