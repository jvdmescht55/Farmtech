<?php

namespace Tests\Feature;

use App\Mail\SupportRequestMailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** The support form sends a real email — no submission is silently dropped, and validation actually blocks bad input. */
class SupportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_submission_sends_a_real_email_to_the_admin(): void
    {
        Mail::fake();

        $response = $this->post(route('support.store'), [
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'topic' => 'Order status',
            'message' => 'When will my order ship?',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertSent(SupportRequestMailable::class, function (SupportRequestMailable $mail) {
            return $mail->senderEmail === 'farmer@example.com' && $mail->topic === 'Order status';
        });
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        Mail::fake();

        $response = $this->post(route('support.store'), ['name' => 'Test Farmer']);

        $response->assertSessionHasErrors(['email', 'topic', 'message']);
        Mail::assertNothingSent();
    }
}
