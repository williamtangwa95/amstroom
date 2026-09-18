<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FilteredSalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;

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
        $subject = ($this->reportData['scope'] ?? 'Sales') . ' Sales Report — ' . ($this->reportData['period_label'] ?? now()->format('d M Y'));
        return $this->subject($subject)
                    ->view('emails.filtered_sales_report');
    }
}
