import { createCookie, getCookie } from './utils.js'

// onload if session cookie exists, redirect to user.html
$(() => {
	let cookie = getCookie('matchmaking_session')
	if (cookie.length > 0) {
		$.ajax("api.php/user", {
			method: "GET", headers: {
				Authorization: 'Bearer ' + cookie
			}
		}).done((_) => {
			let url = window.location.href.replace(/login\.html.*/i, 'user.html')
			window.location.href = url
		})
	}
})


$("#loginForm").on("submit", (event) => {
	event.preventDefault()
	$.ajax("api.php/login", {
		method: "POST", data: {
			email: $("#emailLogin").val(),
			password: $("#passwordLogin").val()
		},
		error: (err) => {
			let statusCode = err['status']
			if (statusCode == 400) {
				alert('Your e-mail or password is incorrect.')
			}
		}
	}).done((data) => {
		createCookie('matchmaking_session', data['access_token'])
		let url = window.location.href.replace(/login\.html.*/i, 'user.html')
		window.location.href = url
	})
})


$("#registerForm").on("submit", (event) => {
	event.preventDefault()

	let password = $("#passwordRegister").val()
	let passwordConf = $("#passwordRegisterConf").val()

	if (password != passwordConf) {
		alert("Password don't match")
	}

	let email = $("#emailRegister").val()

	$.ajax("api.php/check_email", {
		method: "GET", data: {
			email: email
		}
	}).done((data) => {
		let valid = data['valid']
		if (!valid) {
			alert("Email is not valid or already used.")
		} else {
			$.ajax("api.php/register", {
				method: "POST", data: {
					first_name: $("#firstnameRegister").val(),
					last_name: $("#lastnameRegister").val(),
					email: $("#emailRegister").val(),
					password: $("#passwordRegister").val(),
					birthdate: $("#birthdateRegister").val()
				},
				error: (err) => {
					let statusCode = err['status']
					if (statusCode == 400) {
						alert('An error has occurred, please try again.')
					}
				}
			}).done((data) => {
				createCookie('matchmaking_session', data['access_token'])
				let url = window.location.href.replace(/login\.html.*/i, 'user.html')
				window.location.href = url
			})
		}
	})

})