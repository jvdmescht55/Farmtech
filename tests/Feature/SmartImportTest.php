<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

require_once __DIR__.'/../Support/mkxlsx.php';

class SmartImportTest extends TestCase
{
    use RefreshDatabase;

    private function farmer(): User
    {
        return User::create(['name' => 'Test Boer', 'email' => 'boer@example.com', 'password' => bcrypt('secret-pass'), 'role' => 'admin', 'is_active' => true, 'terms_accepted_at' => now(), 'species' => 'sheep']);
    }

    /** Upload, then accept the preview as we guessed it. */
    private function upload(User $u, UploadedFile $file): void
    {
        $this->actingAs($u)->post(route('rfid.data.preview'), ['file' => $file])->assertRedirect(route('rfid.data'));
        $preview = session('preview');
        $this->assertNotEmpty($preview['sheets']);
        $this->post(route('rfid.data.import'), ['token' => $preview['token'], 'name' => $preview['name']])->assertRedirect(route('rfid.data'));
    }

    public function test_a_messy_afrikaans_workbook_lands_in_the_right_places(): void
    {
        $u = $this->farmer();
        $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        mkxlsx($path, [
            'Kudde' => [
                ['Die Bult Stoet: kudde 2026'], [],
                ['Oornommer', 'Elektroniese nommer', 'Geslag', 'Gebore', 'Tweeling', 'Vaar', 'Moer', 'Kamp', 'Geboorte gewig', 'Speengewig'],
                ['TST 26 001', '982 000412345001', 'Ooi', 46082, 'Tweeling', 'TST 22 100', 'TST 23 200', 'Kamp 4', '4,2', 31.5],
                ['TST 26 002', '982000412345002', 'Ram', '15/03/2026', 'Enkel', 'TST 22 100', 'TST 23 201', 'Kamp 2', 5.1, ''],
                ['Totaal', '', '', '', '', '', '', '', '', ''],
            ],
            'Wegings' => [
                ['Tag', 'Weight 01/09/2026', 'Weight 01/10/2026'],
                ['982000412345001', 38.2, 41.0],
            ],
            'Behandelings' => [
                ['Dier', 'Datum', 'Aksie', 'Produk', 'Onttrekking dae'],
                ['TST 26 001', '05/09/2026', 'Doseer', 'Closantel', 28],
                ['TST 26 002', '05/09/2026', 'Ingeënt', 'Multivax', ''],
            ],
        ]);
        $this->upload($u, new UploadedFile($path, 'plaas.xlsx', null, null, true));

        $ewe = Animal::where('visual_id', 'TST 26 001')->firstOrFail();
        $this->assertSame('982000412345001', $ewe->eid);
        $this->assertSame('F', $ewe->sex);
        $this->assertSame('2026-03-01', $ewe->birth_date->toDateString());
        $this->assertSame('02', $ewe->birth_type);
        $this->assertSame('TST 22 100', $ewe->sire->visual_id);
        $this->assertSame('TST 23 200', $ewe->dam->visual_id);
        $this->assertStringContainsString('Kamp: Kamp 4', $ewe->notes);
        $this->assertSame('M', Animal::where('visual_id', 'TST 26 002')->value('sex'));
        $this->assertNull(Animal::where('visual_id', 'TOTAAL')->first());

        $weights = Scan::where('animal_id', $ewe->id)->orderBy('scanned_at')->get();
        $this->assertSame([4.2, 31.5, 38.2, 41.0], $weights->pluck('weight_kg')->map(fn ($w) => (float) $w)->all());
        $this->assertSame(['birth', 'wean', 'routine', 'routine'], $weights->pluck('weigh_type')->all());
        $this->assertSame('2026-09-01', $weights[2]->scanned_at->toDateString());

        $dose = AnimalEvent::where('animal_id', $ewe->id)->firstOrFail();
        $this->assertSame('dosing', $dose->type);
        $this->assertSame('2026-10-03', $dose->withdrawal_until->toDateString());
        $this->assertSame('vaccination', AnimalEvent::where('product', 'Multivax')->value('type'));
    }

    public function test_a_semicolon_csv_with_english_headings_and_cattle_breeds(): void
    {
        $u = $this->farmer();
        $csv = "Cow ID;RFID;Breed;DOB;Sex;Status;Weight (kg);Weigh Date;Paddock\nC1;982000412345101;Bonsmara;2024-02-11;Heifer;Active;412;2026-10-01;North\nC2;982000412345102;Bonsmara;2023-12-30;Bull;Sold;610,5;2026-10-01;South\n";
        $this->upload($u, UploadedFile::fake()->createWithContent('cattle.csv', $csv));

        $c2 = Animal::where('visual_id', 'C2')->firstOrFail();
        $this->assertSame('cattle', $c2->species);
        $this->assertSame('sold', $c2->status);
        $this->assertSame('M', $c2->sex);
        $this->assertSame(610.5, (float) Scan::where('animal_id', $c2->id)->value('weight_kg'));
        $this->assertStringContainsString('Paddock: South', $c2->notes);
    }

    public function test_importing_the_same_file_twice_adds_nothing_new(): void
    {
        $u = $this->farmer();
        $csv = "ID,Sex,Weight,Date\nA1,F,40,01/10/2026\nA2,M,44,01/10/2026\n";
        $this->upload($u, UploadedFile::fake()->createWithContent('a.csv', $csv));
        $this->upload($u, UploadedFile::fake()->createWithContent('a.csv', $csv));

        $this->assertSame(2, Animal::count());
        $this->assertSame(2, Scan::count());
    }

    public function test_rows_pasted_from_excel_are_read_too(): void
    {
        $u = $this->farmer();
        $this->actingAs($u)->post(route('rfid.data.paste'), ['lines' => "Oornommer\tGewig\tDatum\nP1\t42,5\t30/09/2026"])->assertRedirect(route('rfid.data'));
        $preview = session('preview');
        $this->assertSame(['visual_id', 'weight', 'date'], $preview['sheets'][0]['map']);
    }
}
