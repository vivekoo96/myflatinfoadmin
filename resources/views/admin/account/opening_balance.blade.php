@extends('layouts.admin')

@section('title')
    Opening Balance
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Opening Balance</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Opening Balance</li>
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
                <div class="">
                    @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session()->get('error') }}
                    </div>
                    @endif
                    @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                    @endif
                </div>

            <div class="card">
                <div class="card-body">
                  <form action="{{url('account/update-opening-balance')}}" method="post" class="add-form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="error"></div>
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Maintenance Balance InBank:</label>
                            <input type="number" name="maintenance_in_bank" class="form-control" id="maintenance_in_bank" value="{{$building->maintenance_in_bank}}" min="0" placeholder="Maintenance Balance InBank" required>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Maintenance Balance InCash:</label>
                            <input type="number" name="maintenance_in_hand" class="form-control" id="maintenance_in_hand" value="{{$building->maintenance_in_hand}}" min="0" placeholder="Maintenance Balance InHand" required>
                          </div>
                        </div>
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Corpus Balance InBank:</label>
                            <input type="number" name="corpus_in_bank" class="form-control" id="corpus_in_bank" value="{{$building->corpus_in_bank}}" min="0" placeholder="Corpus Balance InBank" required>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Corpus Balance InCash:</label>
                            <input type="number" name="corpus_in_hand" class="form-control" id="corpus_in_hand" value="{{$building->corpus_in_hand}}" min="0" placeholder="Corpus Balance InHand" required>
                          </div>
                        </div>
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            @if(Auth::User()->role == 'BA' ||  Auth::User()->selectedRole->name == 'Accounts')
                          @php
                                $hasOpeningTransaction = \App\Models\Transaction::where('building_id', $building->id)
                                    ->where('model', 'Opening')
                                    ->exists();
                            @endphp
                            
                            <button
                                type="submit"
                                class="btn btn-primary btn-block"
                                id="save-button"
                                {{ $hasOpeningTransaction ? 'disabled' : '' }}
                            >
                                Save
                            </button>
                            @endif
                          </div>
                        </div>
                  </form>
                </div>
            </div>
            
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->



@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    var model = "{{ request('model') }}";
    var model_id = "{{ request('model_id') }}";
    
    

  });
</script>

<script src="{{asset('public/admin/plugins/summernote/summernote-bs4.min.js')}}"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>

@endsection


@endsection