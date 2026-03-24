<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>जानिए अपने बॉयफ्रेंड का पूरा सच, क्या आपका बॉयफ्रेंड भी आपसे उतना ही प्यार करता है जितना आप करती हैं, अपने बॉयफ्रेंड का पूरा सच जानने के लिए नीचे दिए गए लिंक पर क्लिक करें</title>
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
  <!--<link rel="stylesheet" href="{{asset('public/admin/plugins/select2/css/select2.min.css')}}">-->
  <!--<link rel="stylesheet" href="{{asset('public/admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">-->

  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('public/admin/dist/css/adminlte.min.css')}}">
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
      .form-control{border-radius:0px !important;}
  </style>
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="text-center mt-5">जानिए... अपने बॉयफ्रेंड की पूरी सच्चाई</h1>
                <h1 class="text-center mt-5">बहुत ही आसान तरीका से</h1>
            </div>
            <?php 
            $a = 'बॉयफ्रेंड का नाम';
            $b = 'लगभाग कितने दिन तक साथ रहे';
            $c = 'कितना बार एक दूसरे के साथ सेक्स हुआ है';
            $save = 'पूरी सच्चाई जानने के लिए यहाँ क्लिक करे';
            ?>
            <div class="col-md-12">
                <form action="{{url('post-love-test')}}" method="post">
                    @csrf
                    <div class="row p-5 bg-red">
                        <div class="form-group col-md-4">
                            <label>{{$a}}</label>
                            <input type="text" name="a" class="form-control" placeholder="" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>{{$b}}</label>
                            <input type="text" name="b" class="form-control" placeholder="" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>{{$c}}</label>
                            <input type="text" name="c" class="form-control" placeholder="" required>
                        </div>
                    </div>
                    <div class="row p-5">
                        <button tye="submit" class="btn btn-block btn-primary">{{$save}}</button>
                    </div>
                </form>
                <div class="row p-3">
                            <div class="col-md-12">
                                @if(session()->has('success'))
                                    <div class="alert alert-success">
                                        {{ session()->get('success') }}
                                    </div>
                                @endif
                            </div>
                        </div>
            </div>
        </div>
    </div>
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
<!-- AdminLTE App -->
<script src="{{asset('public/admin/dist/js/adminlte.min.js')}}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{asset('public/admin/dist/js/demo.js')}}"></script>

</body>

</html>