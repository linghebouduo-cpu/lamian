/* ======(頁面) 你之後只要改這裡就能串 DB / API ====== */
const BASE_URL = '';  // ← 同層資料夾，不需 /api/
const DEFAULT_HEADERS = { 'Content-Type': 'application/json' };

async function fetchJSON(path, options = {}) {
  try {
    const res = await fetch(BASE_URL + path, { headers: DEFAULT_HEADERS, credentials: 'include', ...options });
    if (!res.ok) throw new Error(res.status + ' ' + res.statusText);
    return await res.json();
  } catch (err) {
    console.warn('[API ERROR]', path, err);
    return null;
  }
}

// 今日日期
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('zh-TW', { year:'numeric', month:'long', day:'numeric', weekday:'long' });
document.getElementById('sidebarToggle').addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });

/* ====== 週期計算 ====== */
function getMonday(d = new Date()) {
  const date = new Date(d);
  const day = (date.getDay() + 6) % 7; // 0=Mon
  date.setHours(0,0,0,0);
  date.setDate(date.getDate() - day);
  return date;
}
function fmt(d) { return d.toISOString().slice(0,10); }
function addDays(d, n){ const x = new Date(d); x.setDate(x.getDate()+n); return x; }
function rangeMonToSun(monday){
  const arr = [];
  for(let i=0;i<7;i++){ arr.push(addDays(monday,i)); }
  return arr;
}

/* ====== 唯讀班表（瀏覽＋下載圖片） ====== */
async function loadReadonlySchedule(monday){
  // 呼叫同層的 班表.php，用 GET 傳 start 參數
  const data = await fetchJSON(`班表.php?start=${fmt(monday)}`);
  const days = rangeMonToSun(monday);
  const head = document.getElementById('viewHeader');
  const body = document.getElementById('viewBody');

  const weekday = ['一','二','三','四','五','六','日'];
  head.innerHTML = `
    <tr>
      <th style="min-width:120px">員工</th>
      ${days.map((d,i)=>`<th>${d.getMonth()+1}/${d.getDate()}<br>星期${weekday[i]}</th>`).join('')}
    </tr>`;

  if(!data || !Array.isArray(data.rows) || data.rows.length===0){
    body.innerHTML = `<tr><td colspan="8" class="text-center text-muted">目前沒有班表資料。</td></tr>`;
    return;
  }
  body.innerHTML = data.rows.map(r => `
  <tr>
    <th class="bg-light text-start">${r.name ?? ''}</th>
    ${(r.shifts ?? Array(7).fill([])).map(dayShifts => {
      if (!dayShifts || dayShifts.length === 0) return `<td><span class="badge-off">休</span></td>`;
      // 將每個時段包在 badge 框裡
      return `<td>` + dayShifts.map(s => `<span class="badge-shift">${s}</span>`).join('<br>') + `</td>`;
    }).join('')}
  </tr>
`).join('');
}

// 下載圖片
async function downloadSchedulePng(){
  const el = document.getElementById('scheduleViewCard');
  const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#ffffff' });
  const url = canvas.toDataURL('image/png');
  const a = document.createElement('a');
  a.href = url;
  a.download = `班表_${document.getElementById('weekRangeText').textContent}.png`;
  a.click();
}

/* ====== 可排時段填報 ====== */
function renderAvailabilityTable(monday){
  const days = rangeMonToSun(monday);
  const weekdayFull = ['星期一','星期二','星期三','星期四','星期五','星期六','星期日'];
  const head = document.getElementById('availHeader');
  const body = document.getElementById('availBody');

  head.innerHTML = `
    <tr>
      ${days.map((d,i)=>`<th class="text-center">${weekdayFull[i]}<br>${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}</th>`).join('')}
    </tr>`;

  const row = document.createElement('tr');
  days.forEach((d, i) => {
    const dateStr = fmt(d);
    const td = document.createElement('td');
    td.style.minWidth = '220px';
    td.innerHTML = `
      <div class="ranges" data-date="${dateStr}"></div>
      <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-action="add-range" data-date="${dateStr}">
        <i class="fas fa-plus me-1"></i>新增時段
      </button>
      <div class="form-text mt-1">可新增多段時間</div>
    `;
    row.appendChild(td);
  });
  body.innerHTML = '';
  body.appendChild(row);

  document.querySelectorAll('.ranges').forEach(r => addRangeRow(r.dataset.date));
}

function addRangeRow(dateStr){
  const container = document.querySelector(`.ranges[data-date="${dateStr}"]`);
  const idx = container.querySelectorAll('.range-item').length;
  const idStart = `s_${dateStr}_${idx}`;
  const idEnd   = `e_${dateStr}_${idx}`;
  const div = document.createElement('div');
  div.className = 'range-item input-group input-group-sm mb-2';
  div.innerHTML = `
    <span class="input-group-text">起</span>
    <input type="time" class="form-control start" id="${idStart}" aria-label="start">
    <span class="input-group-text">迄</span>
    <input type="time" class="form-control end" id="${idEnd}" aria-label="end">
    <button class="btn btn-outline-danger" type="button" title="移除" data-action="remove-range">&times;</button>
  `;
  container.appendChild(div);
}

function clearAllRanges(){
  document.querySelectorAll('.ranges').forEach(r => r.innerHTML = '');
}

document.addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-action]');
  if(!btn) return;
  const action = btn.dataset.action;
  if(action === 'add-range'){
    addRangeRow(btn.dataset.date);
  } else if(action === 'remove-range'){
    const item = btn.closest('.range-item');
    if(item) item.remove();
  }
});

function showFormMsg(text, type='secondary'){
  const slot = document.getElementById('formMsg');
  slot.innerHTML = `<div class="alert alert-${type} mb-0" role="alert">${text}</div>`;
}

async function submitAvailability(e){
  e.preventDefault();
  const weekStartStr = document.getElementById('weekStartInput').value;
  if(!weekStartStr){
    showFormMsg('請先選擇「填報週」', 'danger'); return;
  }
  const weekStart = getMonday(new Date(weekStartStr));

  const availability = {};
  let invalid = false;

  document.querySelectorAll('.ranges').forEach(r => {
    const date = r.dataset.date;
    const items = Array.from(r.querySelectorAll('.range-item'));
    const ranges = [];
    items.forEach(it => {
      const s = it.querySelector('.start')?.value || '';
      const e = it.querySelector('.end')?.value || '';
      if(!s && !e){ return; }
      if(!s || !e || s >= e){ invalid = true; return; }
      ranges.push({ start: s, end: e });
    });
    availability[date] = ranges;
  });

  if(invalid){
    showFormMsg('有不合法的時間段（起需早於迄，或欄位不可空白）。請修正後再送出。', 'danger');
    return;
  }

  const payload = { week_start: fmt(weekStart), availability };
  const ok = await fetchJSON('班表.php', { method:'POST', body: JSON.stringify(payload) });
  if(ok && ok.success){
    showFormMsg('已送出，感謝填報！', 'success');
  }else{
    showFormMsg('送出失敗，請稍後再試。', 'danger');
  }
}

function clearForm(){
  clearAllRanges();
  document.querySelectorAll('.ranges').forEach(r => addRangeRow(r.dataset.date));
  showFormMsg('已清除全部時間段。', 'secondary');
}

/* ====== 週切換控制 ====== */
let currentMonday = getMonday(new Date());
function updateWeekRangeText(monday){
  const sun = addDays(monday, 6);
  const s = `${monday.getFullYear()}/${String(monday.getMonth()+1).padStart(2,'0')}/${String(monday.getDate()).padStart(2,'0')}`;
  const e = `${sun.getFullYear()}/${String(sun.getMonth()+1).padStart(2,'0')}/${String(sun.getDate()).padStart(2,'0')}`;
  document.getElementById('weekRangeText').textContent = `${s} - ${e}`;
}

async function refreshAll(){
  updateWeekRangeText(currentMonday);
  await loadReadonlySchedule(currentMonday); // ✅ 這行可保留
  renderAvailabilityTable(currentMonday);
  document.getElementById('weekStartInput').value = fmt(currentMonday);
}

/* ====== 初始化 ====== */
window.addEventListener('DOMContentLoaded', async () => {
  document.getElementById('btnPrevWeek').addEventListener('click', async () => { currentMonday = addDays(currentMonday, -7); await refreshAll(); });
  document.getElementById('btnThisWeek').addEventListener('click', async () => { currentMonday = getMonday(new Date()); await refreshAll(); });
  document.getElementById('btnNextWeek').addEventListener('click', async () => { currentMonday = addDays(currentMonday, 7); await refreshAll(); });
  document.getElementById('btnDownloadPng').addEventListener('click', downloadSchedulePng);

  document.getElementById('availabilityForm').addEventListener('submit', submitAvailability);
  document.getElementById('btnClear').addEventListener('click', clearForm);

  document.getElementById('weekStartInput').addEventListener('change', async (e) => {
    const d = new Date(e.target.value);
    currentMonday = getMonday(d);
    await refreshAll();
  });

  await refreshAll();
});
