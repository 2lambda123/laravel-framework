<?php

namespace Illuminate\Mail\Transport;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Exception;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class SesTransport extends AbstractTransport
{
    /**
     * Amazon SES has a limit on the total number of recipients per message.
     * See Also: https://docs.aws.amazon.com/ses/latest/dg/quotas.html.
     */
    protected const RECIPIENT_LIMIT = 50;

    /**
     * The Amazon SES instance.
     *
     * @var \Aws\Ses\SesClient
     */
    protected $ses;

    /**
     * The Amazon SES transmission options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SES transport instance.
     *
     * @param  \Aws\Ses\SesClient  $ses
     * @param  array  $options
     * @return void
     */
    public function __construct(SesClient $ses, $options = [])
    {
        $this->ses = $ses;
        $this->options = $options;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        try {
            // Batch up API calls to adhere to the recipient limit of the service.
            // See also: https://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Ses.SesClient.html#_sendRawEmail.
            foreach (collect($message->getEnvelope()->getRecipients())->chunk(self::RECIPIENT_LIMIT) as $recipients) {
                $this->ses->sendRawEmail(
                    array_merge(
                        $this->options, [
                            'Source' => $message->getEnvelope()->getSender()->toString(),
                            'RawMessage' => [
                                'Data' => $message->toString(),
                            ],
                            'Destinations' => $recipients,
                        ]
                    )
                );
            }
        } catch (AwsException $e) {
            throw new Exception('Request to AWS SES API failed.', $e->getCode(), $e);
        }
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'ses';
    }

    /**
     * Get the Amazon SES client for the SesTransport instance.
     *
     * @return \Aws\Ses\SesClient
     */
    public function ses()
    {
        return $this->ses;
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options)
    {
        return $this->options = $options;
    }
}
