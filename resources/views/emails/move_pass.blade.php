<!DOCTYPE html>
<html>
<head>
    <title>Move-In/Out Pass</title>
</head>
<body>
    <h1>Hello, {{ $name }}</h1>
    <p>Your {{ $request->type }} pass for flat {{ $request->flat->name }} in {{ $request->building->name }} has been generated.</p>
    <p><strong>Date:</strong> {{ date('d-m-Y', strtotime($request->date_of_entry_exit)) }}</p>
    <p><strong>Passcode:</strong> <span style="font-size: 20px; font-weight: bold; background: #eee; padding: 5px;">{{ $request->passcode }}</span></p>
    <p>Please show this passcode to the security at the gate for verification.</p>
    <p>Thank you!</p>
</body>
</html>
