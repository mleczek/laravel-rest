<?php


namespace Mleczek\Rest\Tests\Mocks;


class UserWithContext
{
    public function messages()
    {
        return false;
    }

    public function messagesRecipient()
    {
        return true;
    }
}