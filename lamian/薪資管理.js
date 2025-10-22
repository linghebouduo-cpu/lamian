// ===== 薪資管理.js (整合完整版) =====

// 今日日期 / 側欄收合
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('zh-TW', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  weekday: 'long'
});

document.getElementById('sidebarToggle').addEventListener('click', e => {
  e.preventDefault();
  document.body.classList.toggle('sb-sidenav-toggled');
});

/* ===== API Client ===== */
const API_PHP = '薪資管理.php';
let salaries = [];
let filtered = [];
let currentPage = 1;
const pageSize = 10;
let currentMonth = '';

class APIClient {
  static async request(url, options = {}) {
    const resp = await fetch(url, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options
    });
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    // 匯出Excel等可能不是JSON，呼叫端自行處理
    const ct = resp.headers.get('content-type') || '';
    if (ct.includes('application/json')) return resp.json();
    return resp;
  }

  static getSalaries(params = {}) {
    params.action = 'list';
    const q = new URLSearchParams(params).toString();
    return this.request(`${API_PHP}?${q}`);
  }

  static getSalaryDetail(user_id, month) {
    const params = new URLSearchParams({
      action: 'detail',
      user_id: user_id,
      month: month
    });
    return this.request(`${API_PHP}?${params.toString()}`);
  }

  static updateSalary(user_id, payload) {
    return this.request(API_PHP, {
      method: 'POST',
      body: JSON.stringify({
        action: 'update',
        user_id: user_id,
        ...payload
      })
    });
  }

  static recalc(month) {
    return this.request(API_PHP, {
      method: 'POST',
      body: JSON.stringify({
        action: 'recalculate',
        month: month
      })
    });
  }

  static async exportExcel(params = {}) {
    params.action = 'export';
    const q = new URLSearchParams(params).toString();
    const resp = await fetch(`${API_PHP}?${q}`);
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    return resp.blob();
  }
}

/* ===== Helper Functions ===== */
function showLoading(show = true) {
  document.getElementById('loadingIndicator').classList.toggle('d-none', !show);
}

function showError(msg) {
  const a = document.getElementById('errorAlert');
  document.getElementById('errorMessage').textContent = msg;
  a.classList.remove('d-none');
  setTimeout(() => a.classList.add('d-none'), 5000);
}

function showSuccess(msg) {
  const a = document.getElementById('successAlert');
  document.getElementById('successMessage').textContent = msg;
  a.classList.remove('d-none');
  setTimeout(() => a.classList.add('d-none'), 3000);
}

function currency(n) {
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0
  }).format(n || 0);
}

function calcBasePay(row) {
  // 若有hourly_rate → 用時薪*工時，否則用base_salary
  if (row.hourly_rate != null && row.hourly_rate !== undefined) {
    return Math.round((row.hourly_rate || 0) * (row.working_hours || 0));
  }
  return row.base_salary || 0;
}

function calcTotal(row) {
  return calcBasePay(row) + (row.bonus || 0) - (row.deductions || 0);
}

function payTypeBadge(row) {
  const isHourly = (row.hourly_rate != null && row.hourly_rate !== undefined);
  return isHourly
    ? '<span class="badge bg-info badge-paytype"><i class="fas fa-clock me-1"></i>時薪</span>'
    : '<span class="badge bg-secondary badge-paytype"><i class="fas fa-briefcase me-1"></i>月薪</span>';
}

/* ===== 初始化 ===== */
document.addEventListener('DOMContentLoaded', () => {
  // 預設當月
  const now = new Date();
  const ym = now.toISOString().slice(0, 7);
  currentMonth = ym;
  document.getElementById('monthPicker').value = ym;
  loadSalaries();
});

/* ===== 載入薪資資料 ===== */
async function loadSalaries() {
  try {
    showLoading(true);
    const keyword = document.getElementById('keyword').value?.trim() || '';
    const month = document.getElementById('monthPicker').value || currentMonth;
    currentMonth = month;

    const resp = await APIClient.getSalaries({
      month,
      q: keyword,
      page: currentPage,
      limit: pageSize
    });

    // 後端可回 { data, total }；若沒有就直接是array
    salaries = Array.isArray(resp) ? resp : (resp.data || []);
    filtered = [...salaries];
    renderTable();
    updateSummary();
    updatePagination((resp.total != null) ? resp.total : filtered.length);
  } catch (e) {
    console.error(e);
    showError('載入薪資資料失敗');
    salaries = [];
    filtered = [];
    renderTable();
    updateSummary();
    updatePagination(0);
  } finally {
    showLoading(false);
  }
}

/* ===== 渲染表格 ===== */
function renderTable() {
  const tbody = document.getElementById('salaryTableBody');
  const no = document.getElementById('noDataRow');
  tbody.innerHTML = '';

  if (filtered.length === 0) {
    tbody.appendChild(no);
    no.classList.remove('d-none');
    return;
  }

  no.classList.add('d-none');

  filtered.forEach(row => {
    const baseDisplay = (row.hourly_rate != null && row.hourly_rate !== undefined)
      ? currency(row.hourly_rate) + '/時'
      : currency(row.base_salary || 0) + '/月';

    const total = (row.total_salary != null) ? row.total_salary : calcTotal(row);
    const computed = calcTotal(row);
    const showDiff = (row.total_salary != null && row.total_salary !== computed);
    const diffPill = showDiff
      ? `<span class="badge bg-danger diff-pill ms-1" title="與前端公式計算不同">異</span>`
      : '';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.user_id}</td>
      <td>${row.name || '-'}</td>
      <td>${row.salary_month || currentMonth}</td>
      <td>${payTypeBadge(row)}</td>
      <td>${baseDisplay}</td>
      <td>${(row.working_hours || 0).toFixed(2)}</td>
      <td>${currency(row.bonus || 0)}</td>
      <td>${currency(row.deductions || 0)}</td>
      <td><strong>${currency(total)}</strong>${diffPill}</td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-info me-1" title="詳情" onclick="openDetail(${row.user_id}, '${row.salary_month || currentMonth}')"><i class="fas fa-eye"></i></button>
        <button class="btn btn-sm btn-warning" title="編輯" onclick='openEdit(${JSON.stringify(row).replaceAll("'", "&apos;")})'><i class="fas fa-edit"></i></button>
      </td>`;
    tbody.appendChild(tr);
  });
}

/* ===== 更新統計摘要 ===== */
function updateSummary() {
  const count = filtered.length;
  const totalPayroll = filtered.reduce((s, r) => s + ((r.total_salary != null) ? r.total_salary : calcTotal(r)), 0);
  const totalBonus = filtered.reduce((s, r) => s + (r.bonus || 0), 0);
  const totalDeductions = filtered.reduce((s, r) => s + (r.deductions || 0), 0);

  document.getElementById('summary_employees').textContent = count;
  document.getElementById('summary_total_payroll').textContent = currency(totalPayroll);
  document.getElementById('summary_total_bonus').textContent = currency(totalBonus);
  document.getElementById('summary_total_deductions').textContent = currency(totalDeductions);
}

/* ===== 分頁功能 ===== */
function updatePagination(total) {
  const totalPages = Math.ceil((total || 0) / pageSize);
  const p = document.getElementById('pagination');
  p.innerHTML = '';
  if (totalPages <= 1) return;

  const prev = document.createElement('li');
  prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
  prev.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1})"><i class="fas fa-chevron-left"></i> 上一頁</a>`;
  p.appendChild(prev);

  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
      const li = document.createElement('li');
      li.className = `page-item ${i === currentPage ? 'active' : ''}`;
      li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
      p.appendChild(li);
    } else if (i === currentPage - 3 || i === currentPage + 3) {
      const li = document.createElement('li');
      li.className = 'page-item disabled';
      li.innerHTML = '<span class="page-link">...</span>';
      p.appendChild(li);
    }
  }

  const next = document.createElement('li');
  next.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
  next.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1})">下一頁 <i class="fas fa-chevron-right"></i></a>`;
  p.appendChild(next);
}

async function changePage(i) {
  const totalPages = Math.ceil(filtered.length / pageSize);
  if (i < 1 || i > totalPages) return;
  currentPage = i;
  await loadSalaries();
}

/* ===== 篩選與清除 ===== */
async function filterSalaries() {
  currentPage = 1;
  await loadSalaries();
}

async function clearFilters() {
  document.getElementById('keyword').value = '';
  document.getElementById('monthPicker').value = currentMonth;
  currentPage = 1;
  await loadSalaries();
}

/* ===== 詳情 Modal ===== */
async function openDetail(user_id, month) {
  try {
    showLoading(true);
    const d = await APIClient.getSalaryDetail(user_id, month);
    const body = document.getElementById('detailBody');
    // 後端可回：{ salary: {...}, breakdown:{ from_daily_reports:[], from_clock:[], notes:'' } }
    const s = d.salary || d;
    const calcBase = calcBasePay(s);
    const calcTot = calcTotal(s);

    body.innerHTML = `
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-primary"><i class="fas fa-user me-1"></i> 基本資料</h6>
          <table class="table table-sm table-borderless">
            <tr><td class="fw-bold">員工ID：</td><td>${s.user_id}</td></tr>
            <tr><td class="fw-bold">姓名：</td><td>${s.name || '-'}</td></tr>
            <tr><td class="fw-bold">月份：</td><td>${s.salary_month || '-'}</td></tr>
            <tr><td class="fw-bold">薪資類型：</td><td>${(s.hourly_rate != null) ? '時薪' : '月薪'}</td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <h6 class="text-success"><i class="fas fa-calculator me-1"></i> 計算</h6>
          <table class="table table-sm table-borderless">
            <tr><td class="fw-bold">底薪 / 時薪：</td><td>${(s.hourly_rate != null) ? (currency(s.hourly_rate) + '/時') : (currency(s.base_salary || 0) + '/月')}</td></tr>
            <tr><td class="fw-bold">工時：</td><td>${(s.working_hours || 0).toFixed(2)}</td></tr>
            <tr><td class="fw-bold">計算底薪：</td><td>${currency(calcBase)}</td></tr>
            <tr><td class="fw-bold">獎金：</td><td>${currency(s.bonus || 0)}</td></tr>
            <tr><td class="fw-bold">扣款：</td><td>${currency(s.deductions || 0)}</td></tr>
            <tr class="table-success border-top"><td class="fw-bold">實領：</td><td class="fw-bold">${currency((s.total_salary != null) ? s.total_salary : calcTot)}</td></tr>
          </table>
        </div>
      </div>

      ${d.breakdown ? `
      <div class="row mt-3">
        <div class="col-md-6">
          <h6 class="text-info"><i class="fas fa-file-invoice-dollar me-1"></i> 日報表彙整（獎金/津貼來源）</h6>
          <div class="small text-muted">* 由後端彙整回傳；此區塊將顯示影響薪資的營收或補貼項目。</div>
          <ul class="mt-2">${(d.breakdown.from_daily_reports || []).map(x => `<li>${x.title}：${currency(x.amount)}</li>`).join('') || '<li class="text-muted">無資料</li>'}</ul>
        </div>
        <div class="col-md-6">
          <h6 class="text-warning"><i class="fas fa-user-clock me-1"></i> 工時來源（打卡聚合）</h6>
          <div class="small text-muted">* 由後端彙整回傳；此區塊將顯示每日工時明細合計。</div>
          <ul class="mt-2">${(d.breakdown.from_clock || []).map(x => `<li>${x.date}：${x.hours} 小時</li>`).join('') || '<li class="text-muted">無資料</li>'}</ul>
        </div>
      </div>` : ''}

      ${d.breakdown?.notes ? `
      <div class="row mt-3">
        <div class="col-12">
          <h6 class="text-secondary"><i class="fas fa-sticky-note me-1"></i> 備註</h6>
          <div class="alert alert-light">${d.breakdown.notes}</div>
        </div>
      </div>` : ''}`;

    new bootstrap.Modal(document.getElementById('detailModal')).show();
  } catch (e) {
    console.error(e);
    showError('讀取薪資詳情失敗');
  } finally {
    showLoading(false);
  }
}

/* ===== 編輯 Modal ===== */
function openEdit(row) {
  // row 可能是字串（innerHTML escape），轉回物件
  if (typeof row === 'string') {
    try {
      row = JSON.parse(row);
    } catch (e) {
      console.error('解析 row 失敗', e);
    }
  }

  document.getElementById('edit_user_id').value = row.user_id;
  document.getElementById('edit_name').value = row.name || '';
  document.getElementById('edit_month').value = row.salary_month || currentMonth;
  document.getElementById('edit_base_salary').value = row.base_salary || 0;
  document.getElementById('edit_hourly_rate').value = (row.hourly_rate != null) ? row.hourly_rate : '';
  document.getElementById('edit_working_hours').value = (row.working_hours || 0).toFixed(2);
  document.getElementById('edit_bonus').value = row.bonus || 0;
  document.getElementById('edit_deductions').value = row.deductions || 0;

  const isHourly = (row.hourly_rate != null && row.hourly_rate !== undefined);
  document.getElementById('paytype_hourly').checked = isHourly;
  document.getElementById('paytype_monthly').checked = !isHourly;
  togglePayType(isHourly ? 'hourly' : 'monthly');
  recalcEditPreview();

  new bootstrap.Modal(document.getElementById('editModal')).show();
}

// 綁定薪資類型切換事件
document.getElementById('paytype_monthly').addEventListener('change', () => togglePayType('monthly'));
document.getElementById('paytype_hourly').addEventListener('change', () => togglePayType('hourly'));

// 綁定即時計算事件
['edit_base_salary', 'edit_hourly_rate', 'edit_working_hours', 'edit_bonus', 'edit_deductions'].forEach(id => {
  const el = document.getElementById(id);
  el && el.addEventListener('input', recalcEditPreview);
});

function togglePayType(type) {
  const baseWrap = document.getElementById('baseSalaryWrap');
  const hrWrap = document.getElementById('hourlyRateWrap');
  if (type === 'hourly') {
    baseWrap.style.display = 'none';
    hrWrap.style.display = 'block';
  } else {
    baseWrap.style.display = 'block';
    hrWrap.style.display = 'none';
  }
  recalcEditPreview();
}

function recalcEditPreview() {
  const typeHourly = document.getElementById('paytype_hourly').checked;
  const base = Number(document.getElementById('edit_base_salary').value) || 0;
  const rate = Number(document.getElementById('edit_hourly_rate').value) || 0;
  const hrs = Number(document.getElementById('edit_working_hours').value) || 0;
  const bonus = Number(document.getElementById('edit_bonus').value) || 0;
  const ded = Number(document.getElementById('edit_deductions').value) || 0;

  const calcBase = typeHourly ? Math.round(rate * hrs) : base;
  document.getElementById('edit_calc_basepay').value = currency(calcBase);
  document.getElementById('edit_total_salary').textContent = currency(calcBase + bonus - ded);
}

async function submitEdit(e) {
  e.preventDefault();
  try {
    showLoading(true);
    const typeHourly = document.getElementById('paytype_hourly').checked;
    const payload = {
      salary_month: document.getElementById('edit_month').value,
      base_salary: typeHourly ? 0 : (Number(document.getElementById('edit_base_salary').value) || 0),
      hourly_rate: typeHourly ? (Number(document.getElementById('edit_hourly_rate').value) || 0) : null,
      bonus: Number(document.getElementById('edit_bonus').value) || 0,
      deductions: Number(document.getElementById('edit_deductions').value) || 0
      // working_hours 由系統計算，不在此修改
    };
    const uid = document.getElementById('edit_user_id').value;
    await APIClient.updateSalary(uid, payload);
    showSuccess('薪資已更新');
    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
    await loadSalaries();
  } catch (e) {
    console.error(e);
    showError('更新薪資失敗');
  } finally {
    showLoading(false);
  }
}

/* ===== 重新計算 ===== */
async function recalculateMonth() {
  try {
    showLoading(true);
    const month = document.getElementById('monthPicker').value || currentMonth;
    await APIClient.recalc(month); // 後端會「結合日報表/打卡」重算 working_hours/bonus 等
    showSuccess('已觸發重新計算，請稍候…');
    await loadSalaries();
  } catch (e) {
    console.error(e);
    showError('重新計算失敗');
  } finally {
    showLoading(false);
  }
}

/* ===== 匯出 Excel ===== */
async function exportToExcel() {
  try {
    const btn = document.getElementById('exportBtn');
    const t = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 匯出中...';
    btn.disabled = true;

    const month = document.getElementById('monthPicker').value || currentMonth;
    const q = document.getElementById('keyword').value || '';
    const blob = await APIClient.exportExcel({ month, q });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `薪資管理_${month}.xlsx`;
    document.body.appendChild(a);
    a.click();
    URL.revokeObjectURL(url);
    a.remove();
    showSuccess('Excel 檔案匯出成功');
  } catch (e) {
    console.error(e);
    showError('匯出失敗');
  } finally {
    const btn = document.getElementById('exportBtn');
    btn.innerHTML = '<i class="fas fa-download"></i> 匯出Excel';
    btn.disabled = false;
  }
}

/* ===== 快捷鍵：Enter 搜尋 ===== */
document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && ['keyword', 'monthPicker'].includes(document.activeElement.id)) {
    filterSalaries();
  }
});