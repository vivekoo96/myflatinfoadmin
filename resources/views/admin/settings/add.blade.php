@extends('layouts.admin')

@section('title')
    Add
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Add</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Add</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                Add Setting
              </div>
              <div class="card-body">
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session()->get('error') }}
                    </div>
                @endif
                <form action="{{url('update-add')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Add Type</label>
                                <select class="form-control" name="add_type" value="{{$setting->add_type}}">
                                    <option value="Image" {{$setting->add_type=='Image' ? 'selected' : ''}}>Image</option>
                                    <option value="Video" {{$setting->add_type=='Video' ? 'selected' : ''}}>Video</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>File <a href="{{$setting->add_url}}" target="_blank">{{$setting->add_url}}</a></label>
                                <input type="file" class="form-control" name="add_url" accept="image/*,video/*">
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <input type="submit" class="btn btn-block bg-gradient-primary btn-flat" value="Save">
                        </div>
                    </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    

@section('script')

<script>
$(document).ready(function(){

});
</script>

@endsection

@endsection




