<?php

namespace MailMerge;

use Carbon\Carbon;
use MailMerge\BatchMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MailMerge\MailClient;

class Batch extends Model
{
    public $guarded = [];

    protected $casts = ['resend_at' => 'date'];

    public static function record(BatchMessage $message, string $service): void
    {
        Batch::create([
            'service' => $service,
            'batch_message_hash' => $message->getHash(),
            'batch_message' => $message->serialize(),
        ]);
    }

    public function scopeWhereService(Builder $query, string $service): void
    {
        $query->where('service', $service);
    }

    public function scopeWhereHash(Builder $query, string $service): void
    {
        $query->where('service', $service);
    }

    public function resend(BatchMessage $message): bool
    {
        return static::whereService(get_current_service())
            ->whereHash($message->getHash())
            ->retried()
            ->exists();
    }

    public function getBatchMessageAttribute($serialized): BatchMessage
    {
        $batchMessage = new BatchMessage();
        $batchMessage->unserialize($serialized);

        return $batchMessage;
    }

    public function markAsResend()
    {
        $this->resend_at = Carbon::now();
        $this->save();

        return $this;
    }

    public function mailClient(): MailClient
    {
        return get_mail_client($this->service);
    }
}
