<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Auth Code</title>
</head>
<body>
<h1>Please wait...</h1>
<script>
  (function() {
    window.opener.setAuthCode("<?=$code?>");
  })();
</script>
</body>
</html>
