<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="Ml63mUEFyaGvIM0h52l8aZ6cEGkZ61t2Jw0t9jhv" />
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="Ml63mUEFyaGvIM0h52l8aZ6cEGkZ61t2Jw0t9jhv" />
    <title>Select Building | {{$setting->bussiness_name}}</title>
    <link rel="stylesheet" href="{{asset('public/admin/css/bootstrap.min.css')}}">
    <script src="{{asset('public/admin/js/jquery.min.js')}}"></script>
    <link rel="shortcut icon" href="{{$setting->favicon}}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <style>
        @import  url('https://fonts.googleapis.com/css2?family=Questrial&display=swap');
        *{font-family:Jost;}
        .btn-custom{background-color:black;color:white;}
        .btn-custom:hover{background-color:black;color:white;}
        .btn-custom:after{background-color:black;color:white;}
        a{color:black;}
        a:hover{text-decoration:none;}
        .card{box-shadow: 0px 2px red;}
        .right{float:right;}
        @media screen and (max-width: 468px) {
            video{width:100% !important;}
        }
    </style>
    
      <style>
    body {
        font-family: 'Times New Roman', Times, serif;
        background: url('{{url("/public/admin/ChatGPT Image Nov 15_ 2025_ 04_23_23 PM.png")}}') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(37, 62, 88, 0.6);
        z-index: -1;
    }
    .building-options-container {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 500px;
        width: 90%;
        margin: 0 auto;
        background: rgba(255,255,255,0.95);
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.10);
        padding: 40px 30px 30px 30px;
    }
</style>


</head>

<body style="background-color:grey;">
    <div class="container">
        <div class="row mt-5">
            <div class="col-md-12 mt-2">
                <center>
                    <div>
                        <img src="{{$setting->logo}}" style="width:240px;">
                    </div>
                    @if(session()->has('error'))
                        <div class="alert alert-danger mt-5">
                            {{ session()->get('error') }}
                        </div>
                    @endif
                </center>
            </div>
            @forelse($buildings as $building)
                <div class="col-lg-4 col-4 mt-5">
                    <a href="{{url('select-building',$building->id)}}">
                    <div class="card">
                        <div class="card-body">
                            <div class="box bg-red">
                                <h2 class="text-center">{{$building->name}}</h2>
                                <hr>
                                <?php 
                                    $created_counts = \App\Models\Flat::where('building_id', $building->id)->count();
                                    $flat_limit = $building->no_of_flats;
                                ?>
                                <p>No of Flats: <span class="right">{{$created_counts}}/{{$flat_limit}}</span></p>
                                <?php 
                                    $created_counts = \App\Models\User::where('created_by', $building->id)->where('created_type','direct')->count(); 
                                    $login_limit = $building->no_of_logins;
                                    $other_created_counts = \App\Models\User::where('created_by', $building->id)->where('created_type','other')->count(); 
                                    $other_login_limit = $building->no_of_other_users;
                                ?>
                                <p>No of Users: <span class="right">{{$created_counts}}/{{$login_limit}}</span></p>
                                <p>No of Other Users: <span class="right">{{$other_created_counts}}/{{$other_login_limit}}</span></p>
                                <p>Valid Till: <span class="right">{{$building->valid_till}}</span></p>
                             
                            <p>Address: <span class="right">{{$building->address}}</span></p>
                             
                            </div>
                           
                            <br>
                            <p>Status: {{$building->status}}<span class="right">Continue....</span></p>
                        </div>
                    </div>
                    </a>
                </div>
                @empty
                <div class="col-md-12">
                    <center>
                        <p class="text-white py-3">You dont have any building to manage, Please contact to support</p>
                      {{--  <a href="{{url('logout')}}" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a> --}}

                        <form id="logout-form" action="{{url('logout')}}" method="POST" style="display: none;">
                            <input type="hidden" name="_token" value="{{csrf_token()}}">
                        </form>
                    </center>
                </div>
                @endforelse
                
                <div class="col-md-12 mt-5">
                    <div>
                        <a href="{{url('logout')}}" class="btn btn-sm btn-primary pt-3 pl-3 pr-3" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                          <p>Logout <i class="nav-icon fas fa-sign-out-alt"></i></p>
                        </a>
            
                        <form id="logout-form" action="{{url('logout')}}" method="POST" style="display: none;">
                            <input type="hidden" name="_token" value="{{csrf_token()}}">
                        </form>
                    </div>
                </div>
        </div>
    </div>
    <script src="{{asset('public/admin/plugins/jquery/jquery.min.js')}}"></script>
    
</body>
</html>