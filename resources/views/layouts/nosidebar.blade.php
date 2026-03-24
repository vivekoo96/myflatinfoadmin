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
  <link rel="stylesheet" href="{{asset('admin/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- Select2 -->
  <!--<link rel="stylesheet" href="{{asset('admin/plugins/select2/css/select2.min.css')}}">-->
  <!--<link rel="stylesheet" href="{{asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">-->

  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('admin/dist/css/adminlte.min.css')}}">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
    
    /* Base styles for print/invoice layout */
    body {
        font-family: 'DejaVu Sans', sans-serif;
        margin: 0;
        padding: 0;
        background: white;
        color: #333;
        line-height: 1.4;
    }
    
    /* Hide unnecessary elements for print */
    tfoot { display: none; }
    .breadcrumb { display: none; }
    
    /* Button styles */
    .btn-primary {
        background-color: #3c5795;
        border-color: #3c5795;
        color: white;
    }
    .btn-primary:hover {
        background-color: #2a4078;
        border-color: #2a4078;
        color: white;
    }
    
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
        color: white;
    }
    
    .btn {
        margin-bottom: 3px;
    }
    
    /* Card styles */
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Table styles for better printing */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
    }
    
    .table th,
    .table td {
        padding: 0.5rem;
        vertical-align: top;
        border: 1px solid #dee2e6;
    }
    
    .table th {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        font-weight: bold;
    }
    
    /* Utility classes */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .float-right { float: right; }
    .float-left { float: left; }
    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
    
    /* Print-specific styles */
    @media print {
        body {
            font-size: 12px;
            line-height: 1.3;
        }
        .no-print {
            display: none !important;
        }
        .page-break {
            page-break-before: always;
        }
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        .table {
            font-size: 14px;
        }
    }
  </style>
</head>
<body class="">
<!-- Site wrapper -->
<div class="wrapper">

    @yield('content')

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{asset('admin/plugins/jquery/jquery.min.js')}}"></script>
<!-- Bootstrap 4 -->
<script src="{{asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- DataTables  & Plugins -->
<script src="{{asset('admin/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('admin/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('admin/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('admin/plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('admin/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script>
<script src="{{asset('admin/plugins/inputmask/jquery.inputmask.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{asset('admin/dist/js/adminlte.min.js')}}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{asset('admin/dist/js/demo.js')}}"></script>

<!-- Select2 -->
<!--<script src="{{asset('admin/plugins/select2/js/select2.full.min.js')}}"></script>-->

<!-- Bootstrap Switch -->
<script src="{{asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js')}}"></script>

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
    
    $(function () {
        $(".table").DataTable({
          "responsive": false, "ordering": true, "lengthChange": false, "autoWidth": false,"bPaginate": true,"bInfo": false,"searching": true,"pageLength": parseInt("{{$setting->pagination}}"),
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
    });

    
});
</script>

@yield('script')
</body>
</html>


