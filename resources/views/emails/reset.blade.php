<html>
	<head>
		<style type="text/css">
			h1 {
				color: #337ab7;
			}

			a {
				color: #337ab7;
			}
		</style>
	</head>
	<body>
		<h1>Your METIS password reset request</h1>
		<p>
			Please click here to reset your password:
			<a href="{{$baseUrl}}/#!/reset/{{$token}}">{{$baseUrl}}/#!/reset/{{$token}}</a>
		</p>
		<p>This link will expire in 24 hours.</p>
	</body>
</html>