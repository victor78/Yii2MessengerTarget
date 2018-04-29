<?php

namespace Victor78\MessengerTarget;

use yii\base\Model;
use yii\httpclient\Client;
use yii\base\InvalidConfigException;
use Victor78\MessengerTarget\Interfaces\MessagePusherInterface;

class TelegramPusher extends Model implements MessagePusherInterface
{
    const MAX_MESSAGE_SIZE = 4096;
    
    public $recipients; 
    public $tokens;
    
    
    public function rules()
    {
        return [
            [['recipients', 'tokens'], 'required'],
            ['recipients', 'validateAndSetRecipients'],
            ['tokens', 'validateTokens'],
        ];
    }
    
    public function validateAndSetRecipients($attribute, $params)
    {
        if (is_callable($this->recipients)){
            $call = $this->recipients;
            $this->recipients = $call();
        }
        
        if (!is_array($this->recipients) || !count($this->recipients)){
            $this->addError($attribute, 'Option "recipients" must be an array.');
        }
             
    }
    
    public function validateTokens($attribute, $params)
    {
        if (!is_array($this->tokens)){
            $this->addError($attribute, 
            'Option "tokens": must be an array, where every key '
                . 'is the category or the level of logging and '
                . 'the appropriate  value is directly token of Telegram Bot.');
        }
    }
    
    public function init($config = [])
    {
        if (!$this->validate()){
            foreach ($this->getFirstErrors() as $errorMessage){
                throw new InvalidConfigException('"Component "' 
                . get_class($this) . '" is configured invalid. '. $errorMessage);
            }
        }
        parent::init($config);
    }

    protected function getChats($category)
    {
        $chats = [];
        if (isset($this->recipients[$category])){
            $chats = $this->recipients[$category];
        }
        
        if (isset($this->recipients['*'])) {
            $chats = array_merge($chats, $this->recipients['*']);
            $chats = array_unique($chats);
        }

        return $chats;         
    }
    
    /**
     * Sending the log as a text message.
     * @param string $message - text message 
     * @param string $category - the  category of recipients
     */
    public function sendText(string $message, string $category)
    {
        if (!isset($this->tokens[$category])){
            return;
        }
        $token = $this->tokens[$category];

        $chats = $this->getChats($category);
        
        foreach ($chats as $chat_id){
            $this->sending($message, $chat_id, $token);
        }
            
        
    }
    
    /**
     * 
     * @param string $message - text which is sending
     * @param string/int $toChatId - chat_id where bot's sending
     * @throws \yii\base\InvalidValueException
     */
    protected function sending(string $message, $toChatId, $token)
    {
        
        $url = 'https://api.telegram.org/bot'
                .$token
                .'/sendMessage';
        $client = new Client();
        
        //sending full text by parts 
        while (mb_strlen($message, 'UTF-8') > 0) {
            $chopped = mb_substr($message, 0, self::MAX_MESSAGE_SIZE);
            $Response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl($url)
                ->setData([
                    'disable_web_page_preview' => true,
                    'text' =>  $chopped,
                    'chat_id' =>  $toChatId,
                ])->send();
            if (!$Response->getIsOk()) {
                if (isset($Response->getData()['description'])) {
                    $description = $Response->getData()['description'];
                } else {
                    $description = $Response->getContent();
                }

                throw new \yii\base\InvalidValueException(
                    'Unable to send message to Telegram: ' . $description, 
                    (int) $Response->getStatusCode()
                );
            }   
            $message = mb_substr($message, self::MAX_MESSAGE_SIZE);                 
        }
    }
    
    /**
     * 
     * @param string $file - the full path to file
     * @param string $category - the category of recipients
     * @param string $fileName - name of file how it will be for recepient
     */
    public function sendFile(string $file, string $category, string $fileName = null)
    {
        if (!isset($this->tokens[$category])){
            return false;
        }
        
        $token = $this->tokens[$category];
        
        $chats = $this->getChats($category);
        
        $url = 'https://api.telegram.org/bot'.$token.'/sendDocument';
        
        $client = new Client();

        foreach ($chats as $chat_id) {
            $result = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($url)
                ->addFile('document', $file, [
                    'fileName' => $fileName,
                ])
                ->setData([
                    'chat_id' => $chat_id,
                ])->send();
        }            
        return $result;
    }   
    
    
    public function isEmojiSupported():bool
    {
        return true;
    }
}