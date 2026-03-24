@extends('layouts.admin')

@section('title')
    Building Policy
@endsection

@section('content')

<link rel="stylesheet" href="{{asset('public/admin/plugins/summernote/summernote-bs4.min.css')}}">

<!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Building Policy</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Building Policy</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @endif
                       @if(Auth::User()->role == 'BA' )
                    <div class="card-body">
                            <form action="{{url('update-building-policy')}}" method="post">
                                @csrf
                                <div class="form-group">
                                    <label>Building Policy</label>
                                    <textarea name="building_policy" id="summernote" class="form-control">{!! $building_policy !!}</textarea>
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="btn bg-gradient-primary btn-flat" value="Save Changes">
                                </div>
                            </form>
                        </div>
                     @else
                          <div style="background-color: white; padding: 20px; border-radius: 4px; border: 1px solid #dee2e6;">
                                {!! $building_policy !!}
                              </div>
                     @endif
                   
                </div>
            </div>
        </div>
    </section>


@section('script')
<script src="{{asset('public/admin/plugins/summernote/summernote-bs4.min.js')}}"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>
@endsection

@endsection