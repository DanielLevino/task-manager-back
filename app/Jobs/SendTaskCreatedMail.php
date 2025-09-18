<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;
use App\Mail\TaskCreatedMail;
use Illuminate\Support\Facades\Mail;

class SendTaskCreatedMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle()
    {
        $to = $this->task->creator?->email;
        if ($to) {
            Mail::to($to)->send(new TaskCreatedMail($this->task));
        }
    }
}
