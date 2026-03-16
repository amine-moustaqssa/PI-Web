<?php

namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;

    public function __construct(
        string $accountSid, 
        string $authToken, 
        string $fromNumber
    ) {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->fromNumber = $fromNumber;
    }

    public function sendSms(string $to, string $message): array
    {
        try {
            $client = new Client($this->accountSid, $this->authToken);
            
            $twilioMessage = $client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );

            return [
                'success' => true,
                'sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}