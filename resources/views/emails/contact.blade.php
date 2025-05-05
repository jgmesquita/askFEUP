<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }


    h1 {
        text-align: center;
        font-weight: 700;
        font-size: 17px;
        margin: 20px auto;
    }

    p {
        text-align: center;
        font-size: 13px;
        color: #191919;
        margin: 20px auto;
        max-width: 60%;
    }
    .message {
        padding:20px;
        background-color:rgb(246, 248, 249);
        border-radius:15px;
        overflow-wrap: break-word; 
        white-space: pre-wrap;
        text-align: left; /* Ensure text starts from the left */
    }
</style>

<!DOCTYPE html>
<html>
    <head>
        <title>Contacts Form</title>
    </head>
    <body>
        <h1>From:</h1> 
            <p>{{ $mailData['name'] }}</p>
        <h1>Email Address:</h1>
            <p> {{ $mailData['email'] }}</p>
        <h1>Message:</h1>
            <p class="message">{{ $mailData['message'] }}</p>
    </body>
</html>
