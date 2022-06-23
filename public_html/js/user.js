import { getCookie, deleteCookie } from './utils.js'

$(() => {
    let cookie = getCookie('matchmaking_session')
    // onload if session cookie exists, redirect to user.html
    if (cookie.length > 0) {
        $.ajax("api.php/user", {
            method: "GET", headers: {
                Authorization: 'Bearer ' + cookie
            },
            error: (_) => {
                let url = window.location.href.replace(/user\.html.*/i, 'login.html')
                window.location.href = url
            }
        }).done((data) => {
            console.log(data)
            $("#idProfile").val(data['id'])
            $("#firstnameProfile").val(data['first_name'])
            $("#lastnameProfile").val(data['last_name'])
            $("#emailProfile").val(data['email'])
            $("#phoneNumberProfile").val(data['phone_number'])
            $("#birthdateProfile").val(data['birthdate'])
            $("#pictureProfile").attr('src', data['profile_picture_url'] ?? 'public_html/img/no-user.png')
        })
    }
})