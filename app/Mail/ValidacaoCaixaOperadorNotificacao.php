<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ValidacaoCaixaOperadorNotificacao extends Mailable
{
    use Queueable, SerializesModels;


    private $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->to($this->data['email']);
        $this->from(env('MAIL_FROM_ADDRESS'));
        $this->subject($this->data['assunto']);
        return $this->view("mail.validacaoFechoCaixaOperador", $this->data);
    }
}
