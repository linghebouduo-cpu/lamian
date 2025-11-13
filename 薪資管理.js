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

  let editOriginal = {}; // 儲存 Modal 原始資料

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
      // [!! 關鍵修改 !!]
      const res = await fetch("薪資管理_api.php", {
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
      const payType = rec.salary_type || (rec.base_salary ? "月薪" : "時薪");
      const payValue = rec.base_salary > 0 ? rec.base_salary : rec.hourly_rate;
      const workingHours = rec.working_hours ?? 0;
      const bonus = rec.bonus ?? 0;
      const deductions = rec.deductions ?? 0;
      const totalSalary = rec.total_salary ?? 0;

      // 轉義字串,避免特殊字元導致錯誤
      const escapeHtml = (str) => {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
      };

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(rec.id)}</td>
        <td>${escapeHtml(rec.name)}</td>
        <td>${escapeHtml(rec.salary_month)}</td>
        <td><span class="badge bg-info text-dark">${escapeHtml(payType)}</span></td>
        <td>${payValue}</td>
        <td>${workingHours}</td>
        <td>${bonus}</td>
        <td>${deductions}</td>
        <td>${totalSalary}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-info me-2" data-action="detail" data-id="${escapeHtml(rec.id)}" data-month="${escapeHtml(rec.salary_month)}">
            <i class="fas fa-eye"></i>
          </button>
          <button class="btn btn-sm btn-warning" data-action="edit" 
            data-id="${escapeHtml(rec.id)}" 
            data-name="${escapeHtml(rec.name)}" 
            data-month="${escapeHtml(rec.salary_month)}" 
            data-paytype="${escapeHtml(payType)}" 
            data-payvalue="${payValue}" 
            data-hours="${workingHours}" 
            data-bonus="${bonus}" 
            data-deduction="${deductions}">
            <i class="fas fa-edit"></i>
          </button>
        </td>
      `;
      salaryTableBody.appendChild(tr);
    });

    // 使用事件委派來處理按鈕點擊
    salaryTableBody.addEventListener('click', handleTableClick);
  }

  // ===== 處理表格按鈕點擊事件 =====
  function handleTableClick(e) {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    
    if (action === 'detail') {
      showDetail(btn.dataset.id, btn.dataset.month);
    } else if (action === 'edit') {
      openEditModal(
        btn.dataset.id,
        btn.dataset.name,
        btn.dataset.month,
        btn.dataset.paytype,
        btn.dataset.payvalue,
        btn.dataset.hours,
        btn.dataset.bonus,
        btn.dataset.deduction
      );
    }
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
      // [!! 關鍵修改 !!]
      const res = await fetch("薪資管理_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "detail", id, month })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message);

      const d = data.record;
      document.getElementById("detailBody").innerHTML = `
        <p><strong>員工ID:</strong>${d.id}</p>
        <p><strong>姓名:</strong>${d.name}</p>
        <p><strong>薪資月份:</strong>${d.salary_month}</p>
        <p><strong>薪資類型:</strong>${d.salary_type}</p>
        <p><strong>底薪/時薪:</strong>${d.base_salary || d.hourly_rate}</p>
        <p><strong>本月工時:</strong>${d.working_hours}</p>
        <p><strong>獎金:</strong>${d.bonus}</p>
        <p><strong>扣款:</strong>${d.deductions}</p>
        <p><strong>實領金額:</strong>${d.total_salary}</p>
      `;
      new bootstrap.Modal(document.getElementById("detailModal")).show();
    } catch (err) {
      alert("讀取詳情失敗:" + err.message);
    }
  };

  // ======= 修改功能 =======
  window.openEditModal = function (id, name, month, payType, payValue, hours, bonus, deduction) {
    editOriginal = { 
      working_hours: hours,
      bonus,
      deductions: deduction 
    }; // 儲存原始值

    document.getElementById("edit_user_id").value = id;
    document.getElementById("edit_name").value = name;
    document.getElementById("edit_month").value = month;
    document.getElementById("edit_working_hours").value = hours;
    document.getElementById("edit_bonus").value = bonus;
    document.getElementById("edit_deductions").value = deduction;

    // 薪資類型與底薪/時薪唯讀
    const baseInput = document.getElementById("edit_base_salary");
    const hourlyInput = document.getElementById("edit_hourly_rate");
    if (payType === "月薪") {
      document.getElementById("paytype_monthly").checked = true;
      baseInput.value = payValue;
      hourlyInput.value = "";
    } else {
      document.getElementById("paytype_hourly").checked = true;
      hourlyInput.value = payValue;
      baseInput.value = "";
    }

    updateTotalSalary();

    // 顯示 Modal,確保使用 Bootstrap 的 show 方法顯示,並自動處理 aria-hidden
    const modal = new bootstrap.Modal(document.getElementById("editModal"));
    modal.show(); // 使用 Bootstrap Modal 顯示方法

    // 設置焦點,確保焦點在顯示的 Modal 中
    document.getElementById("edit_working_hours").focus();
  };

  // ===== 顯示 Modal 時,確保 aria-hidden 被正確處理 =====
  document.getElementById("editModal").addEventListener('shown.bs.modal', () => {
    const modalElement = document.getElementById("editModal");
    modalElement.setAttribute("aria-hidden", "false");
  });

  // ===== 隱藏 Modal 時,確保 aria-hidden 被設置 =====
  document.getElementById("editModal").addEventListener('hidden.bs.modal', () => {
    const modalElement = document.getElementById("editModal");
    modalElement.setAttribute("aria-hidden", "true");
  });

  // ===== 自動計算實領 =====
  function updateTotalSalary() {
    const base = parseFloat(document.getElementById("edit_base_salary").value) || 0;
    const rate = parseFloat(document.getElementById("edit_hourly_rate").value) || 0;
    const hours = parseFloat(document.getElementById("edit_working_hours").value) || 0;
    const bonus = parseFloat(document.getElementById("edit_bonus").value) || 0;
    const deductions = parseFloat(document.getElementById("edit_deductions").value) || 0;

    const paytype = document.querySelector('input[name="paytype"]:checked').value;
    const calcBase = paytype === "monthly" ? base : rate * hours;
    document.getElementById("edit_calc_basepay").value = calcBase.toFixed(0);

    const total = calcBase + bonus - deductions;
    document.getElementById("edit_total_salary").textContent = total.toFixed(0);
  }

  document.getElementById("edit_bonus").addEventListener("input", updateTotalSalary);
  document.getElementById("edit_deductions").addEventListener("input", updateTotalSalary);
  document.getElementById("edit_working_hours").addEventListener("input", updateTotalSalary);

  // ===== 恢復原始 =====
  document.getElementById("resetEditBtn").addEventListener("click", () => {
    document.getElementById("edit_working_hours").value = editOriginal.working_hours;
    document.getElementById("edit_bonus").value = editOriginal.bonus;
    document.getElementById("edit_deductions").value = editOriginal.deductions;
    updateTotalSalary();
  });

  // ===== 提交修改 =====
  window.submitEdit = async function (e) {
    e.preventDefault();

    const hoursInput = document.getElementById("edit_working_hours");
    const bonusInput = document.getElementById("edit_bonus");
    const deductionInput = document.getElementById("edit_deductions");

    let confirmMsg = "";
    if (hoursInput.value != editOriginal.working_hours) confirmMsg += `本月工時: ${editOriginal.working_hours} → ${hoursInput.value}\n`;
    if (bonusInput.value != editOriginal.bonus) confirmMsg += `獎金: ${editOriginal.bonus} → ${bonusInput.value}\n`;
    if (deductionInput.value != editOriginal.deductions) confirmMsg += `扣款: ${editOriginal.deductions} → ${deductionInput.value}\n`;

    if (confirmMsg) {
      if (!confirm("確定要修改以下欄位嗎?\n" + confirmMsg)) return;
    } else {
      alert("未修改任何欄位。");
      return;
    }

    const data = {
      action: "update",
      user_id: document.getElementById("edit_user_id").value,
      month: document.getElementById("edit_month").value,
      working_hours: hoursInput.value,
      bonus: bonusInput.value,
      deductions: deductionInput.value
    };

    try {
      // [!! 關鍵修改 !!]
      const res = await fetch("薪資管理_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });
      const result = await res.json();

      if (result.success) {
        alert("薪資資料已更新!");
        bootstrap.Modal.getInstance(document.getElementById("editModal")).hide();
        loadSalaryData(monthPicker.value);
      } else {
        alert("更新失敗:" + result.message);
      }
    } catch (err) {
      console.error("更新錯誤", err);
      alert("伺服器錯誤,請稍後再試");
    }
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