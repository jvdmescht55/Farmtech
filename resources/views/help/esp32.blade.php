@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Get the latest scale firmware', 'body' => '<p>Download the KraalTrac Pro sketch. It talks straight to farmtech.site — no PC, XAMPP, router or DuckDNS needed any more.</p><p>In the Arduino IDE install the libraries <strong>LiquidCrystal_I2C</strong>, <strong>Keypad</strong> and <strong>ArduinoJson</strong> (Sketch → Include Library → Manage Libraries).</p>', 'cta' => [route('rfid.readers.firmware'), '↓ kraaltrac_pro.ino']])
    @include('help._step', ['n' => 2, 'title' => 'Put in your Wi-Fi networks', 'body' => '<p>Near the top, in <code>knownNetworks[]</code>, list every Wi-Fi it might be near — farm Wi-Fi and your phone\'s hotspot. Give the hotspot a fixed name and password in your phone\'s settings first.</p><p>Nothing else needs changing. Flash it to the ESP32.</p>'])
    @include('help._step', ['n' => 3, 'title' => 'Pair it — six digits', 'body' => '<p>On first start the LCD shows <code>Devices &gt; Pair it: 482 913</code>. Type that code on the Devices page. The scale shows <strong>Paired! Lekker.</strong> and remembers its key.</p>', 'cta' => [route('rfid.readers.index'), 'Open Devices']])
    @include('help._step', ['n' => 4, 'title' => 'Weigh as usual', 'body' => '<p>Scan a tag (or type the number), pick the weight type, punch in the weight, <code>#</code> to save. Every record is saved on the scale first, then sent. Open <strong>Start weighing</strong> on your phone to watch them arrive.</p><p><strong>New lamb?</strong> The scale fills in the next birthday number for this month (e.g. <code>251004</code>) — press <code>#</code> to accept. <a class="link-u text-char" href="'.route('help.show', 'ids').'">About birthday numbers</a></p>'])
    @include('help._step', ['n' => 5, 'title' => 'No Wi-Fi at the kraal? Three options', 'body' => '<ul class="list-disc pl-5 space-y-2"><li><strong>Do nothing</strong> — it keeps up to 300 records and syncs by itself next time it sees Wi-Fi. The top line shows <code>&gt;&gt; 12 QUEUED &lt;&lt;</code> until then.</li><li><strong>Paste it</strong> — plug into a laptop, open the Serial Monitor at 115200, press <code>B</code> on the scale, copy the lines between BEGIN and END, and paste them under Import &amp; export → <strong>Paste from the scale</strong>.</li><li><strong>USB script</strong> — on Windows, run <code>sync_from_scale.ps1</code>: it reads the scale, sends everything, and clears the scale only once all of it saved.</li></ul>', 'cta' => [route('rfid.readers.firmware', ['device' => 'usb']), '↓ sync_from_scale.ps1']])
    @include('help._step', ['n' => 6, 'title' => 'After a paste or USB sync', 'body' => '<p>Once the website confirms the records, clear the scale\'s queue: idle screen → <code>A</code> → PIN → <code>#</code>. (The USB script does this for you.) Pasting the same lines twice is safe — duplicates are skipped.</p>'])
@endsection
@section('aside')
    <div class="rounded-[24px] bg-char text-sand p-6 text-sm">
        <div class="font-headline text-2xl">Scale keys</div>
        <dl class="mt-4 grid grid-cols-[3.5rem_1fr] gap-y-1.5 text-sand/80">
            <dt class="font-num">#</dt><dd>OK / save</dd><dt class="font-num">*</dt><dd>Back / cancel</dd><dt class="font-num">C</dt><dd>Delete a digit</dd>
            <dt class="font-num">A</dt><dd>Decimal point (weight) · Clear queue (idle)</dd><dt class="font-num">B</dt><dd>Send queue over USB (idle)</dd><dt class="font-num">D</dt><dd>Skip (sire/dam) · Device info (idle)</dd>
        </dl>
    </div>
@endsection
