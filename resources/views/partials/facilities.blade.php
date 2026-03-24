    <div class="model-id">
        <div class="form-group">
            <label for="name" class="col-form-label">Building Facility:</label>
            <select name="model_id" class="form-control" id="model_id" required>
                @forelse($facilities as $facility)
                <option value="{{$facility->id}}" {{$facility->id == $model_id ? 'selected' : ''}}>{{$facility->name}}</option>
                @empty
                @endforelse
            </select>
        </div>
    </div>