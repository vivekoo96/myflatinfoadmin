@extends('layouts.admin')

@section('title')
    Setting
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row mt-5">
            <div class="col-md-12 mt-2">
                <center>
                    <div>
                        <!--<img src="{{$setting->logo}}" style="width:60%">-->
                    </div>
                </center>
            </div>
            <div class="col-md-6 offset-md-3">
                <center>
                    @if(session()->has('error'))
                        <div class="alert alert-danger">
                            {{ session()->get('error') }}
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <div class="box">
                                <h3>Permission Denied !</h3>
                                <p>You dont have permission to access this page</p>
                            </div>
                            <br>
                            <p><a href="{{url('/dashboard')}}">Back To Home</a></p>
                        </div>
                    </div>
                    </a>
                </center>
            </div>
        </div>
    </div>
</section>
@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    
    

  });
</script>
@endsection

@endsection



