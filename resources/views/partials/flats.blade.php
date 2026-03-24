
            <select name="flat_id" class="form-control" id="flat_id">
                <option value="">All</option>
                @forelse($flats as $flat)
                <option value="{{$flat->id}}" {{$flat->id == $flat_id ? 'selected' : ''}}>{{$flat->name}}</option>
                @empty
                @endforelse
            </select>