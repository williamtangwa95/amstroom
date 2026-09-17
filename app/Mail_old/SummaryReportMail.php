<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SummaryReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reportData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = 'AMSTROOM ' . ($this->reportData['scope'] ?? 'System') . ' Summary Report — ' . now()->format('d M Y');
        return $this->subject($subject)
                    ->view('emails.summary_report');
    }
}
