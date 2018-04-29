<?php

namespace Victor78\MessengerTarget;

use Yii;
use Victor78\Zipper\Zipper;
use \yii\log\Logger;

use yii\base\InvalidValueException;

use Victor78\MessengerTarget\Interfaces\MessagePusherInterface;
use Victor78\Zipper\ZipperInterface;

class MessengerTarget extends \yii\log\Target
{
    public $levelEmojis = [
        Logger::LEVEL_ERROR => '‼️',
        Logger::LEVEL_WARNING => '❗️',
        Logger::LEVEL_INFO => 'ℹ️',
        Logger::LEVEL_TRACE => '✏️',
    ];
    
    //component name or object of Messenger, which implements MessagePusherInterface 
    public $messenger;
    
    public $archiverMethod; // 'zip', '7zip', 'tar', '.tar.gz', '.tar.bz2' or null
    public $password7zip; // only for '7zip' archiverMethod
    public $dir = '@runtime/logs/messenger_target';
    
    public $viewBothInOneAs = false;
    public $viewMessageAs = 'text';
    public $viewContextAs = 'file';
    
    public $enableArchiving = true;
    
    public $archiver;
    
    protected $timestamp;
    protected $level;
    protected $category;

    
    public function init()
    {
 
        parent::init();
        $this->initArchiver();
    }
    
    protected function initArchiver()
    {
        if (!$this->archiver) {
            $zipperClass = Zipper::class;
            $config = [];
            if ($this->archiverMethod) {
                $config['type'] = $this->archiverMethod;
            }
            if ($this->password7zip && $this->archiverMethod == '7zip') {
                $config['password'] = $this->password7zip;
            }
            $this->archiver = new $zipperClass($config);
        } else {
            if (is_callable($this->archiver)) {
                $func = $this->archiver;
                $archiver = $func();
            } else {
                $archiver = $this->archiver;
            }
            if ($archiver instanceof ZipperInterface){
                $this->archiver = $archiver;
            } else {
                throw new InvalidValueException(
                    'archiver must implement Victor78\Zipper\ZipperInterface! '
                );   
            }
        }
    }
    
    /**
     * 
     * @return object with MessagePusherInterface
     */
    protected function getMessenger()
    {

        if ($this->messenger && Yii::$app->get($this->messenger)){
            $messenger = Yii::$app->get($this->messenger);
        } else {
            $messenger = $this->messenger;
        }     
        if (is_object($messenger) 
            &&  ($messenger instanceof MessagePusherInterface)){
            return $messenger;
        }   
    }
    
    /**
     * 
     * @param int $level - corresponds to \yii\log\Logger::LEVEL_[NAME]
     * @return string 
     */
    protected function getSignForLevel(int $level): string
    {
        
        $messenger = $this->getMessenger();
        if ($messenger->isEmojiSupported()){
            return $this->levelEmojis[$level]??"[$level]";
        } else {
            return "[$level]";
        }
    }
    
    /**
     * 
     * @param $item - variable with unknown type 
     * @return boolean - true if $item can be string
     */
    public static function isString($item)
    {

        return 
        (!is_array($item)) && ((!is_object($item) && settype($item, 'string') !== false) 
        || (is_object($item) && method_exists($item, '__toString' )));
        
    }
    
    /**
     * 
     * @param string $message
     * @return string
     */
    public function formatMessage($message) : string
    {   
        
        list($text, $level, $category, $t) = $message;
        
        if (!self::isString($text)){
            $text = var_export($text, 1);
        }
        $full_text = $this->getSignForLevel($level);
        $full_text .= " [$t] ";
        $full_text .= '['.Yii::$app->name.']';     
        if (isset(Yii::$app, Yii::$app->request, Yii::$app->request->ipHeaders)){
            $full_text .= PHP_EOL.'IP '.Yii::$app->request->getUserIP();
        }
        $full_text .= PHP_EOL.PHP_EOL.$text;
        return $full_text;
    }

    /**
     * 
     * @param array $firstMessage
     */
    protected function setLevelCategoryTimestamp(array $firstMessage)
    {
        list($text, $level, $category, $t) = $firstMessage;
        $this->timestamp = $t;        
        $this->level = $level;        
        $this->category = $category;        
    }
    
    /**
     * 
     * @param array $messages
     * @param boolean $final
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, static::filterMessages($messages, $this->getLevels(), $this->categories, $this->except));

        
        $count = count($this->messages);
        if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
            
            $this->setLevelCategoryTimestamp($this->messages[0]);
            $this->categoryMapper();            
            
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            $this->export($this->getContextMessage());
            

            $this->exportInterval = $oldExportInterval;

            $this->messages = [];
        }
    }
    
    
    public function export($context = '')
    {
        
        $messenger = $this->getMessenger();
        foreach ($this->messages as $msg){
            list($text, $level, $category, $timestamp) = $msg;
            $message = $this->formatMessage($msg);
            
            if ($category == 'application') {
                $recepientsCategory = Logger::getLevelName($level);
            } else {
                $recepientsCategory = $category;
            }

            if ($this->viewBothInOneAs){
                $bothText = $message
                    .PHP_EOL.PHP_EOL.str_repeat('-', 32).PHP_EOL.PHP_EOL
                    .$context;
                if ($this->viewBothInOneAs == 'file'){
                    $this->createAndPushFile($bothText, $recepientsCategory, $timestamp, 'FULL_LOG');
                } elseif ($this->viewBothInOneAs == 'text') {
                    $messenger->sendText($bothText, $recepientsCategory); 
                }
            } else {            
                
                if ($this->viewMessageAs == 'text'){
                    $messenger->sendText($message, $recepientsCategory); 
                } elseif ($this->viewMessageAs == 'file'){
                    $this->createAndPushFile($message, $recepientsCategory, $timestamp, 'LOG');
                }
                if ($this->viewContextAs == 'text') {
                    $messenger->sendText($context, $recepientsCategory);
                } else if ($this->viewContextAs == 'file'){
                    $this->createAndPushFile($context, $recepientsCategory, $timestamp, 'CONTEXT');
                }
            }
        }
    }
    
    public function createAndPushFile($text, $recepientsCategory, $timestamp, $prefix)
    {
        $messenger = $this->getMessenger();

        $tmpFileName = $this->saveTextToRandFile($text);
        $fileName = $prefix.'_'.$timestamp;
        if ($this->enableArchiving){
            $file = $this->archiving($tmpFileName);
            $fileName .= '.'.$this->archiver->ext;
        } else {
            $file = $tmpFileName;
            $fileName .= '.txt';
        }
        $messenger->sendFile($file, $recepientsCategory, $fileName);          
        
        @unlink($tmpFileName);
        @unlink($file);
        
    }
    
    protected function saveTextToRandFile($text, $prefix = 'MSSNGR_TRGT_')
    {
        $filepath = Yii::getAlias($this->dir);
        \yii\helpers\FileHelper::createDirectory($filepath);
        $tmpfname = tempnam($filepath, $prefix);

        $fp = fopen($tmpfname, "w");
        fwrite($fp, $text);
        fclose($fp);
        return $tmpfname;
    }
    
    /**
     * 
     * @param array|string $input - names or name of files for archiving
     * @return type
     */
    protected function archiving($input)
    {
        if (is_array($input)) {
            $files = $input;
        } else {
            $file = $input;
            $files = [];
            $files[] = $file;
        }
        
        $folder = Yii::getAlias($this->dir);
        foreach ($files as $file){
            $configFiles[str_replace($folder.'/', '', $file)] = $file;
        }
        
        $archiveName = strtr($this->timestamp, '.', '_')
                .'__'.bin2hex(random_bytes(5)).'.'.$this->archiver->ext;
        $archivePath =  $folder.DIRECTORY_SEPARATOR.$archiveName;
        $type = $this->archiver->type;
        $archive = $this->archiver->create($archivePath, $configFiles, true, $type);
        $this->clearFiles($files);
        return $archivePath;
    }
    
    protected function clearFiles($files)
    {
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    protected function categoryMapper()
    {
        $matches = null;
        if (preg_match('^(.*):[0-9]+^', $this->category, $matches)){
            if (isset($matches[1])){
                $exceptionClass = $matches[1];
                if (is_subclass_of($exceptionClass, \ErrorException::class)
                    || is_subclass_of($exceptionClass, \Exception::class)){
                    $this->category = 'error';
                }
            }
        }
    }
}
