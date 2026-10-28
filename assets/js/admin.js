// ยืนยันก่อนลบ
document.querySelectorAll('form[data-confirm]').forEach(f => {
  f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});

// แสดงตัวอย่างรูปก่อนอัปโหลด
document.querySelectorAll('input[type=file][data-preview]').forEach(input => {
  input.addEventListener('change', () => {
    const box = document.getElementById(input.dataset.preview);
    if (!box) return;
    box.innerHTML = '';
    [...input.files].forEach(file => {
      if (!file.type.startsWith('image/')) return;
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.title = file.name;
      box.appendChild(img);
    });
  });
});

// แผนที่เลือกพิกัด
(function () {
  const el = document.getElementById('pickerMap');
  if (!el || typeof L === 'undefined') return;
  const latIn = document.getElementById('lat');
  const lngIn = document.getElementById('lng');
  const KK = [16.4322, 102.8236]; // ตัวเมืองขอนแก่น
  const has = latIn.value !== '' && lngIn.value !== '';
  const map = L.map(el).setView(has ? [+latIn.value, +lngIn.value] : KK, has ? 15 : 9);

  const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
  const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: 'Esri' });
  L.control.layers({ 'แผนที่': street, 'ภาพดาวเทียม': sat }).addTo(map);

  let marker = null;
  function place(lat, lng, pan) {
    if (!marker) {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', () => { const p = marker.getLatLng(); setInputs(p.lat, p.lng); });
    } else marker.setLatLng([lat, lng]);
    if (pan) map.setView([lat, lng], Math.max(map.getZoom(), 15));
  }
  function setInputs(lat, lng) { latIn.value = lat.toFixed(7); lngIn.value = lng.toFixed(7); }
  if (has) place(+latIn.value, +lngIn.value);

  map.on('click', e => { place(e.latlng.lat, e.latlng.lng); setInputs(e.latlng.lat, e.latlng.lng); });
  [latIn, lngIn].forEach(i => i.addEventListener('change', () => {
    const la = parseFloat(latIn.value), ln = parseFloat(lngIn.value);
    if (!isNaN(la) && !isNaN(ln)) place(la, ln, true);
  }));
  // วางค่า "16.44, 102.83" จาก Google Maps ลงช่อง lat ได้ทันที
  latIn.addEventListener('paste', e => {
    const t = (e.clipboardData || window.clipboardData).getData('text');
    const m = t.match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/);
    if (m) { e.preventDefault(); latIn.value = m[1]; lngIn.value = m[2]; place(+m[1], +m[2], true); }
  });
  document.getElementById('btnMyLocation')?.addEventListener('click', () => {
    if (!navigator.geolocation) return alert('เบราว์เซอร์ไม่รองรับการระบุตำแหน่ง');
    navigator.geolocation.getCurrentPosition(
      p => { place(p.coords.latitude, p.coords.longitude, true); setInputs(p.coords.latitude, p.coords.longitude); },
      () => alert('ไม่สามารถระบุตำแหน่งได้')
    );
  });
  document.getElementById('btnClearLoc')?.addEventListener('click', () => {
    latIn.value = lngIn.value = '';
    if (marker) { map.removeLayer(marker); marker = null; }
  });
  setTimeout(() => map.invalidateSize(), 200);
})();
