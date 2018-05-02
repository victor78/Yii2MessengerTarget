# Yii2MessengerTarget
MessengerTarget for Yii2 for sending logs as text and/or as files (of which archives) through messengers.
Now this extension supports as messenger only Telegram.

MessengerTarget для Yii2 для отправки логов как текст и/или как файлы(в том числе как архивы) через месенджеры.
Сейчас расширение поддерживает в качестве месседжера только Телеграм.

__English__:
* [Installation](#installation)
* [Configuration](#configuration)
  * [How to create telegram bot](#how-to-create-telegram-bot)
  * [How to take telegram chat id which will receive messages from the bot](#how-to-take-telegram-chat-id-which-will-receive-messages-from-the-bot)
* [How to use](#how-to-use)
* [Notes](#notes)

__Русский__:
* [Установка](https://github.com/victor78/Yii2MessengerTarget#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)
* [Настройка](https://github.com/victor78/Yii2MessengerTarget#%D0%9D%D0%B0%D1%81%D1%82%D1%80%D0%BE%D0%B9%D0%BA%D0%B0)
  * [Как создать телеграм бота](https://github.com/victor78/Yii2MessengerTarget#%D0%9A%D0%B0%D0%BA-%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D1%82%D1%8C-%D1%82%D0%B5%D0%BB%D0%B5%D0%B3%D1%80%D0%B0%D0%BC-%D0%B1%D0%BE%D1%82%D0%B0)
  * [Как получить id чата (id пользователя), который будет получать сообщения от бота](https://github.com/victor78/Yii2MessengerTarget#%D0%9A%D0%B0%D0%BA-%D0%BF%D0%BE%D0%BB%D1%83%D1%87%D0%B8%D1%82%D1%8C-id-%D1%87%D0%B0%D1%82%D0%B0-id-%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D1%82%D0%B5%D0%BB%D1%8F-%D0%BA%D0%BE%D1%82%D0%BE%D1%80%D1%8B%D0%B9-%D0%B1%D1%83%D0%B4%D0%B5%D1%82-%D0%BF%D0%BE%D0%BB%D1%83%D1%87%D0%B0%D1%82%D1%8C-%D1%81%D0%BE%D0%BE%D0%B1%D1%89%D0%B5%D0%BD%D0%B8%D1%8F-%D0%BE%D1%82-%D0%B1%D0%BE%D1%82%D0%B0)
* [Как использовать](https://github.com/victor78/Yii2MessengerTarget#%D0%9A%D0%B0%D0%BA-%D0%B8%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D1%82%D1%8C)
* [Замечания](https://github.com/victor78/Yii2MessengerTarget#%D0%97%D0%B0%D0%BC%D0%B5%D1%87%D0%B0%D0%BD%D0%B8%D1%8F)


## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist victor78/yii2-messenger-target:"~0.0.4"
```

or add

```json
"victor78/yii2-messenger-target": "~0.0.4"
```

to the require section of your composer.json.

## Configuration

```php
return [
    'components' => [
        'telegramPusher' => [
            'class' => 'Victor78\MessengerTarget\TelegramPusher',
            
            'recipients' => [
                //any element is not required
                
                
                //all messages of levels and categories will be received by these users:
                '*' => [
                ],
                
                //messages which initiated by Yii::error('some message') or Yii::info('some message', 'error')
                //or during catching ErrorException 
                    'telegram_chat_id_0', //for example, user_id
                    'telegram_chat_id_1',
                    //...
                    'telegram_chat_id_N',
            'tokens' => [
                //all elements are optional
                //'some_level_or_category' => 'telegram_bot_api_token'
               'info' => 'NNNNNNNNN:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
               'error' => 'NNNNNNNNN:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
               'warning' => 'NNNNNNNNN:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
               'trace' => 'NNNNNNNNN:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
               'some_category_1' => 'NNNNNNNNN:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
               'some_categiry_2' => 'NNNNNNNNN:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ], 
        ]
    ],
    //....
    'log' => [
        'targets' => [
            [
            //SPECIFIC OPTIONS for Yii2MessengerTarget
                //required properties
                'class' => 'Victor78\MessengerTarget\MessengerTarget', //required
                'messenger' => 'telegramPusher', //name of configured component
                
                //optional
                'archiverMethod' => 'zip', // default 'zip', also '7zip', 'tar', '.tar.gz', '.tar.bz2'
                'enableArchiving' => true, //default true; set false to disable archiving of sending files
                'password7zip' => 'password12345', //optional, only with type '7zip'
                
                'viewBothInOneAs' => false, //false is default, choice 'file' or 'text' - if you want glue text and context (global PHP vars) and send it by one message ('text') or one file ('file').
                // if viewBothInOneAs = false, you can choice view of text and context
                'viewMessageAs' => 'text', //also 'file' and false
                'viewContextAs' => 'file', //also 'text' and false
                
                
                //also if you want you can use your own archiver, which implements Victor78\Zipper\ZipperInterface
                'archiver' => function(){
                    return new Some\Namespace\SomeArchiver();
                },
             //USUAL OPTIONS for log target
                //for example
                'categories' => [
                    'yii\db\*',
                    'yii\web\HttpException:*',
                ],     
                'levels' => ['error', 'warning', 'trace', 'info'],
                'except' => [
                    'yii\web\HttpException:404',
                ],         
                'logVars' => ['_SERVER'],
            ]
        ]
    ]
];
```
### How to create telegram bot

1. Message @botfather https://telegram.me/botfather with the following
text: `/newbot`
   If you don't know how to message by username, click the search
field on your Telegram app and type `@botfather`, where you should be able
to initiate a conversation. Be careful not to send it to the wrong
contact, because some users has similar usernames to `botfather`.

   ![botfather initial conversation](http://i.imgur.com/aI26ixR.png)

2. @botfather replies with `Alright, a new bot. How are we going to
call it? Please choose a name for your bot.`

3. Type whatever name you want for your bot.

4. @botfather replies with ```Good. Now let's choose a username for your
bot. It must end in `bot`. Like this, for example: TetrisBot or
tetris_bot.```

5. Type whatever username you want for your bot, minimum 5 characters,
and must end with `bot`. For example: `telesample_bot`

6. @botfather replies with:

    ```
    Done! Congratulations on your new bot. You will find it at
    telegram.me/telesample_bot. You can now add a description, about
    section and profile picture for your bot, see /help for a list of
    commands.

    Use this token to access the HTTP API:
    123456789:AAG90e14-0f8-40183D-18491dDE

    For a description of the Bot API, see this page:
    https://core.telegram.org/bots/api
    ```

7. Note down the 'token' mentioned above.

8. Type `/setprivacy` to @botfather.

   ![botfather later conversation](http://i.imgur.com/tWDVvh4.png)

9. @botfather replies with `Choose a bot to change group messages settings.`

10. Type (or select) `@telesample_bot` (change to the username you set at step 5
above, but start it with `@`)
                //will be sent to these chats
                'error' => [
                    'telegram_chat_id_0', //for example, user_id
                    'telegram_chat_id_1',
                    //...
                    'telegram_chat_id_N',
                ],
                //messages which initiated by Yii::warning('some message') or Yii::info('some message', 'warning')
                //will be sent to these chats
                'warning' => [
                    'telegram_chat_id_0', //for example, user_id
                    'telegram_chat_id_1',
                    //...
                    'telegram_chat_id_N',
                ],
                //messages which initiated by Yii::debug('some message') or Yii::info('some message', 'trace')
                //will be sent to these chats
                'trace' => [
                    'telegram_chat_id_0', //for example, user_id
                    'telegram_chat_id_1',
                    //...
                    'telegram_chat_id_N',
                ],         
                //messages initiated by 
                //Yii::info('info message', 'some_category_1')  or
                //Yii::debug('trace message', 'some_category_1') or
                //Yii::warning('warning message', 'some_category_1') or
                //Yii::error('error message', 'some_category_1')
                //will be sent to these chats     
                'some_category_1' => [
                    'telegram_chat_id_0', //for example, user_id
                    'telegram_chat_id_1',
                    //...
                    'telegram_chat_id_N',
                ],
                //messages initiated by 
                //Yii::info('info message', 'some_category_2')  or
                //Yii::debug('trace message', 'some_category_2') or
                //Yii::warning('warning message', 'some_category_2') or
                //Yii::error('error message', 'some_category_2')
                //will be sent to these chats                
                'some_category_2' => [
                    'telegram_chat_id_0', //for example, user_id
                    'telegram_chat_id_1',
                    //...
                    'telegram_chat_id_N',
                ],
                
            ],
            // level or category accroding to telegram bot through 
11. @botfather replies with

    ```
    'Enable' - your bot will only receive messages that either start with the '/' symbol or mention the bot by username.
    'Disable' - your bot will receive all messages that people send to groups.
    Current status is: ENABLED
    ```

12. Type (or select) `Disable` to let your bot receive all messages sent to a
group. This step is up to you actually.

13. @botfather replies with `Success! The new status is: DISABLED. /help`

## How to use
It is enough to configure component right, and it will work.
To test yout configuration, add it, for example, to some controller:
```php
Yii::info('INFO MESSAGE');
Yii::debug('DEBUG MESSAGE');
Yii::warning('WARNING MESSAGE');
Yii::error('ERROR MESSAGE');

//also you can try create catching of Exception, for example:
1/0;
```
## Notes
For archiving MessengerTarget use [yii2-zipper](https://github.com/victor78/yii2-zipper).

* For zip type Zipper try to use console command zip or php zip extension, so one of them is required on server for zipping.
* For tar, tar.gz, tar.bz2 Zipper try to use GNU tar and BSD tar, so one ofo them is required on server for these ways of arching.
* For zipping by 7zip, the 7za utiliy is required on server.


## Установка
