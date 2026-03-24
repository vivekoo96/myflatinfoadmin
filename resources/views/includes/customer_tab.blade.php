<div class="table-responsive">
                    <table class="table table-bordered">
                      <tbody>
                        <tr>
                          <th>Booked By</th>
                          <td>{{$booking->user->name}}</td>
                          <th>Customer Name</th>
                          <td>{{$booking->name}}</td>
                        </tr>
                        <tr>
                          <th>Email</th>
                          <td>{{$booking->email}}</td>
                          <th>Phone</th>
                          <td>{{$booking->phone}}</td>
                        </tr>
                        <tr>
                          <th>Gender</th>
                          <td>{{$booking->gender}}</td>
                          <th>Age</th>
                          <td>{{$booking->age}}</td>
                        </tr>
                        <tr>
                          <th>Aadhar No</th>
                          <td>{{$booking->aadhar_no}}</td>
                          <th>Aadhar Copy</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/bookings/customer/'.$booking->aadhar_front_copy)}}" class="image">
                                <img src="{{asset('public/images/bookings/customer/'.$booking->aadhar_front_copy)}}" width="40px;" />
                              </a>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/bookings/customer/'.$booking->aadhar_back_copy)}}" class="image">
                                <img src="{{asset('public/images/bookings/customer/'.$booking->aadhar_back_copy)}}" width="40px;" />
                              </a>
                        </tr>
                        <tr>
                          <th>Licence No</th>
                          <td>{{$booking->licence_no}}</td>
                          <th>Licence copy</th>
                          <td>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/bookings/customer/'.$booking->licence_front_copy)}}" class="image">
                                <img src="{{asset('public/images/bookings/customer/'.$booking->licence_front_copy)}}" width="40px;" />
                              </a>
                              <a href="javascript:void(0);" data-toggle="modal" data-target="#imageModal" data-image="{{asset('public/images/bookings/customer/'.$booking->licence_back_copy)}}" class="image">
                                <img src="{{asset('public/images/bookings/customer/'.$booking->licence_back_copy)}}" width="40px;" />
                              </a>
                        </tr>
                        <tr>
                          <th>Flat No</th>
                          <td>{{$booking->flat_no}}</td>
                          <th>Street</th>
                          <td>{{$booking->street}}</td>
                        </tr>
                        <tr>
                          <th>Landmark</th>
                          <td>{{$booking->landmark}}</td>
                          <th>Pincode</th>
                          <td>{{$booking->pincode}}</td>
                        </tr>
                        <tr>
                          <th>Address 1</th>
                          <td>{{$booking->address_1}}</td>
                          <th>Address 2</th>
                          <td>{{$booking->address_2}}</td>
                        </tr>
                        <tr>
                          <th>City</th>
                          <td>{{$booking->city}}</td>
                          <th>State</th>
                          <td>{{$booking->state}}</td>
                        </tr>
                      </tbody>
                    </table>
                    </div>