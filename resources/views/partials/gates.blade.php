            <select name="gate_id" class="form-control" id="gate_id" required>
                <option value="">--Select--</option>
                @forelse($gates as $gate)
                <option value="{{$gate->id}}" {{$gate->id == $gate_id ? 'selected' : ''}}>{{$gate->name}}</option>
                @empty
                @endforelse
            </select>