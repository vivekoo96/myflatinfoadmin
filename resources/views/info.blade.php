<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      background: #000000;
    }

    .container {
      max-width: 800px;
      width: 90%;
      background: #ffffff;
      padding: 30px;
      margin-top: 50px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    h1 {
      text-align: center;
      font-weight: 600;
      font-size: 2em;
      margin-bottom: 25px;
      letter-spacing: 1px;
      color: #000000;
      position: relative;
    }

    h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 3px;
      background: #000000;
    }

    .card {
      background: #ffffff;
      padding: 25px;
      color: #000000;
      line-height: 1.8;
      font-size: 1.05em;
    }

    @media (max-width: 768px) {
      .container {
        padding: 20px;
        width: 100%;
      }

      .card {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>About Us</h1>
    <div class="card">
      {!! $aboutUs !!}
    </div>
  </div>
</body>
</html>
