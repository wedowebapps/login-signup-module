<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lordhair\Customizations\Model\Mail;

/**
 * Email from contact form
 *
 * @api
 * @since 100.2.0
 */
interface MailInterface
{ 
    /**
     * Send email from contact form
     *
     * @param string $replyTo Reply-to email address
     * @param array $variables Email template variables
     * @return void
     * @since 100.2.0
     */
    public function send($replyTo, array $variables);
}
