@extends('layouts.app')
@section('title', 'Scanner')
@section('content')
<h1>{{ $event->name }}</h1>
<div class="panel">
    <div class="muted">Entered</div>
    <div class="stat"><span id="entered">{{ $count['entered'] }}</span> / {{ $count['capacity'] }}</div>
</div>

<div class="panel">
    <div id="result" class="result" hidden aria-live="assertive"></div>

    <div id="camera-block" hidden>
        <video id="video" playsinline muted style="width:100%;max-height:320px;background:#000;border-radius:10px"></video>
        <p><button id="camera-toggle" class="btn line" type="button">Start camera</button></p>
    </div>
    <p id="camera-note" class="muted" hidden>This browser cannot scan QR codes with the camera. Type the ticket code or search below.</p>

    <form id="scan-form" class="stack">
        <label for="code">Ticket code</label>
        <input id="code" name="code" autocomplete="off" autocapitalize="characters" placeholder="GTAV-XXXXXXXX" required>
        <p><button class="btn" type="submit">Check in</button></p>
    </form>
</div>

<div class="panel">
    <h2 style="margin-top:0">Find an attendee</h2>
    <form id="search-form" class="stack">
        <label for="q">Name or ticket code</label>
        <input id="q" name="q" minlength="2" required>
        <p><button class="btn line" type="submit">Search</button></p>
    </form>
    <div id="search-results"></div>
</div>

<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const scanUrl = @json(route('checkin.scan', $event));
    const searchUrl = @json(route('checkin.search', $event));
    const countUrl = @json(route('checkin.count', $event));
    const resultBox = document.getElementById('result');
    const entered = document.getElementById('entered');
    let busy = false;

    function show(status, message, name) {
        const labels = {authorized: 'AUTHORIZED', denied: 'DENIED', already_used: 'ALREADY USED'};
        resultBox.hidden = false;
        resultBox.className = 'result ' + status;
        resultBox.textContent = (labels[status] || status) + (name ? ' · ' + name : '') + ' · ' + message;
    }

    async function scan(code) {
        if (busy || !code) return;
        busy = true;
        try {
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({code}),
            });
            if (!res.ok) { show('denied', res.status === 429 ? 'Too many scans, wait a moment.' : 'Request failed (' + res.status + ').'); return; }
            const data = await res.json();
            show(data.status, data.message, data.name);
            entered.textContent = data.count.entered;
        } catch (e) {
            show('denied', 'No connection. Try again.');
        } finally {
            setTimeout(() => { busy = false; }, 1500);
        }
    }

    document.getElementById('scan-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const input = document.getElementById('code');
        scan(input.value.trim());
        input.value = '';
    });

    document.getElementById('search-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const box = document.getElementById('search-results');
        box.textContent = 'Searching...';
        const res = await fetch(searchUrl + '?q=' + encodeURIComponent(document.getElementById('q').value), {headers: {'Accept': 'application/json'}});
        if (!res.ok) { box.textContent = 'Search failed.'; return; }
        const rows = await res.json();
        box.textContent = '';
        if (!rows.length) { box.textContent = 'No matching attendee.'; return; }
        rows.forEach((r) => {
            const div = document.createElement('div');
            div.className = 'card';
            div.style.marginBottom = '8px';
            div.textContent = r.name + (r.company ? ' (' + r.company + ')' : '') + ' · ' + r.code + ' · ' + (r.checked_in ? 'checked in' : (r.paid ? 'paid' : 'not paid')) + ' ';
            if (r.paid && !r.checked_in) {
                const b = document.createElement('button');
                b.className = 'btn small';
                b.type = 'button';
                b.textContent = 'Check in';
                b.addEventListener('click', () => scan(r.code));
                div.appendChild(b);
            }
            box.appendChild(div);
        });
    });

    // Live entry count (FR-24), refreshed every 10 seconds.
    setInterval(async () => {
        try {
            const res = await fetch(countUrl, {headers: {'Accept': 'application/json'}});
            if (res.ok) entered.textContent = (await res.json()).entered;
        } catch (e) {}
    }, 10000);

    // Camera scanning where the browser supports BarcodeDetector (Chrome on Android, for example).
    if (!('BarcodeDetector' in window) || !navigator.mediaDevices) {
        document.getElementById('camera-note').hidden = false;
        return;
    }
    document.getElementById('camera-block').hidden = false;
    const video = document.getElementById('video');
    const toggle = document.getElementById('camera-toggle');
    const detector = new BarcodeDetector({formats: ['qr_code']});
    let stream = null;

    async function loop() {
        if (!stream) return;
        try {
            const codes = await detector.detect(video);
            if (codes.length) scan(codes[0].rawValue.trim());
        } catch (e) {}
        requestAnimationFrame(loop);
    }

    toggle.addEventListener('click', async () => {
        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            stream = null;
            toggle.textContent = 'Start camera';
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
            video.srcObject = stream;
            await video.play();
            toggle.textContent = 'Stop camera';
            loop();
        } catch (e) {
            document.getElementById('camera-note').hidden = false;
        }
    });
})();
</script>
@endsection
