// ======= 薪資管理.js =======
document.addEventListener("DOMContentLoaded", () => {
  const monthPicker = document.getElementById("monthPicker");
  const keywordInput = document.getElementById("keyword");
  const salaryTableBody = document.getElementById("salaryTableBody");
  const noDataRow = document.getElementById("noDataRow");
  const currentDateEl = document.getElementById("currentDate");

  const summaryEmployees = document.getElementById("summary_employees");
  const summaryTotalPayroll = document.getElementById("summary_total_payroll");
  const summaryTotalBonus = document.getElementById("summary_total_bonus");
  const summaryTotalDeductions = document.getElementById("summary_total_deductions");

  const loadingIndicator = document.getElementById("loadingIndicator");
  const errorAlert = document.getElementById("errorAlert");
  const errorMessage = document.getElementById("errorMessage");

  // ===== 顯示今天日期 =====
  const now = new Date();
  const yyyyMM = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
  currentDateEl.textContent = now.toLocaleDateString("zh-TW");
  monthPicker.value = yyyyMM;

  // 初始化載入
  loadSalaryData(yyyyMM);

  // ===== 主查詢函式 =====
  async function loadSalaryData(month, keyword = "") {
    showLoading(true);
    try {
      const res = await fetch("薪資管理.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "fetch", month, keyword })
      });
      const data = await res.json();

      if (!data.success) throw new Error(data.message);

      renderSalaryTable(data.records);
      updateSummary(data.records);
      showError(false);
    } catch (err) {
      console.error(err);
      showError(true, err.message);
    } finally {
      showLoading(false);
    }
  }

  // ===== 渲染表格 =====
  function renderSalaryTable(records) {
    salaryTableBody.innerHTML = "";

    if (!records || records.length === 0) {
      noDataRow.classList.remove("d-none");
      return;
    }
    noDataRow.classList.add("d-none");

    records.forEach(rec => {
      const payType = rec.base_salary ? "月薪" : "時薪";
      const payValue = rec.base_salary || rec.hourly_rate || 0;

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${rec.id}</td>
        <td>${rec.name}</td>
        <td>${rec.salary_month}</td>
        <td><span class="badge bg-info text-dark">${payType}</span></td>
        <td>${payValue}</td>
        <td>${rec.working_hours ?? "-"}</td>
        <td>${rec.bonus ?? 0}</td>
        <td>${rec.deductions ?? 0}</td>
        <td>${rec.total_salary ?? 0}</td>
       <td class="text-center">
  <button class="btn btn-sm btn-info me-2" onclick="showDetail(${rec.id}, '${rec.salary_month}')">
    <i class="fas fa-eye"></i>
  </button>
  <button class="btn btn-sm btn-warning" onclick="openEditModal(${rec.id}, '${rec.name}', '${rec.salary_month}', '${payType}', '${payValue}', '${rec.working_hours ?? 0}', '${rec.bonus ?? 0}', '${rec.deductions ?? 0}')">
    <i class="fas fa-edit"></i>
  </button>
</td>
      `;
      salaryTableBody.appendChild(tr);
    });
  }

  // ===== 更新統計卡片 =====
  function updateSummary(records) {
    const totalPeople = records.length;
    const totalPayroll = records.reduce((sum, r) => sum + Number(r.total_salary || 0), 0);
    const totalBonus = records.reduce((sum, r) => sum + Number(r.bonus || 0), 0);
    const totalDeduct = records.reduce((sum, r) => sum + Number(r.deductions || 0), 0);

    summaryEmployees.textContent = totalPeople;
    summaryTotalPayroll.textContent = totalPayroll.toLocaleString();
    summaryTotalBonus.textContent = totalBonus.toLocaleString();
    summaryTotalDeductions.textContent = totalDeduct.toLocaleString();
  }

  // ===== 查詢按鈕 =====
  window.filterSalaries = function () {
    loadSalaryData(monthPicker.value, keywordInput.value.trim());
  };

  // ===== 清除按鈕 =====
  window.clearFilters = function () {
    keywordInput.value = "";
    monthPicker.value = yyyyMM;
    loadSalaryData(yyyyMM);
  };

  // ===== 匯出Excel =====
  window.exportToExcel = function () {
    const wb = XLSX.utils.table_to_book(document.querySelector("table"), { sheet: "薪資資料" });
    XLSX.writeFile(wb, `薪資管理_${monthPicker.value}.xlsx`);
  };

  // ===== 顯示詳細資料 =====
  window.showDetail = async function (id, month) {
    try {
      const res = await fetch("薪資管理.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "detail", id, month })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message);

      const d = data.record;
      document.getElementById("detailBody").innerHTML = `
        <p><strong>員工ID：</strong>${d.id}</p>
        <p><strong>姓名：</strong>${d.name}</p>
        <p><strong>薪資月份：</strong>${d.salary_month}</p>
        <p><strong>薪資類型：</strong>${d.base_salary ? "月薪" : "時薪"}</p>
        <p><strong>底薪/時薪：</strong>${d.base_salary || d.hourly_rate}</p>
        <p><strong>本月工時：</strong>${d.working_hours}</p>
        <p><strong>獎金：</strong>${d.bonus}</p>
        <p><strong>扣款：</strong>${d.deductions}</p>
        <p><strong>實領金額：</strong>${d.total_salary}</p>
      `;
      new bootstrap.Modal(document.getElementById("detailModal")).show();
    } catch (err) {
      alert("讀取詳情失敗：" + err.message);
    }
  };

  // ======= 修改功能 =======

  // 打開編輯薪資 Modal
  window.openEditModal = function (id, name, month, payType, payValue, hours, bonus, deduction) {
    document.getElementById("edit_user_id").value = id;
    document.getElementById("edit_name").value = name;
    document.getElementById("edit_month").value = month;
    document.getElementById("edit_working_hours").value = hours;
    document.getElementById("edit_bonus").value = bonus;
    document.getElementById("edit_deductions").value = deduction;

    if (payType === "月薪") {
      document.getElementById("paytype_monthly").checked = true;
      document.getElementById("baseSalaryWrap").style.display = "block";
      document.getElementById("hourlyRateWrap").style.display = "none";
      document.getElementById("edit_base_salary").value = payValue;
      document.getElementById("edit_hourly_rate").value = "";
    } else {
      document.getElementById("paytype_hourly").checked = true;
      document.getElementById("baseSalaryWrap").style.display = "none";
      document.getElementById("hourlyRateWrap").style.display = "block";
      document.getElementById("edit_hourly_rate").value = payValue;
      document.getElementById("edit_base_salary").value = "";
    }

    updateTotalSalary();
    new bootstrap.Modal(document.getElementById("editModal")).show();
  };

  // 即時計算薪資
  window.updateTotalSalary = function () {
    const paytype = document.querySelector('input[name="paytype"]:checked').value;
    const base = parseFloat(document.getElementById("edit_base_salary").value) || 0;
    const rate = parseFloat(document.getElementById("edit_hourly_rate").value) || 0;
    const hours = parseFloat(document.getElementById("edit_working_hours").value) || 0;
    const bonus = parseFloat(document.getElementById("edit_bonus").value) || 0;
    const deductions = parseFloat(document.getElementById("edit_deductions").value) || 0;

    let calcBase = paytype === "monthly" ? base : rate * hours;
    document.getElementById("edit_calc_basepay").value = calcBase.toFixed(0);
    const total = calcBase + bonus - deductions;
    document.getElementById("edit_total_salary").textContent = total.toFixed(0);
  };

  // 送出修改表單
  window.submitEdit = async function (e) {
    e.preventDefault();

    const data = {
      action: "update",
      user_id: document.getElementById("edit_user_id").value,
      month: document.getElementById("edit_month").value,
      paytype: document.querySelector('input[name="paytype"]:checked').value,
      base_salary: document.getElementById("edit_base_salary").value,
      hourly_rate: document.getElementById("edit_hourly_rate").value,
      bonus: document.getElementById("edit_bonus").value,
      deductions: document.getElementById("edit_deductions").value
    };

    try {
      const res = await fetch("薪資管理.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });
      const result = await res.json();

      if (result.success) {
        alert("薪資資料已更新！");
        bootstrap.Modal.getInstance(document.getElementById("editModal")).hide();
        loadSalaryData(monthPicker.value);
      } else {
        alert("更新失敗：" + result.message);
      }
    } catch (err) {
      console.error("更新錯誤", err);
      alert("伺服器錯誤，請稍後再試");
    }
  };// ======= 修改功能 =======

// 打開編輯薪資 Modal
window.openEditModal = function (id, name, month, payType, payValue, hours, bonus, deduction) {
  document.getElementById("edit_user_id").value = id;
  document.getElementById("edit_name").value = name;
  document.getElementById("edit_month").value = month;
  document.getElementById("edit_working_hours").value = hours;
  document.getElementById("edit_bonus").value = bonus;
  document.getElementById("edit_deductions").value = deduction;

  const baseWrap = document.getElementById("baseSalaryWrap");
  const hourlyWrap = document.getElementById("hourlyRateWrap");
  const baseInput = document.getElementById("edit_base_salary");
  const hourlyInput = document.getElementById("edit_hourly_rate");

  if (payType === "月薪") {
    document.getElementById("paytype_monthly").checked = true;
    baseWrap.style.display = "block";
    hourlyWrap.style.display = "block"; // 仍顯示但反灰
    baseInput.disabled = false;
    hourlyInput.disabled = true;
    baseInput.value = payValue;
    hourlyInput.value = "";
  } else {
    document.getElementById("paytype_hourly").checked = true;
    baseWrap.style.display = "block";
    hourlyWrap.style.display = "block";
    baseInput.disabled = true;
    hourlyInput.disabled = false;
    hourlyInput.value = payValue;
    baseInput.value = "";
  }

  updateTotalSalary();
  new bootstrap.Modal(document.getElementById("editModal")).show();
};

// ====== 薪資類型切換監聽 ======
document.querySelectorAll('input[name="paytype"]').forEach(radio => {
  radio.addEventListener("change", () => {
    const selected = document.querySelector('input[name="paytype"]:checked').value;
    const baseInput = document.getElementById("edit_base_salary");
    const hourlyInput = document.getElementById("edit_hourly_rate");

    if (selected === "monthly") {
      baseInput.disabled = false;
      hourlyInput.disabled = true;
      hourlyInput.value = ""; // 自動清空
    } else {
      baseInput.disabled = true;
      hourlyInput.disabled = false;
      baseInput.value = ""; // 自動清空
    }
    updateTotalSalary();
  });
});

// 即時計算薪資
window.updateTotalSalary = function () {
  const paytype = document.querySelector('input[name="paytype"]:checked').value;
  const base = parseFloat(document.getElementById("edit_base_salary").value) || 0;
  const rate = parseFloat(document.getElementById("edit_hourly_rate").value) || 0;
  const hours = parseFloat(document.getElementById("edit_working_hours").value) || 0;
  const bonus = parseFloat(document.getElementById("edit_bonus").value) || 0;
  const deductions = parseFloat(document.getElementById("edit_deductions").value) || 0;

  let calcBase = paytype === "monthly" ? base : rate * hours;
  document.getElementById("edit_calc_basepay").value = calcBase.toFixed(0);
  const total = calcBase + bonus - deductions;
  document.getElementById("edit_total_salary").textContent = total.toFixed(0);
};


  // ===== 輔助 =====
  function showLoading(show) {
    loadingIndicator.classList.toggle("d-none", !show);
  }
  function showError(show, msg = "") {
    errorAlert.classList.toggle("d-none", !show);
    if (show) errorMessage.textContent = msg;
  }
});
