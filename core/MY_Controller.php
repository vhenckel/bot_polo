<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use pimax\FbBotApp;
use pimax\UserProfile;
use pimax\Messages\Address;
use pimax\Messages\Adjustment;
use pimax\Messages\Attachment;
use pimax\Messages\AudioMessage;
use pimax\Messages\FileMessage;
use pimax\Messages\ImageMessage;
use pimax\Messages\Message;
use pimax\Messages\MessageButton;
use pimax\Messages\MessageElement;
use pimax\Messages\MessageReceiptElement;
use pimax\Messages\QuickReply;
use pimax\Messages\SenderAction;
use pimax\Messages\StructuredMessage;
use pimax\Messages\Summary;
use pimax\Messages\VideoMessage;


class MY_Controller extends CI_Controller {

    public $mybot;

    public function __construct($token)
    {
        parent::__construct();

        define('BOT_TOKEN', $token);
        define('API_URL', 'https://graph.facebook.com/v2.8/me/messages?access_token='.BOT_TOKEN);

        // $this->load->library('bot');

        $this->mybot = new FbBotApp(BOT_TOKEN);
    }

    protected function typing_on($sender, $seconds=null)
    {
        $this->mybot->send(new SenderAction($sender, SenderAction::ACTION_TYPING_ON));
        if ($seconds) {
            sleep($seconds);
        }
    }

    protected function mark_seen($sender)
    {
        $this->mybot->send(new SenderAction($sender, SenderAction::ACTION_MARK_SEEN));
    }

    protected function setPersistentMenu($attributes)
    {
        foreach ($attributes as $option) {
            if ($option['type'] == 'postback') {
                $buttons[] = new MessageButton(MessageButton::TYPE_POSTBACK, $option['title'], $option['link']);
            } else {
                $buttons[] = new MessageButton(MessageButton::TYPE_WEB, $option['title'], $option['link']);
            }
        }
        $this->mybot->setPersistentMenu($buttons);
    }

    protected function deletePersistentMenu()
    {
        $this->mybot->deletePersistentMenu();
    }

    protected function setGetStartedButton($payload)
    {
        $this->mybot->setGetStartedButton($payload);
    }

    protected function deleteGetStartedButton()
    {
        $this->mybot->deleteGetStartedButton();
    }

    protected function setGreetingText($greetingText)
    {
        $this->mybot->setGreetingText(['text' => $greetingText]);
    }

    protected function deleteGreetingText()
    {
        $this->mybot->deleteGreetingText();
    }

    protected function sendText($attributes)
    {
        $this->mybot->send(new Message($attributes['sender'], $attributes['message']));
    }

    protected function sendImage($attributes)
    {
        $this->mybot->send(new ImageMessage($attributes['sender'], $attributes['image']));
    }

    protected function sendQuickReply($attributes)
    {
        $this->mybot->send(new QuickReply($attributes['sender'], $attributes['text'], $attributes['quick_replies']));
    }

    protected function sendProfile($attributes)
    {
        $user = $this->mybot->userProfile($attributes['sender']);
        $this->mybot->send(new StructuredMessage($attributes['sender'],
            StructuredMessage::TYPE_GENERIC,
            [
                'elements' => [
                     new MessageElement($user->getFirstName()." ".$user->getLastName(), $user->getGender()." ".$user->getLocale(), $user->getPicture())
                ]
            ]
        ));
    }

    protected function sendButtons($attributes)
    {
        foreach ($attributes['buttons'] as $button) {

            if ($button['type'] == 'web') {
                $buttons[] = new MessageButton(MessageButton::TYPE_WEB, $button['title'], $button['link']);
            } else {
                $buttons[] = new MessageButton(MessageButton::TYPE_POSTBACK, $button['title'], $button['link']);
            }

        }

        $this->mybot->send(new StructuredMessage($attributes['sender'],
            StructuredMessage::TYPE_BUTTON,
            [
                'text' => $attributes['text'],
                'buttons' => $buttons
            ]
        ));
    }

    protected function sendButtonsWV($attributes)
    {
        foreach ($attributes['buttons'] as $button) {

            if ($button['type'] == 'web') {
                $buttons[] = new MessageButton(MessageButton::TYPE_WEB, $button['title'], $button['link'], 'full', true, $button['fallback']);
            } else {
                $buttons[] = new MessageButton(MessageButton::TYPE_POSTBACK, $button['title'], $button['link'], 'full', true, $button['fallback']);
            }

        }
        $this->mybot->send(new StructuredMessage($attributes['sender'],
            StructuredMessage::TYPE_BUTTON,
            [
                'text' => $attributes['text'],
                'buttons' => $buttons
            ]
        ));
        dd($buttons);
    }

    protected function sendGeneric($attributes)
    {
        foreach ($attributes['elements'] as $element) {
            $buttons = null;

            foreach ($element['buttons'] as $button) {
                if ($button['type'] == 'postback') {
                    $buttons[] = new MessageButton(MessageButton::TYPE_POSTBACK, $button['title'], $button['link']);
                } else if ($button['type'] == 'web') {
                    $buttons[] = new MessageButton(MessageButton::TYPE_WEB, $button['title'], $button['link']);
                } else if ($button['type'] == 'share') {
                    $buttons[] = new MessageButton(MessageButton::TYPE_SHARE);
                }
            }
            $final[] = new MessageElement($element['title'], $element['description'], $element['image'], $buttons);
        }

        $this->mybot->send(new StructuredMessage($attributes['sender'],
            StructuredMessage::TYPE_GENERIC,
                [
                    'elements' => $final
                ]
        ));
    }

    protected function sendReceipt($attributes)
    {

        foreach ($attributes['elements'] as $item) {
            $itens[] = new MessageReceiptElement($item['title'], $item['description'], $item['image'], $item['qty'], $item['amount'], "BRL");
        }

        foreach ($attributes['adjustments'] as $adjustmentItem) {
            $adjustment[] = new Adjustment([
                        'name' => $adjustmentItem['name'],
                        'amount' => $adjustmentItem['amount']
                    ]
                );
        }

        $this->mybot->send(new StructuredMessage($attributes['sender'],
            StructuredMessage::TYPE_RECEIPT,
            [
                'recipient_name' => $attributes['recipient_name'],
                'order_number'   => $attributes['order_number'],
                'currency'       => $attributes['currency'],
                'payment_method' => $attributes['payment_method'],
                'order_url'      => $attributes['order_url'],
                'timestamp'      => $attributes['timestamp'],
                'elements'       => $itens,
                'address'        => new Address([
                    'country'     => $attributes['address']['country'],
                    'state'       => $attributes['address']['state'],
                    'postal_code' => $attributes['address']['postal_code'],
                    'city'        => $attributes['address']['city'],
                    'street_1'    => $attributes['address']['street_1'],
                    'street_2'    => $attributes['address']['street_2']
                ]),
                'summary' => new Summary([
                    'subtotal'      => $attributes['summary']['subtotal'],
                    'shipping_cost' => $attributes['summary']['shipping_cost'],
                    'total_tax'     => $attributes['summary']['total_tax'],
                    'total_cost'    => $attributes['summary']['total_cost'],
                ]),
                'adjustments' => $adjustment
            ]
        ));
    }
}