<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use DrewM\MailChimp\MailChimp;
use Log;

class SyncUserWithMailchimp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($attributes)
    {
        $this->attributes = $attributes;
        $this->mailChimp = new MailChimp(env('MAILCHIMP_API_KEY'));
        $this->listId = env('MAILCHIMP_LIST');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $previousEmail = data_get($this->attributes, 'previous.email');
        $current = data_get($this->attributes, 'current');

        if (!isset($current->email)) {
            return $this->delete($previousEmail);
        }

        return $previousEmail
            ? $this->update($previousEmail, $current)
            : $this->create($current);
    }

    /**
     * create
     *
     * @param  mixed $attributes
     * @return void
     */
    private function create($attributes)
    {
        $name = $this->splitName($attributes->name);

        return $this->mailChimp->post("lists/" . $this->listId . "/members", [
            'email_address' => trim($attributes->email),
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => $name[0],
                'LNAME' => $name[1],
                'NAME' => $attributes->name,
            ],
        ]);
    }

    /**
     * update
     *
     * @param  mixed $email
     * @param  mixed $attributes
     * @return void
     */
    private function update($email, $attributes)
    {
        $hash = MailChimp::subscriberHash(trim($email));

        $name = $this->splitName($attributes->name);

        return $this->mailChimp->patch("lists/". $this->listId . "/members/$hash", [
            'email_address' => trim($attributes->email),
            'merge_fields' => [
                'FNAME' => $name[0],
                'LNAME' => $name[1],
                'NAME' => $attributes->name,
            ],
        ]);
    }


    /**
     * delete
     *
     * @param  mixed $email
     * @return void
     */
    private function delete($email)
    {
        $hash = MailChimp::subscriberHash(trim($email));

        return $this->mailChimp->delete("lists/". $this->listId . "/members/$hash");
    }

    /**
     * splitName
     *
     * @param  mixed $name
     * @return void
     */
    private function splitName($name)
    {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#'.preg_quote($last_name, '#').'#', '', $name));

        return array($first_name, $last_name);
    }
}
