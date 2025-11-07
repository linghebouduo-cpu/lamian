// ===== 日報表.js =====
// 🔥 已修正：formatDate 函式中的語法錯誤
// 🔥 (移除了外層 DOMContentLoaded)
// 🔥 (已修正 API 呼叫路徑)

// ===== DOM 元素 =====
const currentDateEl = document.getElementById("currentDate");
const reportDate = document.getElementById("report_date");
const weekdayInput = document.getElementById("weekday");
const filledBy = document.getElementById("filled_by");

const incomeInputs = document.querySelectorAll(".income");
const expenseInputs = document.querySelectorAll(".expense");
const cashInputs = document.querySelectorAll(".cash");

const totalIncomeEl = document.getElementById("total_income");
const fixedExpenseEl = document.getElementById("t_expense");
const variableExpenseEl = document.getElementById("total_variable");
const cashTotalEl = document.getElementById("cash_total");

const kpiIncomeEl = document.getElementById("kpi_income");
const kpiExpenseEl = document.getElementById("kpi_expense");
const kpiNetEl = document.getElementById("kpi_net");
const kpiDepositEl = document.getElementById("kpi_deposit");

const successAlert = document.getElementById("successAlert");
const warningAlert = document.getElementById("warningAlert");
const errorAlert = document.getElementById("errorAlert");
const successMessage = document.getElementById("successMessage");
const warningMessage = document.getElementById("warningMessage");
const errorMessage = document.getElementById("errorMessage");

const depositInput = document.getElementById("deposit_to_bank");

// 勾選啟用元素
const utilitiesCheckbox = document.getElementById("enable_utilities");
const utilitiesInput = document.getElementById("expense_utilities");
const utilityTermSelect = document.getElementById("utility_month");

const rentCheckbox = document.getElementById("enable_rent");
const rentInput = document.getElementById("expense_rent");
const rentPeriodSelect = document.getElementById("rent_period");
const seasonSelect = document.getElementById("season_months");

const rentStartInput = document.getElementById("rent_start");
const rentEndInput = document.getElementById("rent_end");
const saveRentBtn = document.getElementById("saveRentSetting");
const rentSettingHidden = document.getElementById("rent_setting");

const dailyReportForm = document.getElementById("dailyReportForm");

// ===== 初始化日期與星期 =====
function formatDate(date) {
  const y = date.getFullYear();
  // 🔥 修正：加上 + 號
  const m = ("0" + (date.getMonth() + 1)).slice(-2);
  // 🔥 修正：加上 + 號
  const d = ("0" + date.getDate()).slice(-2);
  return `${y}-${m}-${d}`;
}

function getWeekday(date) {
  const weekdays = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
  return weekdays[date.getDay()];
}

const today = new Date();
if (currentDateEl) currentDateEl.textContent = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`;
if (reportDate) reportDate.value = formatDate(today); //
if (weekdayInput) weekdayInput.value = getWeekday(today);

// ===== 收入總計 =====
function calculateIncome() {
  let total = 0;
  incomeInputs.forEach(input => {
    const val = parseFloat(input.value) || 0;
    total += val;
  });
  if (totalIncomeEl) totalIncomeEl.textContent = total.toLocaleString();
  return total;
}

// ===== 支出總計 =====
function calculateExpense() {
  let fixedTotal = 0;
  let variableTotal = 0;
  expenseInputs.forEach(input => {
    const val = parseFloat(input.value) || 0;
    if (["expense_salary", "expense_rent", "expense_utilities"].includes(input.id)) {
      fixedTotal += val;
    } else {
      variableTotal += val;
    }
  });
  if (fixedExpenseEl) fixedExpenseEl.textContent = fixedTotal.toLocaleString();
  if (variableExpenseEl) variableExpenseEl.textContent = variableTotal.toLocaleString();
  return { fixedTotal, variableTotal };
}

// ===== 現金總計 =====
function calculateCash() {
let total = 0;
cashInputs.forEach(input => {
  const val = Number(input.value) || 0;
  const span = input.closest(".input-group")?.querySelector(".input-group-text");
  const denomination = span ? Number(span.textContent) : 0;
  total += val * denomination;
});
if (cashTotalEl) cashTotalEl.textContent = total.toLocaleString();
return total;
}


// ===== KPI 更新 =====
function updateKPI() {
  const incomeTotal = calculateIncome();
  const { fixedTotal, variableTotal } = calculateExpense();
  const deposit = parseFloat(depositInput.value) || 0;

  if (kpiIncomeEl) kpiIncomeEl.textContent = incomeTotal.toLocaleString();
  if (kpiExpenseEl) kpiExpenseEl.textContent = (fixedTotal + variableTotal).toLocaleString();
  if (kpiNetEl) kpiNetEl.textContent = (incomeTotal - (fixedTotal + variableTotal)).toLocaleString();
  if (kpiDepositEl) kpiDepositEl.textContent = deposit.toLocaleString();
}

// ===== 綁定輸入事件 =====
incomeInputs.forEach(input => input.addEventListener("input", updateKPI));
expenseInputs.forEach(input => input.addEventListener("input", updateKPI));
cashInputs.forEach(input => input.addEventListener("input", updateKPI));
if (depositInput) depositInput.addEventListener("input", updateKPI);

// ===== 勾選啟用控制 =====
if (utilitiesCheckbox) {
  utilitiesCheckbox.addEventListener("change", () => {
    const enabled = utilitiesCheckbox.checked;
    utilitiesInput.disabled = !enabled;
    utilityTermSelect.disabled = !enabled;
  });
}

if (rentCheckbox) {
  rentCheckbox.addEventListener("change", () => {
    const enabled = rentCheckbox.checked;
    rentInput.disabled = !enabled;
    rentPeriodSelect.disabled = !enabled;
    seasonSelect.disabled = !enabled;
  });
}

// ===== 租金設定 Modal 顯示/期別控制 =====
if (rentPeriodSelect) {
  rentPeriodSelect.addEventListener("change", () => {
    const seasonWrap = document.getElementById("season_wrap");
    if (rentPeriodSelect.value === "season") {
      seasonWrap.classList.remove("d-none");
    } else {
      seasonWrap.classList.add("d-none");
    }
  });
}

// ===== 租金日期即時檢查 =====
if (saveRentBtn) {
  saveRentBtn.addEventListener("click", async () => {
    if (typeof API_BASE === 'undefined') return alert('API_BASE 未定義');
    const start = rentStartInput.value;
    const end = rentEndInput.value;
    const rentModalEl = document.getElementById("rentSettingModal");
    let rentModal = bootstrap.Modal.getOrCreateInstance(rentModalEl, { focus: false });

    if (!start || !end) {
      rentModal.hide();
      showAlert("warning", "請選擇完整的租金起訖日期！");
      return;
    }

    try {
      // 🔥 API 路徑
      const checkRes = await fetch(`${API_BASE}/api_report_check.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ rent_start: start, rent_end: end })
      });
      const checkResult = await checkRes.json();

      if (!checkResult.success) {
        rentModal.hide();
        showAlert("warning", checkResult.message || "租金日期重疊");
        return;
      }

      // 檢查通過 → 儲存設定
      const setting = {
        period: rentPeriodSelect.value,
        months: parseInt(seasonSelect.value) || 1,
        start,
        end
      };
      rentSettingHidden.value = JSON.stringify(setting);
      rentModal.hide();
      showAlert("success", "租金設定已儲存");

    } catch (err) {
      rentModal.hide();
      showAlert("error", "租金日期檢查錯誤：" + err.message);
    }
  });
}

// ===== 水電瓦斯選項即時檢查（整合到日報表ch.php） =====
if (utilityTermSelect) {
  utilityTermSelect.addEventListener("change", async () => {
    if (typeof API_BASE === 'undefined') return alert('API_BASE 未定義');
    const utilityText = getUtilityTermText(utilityTermSelect.value);
    if (!utilityText) return;

    try {
      // 🔥 API 路徑
      const res = await fetch(`${API_BASE}/api_report_check.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ utilities_month: utilityText })
      });
      const result = await res.json();

      if (!result.success) {
        showAlert("warning", result.message || `今年已存在 ${utilityText} 的水電瓦斯資料`);
        utilityTermSelect.value = ""; // 清空選擇
      } else {
        showAlert("success", result.message || `水電瓦斯月份 ${utilityText} 可使用`);
      }
    } catch (err) {
      showAlert("error", "水電瓦斯檢查錯誤：" + err.message);
    }
  });
}

// ===== 捲回頂端 =====
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// ===== 共用通知顯示函式 =====
function showAlert(type, message) {
  if (successAlert) successAlert.classList.add("d-none");
  if (warningAlert) warningAlert.classList.add("d-none");
  if (errorAlert) errorAlert.classList.add("d-none");

  if (type === "success") {
    if (successMessage) successMessage.textContent = message;
    if (successAlert) successAlert.classList.remove("d-none");
  } else if (type === "warning") {
    if (warningMessage) warningMessage.textContent = message;
    if (warningAlert) warningAlert.classList.remove("d-none");
  } else if (type === "error") {
    if (errorMessage) errorMessage.textContent = message;
    if (errorAlert) errorAlert.classList.remove("d-none");
  }

  scrollToTop();
}

// ===== 將水電瓦斯選項轉換成文字 =====
function getUtilityTermText(value) {
  const mapping = {
    term1: "1–2月",
    term2: "3–4月",
    term3: "5–6月",
    term4: "7–8月",
    term5: "9–10月",
    term6: "11–12月"
  };
  return mapping[value] || "";
}

// ===== 表單送出 =====
if (dailyReportForm) {
  dailyReportForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (typeof API_BASE === 'undefined') return alert('API_BASE 未定義');

    const utilityText = getUtilityTermText(utilityTermSelect.value);

    const data = {
      report_date: reportDate.value,
      weekday: weekdayInput.value,
      filled_by: filledBy.value,
      cash_income: parseFloat(document.getElementById("cash_income").value) || 0,
      linepay_income: parseFloat(document.getElementById("linepay_income").value) || 0,
      uber_income: parseFloat(document.getElementById("uber_income").value) || 0,
      other_income: parseFloat(document.getElementById("other_income").value) || 0,
      total_income: calculateIncome(),
      expense_salary: parseFloat(document.getElementById("expense_salary").value) || 0,
      expense_utilities: parseFloat(document.getElementById("expense_utilities").value) || 0,
      utilities_month: utilityText,
      expense_rent: parseFloat(document.getElementById("expense_rent").value) || 0,
      expense_food: parseFloat(document.getElementById("expense_food").value) || 0,
      expense_delivery: parseFloat(document.getElementById("expense_delivery").value) || 0,
      expense_misc: parseFloat(document.getElementById("expense_misc").value) || 0,
      cash_1000: parseInt(document.getElementById("cash_1000").value) || 0,
      cash_500: parseInt(document.getElementById("cash_500").value) || 0,
      cash_100: parseInt(document.getElementById("cash_100").value) || 0,
      cash_50: parseInt(document.getElementById("cash_50").value) || 0,
      cash_10: parseInt(document.getElementById("cash_10").value) || 0,
      cash_5: parseInt(document.getElementById("cash_5").value) || 0,
      cash_1: parseInt(document.getElementById("cash_1").value) || 0,
      cash_total: calculateCash(),
      deposit_to_bank: parseFloat(depositInput.value) || 0,
      rent_setting: rentSettingHidden.value
    };

    try {
      // 表單完整檢查
      // 🔥 API 路徑
      const checkRes = await fetch(`${API_BASE}/api_report_check.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });
      const checkResult = await checkRes.json();

      if (!checkResult.success) {
        showAlert("warning", checkResult.message || "資料驗證未通過");
        return;
      }

      // 🔥 API 路徑
      const saveRes = await fetch(`${API_BASE}/api_report_create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });
      const saveResult = await saveRes.json();

      if (saveResult.success) {
        showAlert("success", saveResult.message || "日報表送出成功！");
        dailyReportForm.reset();
        const today = new Date();
        reportDate.value = formatDate(today);
        weekdayInput.value = getWeekday(today);
        updateKPI();
      } else {
        showAlert("error", saveResult.message || "資料儲存失敗");
      }
    } catch (err) {
      showAlert("error", "系統錯誤：" + err.message);
    }
  });
}

// 🔥 移除：初始化 updateKPI() (改由 PHP 頁尾的 DOMContentLoaded 觸發)
// updateKPI();