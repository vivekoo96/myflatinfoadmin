<!DOCTYPE html>
<html>
<head>
    <title>Tenant Move Pass Generated</title>
</head>
<body>
    <h1>Hello, {{ $owner->name }}</h1>
    <p>A {{ $request->type }} pass for your flat <strong>{{ $request->flat->name }}</strong> in <strong>{{ $request->building->name }}</strong> has been generated for your tenant <strong>{{ $tenant_name }}</strong>.</p>
    <p><strong>Date:</strong> {{ date('d-m-Y', strtotime($request->date_of_entry_exit)) }}</p>
    <p><strong>Passcode:</strong> <span style="font-size: 20px; font-weight: bold; background: #eee; padding: 5px;">{{ $request->passcode }}</span></p>
    <p>The tenant can present this passcode at the gate for entry/exit clearance.</p>
    <p>Thank you!</p>
</body>
</html>
