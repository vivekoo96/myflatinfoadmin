 {{-- @php
    // Ensure we have a Collection so ->pluck() and other methods are available
    $building_users = collect($building_users);
@endphp

<select name="treasurer_id" class="form-control" id="treasurer_id">
    @php
        $uniqueUsers = $building_users->unique('user_id');
    @endphp
    @if($uniqueUsers->isEmpty())
        <option value="{{ Auth::id() }}" selected>{{ Auth::user()->name }}</option>
    @else
        @foreach($uniqueUsers as $building_user)
            <option value="{{$building_user->user_id}}" {{ ($building->treasurer_id ?? null) == $building_user->user_id ? 'selected' : '' }}>{{$building_user->user->name}}</option>
        @endforeach
    @endif
</select> --}}
<style>
.readonly-select {
    background-color: #f4f4f4 !important;
    cursor: not-allowed;
}
</style>
@php
    use Illuminate\Support\Facades\Auth;

    $building_users = collect($building_users);
    $isBA = Auth::user()->role === 'BA';
@endphp

<select 
    name="treasurer_id" 
    id="treasurer_id"
    class="form-control {{ !$isBA ? 'readonly-select' : '' }}"
    {{ !$isBA ? 'disabled' : '' }}
>
    @php
        $uniqueUsers = $building_users->unique('user_id');
        $selectedTreasurer = $building->treasurer_id ?? Auth::id();
    @endphp

   @foreach($uniqueUsers as $building_user)
    @php
        $user = $building_user->user ?? $building_user;

        $displayName = $user->name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
    @endphp

    <option
        value="{{ $building_user->user_id ?? $building_user->id }}"
        {{ $selectedTreasurer == ($building_user->user_id ?? $building_user->id) ? 'selected' : '' }}
    >
        {{ $displayName }}
    </option>
@endforeach
</select>

{{-- If disabled, send value manually --}}
@if(!$isBA)
    <input type="hidden" name="treasurer_id" value="{{ $selectedTreasurer }}">
@endif

