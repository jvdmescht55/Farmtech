<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_weight_now_is_the_latest_and_gain_ignores_same_day_reweighs(): void
    {
        $u = User::create(['name' => 'Test Boer', 'email' => 'boer@example.com', 'password' => bcrypt('secret-pass'), 'role' => 'admin', 'is_active' => true, 'terms_accepted_at' => now()]);
        $a = Animal::create(['user_id' => $u->id, 'in_herd' => true, 'species' => 'sheep', 'visual_id' => 'A1', 'status' => 'active']);
        foreach ([['2026-09-01 08:00', 15.0], ['2026-09-11 08:00', 20.0], ['2026-09-11 08:01', 20.5]] as [$at, $kg]) {
            Scan::create(['user_id' => $u->id, 'animal_id' => $a->id, 'weight_kg' => $kg, 'scanned_at' => $at]);
        }

        $r = $this->actingAs($u)->get(route('rfid.animals.show', $a))->assertOk();
        $this->assertSame(20.5, (float) $r->viewData('weightPoints')[2]['value']);
        // (20.5 - 15.0) kg over 10 days, not 500 g from the re-weigh a minute later.
        $this->assertSame(550, $r->viewData('recentAdg'));
    }
}
