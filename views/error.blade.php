<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error - Please Report</title>
    <style>
        body {
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .error-box {
            background: #ffffff;
            padding: 2rem 3rem;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: fadeIn 0.6s ease;
        }

        .error-box h1 {
            color: #d63031;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
        }

        .error-box p {
            font-size: 1rem;
            color: #555;
            margin-bottom: 1.5rem;
        }

        .error-box .btn-report {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background-color: #d63031;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .error-box .btn-report:hover {
            background-color: #c0392b;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
<div class="error-box">
    <h1>Oops! Something went wrong.</h1>
    <p>We’re sorry for the inconvenience. Please report this issue and we'll look into it immediately.</p>
    <a href="mailto:{{ $email }}" class="btn-report">Report Issue</a>
</div>
</body>
</html>
