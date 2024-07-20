<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_user_model_validations()
    {
        $user = new \App\Models\User([
            'name' => 'Test User',
            'email' => 'invalid-email',
        ]);

        $this->assertFalse($user->validate());
    }
}
