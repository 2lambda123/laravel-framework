<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class MandrillTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Mandrill API key.
     *
     * @var string
     */
    protected $key;

    /**
     * The Mandrill subaccount.
     *
     * @var string|null
     */
    protected $subaccount;

    /**
     * Create a new Mandrill transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string       $key
     * @param  string|null  $subaccount
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $subaccount = null)
    {
        $this->client = $client;
        $this->key = $key;
        $this->subaccount = $subaccount;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        if (! is_null($this->subaccount)) {
            $message->getHeaders()->addTextHeader('X-MC-Subaccount', $this->subaccount);
        }

        $data = [
            'key' => $this->key,
            'to' => $this->getToAddresses($message),
            'raw_message' => (string) $message,
            'async' => false,
        ];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $options = ['form_params' => $data];
        } else {
            $options = ['body' => $data];
        }

        return $this->client->post('https://mandrillapp.com/api/1.0/messages/send-raw.json', $options);
    }

    /**
     * Get all the addresses this message should be sent to.
     *
     * Note that Mandrill still respects CC, BCC headers in raw message itself.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getToAddresses(Swift_Mime_Message $message)
    {
        $to = [];

        if ($message->getTo()) {
            $to = array_merge($to, array_keys($message->getTo()));
        }

        if ($message->getCc()) {
            $to = array_merge($to, array_keys($message->getCc()));
        }

        if ($message->getBcc()) {
            $to = array_merge($to, array_keys($message->getBcc()));
        }

        return $to;
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string  $key
     * @return void
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     * Get the subaccount being used by the transport.
     *
     * @return string|null
     */
    public function getSubaccount()
    {
        return $this->subaccount;
    }

    /**
     * Set the subaccount being used by the transport.
     *
     * @param  string|null  $subaccount
     * @return void
     */
    public function setSubaccount($subaccount)
    {
        return $this->subaccount = $subaccount;
    }
}
