<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $setting = \App\Models\Setting::first(); ?>
  <title>@yield('title') | {{$setting->bussiness_name}}</title>
  <link rel="shortcut icon" href="{{$setting->favicon}}">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('public/admin/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{asset('public/admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('public/admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('public/admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="{{asset('public/admin/plugins/select2/css/select2.min.css')}}">
  <link rel="stylesheet" href="{{asset('public/admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('public/admin/dist/css/adminlte.min.css')}}">
 
     <style>
    @import url('https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
    tfoot{display:none;}
    .btn-primary{background-color:#3c5795;}
    .btn-primary:hover{background-color:white;color:black;}
    .page-item.active .page-link {
      z-index: 3;
      color: #fff;
      background-color: #3c5795;
      border-color: #007bff;
    }
    .page-item .page-link {
      color: black;
    }
    .active-tab{
        background-color: #3c5795;
        color: white;
    }
    .nav-pills .nav-link.active, .nav-pills .show > .nav-link {
      color: #fff;
      background-color: #3c5795;
      border-radius:0px;
    }
    /*.nav-pills .nav-link:hover, .nav-pills .show > .nav-link:hover {*/
    /*  color: #fff !important;*/
    /*}*/
    .custom-map-control-button{padding:14px;}
    .breadcrumb{display:none;}
    .right{float: right !important}
    
    /* Ensure sidebar content stays left-aligned */
    .main-sidebar {
        text-align: left;
        position: fixed !important;
        height: 100vh;
        overflow-x: hidden !important;
        z-index: 1038;
        display: flex;
        flex-direction: column;
    }
    
    .main-sidebar * {
        box-sizing: border-box;
    }
    
    /* Fix sidebar scrolling - make it scrollable */
    .sidebar {
        height: calc(100vh - 57px) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        flex: 1;
    }
    
    /* Make sidebar menu scrollable */
    .nav-sidebar {
        padding-bottom: 20px !important;
    }
    
    /* Hide scrollbar but keep functionality */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }
    
    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.4);
    }
    
    /* Fix content wrapper alignment when sidebar is toggled */
    .content-wrapper {
        transition: margin-left 0.3s ease-in-out;
    }
    
    /* Ensure proper layout when sidebar is collapsed */
    .sidebar-collapse .content-wrapper {
        margin-left: 0 !important;
    }
    
    .sidebar-collapse .main-footer {
        margin-left: 0 !important;
    }
    
    /* Fix navbar alignment */
    .main-header.navbar {
        transition: margin-left 0.3s ease-in-out;
    }
    .sidebar-dark-primary{background-color:white;}
    [class*="sidebar-dark-"] .sidebar a {
        color: black;
    }
    /*.nav-item:hover{background-color:black;color:white;border-bottom:2px solid white;}*/
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active, .sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
        background-color: #3c5795;
        color: white !important;
        border-radius:0px;
    }
    [class*="sidebar-dark-"] .nav-sidebar > .nav-item.menu-open > .nav-link, [class*="sidebar-dark-"] .nav-sidebar > .nav-item:hover > .nav-link, [class*="sidebar-dark-"] .nav-sidebar > .nav-item > .nav-link:focus {
        background-color: #3c5795;
        color: #fff;
    }
    .sidebar{
        padding-top: 2px;
        /*padding-bottom: 20px;*/
        padding-left: 0;
        padding-right: 0;
        height: calc(100vh - 57px) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }
    
    /* Fix sidebar menu alignment */
    .nav-sidebar {
        margin-left: 0;
        margin-right: 0;
        clear: both;
        margin-top: 10px;
        overflow-x: hidden !important;
        width: 100% !important;
    }
    
    .nav-sidebar .nav-item {
        margin: 0;
    }
    
    .nav-sidebar .nav-link {
        padding-left: 1rem;
        padding-right: 1rem;
        text-align: left;
        display: block;
        width: 100%;
        clear: both;
    }
    
    .nav-sidebar .nav-link i {
        margin-right: 0.5rem;
        width: 1.5rem;
        text-align: center;
        display: inline-block;
    }
    
    .nav-sidebar .nav-link p {
        margin: 0;
        display: inline-block;
        vertical-align: middle;
        flex: 1;
        padding-right: 2rem; /* Add space for arrow */
    }
    
    /* Fix dropdown arrow positioning - use existing AdminLTE arrows */
    .nav-sidebar .nav-link {
        display: flex;
        align-items: center;
        position: relative;
    }
    
    /* Position existing AdminLTE arrows properly */
    .nav-sidebar .nav-item.has-treeview > .nav-link .right,
    .nav-sidebar .nav-item[class*="menu-"] > .nav-link .right {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        margin-left: auto;
    }
    
    /* Ensure text doesn't overlap with arrow */
    .nav-sidebar .has-treeview > .nav-link p,
    .nav-sidebar [class*="menu-"] > .nav-link p {
        margin-right: 2.5rem;
        flex: 1;
    }
    .card-header {
        background-color: #ebf6f8;
    }
    
    /* Brand/Logo styling */
    .brand-link {
        display: block;
        padding: 10px;
        text-decoration: none;
        background-color: rgba(255,255,255,0.1);
        border-bottom: 1px solid rgba(255,255,255,0.2);
        transition: background-color 0.3s ease;
    }
    
    .brand-link:hover {
        background-color: rgba(255,255,255,0.15);
        text-decoration: none;
    }
    
    .brand-image {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        background-color: white;
        padding: 5px;
        float: none;
        clear: both;
    }
    .buttons-html5{padding:5px 8px !important;}
    .btn{margin-bottom:3px;}
    
    .bg-info{background-color:#3c5795 !important; color:white !important;}
    .bg-info:hover{background-color:white !important; color:black !important;}
    .btn-success{background-color:#3c5795;color:white;}
    .btn-success:hover{background-color:white;color:black;border-color:#3c5795;}
      .humburger-menu:hover{color:white !important;background-color:#3c5795 !important;border-radius:4px;}
    
    /* Fix hamburger menu hover state - prevent hover from triggering actions */
    .navbar-nav .nav-item .nav-link.humburger-menu {
        transition: background-color 0.3s ease, color 0.3s ease;
        padding: 8px 12px;
        border-radius: 4px;
        position: relative;
        z-index: 1050;
        cursor: pointer !important;
        display: inline-block !important;
        pointer-events: auto !important;
    }
    
    .navbar-nav .nav-item .nav-link.humburger-menu:hover {
        background-color: #3c5795 !important;
        color: white !important;
        /* Remove transform and other effects that might trigger events */
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Ensure hamburger menu icon is clickable but doesn't interfere */
    .navbar-nav .nav-item .nav-link.humburger-menu i {
        pointer-events: none !important;
        font-size: 1.1rem;
        transition: none !important;
    }
    
    /* Completely disable hover-based sidebar behavior */
    .navbar-nav .nav-item .nav-link[data-widget="pushmenu"] {
        transform: none !important;
    }
    
    .navbar-nav .nav-item .nav-link[data-widget="pushmenu"]:hover {
        transform: none !important;
    }
    
    /* COMPLETELY disable sidebar hover expansion */
    .main-sidebar,
    .main-sidebar:hover,
    .sidebar-collapse .main-sidebar,
    .sidebar-collapse .main-sidebar:hover {
        transition: none !important;
    }
    
    /* Force sidebar width to stay fixed */
    .sidebar-collapse .main-sidebar {
        width: 4.6rem !important;
    }
    
    .sidebar-collapse .main-sidebar:hover {
        width: 4.6rem !important;
    }
    
    /* Prevent nav-sidebar from expanding */
    .sidebar-collapse .nav-sidebar,
    .sidebar-collapse .nav-sidebar:hover,
    .sidebar-collapse .main-sidebar:hover .nav-sidebar {
        width: 4.6rem !important;
    }
    
    /* Disable all AdminLTE sidebar hover classes */
    .sidebar-mini .main-sidebar:hover .nav-sidebar,
    .sidebar-mini-md .main-sidebar:hover .nav-sidebar,
    .sidebar-mini-xs .main-sidebar:hover .nav-sidebar {
        width: 4.6rem !important;
    }
    
    /* Override AdminLTE hover expansion completely */
    .sidebar-collapse .main-sidebar:hover .nav-sidebar > .nav-item > .nav-link {
        width: 4.6rem !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    /* Prevent text from showing on hover */
    .sidebar-collapse .main-sidebar:hover .nav-sidebar .nav-link p {
        display: none !important;
    }
    
    /* Keep icons centered when collapsed */
    .sidebar-collapse .nav-sidebar .nav-link i {
        margin-right: 0 !important;
        text-align: center !important;
        width: 100% !important;
    }
    
    /* Ensure navbar stays above sidebar */
    .main-header.navbar {
        z-index: 1040;
    }
    
    /* Prevent hover conflicts with sidebar items */
    .main-sidebar .nav-sidebar .nav-item .nav-link:hover {
        background-color: #3c5795 !important;
        color: #fff !important;
        transition: all 0.3s ease;
    }
    
    /* Logout button styling - make it stand out */
    .nav-sidebar .nav-item:last-child {
        border-top: 1px solid rgba(255,255,255,0.1);
        /*margin-top: 10px;*/
        /*padding-top: 10px;*/
    }
    
    /* Ensure logout button is always visible */
    /*.nav-sidebar .nav-item .nav-link[href*="logout"] {*/
    /*    background-color: rgba(220, 53, 69, 0.1) !important;*/
    /*    border-left: 3px solid #dc3545;*/
    /*    font-weight: 500;*/
    /*}*/
    
    /*.nav-sidebar .nav-item .nav-link[href*="logout"]:hover {*/
    /*    background-color: #dc3545 !important;*/
    /*    color: white !important;*/
    /*}*/
    
    /* Disabled menu items styling */
    /* .nav-link.disabled-access {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .nav-link.disabled-access:hover {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    } */

    /* For active sub-menu links (like "Income & Expenditure") */
.sidebar-dark-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link.active,
.sidebar-light-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link.active {
    background-color: #3c5795;
    color: white !important;
    border-radius: 0px;
}
.sidebar-dark-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link,
.sidebar-light-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link {
    color: black;
}
.sidebar-dark-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link:hover,
.sidebar-light-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link:hover {
    background-color: #ddd;
}
.second-active{
  background-color: #ddd;
  color: black !important;
}
.third-active{
  background-color: #ddd;
  color: black !important;
}

.second{
  margin-left:12px;
}
.third{
  margin-left:24px;
}


/* .table {
  font-size: 14px; /* adjust as needed: 12px or 13px is common for tables */
/* } */

.table th,
.table td {
  padding: 6px 10px; /* reduce padding for compact spacing */
} */


    
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link humburger-menu" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <?php $user = Auth::User(); ?>
      <?php $building = $user->building; ?>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">{{$building->name}} :
                  @php
                        use App\Models\Role;
                        use App\Models\BuildingUser;
                    
                        $displayRole = 'User';
                        $user = Auth::user();
                        $currentBuildingId = session('current_building_id') ?? $user->building_id;
                        $selectedRoleId = session('selected_role_id');
                    
                      
                        if ($selectedRoleId === 0 || $selectedRoleId === '0') {
                    
                            $hasBuilding = BuildingUser::where('user_id', $user->id)
                                ->where('building_id', $currentBuildingId)
                                ->exists();
                    
                            if ($hasBuilding && !empty($user->role)) {
                                $displayRole = $user->role;
                            }
                        }
                    
                      
                        elseif (!empty($selectedRoleId) && $selectedRoleId != '0') {
                    
                            // 🔹 This is the line you mentioned
                            $role = Role::find($selectedRoleId);
                    
                            if ($role) {
                                $hasAssignment = BuildingUser::where('user_id', $user->id)
                                    ->where('role_id', $role->id)
                                    ->where('building_id', $currentBuildingId)
                                    ->exists();
                    
                                if ($hasAssignment) {
                                    // display from users table OR role table? (your rule)
                                    $displayRole =  $role->name;
                                }
                            }
                        }
                    
                        /*
                        |--------------------------------------------------------------------------
                        | CASE 3: fallback → USERS TABLE ROLE
                        |--------------------------------------------------------------------------
                        */
                        if ($displayRole === 'User' && !empty($user->role)) {
                            $displayRole = $user->role;
                        }
                    @endphp

                

            <span>{{ $displayRole }}</span>
          
        </a>
      </li>
      <!--<li class="nav-item d-none d-sm-inline-block">-->
      <!--  <a href="#" class="nav-link">Contact</a>-->
      <!--</li>-->
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Auto Logout Timer -->
      <!--<li class="nav-item">-->
      <!--  <span class="nav-link" id="logout-timer" style="color: #dc3545; font-weight: bold; display: none;">-->
      <!--    <i class="fas fa-clock"></i> <span id="timer-text">5:00</span>-->
      <!--  </span>-->
      <!--</li>-->
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    
    <a href="{{url('/')}}" class="brand-link text-center">
        
        <img src="{{$setting->logo}}" alt="{{$setting->bussiness_name}} Logo" class="brand-image" style="width:90%; height:auto; max-height:80px; object-fit:contain; display:inline-block; margin:10px auto; float:none;">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        @php
          // Helper function to check if user has access
          function hasAccess($conditions) {
              return $conditions;
          }
          
          // Helper function to get access control attributes
          function getAccessControl($hasPermission) {
              return $hasPermission ? '' : 'data-no-access="true"';
          }
        @endphp
        
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
          <li class="nav-item">
            <a href="{{url('/building-option')}}" class="nav-link {{ request()->is('building-option*') ? 'active' : '' }}">
              <i class="nav-icon fa fa-database"></i>
              <p>Switch Account</p>
            </a>
          </li>
          {{-- {{ dd(Auth::User()->selectedRole->type) }} --}}
       
          @php
            $hasDashboardAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasRole('accounts') || $user->hasRole('custom.roles');
          @endphp
          <li class="nav-item">
            <a href="{{url('/dashboard')}}" class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}" {{ getAccessControl($hasDashboardAccess) }}>
              <i class="nav-icon fa fa-bar-chart"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{url('/profile')}}" class="nav-link {{ request()->is('profile*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-duotone fa-user"></i>
              <p>Profile</p>
            </a>
          </li>
          @php

          
            $isInformationOpen = request()->is('users') || request()->is('other-users') || request()->is('block*') || request()->is('flat*') 
            || request()->is('gate*') || request()->is('parking*');
            $isUsersOpen = request()->is('users');
            $isOtherUsersOpen = request()->is('other-users');
            $isBlockActive = request()->is('block*');
            $isFlatActive = request()->is('flat*');
            $isGateActive = request()->is('gate*');
            $isParkingActive = request()->is('parking*');
            
            // Check access for Information menu items
            $hasInformationAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasRole('accounts') || $user->hasPermission('custom.information');
            $hasUsersAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasPermission('custom.information');
            $hasBlockAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasPermission('custom.information');
            $hasFlatAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('custom.information');
            $hasGateAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasPermission('custom.information');
            $hasParkingAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasPermission('custom.information');
          @endphp

 
             @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') || Auth::User()->hasPermission('custom.information'))
          <li class="nav-item has-treeview {{ $isInformationOpen ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isInformationOpen ? 'active' : '' }}" {{ getAccessControl($hasInformationAccess) }}>
                  <i class="nav-icon fa fa-info"></i>
                  <p>
                      Information
                      <i class="right fas fa-angle-left"></i>
                  </p>
              </a>

              <ul class="nav nav-treeview second">
                  <li class="nav-item">
                      <a href="{{ url('users') }}" class="nav-link {{ $isUsersOpen ? 'second-active' : '' }}" {{ getAccessControl($hasUsersAccess) }}>
                          <i class="far fa-circle nav-icon"></i>
                          <p>Users</p>
                      </a>
                  </li>
                  <li class="nav-item">
                      <a href="{{ url('other-users') }}" class="nav-link {{ $isOtherUsersOpen ? 'second-active' : '' }}" {{ getAccessControl($hasUsersAccess) }}>
                          <i class="far fa-circle nav-icon"></i>
                          <p>Other Users</p>
                      </a>
                  </li>
                  <li class="nav-item">
                      <a href="{{ route('block.index') }}" class="nav-link {{ $isBlockActive ? 'second-active' : '' }}" {{ getAccessControl($hasBlockAccess) }}>
                          <i class="far fa-circle nav-icon"></i>
                          <p>Block</p>
                      </a>
                  </li>
                  <li class="nav-item">
                      <a href="{{ route('flat.index') }}" class="nav-link {{ $isFlatActive ? 'second-active' : '' }}" {{ getAccessControl($hasFlatAccess) }}>
                          <i class="far fa-circle nav-icon"></i>
                          <p>Flat</p>
                      </a>
                  </li>
                  <li class="nav-item">
                      <a href="{{ route('gate.index') }}" class="nav-link {{ $isGateActive ? 'second-active' : '' }}" {{ getAccessControl($hasGateAccess) }}>
                          <i class="far fa-circle nav-icon"></i>
                          <p>Gate</p>
                      </a>
                  </li>
                  <li class="nav-item">
                      <a href="{{ route('parking.index') }}" class="nav-link {{ $isParkingActive ? 'second-active' : '' }}" {{ getAccessControl($hasParkingAccess) }}>
                          <i class="far fa-circle nav-icon"></i>
                          <p>Parking</p>
                      </a>
                  </li>
              </ul>
          </li>
    @endif
          @php
              $isAccountOpen = request()->is('account/*') || request()->is('maintenance*') || request()->is('event*') || request()->is('essential*');
              
              // Check access for Accounts menu
              $hasAccountsAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('feature.maintenance') || $user->hasPermission('custom.statements')
                || $user->hasPermission('custom.forms') || $user->hasPermission('custom.maintenances') || $user->hasPermission('custom.events') || $user->hasPermission('custom.essentials') || $user->hasPermission('custom.roles');
              
              $hasStatementsAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('custom.statements') || $user->hasPermission('custom.roles');
              $hasFormsAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('custom.forms') || $user->hasPermission('custom.roles');
              $hasMaintenanceAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('custom.maintenances') || $user->hasPermission('custom.roles');
              $hasMaintenanceFeature = $building && $building->hasPermission('Maintenance');
              $hasContributionsAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('custom.events') || $user->hasPermission('custom.essentials') || $user->hasPermission('custom.roles');
              $hasEventFeature = $building && $building->hasPermission('Event');
              $hasEssentialFeature = $building && $building->hasPermission('Essential');
          @endphp
          @php
            $role = Auth::user()->selectedRole->name ?? null;
        @endphp
              @if($role == 'Accounts' || Auth::User()->role == 'BA' || $role == 'President' || Auth::User()->hasPermission('custom.statements') || Auth::User()->hasPermission('custom.forms') || Auth::User()->hasPermission('custom.maintenances') || Auth::User()->hasPermission('custom.events') || Auth::User()->hasPermission('custom.essentials'))
          <li class="nav-item has-treeview {{ $isAccountOpen ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isAccountOpen ? 'active' : '' }} {{ getAccessControl($hasAccountsAccess) }}">
                  <i class="nav-icon fa fa-money"></i>
                  <p>
                      Accounts
                      <i class="right fas fa-angle-left"></i>
                  </p>
              </a>
              @php
              $isOpeningBalance = request()->is('account/opening-balance/*');
              $isStatementOpen = request()->is('account/statement/*');
              $isIncomeActive = request()->is('account/statement/income-and-expenditure*');
               $isPendingBillsActive = request()->is('account/pending-bills*');
              @endphp
               @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts') || $role == 'President' )
              <ul class="nav nav-treeview second">
                  <li class="nav-item">
                   <a href="{{url('account/opening-balance')}}" class="nav-link {{ request()->is('account/opening-balance*') ? 'second-active' : '' }} {{ getAccessControl($hasStatementsAccess) }}">
                     <i class="nav-icon fas fa-solid fa-wrench"></i>
                     <p>Opening Balance</p>
                   </a>
                  </li>
              </ul>
              @endif
                       <ul class="nav nav-treeview third">
    <li class="nav-item">
        <a href="{{ url('account/pending-bills') }}" 
           class="nav-link {{ $isPendingBillsActive ? 'third-active' : '' }} {{ getAccessControl($hasStatementsAccess) }}">
           
            <i class="fas fa-bell nav-icon"></i>
            <p>Pending Bills</p>
        </a>
    </li>
</ul>

          
              <ul class="nav nav-treeview second">
                  <li class="nav-item has-treeview {{ $isStatementOpen ? 'menu-open' : '' }}">
                      <a href="#" class="nav-link {{ $isStatementOpen ? 'second-active' : '' }} {{ getAccessControl($hasStatementsAccess) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>
                              Statements
                              <i class="right fas fa-angle-left"></i>
                          </p>
                      </a>

                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ url('account/statement/income-and-expenditure') }}" class="nav-link {{ $isIncomeActive ? 'third-active' : '' }} {{ getAccessControl($hasStatementsAccess) }}">
                                  <p>Income & Expenditure</p>
                              </a>
                          </li>
                      </ul>
                  </li>
              </ul>
              
              @php
                $isFormOpen = request()->is('account/forms*');
                $isPaymentActive = request()->is('account/forms/payment*');
                $isRecieptActive = request()->is('account/forms/reciept*');
              @endphp
              
              <ul class="nav nav-treeview second">
                  <li class="nav-item has-treeview {{ $isFormOpen ? 'menu-open' : '' }}">
                      <a href="#" class="nav-link {{ $isFormOpen ? 'second-active' : '' }} {{ getAccessControl($hasFormsAccess) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>
                              Forms
                              <i class="right fas fa-angle-left"></i>
                          </p>
                      </a>

                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ url('account/forms/payment') }}" class="nav-link {{ $isPaymentActive ? 'third-active' : '' }} {{ getAccessControl($hasFormsAccess) }}">
                                <p>Payments</p>
                              </a>
                          </li>
                      </ul>
                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ url('account/forms/reciept') }}" class="nav-link {{ $isRecieptActive ? 'third-active' : '' }} {{ getAccessControl($hasFormsAccess) }}">
                                <p>Receipts</p>
                              </a>
                          </li>
                      </ul>
                  </li>
              </ul>
              
              @php
                $isMaintenanceOpen = request()->is('account/maintenance*') || request()->is('maintenance*');
                $isGenerateActive = request()->is('maintenance*');
                $isManageActive = request()->is('account/maintenance/manage*');
               
              @endphp
              
              <ul class="nav nav-treeview second">
                  <li class="nav-item has-treeview {{ $isMaintenanceOpen ? 'menu-open' : '' }}">
                      <a href="#" class="nav-link {{ $isMaintenanceOpen ? 'second-active' : '' }} {{ getAccessControl($hasMaintenanceAccess && $hasMaintenanceFeature) }}">
                        <i class="far fa-circle nav-icon"></i>
                          <p>
                              Maintenance
                              <i class="right fas fa-angle-left"></i>
                          </p>
                      </a>

                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ route('maintenance.index') }}" class="nav-link {{ $isGenerateActive ? 'third-active' : '' }} {{ getAccessControl($hasMaintenanceAccess && $hasMaintenanceFeature) }}">
                                <p>Generate</p>
                              </a>
                          </li>
                      </ul>
                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ url('account/maintenance/manage') }}" class="nav-link {{ $isManageActive ? 'third-active' : '' }} {{ getAccessControl($hasMaintenanceAccess && $hasMaintenanceFeature) }}">
                                <p>Manage</p>
                              </a>
                          </li>
                      </ul>
                       
                  </li>
              </ul>
              
              @php
                $isContributionOpen = request()->is('event*') || request()->is('essential*');
                $isEventActive = request()->is('event*');
                $isEssentialActive = request()->is('essential*');
              @endphp
              
              <ul class="nav nav-treeview second">
                  <li class="nav-item has-treeview {{ $isContributionOpen ? 'menu-open' : '' }}">
                      <a href="#" class="nav-link {{ $isContributionOpen ? 'second-active' : '' }} {{ getAccessControl($hasContributionsAccess) }}">
                        <i class="far fa-circle nav-icon"></i>
                          <p>
                              Contributions
                              <i class="right fas fa-angle-left"></i>
                          </p>
                      </a>
                      
                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ route('event.index') }}" class="nav-link {{ $isEventActive ? 'third-active' : '' }} {{ getAccessControl($hasContributionsAccess && $hasEventFeature) }}">
                                <p>Event</p>
                              </a>
                          </li>
                      </ul>
                      
                      <ul class="nav nav-treeview third">
                          <li class="nav-item">
                              <a href="{{ route('essential.index') }}" class="nav-link {{ $isEssentialActive ? 'third-active' : '' }} {{ getAccessControl($hasContributionsAccess && $hasEssentialFeature) }}">
                                <p>Essential</p>
                              </a>
                          </li>
                      </ul>
                  </li>
              </ul>
          </li>
          @endif
          
          @php
            $isIssueManagementOpen = request()->is('issue') || request()->is('issue/*');
            $isIssueOpen = request()->is('issue*');
              $isRoleActive = request()->is('issue-department*');
                $hasStaffFeature = $building && $building->hasPermission('Staff');
                $hasIssueDepartmentAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('issue') || $user->hasPermission('custom.roles');
            // Check access for Issue Management
            $hasIssueManagementAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasAnyIssueRole() || $user->hasPermission('custom.issuestracking');
            $hasIssueFeature = $building && $building->hasPermission('Issue');
          @endphp
            @php
    $role = Auth::user()->selectedRole->name ?? null;
        @endphp

        @if(
            $user->role == 'BA' ||
            $role == 'Issue Tracker' ||
            $role == 'President' ||
            Auth::User()->hasPermission('custom.issuestracking')
        )
            @if($role !== 'Facility') 
                <!-- SHOW MENU -->
                <li class="nav-item has-treeview {{ $isIssueManagementOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isIssueManagementOpen ? 'active' : '' }} {{ getAccessControl($hasIssueManagementAccess) }}">
                        <i class="nav-icon fa fa-server"></i>
                        <p>
                            Issue Management
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
        
                    <ul class="nav nav-treeview second">
                         <li class="nav-item">
                            <a href="{{ url('issue-department') }}" class="nav-link {{ $isRoleActive ? 'active' : '' }} {{ getAccessControl($hasIssueDepartmentAccess && $hasStaffFeature) }}">
                                <i class="nav-icon fa fa-exclamation-triangle"></i>
                                <p>Issue Departments</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('issue.index') }}" class="nav-link {{ $isIssueOpen ? 'second-active' : '' }} {{ getAccessControl($hasIssueManagementAccess && $hasIssueFeature) }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Issue Tracking</p>
                            </a>
                        </li>
        
                       
                    </ul>
                </li>
            @endif
          @endif


          @php
          
            $isAmenitiesManagementOpen = request()->is('facility*') || request()->is('booking*');
            $isFacilityActive = request()->is('facility*');
            $isBookingActive = request()->is('booking*');
            
            // Check access for Amenities Management
            $hasAmenitiesAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('facility') || $user->hasPermission('custom.facilities');
            $hasFacilityFeatureAccess = $building && $building->hasPermission('Facility');
          @endphp
           @if($user->role == 'BA' || Auth::User()->selectedRole->name == 'Facility' || Auth::User()->selectedRole->name == 'President' || Auth::User()->hasPermission('custom.facilities') || Auth::User()->hasPermission('custom.bookings'))
          <li class="nav-item has-treeview {{ $isAmenitiesManagementOpen ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isAmenitiesManagementOpen ? 'active' : '' }} {{ getAccessControl($hasAmenitiesAccess) }}">
                  <i class="nav-icon fa fa-server"></i>
                  <p>
                      Amenities Management
                      <i class="right fas fa-angle-left"></i>
                  </p>
              </a>

              <ul class="nav nav-treeview second">
                  <li class="nav-item">
                      <a href="{{ route('facility.index') }}" class="nav-link {{ $isFacilityActive ? 'second-active' : '' }} {{ getAccessControl($hasAmenitiesAccess && $hasFacilityFeatureAccess) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Facility</p>
                      </a>
                  </li>
                  <li class="nav-item">
                      <a href="{{ route('booking.index') }}" class="nav-link {{ $isBookingActive ? 'second-active' : '' }} {{ getAccessControl($hasAmenitiesAccess && $hasFacilityFeatureAccess) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Bookings</p>
                      </a>
                  </li>
              </ul>
          </li>
          @endif
          @php
            $isStaffOpen = request()->is('department*') || request()->is('custom-departments*');
            $isSecurityOpen = request()->is('guard*');
            $isRoleActive = request()->is('issue-department*');
            $isDepartmentActive = request()->is('department*');
            
            // Check access for Staff/Role Management
          
            $hasRoleManagementAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasPermission('custom.roles');
            $hasSecurityGuardAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasPermission('custom.roles');
          
          @endphp
           @if($user->role == 'BA' || Auth::User()->selectedRole->name == 'President' || Auth::User()->hasPermission('custom.roles'))
          <li class="nav-item has-treeview {{ $isStaffOpen ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isStaffOpen ? 'active' : '' }} {{ getAccessControl($hasRoleManagementAccess && $hasStaffFeature) }}">
                  <i class="nav-icon fa fa-user-secret"></i>
                  <p>
                      Role Management
                      <i class="right fas fa-angle-left"></i>
                  </p>
              </a>

              <ul class="nav nav-treeview second">
                  @forelse(Auth::User()->building->common_roles() as $role)
                  <li class="nav-item">
                    <a href="{{url('department',$role->slug)}}" class="nav-link {{ request()->is('department/'.$role->slug.'*') ? 'second-active' : '' }} {{ getAccessControl($hasRoleManagementAccess && $hasStaffFeature) }}">
                      <i class="nav-icon fas fa-duotone fa-user"></i>
                      <p>{{$role->name}}</p>
                    </a>
                  </li>
                  @empty
                  
                  @endforelse
                  
                  <li class="nav-item">
                    <a href="{{url('custom-departments')}}" class="nav-link {{ request()->is('custom-departments*') ? 'second-active' : '' }} {{ getAccessControl($hasRoleManagementAccess && $hasStaffFeature) }}">
                      <i class="nav-icon fas fa-duotone fa-user"></i>
                      <p>Custom Roles</p>
                    </a>
                  </li>
              </ul>
          </li>
          @endif
         
          
         
          
          @php
            $isVehicleOpen = request()->is('vehicles*') || request()->is('vehicle-inout*') || request()->is('visitor*');
            $isVehicleActive = request()->is('vehicles*');
            $isInoutActive = request()->is('vehicle-inouts*');
            $isVisitorActive = request()->is('visitor*');
            
            // Check access for Security Management (Vehicle)
            $hasVehicleFeature = $building && $building->hasPermission('Vehicle');
            $hasSecurityManagementAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasPermission('custom.vehicles') || $user->hasPermission('custom.vehiclesinouts');
            $hasVisitorsAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasPermission('custom.visitors');
            $hasVehiclesAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasPermission('custom.vehicles');
          @endphp
            @if($user->role == 'BA' || Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Security' || Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President' || Auth::User()->hasPermission('custom.visitors') || Auth::User()->hasPermission('custom.vehicles') || Auth::User()->hasPermission('custom.vehiclesinouts'))
          <li class="nav-item has-treeview {{ $isVehicleOpen ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isVehicleOpen ? 'active' : '' }} {{ getAccessControl($hasSecurityManagementAccess && $hasVehicleFeature) }}">
                  <i class="nav-icon fa fa-car"></i>
                  <p>
                      Security Management
                      <i class="right fas fa-angle-left"></i>
                  </p>
              </a>
              
              <ul class="nav nav-treeview second">
                  <li class="nav-item">
                      <a href="{{ route('visitor.index') }}" class="nav-link {{ $isVisitorActive ? 'second-active' : '' }} {{ getAccessControl($hasVisitorsAccess && $hasVehicleFeature) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Visitors</p>
                      </a>
                  </li>
                  
                  <li class="nav-item">
                      <a href="{{ route('vehicles.index') }}" class="nav-link {{ $isVehicleActive ? 'second-active' : '' }} {{ getAccessControl($hasVehiclesAccess && $hasVehicleFeature) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Vehicles</p>
                      </a>
                  </li>
                  
                  <li class="nav-item">
                      <a href="{{ url('vehicle-inouts') }}" class="nav-link {{ $isInoutActive ? 'second-active' : '' }} {{ getAccessControl($hasVehiclesAccess && $hasVehicleFeature) }}">
                          <i class="far fa-circle nav-icon"></i>
                          <p>Vehicle Inouts</p>
                      </a>
                  </li>
                   <li class="nav-item">
            <a href="{{url('guard')}}" class="nav-link {{ request()->is('guard*') ? 'active' : '' }} {{ getAccessControl($hasSecurityGuardAccess && $hasStaffFeature) }}">
              <i class="nav-icon fa fa-shield"></i>
              <p>Security Guard</p>
            </a>
          </li>
              </ul>
          </li>
          @endif

          @php
            // Check access for Notice Board and Classified
            $hasNoticeBoardFeature = $building && $building->hasPermission('Notice Board');
            $hasNoticeBoardAccess = $user->role == 'BA' || $user->hasRole('president') || $user->hasPermission('custom.noticeboards');
            $hasClassifiedFeature = $building && Auth::User()->building->hasPermission('Classified for withinbuilding') || Auth::User()->building->hasPermission('Classified for all buildings');
         
          $hasClassifiedAccess  =  Auth::User()->building->hasPermission('Classified for withinbuilding') || Auth::User()->building->hasPermission('Classified for all buildings');
          @endphp
        @if($user->role == 'BA' || Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President' || Auth::User()->hasPermission('custom.noticeboards'))
          <li class="nav-item">
            <a href="{{route('noticeboard.index')}}" class="nav-link {{ request()->is('noticeboard*') ? 'active' : '' }} {{ getAccessControl($hasNoticeBoardAccess && $hasNoticeBoardFeature) }}">
              <i class="nav-icon fa fa-flag"></i>
              <p>Notice Board</p>
            </a>
          </li>
        @endif
        
          @if($user->role == 'BA' || Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President' || Auth::User()->hasPermission('custom.classifieds'))
          <li class="nav-item">
            <a href="{{route('classified.index')}}" class="nav-link {{ request()->is('classified*') ? 'active' : '' }}">
              <i class="nav-icon fa fa-glass"></i>
              <p>Classified</p>
            </a>
          </li>
          @endif
          
          <!--@if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('feature.societyfund'))-->
          <!--<li class="nav-item">-->
          <!-- <a href="{{url('orders')}}" class="nav-link {{ request()->is('orders*') ? 'active' : '' }}">-->
          <!--   <i class="nav-icon fa fa-first-order"></i>-->
          <!--   <p>Orders</p>-->
          <!-- </a>-->
          <!--</li>-->
          <!--@endif-->
          
          <!--@if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('feature.societyfund'))-->
          <!--<li class="nav-item">-->
          <!-- <a href="{{url('transactions')}}" class="nav-link {{ request()->is('transactions*') ? 'active' : '' }}">-->
          <!--   <i class="nav-icon fa fa-exchange"></i>-->
          <!--   <p>Transactions</p>-->
          <!-- </a>-->
          <!--</li>-->
          <!--@endif-->
          {{--  @if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') ||  Auth::User()->selectedRole->type == 'custom') --}}
       @if($user->role == 'BA')
          <li class="nav-item">
           <a href="{{route('setting.index')}}" class="nav-link {{ request()->is('setting*') ? 'active' : '' }}">
             <i class="nav-icon fas fa-solid fa-wrench"></i>
             <p>Settings</p>
           </a>
          </li>
          @endif
        
          
          @if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts'))
          <!--<li class="nav-item">-->
          <!-- <a href="{{url('privacy-policy')}}" class="nav-link {{ request()->is('privacy-policy*') ? 'active' : '' }}">-->
          <!--   <i class="nav-icon fas fa-solid fa-anchor"></i>-->
          <!--   <p>Privacy Policy</p>-->
          <!-- </a>-->
          <!--</li>-->
          @endif
          
          @if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts'))
          <!--<li class="nav-item">-->
          <!-- <a href="{{url('terms-conditions')}}" class="nav-link {{ request()->is('terms-conditions*') ? 'active' : '' }}">-->
          <!--   <i class="nav-icon fas fa-solid fa-splotch"></i>-->
          <!--   <p>Terms Conditions</p>-->
          <!-- </a>-->
          <!--</li>-->
          @endif
          
          @if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts'))
          <!--<li class="nav-item">-->
          <!-- <a href="{{url('cancellation-policy')}}" class="nav-link {{ request()->is('cancellation-policy*') ? 'active' : '' }}">-->
          <!--   <i class="nav-icon fas fa-sharp fa-solid fa-fill"></i>-->
          <!--   <p>Cancellation Policy</p>-->
          <!-- </a>-->
          <!--</li>-->
          @endif
          
          <li class="nav-item">
            <a href="{{url('building-policy')}}" class="nav-link {{ request()->is('building-policy') ? 'active' : '' }}">
              <i class="nav-icon fas fa-sharp fa-solid fa-fill"></i>
              <p>Building Policy</p>
            </a>
          </li>


@if($user->role == 'BA' || Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President')
         
                    <li class="nav-item">
                        <a href="{{ url('/terms-conditions') }}" class="nav-link">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p>Terms & Conditions</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/privacy-policy') }}" class="nav-link">
                            <i class="nav-icon fas fa-user-secret"></i>
                            <p>Privacy Policy</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/cancellation-policy') }}" class="nav-link">
                            <i class="nav-icon fas fa-ban"></i>
                            <p>Cancellation Policy</p>
                        </a>
                    </li>
@endif
          <li class="nav-item">
            <a href="{{url('logout')}}" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>

            <form id="logout-form" action="{{url('logout')}}" method="POST" style="display: none;">
                <input type="hidden" name="_token" value="{{csrf_token()}}">
            </form>

          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    @yield('content')
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.0
    </div>
   <strong>Copyright &copy; {{ date('Y') }} <a href="https://myflatinfo.com/home/" target="_blank">Myflat info</a>.</strong> All rights reserved.
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{asset('public/admin/plugins/jquery/jquery.min.js')}}"></script>
<!-- Bootstrap 4 -->
<script src="{{asset('public/admin/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- DataTables  & Plugins -->
<script src="{{asset('public/admin/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script>
<script src="{{asset('public/admin/plugins/inputmask/jquery.inputmask.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{asset('public/admin/dist/js/adminlte.min.js')}}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{asset('public/admin/dist/js/demo.js')}}"></script>

<!-- Select2 -->
<script src="{{asset('public/admin/plugins/select2/js/select2.full.min.js')}}"></script>

<!-- Bootstrap Switch -->
<script src="{{asset('public/admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js')}}"></script>

<script src="https://www.gstatic.com/firebasejs/8.3.2/firebase.js"></script>

<script>
    $("input[data-bootstrap-switch]").each(function(){
      $(this).bootstrapSwitch('state', $(this).prop('checked'));
    });
</script>
<script type="module">

  var firebaseConfig = {
        apiKey: "AIzaSyCMIXqJyXbmxmFMdywPuFbYd6cRUx-l6nc",
        authDomain: "school-979f6.firebaseapp.com",
        databaseURL: "https://school-979f6.firebaseio.com",
        projectId: "school-979f6",
        storageBucket: "school-979f6.appspot.com",
        messagingSenderId: "308636612449",
        appId: "1:308636612449:web:603eb003f33921ad9db720",
        measurementId: "G-45CQ9YMRNN"
    };
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();
    
    function startFCM() {
        messaging
            .requestPermission()
            .then(function () {
                return messaging.getToken()
            })
            .then(function (response) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '{{ route("save.token") }}',
                    type: 'POST',
                    data: {
                        device_token: response,
                        _token:"{{csrf_token()}}"
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        alert('Token stored.');
                    },
                    error: function (error) {
                        alert(error);
                    },
                });
            }).catch(function (error) {
                alert(error);
            });
    }
    messaging.onMessage(function (payload) {
        const title = payload.notification.title;
        const options = {
            body: payload.notification.body,
            icon: payload.notification.icon,
        };
        new Notification(title, options);
    });
    
</script>

<script>
$(document).ready(function(){
        $(".table").DataTable({
          "responsive": false, 
          "scrollX": true,  
          "ordering": true, 
          "lengthChange": false, 
          "autoWidth": false,
          "paging": true,
          "info": false,
          "searching": true,
          "pageLength": parseInt("{{$setting->pagination}}"),
          "language": {
              "search": "Search:"
          },
          order: [[0, 'asc']],
          buttons: [
            {
                extend: 'csvHtml5',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function (xlsx) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
        
                    // Make first row (headers) bold
                    $('row c[r^="A1"], row c[r^="B1"], row c[r^="C1"]', sheet).attr('s', '2');
        
                    // Set column width
                    var cols = '<cols>';
                    cols += '<col min="1" max="1" width="25"/>';
                    cols += '<col min="2" max="2" width="20"/>';
                    cols += '<col min="3" max="3" width="30"/>';
                    cols += '</cols>';
                    sheet.childNodes[0].insertBefore($.parseXML(cols).firstChild, sheet.getElementsByTagName('sheetData')[0]);
                }
            },
            {
                extend: 'pdfHtml5',
                exportOptions: {
                    columns: ':visible'
                }
            },
            'colvis'
        ]
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        
        $('#example2').DataTable({
          "paging": true,
          "lengthChange": false,
          "searching": false,
          "ordering": true,
          "info": true,
          "autoWidth": false,
          "responsive": false,
          "bPaginate": false,
          "bInfo": false,
        });
    
    document.querySelectorAll('input[type="password"], input[type="number"]').forEach(input => {
        ['copy','paste','cut','drop'].forEach(evt => {
            input.addEventListener(evt, e => e.preventDefault());
        });
    });

    // Wait for DataTables to initialize before adding input validation
    setTimeout(function() {
        document.querySelectorAll('input[type="text"], textarea').forEach(input => {
            // Skip DataTables search inputs and any inputs within DataTables structures
            if (input.closest('.dataTables_wrapper') || 
                input.closest('.dt-container') ||
                input.closest('.dataTables_filter') ||
                (input.id && input.id.includes('dt')) ||
                input.getAttribute('data-dt-') ||
                input.placeholder && input.placeholder.toLowerCase().includes('search') ||
                input.type === 'search' ||
                input.className && input.className.includes('dt-')) return;
            
            input.addEventListener('input', e => {
                // Allow only English letters, numbers, space, and common punctuation
                // Remove emojis + non-English characters
                e.target.value = e.target.value.replace(
                    /[^a-zA-Z0-9 .,!?@#%&*()_+\-=:;"'<>\/\\[\]{}|`~$^]/g,
                    ''
                );
            });
        });
    }, 100);
    
    document.querySelectorAll('input[type="password"]').forEach(input => {
        input.addEventListener('input', e => {
            // Remove spaces
            e.target.value = e.target.value.replace(/\s+/g, '');
        });
    });
    
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('input', e => {
            // Remove spaces
            e.target.value = e.target.value.replace(/\s+/g, '').toLowerCase();
        });
        
        // Set custom validation message for HTML5 validation
        input.addEventListener('invalid', function(e) {
            if (this.validity.valueMissing) {
                this.setCustomValidity('Please Enter a Valid email address');
            } else if (this.validity.typeMismatch) {
                this.setCustomValidity('Please Enter a Valid email address');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Clear custom validation message when input becomes valid
        input.addEventListener('input', function(e) {
            this.setCustomValidity('');
        });
    });

    
});
</script>

<!-- Auto Logout Script -->
<script>
$(document).ready(function() {
    let inactivityTimer;
    let warningTimer;
    let countdownTimer;
    let isWarningShown = false;
    let lastActivity = Date.now();
    let isActive = true;
    const INACTIVITY_TIME = 30 * 60 * 1000; // 30 minutes in milliseconds
    const WARNING_TIME = 29 * 60 * 1000; // Show warning at 29 minutes
    const SHOW_TIMER_AFTER = 30 * 1000; // Show timer after 30 seconds of inactivity
    
    // Events that indicate user activity
    const activityEvents = ['mousedown', 'keypress', 'scroll', 'touchstart', 'click'];
    
    // Function to logout user
    function autoLogout() {
        clearInterval(countdownTimer);
        $('#logout-timer').hide();
        alert('You have been logged out due to inactivity.');
        document.getElementById('logout-form').submit();
    }
    
    // Function to show warning
    function showWarning() {
        if (!isWarningShown) {
            isWarningShown = true;
            if (confirm('You will be logged out in 1 minute due to inactivity. Click OK to stay logged in.')) {
                resetTimer();
                isWarningShown = false;
            }
        }
    }
    
    // Function to update countdown display
    function updateCountdown() {
        const now = Date.now();
        const timeSinceLastActivity = now - lastActivity;
        const timeRemaining = INACTIVITY_TIME - timeSinceLastActivity;
        
        if (timeRemaining <= 0) {
            autoLogout();
            return;
        }
        
        // Show timer if inactive for more than 30 seconds
        if (timeSinceLastActivity > SHOW_TIMER_AFTER) {
            const minutes = Math.floor(timeRemaining / 60000);
            const seconds = Math.floor((timeRemaining % 60000) / 1000);
            const display = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            $('#timer-text').text(display);
            $('#logout-timer').show();
            
            // Change color based on time remaining
            if (timeRemaining <= 60000) { // Last minute - red
                $('#logout-timer').css('color', '#dc3545');
            } else if (timeRemaining <= 120000) { // Last 2 minutes - orange
                $('#logout-timer').css('color', '#fd7e14');
            } else { // More than 2 minutes - yellow
                $('#logout-timer').css('color', '#ffc107');
            }
        } else {
            $('#logout-timer').hide();
        }
    }
    
    // Function to reset the inactivity timer
    function resetTimer() {
        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownTimer);
        isWarningShown = false;
        lastActivity = Date.now();
        isActive = true;
        
        // Hide timer when user becomes active
        $('#logout-timer').hide();
        
        // Set warning timer (show warning after 4 minutes)
        warningTimer = setTimeout(showWarning, WARNING_TIME);
        
        // Set logout timer (logout after 5 minutes)
        inactivityTimer = setTimeout(autoLogout, INACTIVITY_TIME);
        
        // Start countdown display timer
        countdownTimer = setInterval(updateCountdown, 1000);
    }
    
   function elementOrAncestorMatches(target, selector) {
        while (target) {
            if (target.nodeType === 1) {
                var matches = target.matches || target.msMatchesSelector || target.webkitMatchesSelector;
                if (matches && matches.call(target, selector)) return true;
            }
            target = target.parentElement;
        }
        return false;
    }

    activityEvents.forEach(function(event) {
        document.addEventListener(event, function(e) {
            // Don't interfere with hamburger menu functionality
            try {
                if (elementOrAncestorMatches(e.target, '.humburger-menu') || elementOrAncestorMatches(e.target, '[data-widget="pushmenu"]')) {
                    return;
                }
            } catch (err) {
                // If something unexpected happens, don't block the timer reset
            }
            resetTimer();
        }, true);
    });
    
    // Initialize timer when page loads
    resetTimer();
    
    // Let AdminLTE handle hamburger menu functionality natively
    // Just ensure proper layout adjustments
    
    // Completely disable AdminLTE hover behavior and use click-only
    $(document).off('click mouseenter mouseleave', '[data-widget="pushmenu"]');
    
    // Disable AdminLTE's automatic sidebar hover behavior
    $('body').removeClass('sidebar-mini sidebar-mini-md sidebar-mini-xs');
    
    // Force remove any hover expansion classes
    $('.main-sidebar').removeClass('sidebar-mini sidebar-mini-md sidebar-mini-xs');
    
    // Continuously prevent AdminLTE from adding hover classes
    setInterval(function() {
        $('body').removeClass('sidebar-mini sidebar-mini-md sidebar-mini-xs');
        $('.main-sidebar').removeClass('sidebar-mini sidebar-mini-md sidebar-mini-xs');
    }, 100);
    
    // Add our own click-only handler
    $(document).on('click', '[data-widget="pushmenu"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Hamburger menu clicked - manual toggle');
        
        // Manually toggle the sidebar
        $('body').toggleClass('sidebar-collapse');
        
        // Log the state
        setTimeout(function() {
            console.log('Sidebar state:', $('body').hasClass('sidebar-collapse') ? 'collapsed' : 'expanded');
        }, 50);
    });
    
    // Block all hover events on hamburger menu
    $(document).on('mouseenter mouseleave mouseover mouseout', '[data-widget="pushmenu"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Disable sidebar hover behavior completely
    $('.main-sidebar').off('mouseenter mouseleave');
    $('body').off('mouseenter mouseleave', '.main-sidebar');
    
    // Ensure proper layout on page load
    $(window).on('load resize', function() {
        // Adjust content wrapper margin based on sidebar state
        if ($('body').hasClass('sidebar-collapse')) {
            $('.content-wrapper, .main-footer').css('margin-left', '0');
        } else {
            $('.content-wrapper, .main-footer').css('margin-left', '');
        }
    });
});

 setTimeout(function() {
      $('.alert-success, .alert-danger').fadeOut('slow');
    }, 2000);
    
    // Handle access denied clicks - redirect to permission denied page
    $(document).on('click', 'a[data-no-access="true"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.location.href = "{{ url('/permission-denied') }}";
        return false;
    });
</script>


@yield('script')
</body>
</html>


