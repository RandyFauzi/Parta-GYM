<div data-attendance-camera>
    <p>Ambil foto saat absensi. Cap waktu akan dibuat oleh server setelah foto diterima.</p>
    <video hidden playsinline muted aria-label="Pratinjau kamera absensi" style="width:100%;max-width:480px"></video>
    <img hidden data-photo-preview alt="Foto absensi yang akan dikirim" style="width:100%;max-width:480px">
    <div class="table-actions">
        <button type="button" class="button secondary" data-photo-start>Buka kamera</button>
        <button type="button" class="button secondary" data-photo-capture hidden>Ambil foto</button>
        <button type="button" class="button secondary" data-photo-stop hidden>Matikan kamera</button>
    </div>
    <div class="field"><label for="{{ $phase }}-photo">Foto {{ $phase === 'clock_in' ? 'masuk' : 'pulang' }} (JPG, PNG, WebP; maks. 5 MB)</label><input id="{{ $phase }}-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="user" required></div>
    <p role="status" data-photo-feedback>Kamera membutuhkan HTTPS atau localhost. Jika kamera tidak tersedia, gunakan pilihan foto di atas.</p>
</div>
