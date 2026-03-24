    <div class="model-id">
        <div class="form-group">
            <label for="name" class="col-form-label">Essentials:</label>
            <select name="model_id" class="form-control" id="model_id" required>
                @forelse($essentials as $essential)
                <option value="{{$essential->id}}" {{$essential->id == $model_id ? 'selected' : ''}}>{{$essential->reason}}</option>
                @empty
                @endforelse
            </select>
        </div>
    </div>