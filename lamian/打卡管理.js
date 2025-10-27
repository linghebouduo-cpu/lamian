    // 今日日期 / 側欄
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    // === API 路徑 ===
    const API_BASE       = "http://localhost/lamian/";
    const API_LIST       = API_BASE + 'api/clock_list.php';
 const API_DELETE = API_BASE + 'api/clock_delete.php';
    const API_ADMIN_SAVE = API_BASE + 'api/clock_admin_save.php'; // 新增：管理端存檔

    // 小工具
    function parseHHMM(t){ if(!t) return null; const [h,m] = t.split(':').map(Number); if(Number.isNaN(h)||Number.isNaN(m)) return null; return h*60+m; }
    function minutesBetween(ci,co){ const a=parseHHMM(ci), b=parseHHMM(co); if(a==null||b==null) return null; let d=b-a; if(d<0) d+=1440; return d; }
    function hr2(mins){ return mins==null? '-' : (Math.round((mins/60)*100)/100).toFixed(2); }
    function inferStatus(ci,co,mins){ if(!ci||!co) return '缺卡'; if(mins!=null && mins>480) return '加班'; return '正常'; }
    function badge(status){
      if(status==='缺卡') return '<span class="badge-status badge-missing">缺卡</span>';
      if(status==='加班') return '<span class="badge-status badge-ot">加班</span>';
      return '<span class="badge-status badge-normal">正常</span>';
    }
    function showOk(m){ const a=document.getElementById('msgOk'); a.textContent=m; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'),2500);}
    function showErr(m){ const a=document.getElementById('msgErr'); a.textContent=m; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'),4000);}

    // 狀態
    let DATA = [];
    let timer = null;

    function setDefaultDates(){
      const end = new Date();
      const start = new Date(); start.setDate(end.getDate()-13);
      document.getElementById('end_date').value = end.toISOString().slice(0,10);
      document.getElementById('start_date').value = start.toISOString().slice(0,10);
    }

    function fillEmployeeFilter(rows){
      const sel = document.getElementById('employee_filter');
      const prev = sel.value;
      const ids = new Map(); // key: employee_id or emp_no, value: 顯示文字
      rows.forEach(r=>{
        const code = r.employee_id ?? r.emp_no ?? '';
        const name = r.emp_name ?? '';
        if(!code && !name) return;
        const label = code ? `${name}（${code}）` : name;
        ids.set(code || name, label);
      });
      sel.innerHTML = '<option value="">全部</option>' +
        Array.from(ids.entries()).map(([v,l])=>`<option value="${String(v).replace(/"/g,'&quot;')}">${l}</option>`).join('');
      if (ids.has(prev)) sel.value = prev; // 保留上一個選擇
    }

    async function loadList(){
      const p = new URLSearchParams();
      const s = document.getElementById('start_date').value;
      const e = document.getElementById('end_date').value;
      const emp = document.getElementById('employee_filter').value;
      const st  = document.getElementById('status_filter').value;

      if(s) p.set('start_date', s);
      if(e) p.set('end_date', e);
      if(emp) p.set('q', emp); // 用 q 篩員工（姓名/編號/工號皆可）

      try{
        const r = await fetch(API_LIST + (p.toString()?('?'+p.toString()):''), {headers:{'Accept':'application/json'}});
        if(!r.ok) throw new Error('HTTP '+r.status);
        const rows = await r.json();
        const list = Array.isArray(rows)? rows : (rows.data||[]);
        // 前端再用狀態做一次過濾
        DATA = list.filter(x=>{
          if(!st) return true;
          const mins = minutesBetween(x.clock_in, x.clock_out);
          const status = inferStatus(x.clock_in, x.clock_out, mins);
          return status === st;
        });
        fillEmployeeFilter(list);
        render();
      }catch(err){
        console.error(err);
        document.getElementById('attTableBody').innerHTML =
          `<tr><td colspan="9" class="text-center text-danger py-4">載入失敗：${String(err.message)}</td></tr>`;
      }
    }

    function render(){
      const tbody = document.getElementById('attTableBody');
      if(!DATA.length){
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">目前沒有資料</td></tr>`;
        setSummary(0,0,0,0); return;
      }
      let total=0, miss=0, otMin=0;
      tbody.innerHTML = DATA.map(row=>{
        const mins = minutesBetween(row.clock_in, row.clock_out);
        const st = inferStatus(row.clock_in, row.clock_out, mins);
        total += (mins||0);
        if(st==='缺卡') miss++;
        if(st==='加班' && mins) otMin += (mins-480);
        const hrs = hr2(mins);
        const empCode = row.employee_id ?? row.emp_no ?? '';
        const ops = `
          <button class="btn btn-sm btn-outline-primary me-1" onclick='openEdit(${JSON.stringify(row).replace(/'/g,"&#39;")})'>
            <i class="fas fa-pen"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="delRow(${row.id})">
            <i class="fas fa-trash"></i>
          </button>`;
        return `
          <tr>
            <td>${row.date??''}</td>
            <td>${row.emp_name??''}</td>
            <td>${empCode}</td>
            <td>${row.clock_in??''}</td>
            <td>${row.clock_out??''}</td>
            <td>—</td>
            <td>${hrs}</td>
            <td>${badge(st)}</td>
            <td>${ops}</td>
          </tr>`;
      }).join('');
      setSummary( (Math.round((total/60)*100)/100).toFixed(2), DATA.length, miss, (Math.round((otMin/60)*100)/100).toFixed(2) );
    }

    function setSummary(h, cnt, miss, ot){
      document.getElementById('sum_hours').textContent = h||'0.00';
      document.getElementById('sum_records').textContent = cnt||0;
      document.getElementById('sum_missing').textContent = miss||0;
      document.getElementById('sum_ot').textContent = ot||'0.00';
    }

    async function delRow(id){
      if(!confirm('確定要刪除此筆資料？')) return;
      try{
        const r = await fetch(API_DELETE + '?id=' + encodeURIComponent(id));
        const resp = await r.json();
        if(!r.ok || resp.error){ throw new Error(resp.error || ('HTTP '+r.status)); }
        showOk('已刪除');
        await loadList();
      }catch(err){
        console.error(err); showErr('刪除失敗：'+err.message);
      }
    }

    function exportCSV(){
      if(!DATA.length){ alert('目前沒有可匯出的資料'); return; }
      const headers = ['日期','員工姓名','員工編號','上班時間','下班時間','地點','工作時數','狀態'];
      const rows = DATA.map(r=>{
        const mins = minutesBetween(r.clock_in, r.clock_out);
        const st = inferStatus(r.clock_in, r.clock_out, mins);
        const empCode = r.employee_id ?? r.emp_no ?? r.user_id ?? '';
        return [ r.date||'', r.emp_name||'', empCode,
                 r.clock_in||'', r.clock_out||'', '—',
                 hr2(mins), st ];
      });
      const csv = [headers, ...rows].map(cols =>
        cols.map(v=> `"${String(v??'').replace(/"/g,'""')}"`).join(',')
      ).join('\r\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = '打卡管理_'+(new Date().toISOString().slice(0,10))+'.csv';
      document.body.appendChild(a); a.click(); document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    // === 編輯 Modal ===
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    document.getElementById('editForm').addEventListener('submit', saveForm);

    function openEdit(row){
      document.getElementById('f_id').value        = row.id || '';
      document.getElementById('f_date').value      = row.date || '';
      document.getElementById('f_emp_id').value    = (row.employee_id ?? row.user_id ?? '');
      document.getElementById('f_clock_in').value  = row.clock_in || '';
      document.getElementById('f_clock_out').value = row.clock_out || '';
      document.getElementById('f_status').value    = row.status || '';
      document.getElementById('f_note').value      = row.note || '';
      editModal.show();
    }

    async function saveForm(e){
      e.preventDefault();
      const payload = {
        id:        (document.getElementById('f_id').value||'') || undefined,
        date:      document.getElementById('f_date').value,
        emp_id:    document.getElementById('f_emp_id').value.trim(),
        clock_in:  document.getElementById('f_clock_in').value || null,
        clock_out: document.getElementById('f_clock_out').value || null,
        status:    document.getElementById('f_status').value || '',
        note:      document.getElementById('f_note').value.trim()
      };
      if(!payload.date)   return showErr('請填 日期');
      if(!payload.emp_id) return showErr('請填 員工編號');

      try{
        const r = await fetch(API_ADMIN_SAVE, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload),
          credentials:'include'
        });
        const resp = await r.json();
        if(!r.ok || resp.error){ throw new Error(resp.detail || resp.error || ('HTTP '+r.status)); }
        editModal.hide();
        showOk('已儲存');
        await loadList();
      }catch(err){
        console.error(err);
        showErr('儲存失敗：'+err.message);
      }
    }

    // 綁定事件 & 初始化
    window.addEventListener('DOMContentLoaded', async ()=>{
      setDefaultDates();
      await loadList();

      document.getElementById('btnSearch').addEventListener('click', loadList);
      document.getElementById('btnClear').addEventListener('click', async ()=>{
        setDefaultDates();
        document.getElementById('employee_filter').value = '';
        document.getElementById('status_filter').value = '';
        await loadList();
      });
      document.getElementById('btnExport').addEventListener('click', exportCSV);

      // 自動刷新（8 秒）
      timer = setInterval(loadList, 8000);
    });