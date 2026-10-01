<?php

namespace App\Mail;

use App\Models\HandoverReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HandoverCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public HandoverReport $handover;
    public string $excelBinary;

    /**
     * Create a new message instance.
     */
    public function __construct(HandoverReport $handover, string $excelBinary)
    {
        $this->handover = $handover;
        $this->excelBinary = $excelBinary;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = 'Cash Handover Confirmed & Commission Summary: ' . $this->handover->handover_no . ' (' . ($this->handover->shop->shop_name ?? 'Shop') . ')';

        $mail = $this->subject($subject)
                     ->view('emails.handover_completed', [
                         'handover' => $this->handover,
                     ]);

        if (!empty($this->excelBinary)) {
            $mail->attachData(
                $this->excelBinary,
                'Handover_Report_' . $this->handover->handover_no . '.xlsx',
                [
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );
        }

        return $mail;
    }
}
