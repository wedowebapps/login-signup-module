<?php
namespace Lordhair\LoginSignup\Model;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Mail implements MailInterface
{

    const SENDER_EMAIL='trans_email/ident_general/email';
    const SENDER_NAME = 'trans_email/ident_general/name';

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected $scopeConfig;

    /**
     * Initialize dependencies.
     *
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager = null
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
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
        $replyToName = !empty($variables['customer']['name']) ? $variables['customer']['name'] : null;

        $this->inlineTranslation->suspend();

        try {
            $transport = $this->transportBuilder
            ->setTemplateIdentifier($variables['customer']['tempId'])
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId()
                ]
            )
            ->setTemplateVars($variables)
            ->setFrom($this->senderEmail())
            ->addTo($replyTo)
            ->setReplyTo($replyTo, $replyToName)
            ->getTransport();
            $transport->sendMessage();

            if(isset($variables['customer']['staff_email'])){
            
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier('14')
                    ->setTemplateOptions(
                        [
                            'area' => Area::AREA_FRONTEND,
                            'store' => $this->storeManager->getStore()->getId()
                        ]
                    )
                    ->setTemplateVars($variables)
                    ->setFrom($this->senderEmail())
                    ->addTo($variables['customer']['staff_email'])
                    ->setReplyTo($replyTo, $replyToName)
                    ->getTransport();
                $transport->sendMessage();
            }

        } finally {
            $this->inlineTranslation->resume();
        }
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