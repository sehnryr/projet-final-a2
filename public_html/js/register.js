import { createCookie, getCookie } from './utils.js'

// onload if session cookie exists, redirect to user.html
$(() => {
	let cookie = getCookie('mm_session')
	if (cookie.length > 0) {
		let url = window.location.href.replace(/register\.html.*/i, 'user.html')
		window.location.href = url
	}
})

$('#formRegister').on('submit', (event) => {
	event.preventDefault()
	$.ajax('api.php/register', {
		method: 'POST', data: {
			firstname: $("#firstnameRegister").val(),
			lastname: $("#lastnameRegister").val(),
			email: $("#emailRegister").val(),
			phone: $("#phoneRegister").val(),
			password: $("#passwordRegister").val(),
			city: $("cityRegister").val()
		}
	}).done((data) => {
		createCookie('mm_session', data['access_token'])
		let url = window.location.href.replace(/register\.html.*/i, 'user.html')
		window.location.href = url
	})
})