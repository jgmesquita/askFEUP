<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }

    img {
        display: block;
        width: 200px;
        margin: 50px auto 20px;
    }

    h1 {
        text-align: center;
        font-weight: 500;
        font-size: 20px;
        margin: 20px auto;
    }

    a.button {
        display: block;
        margin: 20px auto;
        padding: 10px 20px;
        font-size: 13px;
        background-color: #73181F;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        text-align: center;
        cursor: pointer;
        width: fit-content;
    }

    a.button:hover {
        background-color: #4D0C11;
    }

    p {
        text-align: center;
        font-size: 13px;
        color: #191919;
        margin: 20px auto;
        max-width: 60%;
    }

    a.plain-link {
        font-size: 13px;
        word-wrap: break-word;
        text-align: center;
        display: block;
        margin: 0 auto;
        color: #007BFF;
        text-decoration: none;
    }

    a.plain-link:hover {
        text-decoration: underline;
    }

    aside {
        color: #555;
        font-size: 11px;
        text-align: center;
        margin: 30px auto;
        max-width: 60%;
    }
</style>

<!DOCTYPE html>
<html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <img class="logo" src="{{ asset('images/logo/logo_light.png') }}" alt="askFEUP logo" />
        <h1>Hi {{ $mailData['name'] }}! Let's reset your password.</h1>
        <a href="{{ $mailData['resetLink'] }}" class="button">Reset Password</a>

        <p>If the button above does not work for you, copy and paste the link below into your browser's address bar:</p>
        <a href="{{ $mailData['resetLink'] }}" class="plain-link">{{ $mailData['resetLink'] }}</a>

        <aside>If you didn't ask to reset your password, you can disregard this email.</aside>
    </body>
</html>
