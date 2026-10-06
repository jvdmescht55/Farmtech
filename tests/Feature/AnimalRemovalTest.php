<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function farmer(): User
    {
        // Admins have every module, which is all these tests need.
        return User::create(['name' => 'Test Boer', 'email' => 'boer@example.com', 'password' => bcrypt('secret-pass'), 'role' => 'admin', 'is_active' => true, 'terms_accepted_at' => now()]);
    }

    private function animal(User $u, string $id, array $extra = []): Animal
    {
        return Animal::create(['user_id' => $u->id, 'in_herd' => true, 'species' => 'sheep', 'visual_id' => $id, 'status' => 'active'] + $extra);
    }

    public function test_a_test_animal_can_be_deleted(): void
    {
        $u = $this->farmer();
        $a = $this->animal($u, 'TEST-1');
        $this->actingAs($u)->delete(route('rfid.animals.destroy', $a))->assertRedirect(route('rfid.animals.index'));
        $this->assertDatabaseMissing('animals', ['id' => $a->id]);
    }

    public function test_a_parent_cannot_be_deleted_so_pedigrees_stay_whole(): void
    {
        $u = $this->farmer();
        $ewe = $this->animal($u, 'EWE-1', ['sex' => 'F']);
        $lamb = $this->animal($u, 'LAMB-1', ['dam_id' => $ewe->id]);
        $this->actingAs($u)->delete(route('rfid.animals.destroy', $ewe))->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('animals', ['id' => $ewe->id]);
        $this->assertSame($ewe->id, $lamb->fresh()->dam_id);
    }

    public function test_marking_sold_keeps_the_animal(): void
    {
        $u = $this->farmer();
        $a = $this->animal($u, 'RAM-1');
        $this->actingAs($u)->post(route('rfid.animals.status', $a), ['status' => 'sold'])->assertRedirect();
        $this->assertSame('sold', $a->fresh()->status);
        $this->assertNotNull($a->fresh()->status_date);
    }

    public function test_bulk_delete_skips_parents(): void
    {
        $u = $this->farmer();
        $ewe = $this->animal($u, 'EWE-2', ['sex' => 'F']);
        $this->animal($u, 'LAMB-2', ['dam_id' => $ewe->id]);
        $test = $this->animal($u, 'TEST-2');
        $this->actingAs($u)->post(route('rfid.animals.bulk'), ['ids' => [$ewe->id, $test->id], 'action' => 'delete'])->assertRedirect();
        $this->assertDatabaseHas('animals', ['id' => $ewe->id]);
        $this->assertDatabaseMissing('animals', ['id' => $test->id]);
    }

    public function test_you_cannot_delete_someone_elses_animal(): void
    {
        $u = $this->farmer();
        $other = User::create(['name' => 'Ander Boer', 'email' => 'ander@example.com', 'password' => bcrypt('secret-pass'), 'role' => 'admin', 'is_active' => true, 'terms_accepted_at' => now()]);
        $a = $this->animal($other, 'THEIRS-1');
        $this->actingAs($u)->delete(route('rfid.animals.destroy', $a))->assertNotFound();   // 404 on purpose: IDs don't leak
        $this->assertDatabaseHas('animals', ['id' => $a->id]);
    }
}
