<div class="table-responsive">
                    <table class="table table-bordered">
                      <tbody>
                        <tr>
                          <th>Owner Name</th>
                          <td>{{$vehicle->owner_name}}</td>
                          <th>Email</th>
                          <td>{{$vehicle->email}}</td>
                          <th>Phone</th>
                          <td>{{$vehicle->phone}}</td>
                        </tr>
                        <tr>
                          <th>Adhar No</th>
                          <td>{{$vehicle->aadhar_no}}</td>
                          <th>Aadhar Front</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/vehicles/owners/'.$vehicle->aadhar_front)}}" class="image">
                                <img src="{{asset('public/images/vehicles/owners/'.$vehicle->aadhar_front)}}" width="40px;" />
                              </a>
                          <th>Aadhar Back</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/vehicles/owners/'.$vehicle->aadhar_back)}}" class="image">
                                <img src="{{asset('public/images/vehicles/owners/'.$vehicle->aadhar_back)}}" width="40px;" />
                              </a>
                          </td>
                        </tr>
                        <tr>
                          <th>Pan No</th>
                          <td>{{$vehicle->pan_no}}</td>
                          <th>Pan Front</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/vehicles/owners/'.$vehicle->pan_front)}}" class="image">
                                <img src="{{asset('public/images/vehicles/owners/'.$vehicle->pan_front)}}" width="40px;" />
                              </a>
                          </td>
                          <th>Pan Back</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/vehicles/owners/'.$vehicle->pan_back)}}" class="image">
                                <img src="{{asset('public/images/vehicles/owners/'.$vehicle->pan_back)}}" width="40px;" />
                              </a>
                          </td>
                        </tr>
                        <tr>
                          <th>RC No</th>
                          <td>{{$vehicle->rc_no}}</td>
                          <th>RC Front</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/vehicles/owners/'.$vehicle->rc_front)}}" class="image">
                                <img src="{{asset('public/images/vehicles/owners/'.$vehicle->rc_front)}}" width="40px;" />
                              </a>
                          </td>
                          <th>RC Back</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/vehicles/owners/'.$vehicle->rc_back)}}" class="image">
                                <img src="{{asset('public/images/vehicles/owners/'.$vehicle->rc_back)}}" width="40px;" />
                              </a>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    </div>