<?php
// /lamian-ukn/員工資料表.php
// 如果之後需要登入保護，取消下一行註解即可：
// require_once __DIR__ . '/api/auth_guard.php';

declare(strict_types=1);
$pageTitle = '員工資料表 - 令和博多管理系統';

// 保險起見設定輸出編碼（不影響畫面內容）
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- 外部樣式表 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    <!-- 自定義樣式 -->
    <style>
        /* ==================== CSS 變數定義 ==================== */
        :root {
            --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%);
            --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
            --dark-bg: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
            --card-shadow: 0 15px 35px rgba(0,0,0,.1);
            --hover-shadow: 0 25px 50px rgba(0,0,0,.15);
            --border-radius: 20px;
            --transition: all .3s cubic-bezier(.4,0,.2,1);
        }

        /* ==================== 全域樣式 ==================== */
        * {
            transition: var(--transition);
        }

        body {
            background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        /* ==================== 頂部導航欄樣式 ==================== */
        .sb-topnav {
            background: var(--dark-bg) !important;
            border: none;
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(45deg, #ffffff, #ffffff);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
            text-shadow: none;
        }

        /* 頂欄搜尋框樣式 */
        .sb-topnav .form-control {
            border-radius: 25px;
            border: 2px solid transparent;
            background: rgba(255,255,255,.2);
            color: #fff;
        }

        .sb-topnav .form-control:focus {
            background: rgba(255,255,255,.3);
            border-color: rgba(255,255,255,.5);
            box-shadow: 0 0 20px rgba(255,255,255,.2);
            color: #fff;
        }

        /* ==================== 側邊導航欄樣式 ==================== */
        .sb-sidenav {
            background: linear-gradient(180deg, #fbb97ce4 0%, #ff00006a 100%) !important;
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(10px);
        }

        .sb-sidenav-menu-heading {
            color: rgba(255,255,255,.7) !important;
            font-weight: 600;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 20px 15px 10px 15px !important;
            margin-top: 15px;
        }

        .sb-sidenav .nav-link {
            border-radius: 15px;
            margin: 5px 15px;
            padding: 12px 15px;
            position: relative;
            overflow: hidden;
            color: rgba(255,255,255,.9) !important;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .sb-sidenav .nav-link:hover {
            background: rgba(255,255,255,.15) !important;
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(0,0,0,.2);
            color: #fff !important;
        }

        .sb-sidenav .nav-link.active {
            background: rgba(255,255,255,.2) !important;
            color: #fff !important;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(0,0,0,.15);
        }

        .sb-sidenav .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(45deg, #ffffff, #ffffff);
            transform: scaleY(0);
            transition: var(--transition);
            border-radius: 0 10px 10px 0;
        }

        .sb-sidenav .nav-link:hover::before,
        .sb-sidenav .nav-link.active::before {
            transform: scaleY(1);
        }

        .sb-sidenav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            font-size: 1rem;
        }

        .sb-sidenav-footer {
            background: rgba(255,255,255,.1) !important;
            color: #fff !important;
            border-top: 1px solid rgba(255,255,255,.2);
            padding: 20px 15px;
            margin-top: 20px;
        }

        /* ==================== 主內容區樣式 ==================== */
        .container-fluid {
            padding: 30px !important;
        }

        h1 {
            background: var(--primary-gradient);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }

        /* ==================== 麵包屑導航樣式 ==================== */
        .breadcrumb {
            background: rgba(255,255,255,.8);
            border-radius: var(--border-radius);
            padding: 15px 20px;
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(10px);
        }

        /* ==================== 表格容器與滾動設定 ==================== */
        .table-container {
            width: 100%;
            overflow-x: auto;
            white-space: nowrap;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            background: #fff;
        }

        .employee-table {
            border-collapse: separate;
            border-spacing: 0;
            width: max-content;
            min-width: 100%;
        }

        .employee-table thead th {
            position: sticky;
            top: 0;
            background: linear-gradient(90deg, #ff9b6a, #ff5e62);
            color: #000;
            font-weight: bold;
            text-align: center;
            height: 60px;
            min-width: 120px;
            border: none;
            vertical-align: middle;
        }

        .employee-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: rgba(0,0,0,.05);
            text-align: center;
        }

        .employee-table tbody tr:hover {
            background: rgba(227, 23, 111, 0.05);
            transform: scale(1.01);
        }

        /* ==================== 按鈕樣式 ==================== */
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 25px;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(209, 209, 209, 0.976);
        }
    </style>
</head>

<body class="sb-nav-fixed">
    
    <!-- ==================== 頂部導航欄 ==================== -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- 品牌Logo -->
        <a class="navbar-brand ps-3" href="index.html">令和博多管理系統</a>
        
        <!-- 側邊欄切換按鈕 -->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- 搜尋表單 -->
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Search for..." aria-label="Search" />
                <button class="btn btn-primary" id="btnNavbarSearch" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
        
        <!-- 用戶下拉選單 -->
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="#!">Settings</a></li>
                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item" href="#!">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- ==================== 主要佈局容器 ==================== -->
    <div id="layoutSidenav">
        
        <!-- ==================== 側邊導航區 ==================== -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        
                        <!-- Core 區塊 -->
                        <div class="sb-sidenav-menu-heading">Core</div>
                        <a class="nav-link" href="index.html">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
                        </a>
                        
                        <!-- Pages 區塊 -->
                        <div class="sb-sidenav-menu-heading">Pages</div>
                        
                        <!-- 人事管理 -->
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="員工資料表.html">員工資料表</a>
                                <a class="nav-link" href="班表管理.html">班表管理</a>
                                <a class="nav-link" href="假別管理.html">假別管理</a>
                                <a class="nav-link" href="打卡管理.html">打卡管理</a>
                                <a class="nav-link" href="薪資記錄表.html">薪資紀錄表</a>
                            </nav>
                        </div>
                        
                        <!-- 營運管理 -->
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseOperation" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                                <!-- 庫存管理 -->
                                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseInventory">
                                    庫存管理
                                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                </a>
                                <div class="collapse" id="operationCollapseInventory" data-bs-parent="#sidenavAccordionOperation">
                                    <nav class="sb-sidenav-menu-nested nav">
                                        <a class="nav-link" href="庫存查詢.html">庫存查詢</a>
                                        <a class="nav-link" href="庫存調整.html">庫存調整</a>
                                    </nav>
                                </div>
                                
                                <!-- 請假申請 -->
                                <a class="nav-link" href="請假申請.html">
                                    <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請
                                </a>
                                
                                <!-- 報表 -->
                                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseReport">
                                    報表
                                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                </a>
                                <div class="collapse" id="operationCollapseReport" data-bs-parent="#sidenavAccordion">
                                    <nav class="sb-sidenav-menu-nested nav">
                                        <a class="nav-link" href="日報表.html">日報表</a>
                                        <a class="nav-link" href="薪資報表.html">薪資報表</a>
                                        <a class="nav-link" href="班表.html">班表報表</a>
                                    </nav>
                                </div>
                            </nav>
                        </div>
                        
                        <!-- 網站管理 -->
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseWebsite">
                            <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>網站管理
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                                <a class="nav-link" href="layout-static.html">官網資料修改</a>
                                
                                <!-- 會員管理 -->
                                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#websiteCollapseMember">
                                    會員管理
                                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                </a>
                                <div class="collapse" id="websiteCollapseMember" data-bs-parent="#sidenavAccordionWebsite">
                                    <nav class="sb-sidenav-menu-nested nav">
                                        <a class="nav-link" href="member-list.html">會員清單</a>
                                        <a class="nav-link" href="member-detail.html">詳細資料頁</a>
                                        <a class="nav-link" href="point-manage.html">點數管理</a>
                                    </nav>
                                </div>
                            </nav>
                        </div>
                        
                        <!-- Addons 區塊 -->
                        <div class="sb-sidenav-menu-heading">Addons</div>
                        <a class="nav-link" href="charts.html">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts
                        </a>
                        <a class="nav-link" href="tables.html">
                            <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Tables
                        </a>
                    </div>
                </div>
                
                <!-- 側邊欄底部 -->
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    Start Bootstrap
                </div>
            </nav>
        </div>

        <!-- ==================== 主內容區 ==================== -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    
                    <!-- 頁面標題與日期 -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>員工資料表</h1>
                        <div class="text-muted">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span id="currentDate"></span>
                        </div>
                    </div>

                    <!-- 麵包屑導航 -->
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item">
                            <a href="index.html" class="text-decoration-none">首頁</a>
                        </li>
                        <li class="breadcrumb-item active">員工資料表</li>
                    </ol>

                    <!-- 操作按鈕區 -->
                    <div class="d-flex justify-content-end align-items-center mb-3 gap-2">
                        <button class="btn btn-primary" onclick="loadEmployees()">重新載入</button>
                        <button class="btn btn-primary" onclick="openAddEmployeeModal()">新增</button>
                        <div class="input-group" style="width:300px;">
                            <input type="text" class="form-control" placeholder="搜尋員工編號 / 姓名" id="searchInput">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchEmployees()">搜尋</button>
                        </div>
                    </div>

                    <!-- ✅ 改進後的員工資料表 -->
                    <div class="table-container">
                        <table class="table employee-table">
                            <thead>
                                <tr>
                                    <th>員工編號</th>
                                    <th>姓名</th>
                                    <th>出生年月日</th>
                                    <th>電話</th>
                                    <th>Email</th>
                                    <th>地址</th>
                                    <th>身份證</th>
                                    <th>雇用類別</th>
                                    <th>職位</th>
                                    <th>底薪</th>
                                    <th>時薪</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="employeeTable"></tbody>
                        </table>
                    </div>
                </div>
            </main>

            <!-- ==================== 頁腳 ==================== -->
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Xxing 0625</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- ==================== 新增員工Modal ==================== -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">新增員工</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="addEmployeeForm" novalidate>
                        <div class="row">
                            <!-- 基本資訊區塊 -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-primary">基本資訊</h6>
                                
                                <div class="mb-3">
                                    <label for="addName" class="form-label">姓名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addName" required>
                                    <div class="invalid-feedback">請輸入姓名</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="addBirthDate" class="form-label">出生年月日 <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="addBirthDate" required>
                                    <div class="invalid-feedback">請選擇出生年月日</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="addIdCard" class="form-label">身份證字號 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addIdCard" 
                                           pattern="^[A-Za-z][0-9]{9}$" placeholder="例：A123456789" required>
                                    <div class="invalid-feedback">請輸入正確的身份證字號格式</div>
                                </div>
                            </div>

                            <!-- 聯絡資訊區塊 -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-primary">聯絡資訊</h6>
                                
                                <div class="mb-3">
                                    <label for="addTelephone" class="form-label">電話號碼 <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="addTelephone" 
                                           pattern="^09[0-9]{8}$" placeholder="例：0912345678" required>
                                    <div class="invalid-feedback">請輸入正確的手機號碼格式</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="addAddress" class="form-label">地址 <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="addAddress" rows="2" required></textarea>
                                    <div class="invalid-feedback">請輸入地址</div>
                                </div>
                                <!-- 新增 Email 欄位 -->
                                <div class="mb-3">
                                    <label for="addEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="addEmail" 
                                           placeholder="例：employee@example.com">
                                    <div class="invalid-feedback">請輸入正確的Email格式</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- 工作資訊區塊 -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-primary">工作資訊</h6>
                                
                                <div class="mb-3">
                                    <label for="addRole" class="form-label">雇用類別 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="addRole" required>
                                        <option value="">請選擇雇用類別</option>
                                        <option value="正職">正職</option>
                                        <option value="臨時員工">臨時員工</option>
                                    </select>
                                    <div class="invalid-feedback">請選擇雇用類別</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="addPosition" class="form-label">職位 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addPosition" 
                                           placeholder="例：服務生、廚師、店長" required>
                                    <div class="invalid-feedback">請輸入職位</div>
                                </div>
                            </div>

                            <!-- 薪資資訊區塊 -->
                            <div class="col-md-6">
                                <h6 class="mb-3 text-primary">薪資資訊</h6>
                                
                                <div class="mb-3" id="addBaseSalaryGroup" style="display: none;">
                                    <label for="addBaseSalary" class="form-label">底薪 <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">NT$</span>
                                        <input type="number" class="form-control" id="addBaseSalary" 
                                               min="1" step="1" placeholder="請輸入底薪">
                                    </div>
                                    <div class="invalid-feedback">請輸入有效的底薪金額</div>
                                </div>
                                
                                <div class="mb-3" id="addHourlyRateGroup" style="display: none;">
                                    <label for="addHourlyRate" class="form-label">時薪 <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">NT$</span>
                                        <input type="number" class="form-control" id="addHourlyRate" 
                                               min="1" step="1" placeholder="請輸入時薪">
                                    </div>
                                    <div class="invalid-feedback">請輸入有效的時薪金額</div>
                                </div>
                                
                                <div class="text-muted small" id="addSalaryHint">
                                    請先選擇雇用類別以顯示對應的薪資欄位
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddEmployee()">
                        <i class="fas fa-save me-2"></i>新增員工
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 編輯員工Modal ==================== -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">編輯員工資料</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="editEmployeeForm">
                        <input type="hidden" id="editId">
                        
                        <!-- 基本資訊 -->
                        <div class="mb-3">
                            <label for="editName" class="form-label">姓名</label>
                            <input type="text" class="form-control" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editBirthDate" class="form-label">出生年月日</label>
                            <input type="date" class="form-control" id="editBirthDate" required>
                        </div>
                        
                        <!-- 工作相關 -->
                        <div class="mb-3">
                            <label for="editRole" class="form-label">雇用類別</label>
                            <select class="form-select" id="editRole" required>
                                <option value="正職">正職</option>
                                <option value="臨時員工">臨時員工</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPosition" class="form-label">職位</label>
                            <input type="text" class="form-control" id="editPosition" required>
                        </div>
                        
                        <!-- 薪資相關 -->
                        <div class="mb-3" id="editBaseSalaryGroup">
                            <label for="editBaseSalary" class="form-label">底薪</label>
                            <input type="number" class="form-control" id="editBaseSalary" step="1" min="0">
                        </div>
                        <div class="mb-3" id="editHourlyRateGroup">
                            <label for="editHourlyRate" class="form-label">時薪</label>
                            <input type="number" class="form-control" id="editHourlyRate" step="1" min="0">
                        </div>
                        
                        <!-- 聯絡資訊 -->
                        <div class="mb-3">
                            <label for="editTelephone" class="form-label">電話</label>
                            <input type="tel" class="form-control" id="editTelephone" required pattern="09\d{8}">
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">地址</label>
                            <input type="text" class="form-control" id="editAddress" required>
                        </div>
                        <div class="mb-3">
                            <label for="editIdCard" class="form-label">身份證</label>
                            <input type="text" class="form-control" id="editIdCard" required pattern="[A-Z][0-9]{9}">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail">
                            <div class="invalid-feedback">請輸入正確的Email格式</div>
                        </div>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitEdit()">儲存變更</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script src="員工資料表.js"></script>

</body>
</html>
