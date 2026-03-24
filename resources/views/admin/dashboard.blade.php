@extends('layouts.admin')

@section('title')
    Dashboard
@endsection

@php
    // Get user and building for access control
    $user = Auth::user();
    $building = $user->building;
    
    // Helper function to get access control attributes for dashboard
    function getDashboardAccessControl($canViewPermission) {
        return $canViewPermission ? '' : 'data-no-access="true"';
    }
    
    // Check user's current role (similar to admin layout)
    $displayRole = 'User';
    $currentBuildingId = session('current_building_id') ?? Auth::user()->building_id;

    // Prefer session-selected role (explicitly set for current building)
    if (session('selected_role_id')) {
        $role = \App\Models\Role::find(session('selected_role_id'));
        if ($role) {
            // verify that the user actually has this role assignment in the current building
            $canViewAssignment = \App\Models\BuildingUser::where('user_id', Auth::id())
                ->where('role_id', $role->id)
                ->where('building_id', $currentBuildingId)
                ->exists();
            if ($canViewAssignment) {
                $displayRole = $role->name;
            }
        }
    }

    // If no valid session role, check in-memory selectedRole but validate building
    if ($displayRole === 'User' && Auth::user()->selectedRole && Auth::user()->selectedRole->id) {
        $selRole = Auth::user()->selectedRole;
        $canViewAssignment = \App\Models\BuildingUser::where('user_id', Auth::id())
            ->where('role_id', $selRole->id)
            ->where('building_id', $currentBuildingId)
            ->exists();
        if ($canViewAssignment) {
            $displayRole = $selRole->name;
        }
    }

    // Fallback to user's top-level role
    if ($displayRole === 'User' && Auth::user()->role) {
        $displayRole = Auth::user()->role;
    }
    
    // Check access for various dashboard cards - BA and President have full access
    $isAdminOrPresident = $user->role == 'BA' || $user->hasRole('president');
   
    // Full access cards for BA and President, role-based for others (excluding Account role for user cards)
    $isAccountsRole = strtolower($displayRole) === 'accounts';

$canViewUsers =
    !$isAccountsRole &&
    (
        $isAdminOrPresident
        || $user->hasPermission('custom.users')
    );


    $canViewBlocks = $isAdminOrPresident || $user->hasPermission('custom.blocks');
    $canViewFlats = $isAdminOrPresident || $user->hasPermission('custom.flats');
    $canViewEvents = $isAdminOrPresident || $user->hasRole('accounts') || $user->hasPermission('custom.events');
    $canViewNoticeboard = $isAdminOrPresident || ($building && $building->hasPermission('NoticeBoard'));
    $canViewClassified = $isAdminOrPresident || ($building && $building->hasPermission('Classified'));
    $canViewRoles = $isAdminOrPresident || $user->hasPermission('custom.roles');
    $canViewIssues = $isAdminOrPresident || $user->hasRole('issue') || $user->hasPermission('custom.issuestracking');
    $canViewFacilities = $isAdminOrPresident || ($building && $building->hasPermission('Facility'));
    $canViewBookings = $isAdminOrPresident || ($building && $building->hasPermission('Facility'));
    $canViewGates = $isAdminOrPresident || $user->hasPermission('custom.gates');
    $canViewVisitors = $isAdminOrPresident || $user->hasRole('security') || $user->hasPermission('custom.visitors');
    $canViewVehicles = $isAdminOrPresident || $user->hasRole('security') || $user->hasPermission('custom.vehiclesinouts');
    $canViewGuards = $isAdminOrPresident || $user->hasRole('security') || $user->hasPermission('custom.guards');
    $canViewEssentials = $isAdminOrPresident || ($building && $building->hasPermission('Essential'));
@endphp

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Dashboard</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <a href="{{url('users')}}" {{ getDashboardAccessControl($canViewUsers) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{\App\Models\User::where('created_by', $user->building_id)->where('created_type','direct')->count()}}</h3>
                            <p>Direct Users</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{url('other-users')}}" {{ getDashboardAccessControl($canViewUsers) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{\App\Models\User::where('created_by', $user->building_id)->where('created_type','other')->count()}}</h3>
                            <p>Other Users</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('block.index')}}" {{ getDashboardAccessControl($canViewBlocks) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->blocks->count() ?? 0}}</h3>
                            <p>Blocks</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('flat.index')}}" {{ getDashboardAccessControl($canViewFlats) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->flats->count()}}</h3>
                            <p>Flats</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('event.index')}}" {{ getDashboardAccessControl($canViewEvents) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->events->count()}}</h3>
                            <p>Events</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('noticeboard.index')}}" {{ getDashboardAccessControl($canViewNoticeboard) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->noticeboards->count() ?? 0}}</h3>
                            <p>Noticeboards</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('classified.index')}}" {{ getDashboardAccessControl($canViewClassified) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->classifieds->count()}}</h3>
                            <p>Classifieds</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('role.index')}}" {{ getDashboardAccessControl($canViewRoles) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->roles->count()}}</h3>
                            <p>Issue Departments</p>
                        </div>
                    </div>
                    </a>
                </div>
                @if($canViewIssues)
                <div class="col-lg-3 col-6">
                    <a href="{{route('issue.index')}}" {{ getDashboardAccessControl($canViewIssues) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->issues->count()}}</h3>
                            <p>Issues</p>
                        </div>
                    </div>
                    </a>
                </div>
                @endif
                <div class="col-lg-3 col-6">
                    <a href="{{route('facility.index')}}" {{ getDashboardAccessControl($canViewFacilities) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->facilities->count()}}</h3>
                            <p>Facilities</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('booking.index')}}" {{ getDashboardAccessControl($canViewBookings) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->bookings->count()}}</h3>
                            <p>Bookings</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('gate.index')}}" {{ getDashboardAccessControl($canViewGates) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->gates->count()}}</h3>
                            <p>Gates</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('visitor.index')}}" {{ getDashboardAccessControl($canViewVisitors) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->visitors->count()}}</h3>
                            <p>Visitors</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('vehicles.index')}}" {{ getDashboardAccessControl($canViewVehicles) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->vehicles->count()}}</h3>
                            <p>Vehicles</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('guard.index')}}" {{ getDashboardAccessControl($canViewGuards) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->guards->count()}}</h3>
                            <p>Guards</p>
                        </div>
                    </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="{{route('essential.index')}}" {{ getDashboardAccessControl($canViewEssentials) }}>
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{$user->building->essentials->count()}}</h3>
                            <p>Essentials</p>
                        </div>
                    </div>
                    </a>
                </div>
            </div>
            
            <!--<div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Transaction Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="transactionGraph" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Maintenance Payments Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="maintenanceChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Essential Payments Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="essentialChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Event Payments Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Income & Expenditure Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="expensesChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!--            <div class="card-header">-->
            <!--                <h3 class="card-title">Transaction Overview</h3>-->
            <!--            </div>-->
            <!--            <div class="card-body">-->
            <!--                <canvas id="transactionGraph" height="100"></canvas>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--    <div class="col-md-12">-->
            <!--        <div class="card">-->
            <!--            <div class="card-header">-->
            <!--                <h3 class="card-title">Maintenance Payments Overview</h3>-->
            <!--            </div>-->
            <!--            <div class="card-body">-->
            <!--                <canvas id="maintenanceChart" height="100"></canvas>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--    <div class="col-md-12">-->
            <!--        <div class="card">-->
            <!--            <div class="card-header">-->
            <!--                <h3 class="card-title">Essential Payments Overview</h3>-->
            <!--            </div>-->
            <!--            <div class="card-body">-->
            <!--                <canvas id="essentialChart" height="100"></canvas>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--    <div class="col-md-12">-->
            <!--        <div class="card">-->
            <!--            <div class="card-header">-->
            <!--                <h3 class="card-title">Event Payments Overview</h3>-->
            <!--            </div>-->
            <!--            <div class="card-body">-->
            <!--                <canvas id="paymentsChart" height="100"></canvas>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--    <div class="col-md-12">-->
            <!--        <div class="card">-->
            <!--            <div class="card-header">-->
            <!--                <h3 class="card-title">Income & Expenditure Overview</h3>-->
            <!--            </div>-->
            <!--            <div class="card-body">-->
            <!--                <canvas id="expensesChart" height="100"></canvas>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</div>-->
        </div>
    </section>

@section('script')

<script>
    
    $(document).ready(function(){
        var token = "{{csrf_token()}}";
        
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, "", window.location.href);
        };

        $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-user-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const labels = {!! json_encode($labels) !!};

    const data = {
        labels: labels,
        datasets: [
            {
                label: 'Credit - InBank',
                data: {!! json_encode($data['Credit-InBank']) !!},
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                fill: false,
                tension: 0.4,
            },
            {
                label: 'Credit - InHand',
                data: {!! json_encode($data['Credit-InHand']) !!},
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                fill: false,
                tension: 0.4,
            },
            {
                label: 'Debit - InBank',
                data: {!! json_encode($data['Debit-InBank']) !!},
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                fill: false,
                tension: 0.4,
            },
            {
                label: 'Debit - InHand',
                data: {!! json_encode($data['Debit-InHand']) !!},
                borderColor: 'rgba(220, 53, 69, 1)',
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                fill: false,
                tension: 0.4,
            }
        ]
    };

    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.formattedValue;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            }
        },
    };

    new Chart(document.getElementById('transactionGraph'), config);
</script>


<script>
    const maintenanceCtx = document.getElementById('maintenanceChart').getContext('2d');
    const maintenanceChart = new Chart(maintenanceCtx, {
        type: 'bar',
        data: {
            labels: @json($maintenance_labels ?? []),
            datasets: [
                {
                    label: 'Paid',
                    data: @json($maintenance_data['Paid'] ?? []),
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Unpaid',
                    data: @json($maintenance_data['Unpaid'] ?? []),
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Maintenance Payments (Paid vs Unpaid)'
                }
            },
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₹)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
</script>

<script>
    const essentialCtx = document.getElementById('essentialChart').getContext('2d');
    const essentialChart = new Chart(essentialCtx, {
        type: 'bar',
        data: {
            labels: @json($essential_labels),
            datasets: [{
                label: 'Essential Paid (₹)',
                data: @json($essential_data),
                backgroundColor: 'rgba(255, 206, 86, 0.7)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Essential Payments by Date'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₹)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
</script>

<script>
    const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
    const paymentsChart = new Chart(paymentsCtx, {
        type: 'bar',
        data: {
            labels: @json($payment_labels),
            datasets: [{
                label: 'Payments Received (₹)',
                data: @json($payment_data),
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Payments by Date'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₹)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
</script>

<script>
    const expensesCtx = document.getElementById('expensesChart').getContext('2d');
    const expensesChart = new Chart(expensesCtx, {
        type: 'bar',
        data: {
            labels: @json($expense_labels),
            datasets: [
                {
                    label: 'Credit - InBank',
                    data: @json($expense_data['Credit-InBank']),
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Credit - InHand',
                    data: @json($expense_data['Credit-InHand']),
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Debit - InBank',
                    data: @json($expense_data['Debit-InBank']),
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Debit - InHand',
                    data: @json($expense_data['Debit-InHand']),
                    backgroundColor: 'rgba(255, 206, 86, 0.6)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                },
            ]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Expenses by Date & Type'
                }
            },
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₹)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
</script>

@endsection

@endsection