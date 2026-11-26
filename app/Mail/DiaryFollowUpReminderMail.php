<?php

namespace App\Mail;

use App\Models\Diary;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DiaryFollowUpReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Diary $diary;
    public string $stage; // 'reminder' or 'due'

    public function __construct(Diary $diary, string $stage = 'reminder')
    {
        $this->diary = $diary;
        $this->stage = $stage;
    }

    public function build()
    {
        $companyName = Setting::get('company_name', config('app.name', 'Amstroom'));
        $isDue = $this->stage === 'due';

        $subject = $isDue
            ? "[{$companyName}] Diary Follow-up Due: {$this->diary->event_name}"
            : "[{$companyName}] Diary Follow-up Reminder: {$this->diary->event_name}";

        return $this->subject($subject)
            ->view('emails.diary_follow_up_reminder', [
                'diary'       => $this->diary,
                'stage'       => $this->stage,
                'isDue'       => $isDue,
                'companyName' => $companyName,
                'diaryUrl'    => route('diary.index'),
            ]);
    }
}
