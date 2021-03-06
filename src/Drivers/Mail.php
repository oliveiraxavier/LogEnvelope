<?php 

namespace Yaro\LogEnvelope\Drivers;

use Illuminate\Support\Facades\Mail as MailFacade;
use Yaro\LogEnvelope\Mail\LogEmail;

class Mail extends AbstractDriver
{
    
    protected function prepare() 
    {
        $this->config['from_name']  = $this->config['from_name'] ?: 'Log Envelope';
        $this->config['from_email'] = $this->config['from_email'] ?: 'logenvelope@'. $this->data['host'];
    } // end prepare
    
    protected function check() 
    {
        return $this->isEnabled() && (isset($this->config['to']) && $this->config['to']);
    } // end check
    
    public function send()
    {
        if (!$this->check()) {
            return;
        }
        
        $data = $this->data;
        $config = $this->config;
        
        if ($this->isMailablePossible()) {
            MailFacade::queue(new LogEmail($data, $config));
            return;
        }
        
        MailFacade::queue('log-envelope::main', $data, function($message) use ($data, $config) {
            $subject = sprintf('[%s] @ %s: %s', $data['class'], $data['host'], $data['exception']);
            
             // to protect from gmail's anchors automatic generation
            $message->setBody(
                preg_replace(
                    ['~\.~', '~http~'],
                    ['<span>.</span>', '<span>http</span>'],
                    $message->getBody()
                )
            );
            
             $message->to($config['to'])
                     ->from($config['from_email'], $config['from_name'])
                     ->subject($subject);
        });
    } // end send
    
    private function isMailablePossible()
    {
        return class_exists('Illuminate\Mail\Mailable');
    } // end isMailablePossible
    
}
