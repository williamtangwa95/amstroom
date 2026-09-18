<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FilteredTransferReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function build()
    {
        $subject = ($this->reportData['scope'] ?? 'Stock') . ' Stock Transfer Requests Report — ' . now()->format('d M Y');
        return $this->subject($subject)
                    ->view('emails.filtered_transfer_report');
    }
}
