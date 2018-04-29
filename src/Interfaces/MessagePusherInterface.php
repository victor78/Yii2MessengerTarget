<?php
namespace Victor78\MessengerTarget\Interfaces;

/**
 * Interface for classes for sending to some recepients by their category
 */
interface MessagePusherInterface
{
    
    /**
     * Sending the log like a text message.
     * @param string $message - text message 
     * @param string $category - the  category of recipients
     */
    public function sendText(string $message, string $category);
    
    /**
     * 
     * @param string $file - the full path to file
     * @param string $category - the category of recipients
     * @param string $fileName - name of file how it will be for recepient
     */
    public function sendFile(string $file, string $category, string $fileName = null);

    /**
     * return true if the messenger supports emoji in the text messages
     */
    public function isEmojiSupported():bool;

}