<?php
/**
 * Whatsapp Class
 * This class enables you to use the Whatsapp Protocol
 *
 * @subpackage Libraries
 * @category   Analytics
 */

defined('BASEPATH') || exit('No direct script access allowed');


use GuzzleHttp\Client;

class Api_whatsapp
{
    /**
     * @var
     */
    private $client = null;
    /**
     * @var
     */
    private $phoneNumberId;
    /**
     * @var
     */
    private $access_token;
    /**
     * @var string
     */
    private $url;

    /**
     * @param string $phoneNumberId
     * @param string $accessToken
     * @param string $version
     * @throws Exception
     */    
    public function __construct(string $phoneNumberId = null, string $accessToken = null, string $version = "v16.0")
    {
        $this->phoneNumberId = $phoneNumberId;
        $this->access_token = $accessToken;
        if (empty($this->phoneNumberId) || empty($this->access_token)) {
            echo 'phone_number_id and access_token are required';
        }
        $this->url = "https://graph.facebook.com/{$version}/{$this->phoneNumberId}/messages";
        $this->createClient();
    }

    /**
     * @return void
     */
    public function createClient()
    {
        $this->client = new Client([
            'headers' => [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer {$this->access_token}",
                "Accept" => "application/json",
            ],
        ]);
    }    
    
    /**
     * @param string $message
     * @param string $recipientId
     * @param string $recipientType
     * @param bool $previewUrl
     * @return mixed
     */
    public function send_message(
        string $message,
        string $recipientId,
        string $recipientType = "individual",
        bool $previewUrl = true
    ) {
        $data = [
            "messaging_product" => "whatsapp",
            "recipient_type" => $recipientType,
            "to" => $recipientId,
            "type" => "text",
            "text" => ["preview_url" => $previewUrl, "body" => $message],
        ];
        $response = $this->client->post($this->url, ['json' => $data]);

        return $response->getBody();
    }
     

    /**
     * @param string $template
     * @param string $recipientId
     * @param string $lang
     * @return mixed
     */
    public function send_template(string $template, string $recipientId, string $lang = "en_US")
    {
        /*
         * Sends a template message to a WhatsApp user, Template messages can either be;
         * 1. Text template
         * 2. Media based template
         * 3. Interactive template
         * You can customize the template message by passing a dictionary of components.
         * You can find the available components in the documentation.
         * https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
         *
         * @param string $template: Template name to be sent to the user
         * @param string $recipient_id: Phone number of the user with country code wihout +
         * @param string $lang: Language of the template message
         * @param array $components: List of components to be sent to the user
         *
         * @return json
         */

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $recipientId,
            "type" => "template",
            "template" => ["name" => $template, "language" => ["code" => $lang]],
        ];

        $response = $this->client->post($this->url, ['json' => $data]);

        return $response->getBody();
    }
    
    /**
     * @param $data
     * @return mixed
     */
    public function preprocess($data)
    {
        return $data["entry"][0]["changes"][0]["value"];
    }
    
    /**
     * @param $data
     * @return mixed|void
     */
    public function get_message($data)
    {
        $data = $this->preprocess($data);
        if (array_key_exists("messages", $data)) {
            return $data["messages"][0]["text"]["body"];
        }
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function getMessageId($data)
    {
        $data = $this->preprocess($data);
        if (array_key_exists("messages", $data)) {
            return $data["messages"][0]["id"];
        }
    }    
}

