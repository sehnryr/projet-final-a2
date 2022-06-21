import { getCookie, deleteCookie } from './utils.js'

$(() => {
    let cookie = getCookie('mm_session')
    if(cookie.lenght > 0) {

        //set user profile
        $.ajax("api.php/user", {
            method: "GET", headers: {
                Authorization: 'Bearer ' + cookie
            }
            //GET id, city_id, first_name, last_name, email, phone_number, birthdate
        }).done((data) => {
            $("#profile-name").html(data['first_name'] + '' + data['last_name'])
            $("#profile-age").html(data['birthdate'])
            $("#profile-email").html(data['email'])
            $("#profile-phone").html(data['phone_number'])
            $("#profile-city").html(data['city_id'])
        })
        
        //Modifie profile info
        $("#modifie-profile-name").on("click",(event) =>{
            $("#profile-name").append('<form id="set-new-profile-name"><input type="text" placeholder="first name" id="new-first-name" name="new-first-name" required/><input type="text" placeholder="last name" id="new-last-name" name="new-last-name" required/><button "submit">Change</button></form>');
            $("#modifie-profile-name").style.display="none";
        })
        $("#set-new-profile-name").on("submit",(event) =>{
            event.preventDefault()
            $.ajax("api.php/profil",{
                method: "PUT", data: {
                    first_name: $("#new-first-name").val(),
                    last_name: $("#new-last-name").val()
                }
            })
            $("#profile-name").html($("#new-first-name") + '' + $("#new-last-name"));
            $("#set-new-profile-name").style.display="none"; 
            $("#modifie-profile-name").style.display="block";
        })
        $("#modifie-profie-age").on("click", (event) => {
            $("#profile-age").append('<form id="set-new-profile-age"><input type="date" placeholder="set age" id="new-age" name="new-age" required/><button "submit">Change</button></form>');
            $("#modifie-profile-age").style.display="none"
        })
        $("#set-new-profile-age").on("submit",(event) =>{
            event.preventDefault()
            $.ajax("api.php/profil",{
                method: "PUT", data: {
                    birthdate: $("#new-age").val(),
                }
            })
            $("#profile-age").html($("#new-age"));
            $("#set-new-profile-age").style.display="none"; 
            $("#modifie-profile-age").style.display="block";
        })
        $("#modifie-profie-phone").on("click", (event) => {
            $("#profile-phone").append('<form id="set-new-profile-phone"><input type="text" placeholder="set phone" id="new-phone" name="new-phone" required/><button "submit">Change</button></form>');
            $("#modifie-profile-phone").style.display="none"
        })
        $("#set-new-profile-phone").on("submit",(event) =>{
            event.preventDefault()
            $.ajax("api.php/profil",{
                method: "PUT", data: {
                    phone_number: $("#new-phone").val(),
                }
            })
            $("#profile-phone").html($("#new-phone"));
            $("#set-new-profile-phone").style.display="none"; 
            $("#modifie-profile-phone").style.display="block";
        })
        $("#modifie-profie-email").on("click", (event) => {
            $("#profile-email").append('<form id="set-new-profile-email"><input type="text" placeholder="set email" id="new-email" name="new-email" required/><button "submit">Change</button></form>');
            $("#modifie-profile-email").style.display="none"
        })
        $("#set-new-profile-email").on("submit",(event) =>{
            event.preventDefault()
            $.ajax("api.php/profil",{
                method: "PUT", data: {
                    email: $("#new-email").val(),
                }
            })
            $("#profile-email").html($("#new-email"));
            $("#set-new-profile-email").style.display="none"; 
            $("#modifie-profile-email").style.display="block";
        })
        $("#modifie-profie-city").on("click", (event) => {
            $("#profile-city").append('<form id="set-new-profile-city"><input type="text" placeholder="set city" id="new-city" name="new-city" required/><button "submit">Change</button></form>');
            $("#modifie-profile-city").style.display="none"
        })
        $("#set-new-profile-city").on("submit",(event) =>{
            event.preventDefault()
            $.ajax("api.php/profil",{
                method: "PUT", data: {
                    city_id: $("#new-city").val(),
                }
            })
            $("#profile-city").html($("#new-city"));
            $("#set-new-profile-city").style.display="none"; 
            $("#modifie-profile-city").style.display="block";
        })


        
        //set user sports and stat
        $ajax("api.php/user_level",{
            method: "GET", headers: {
                Authorization: 'Bearer ' + cookie
            }
            //GET id, user_id, sport_id, level, description
        }).done((data) => {

        })

        //set user matchs (as a player)
        $ajax("api.php/match",{
            method: "GET", headers: {
                Authorization: 'Bearer ' + cookie
            }
            //GET 
        }).done((data) => {
            $("#")
        })
    }
})