<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FilteredExpensesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function build()
    {
        $subject = ($this->reportData['scope'] ?? 'Expenses') . ' Expenses Report — ' . ($this->reportData['period_label'] ?? now()->format('d M Y'));
        return $this->subject($subject)
                    ->view('emails.filtered_expenses_report');
    }
}
