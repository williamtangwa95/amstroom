<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FilteredStockReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function build()
    {
        $subject = ($this->reportData['scope'] ?? 'Stock') . ' Stock Valuation Report — ' . now()->format('d M Y');
        return $this->subject($subject)
                    ->view('emails.filtered_stock_report');
    }
}
