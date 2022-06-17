import { getCookie, deleteCookie } from './utils.js'

$(() => {
    let cookie = getCookie('mm_session')
    if(cookie.lenght > 0) {

        //set user profile
        $.ajax("api.php/user", {
            method: "GET", headers: {
                Authorization: 'Bearer ' + cookie
            }
            //id, city_id, first_name, last_name, email, phone_number, birthdate
        }).done((data) => {
            $("#profile-name").html(data['first_name'] + '' + data['last_name'])
            $("#profile-age").html(data['birthdate'])
            $("#profile-email").html(data['email'])
            $("#profile-phone").html(data['phone_number'])
        })
    }
})