import { createCookie, getCookie } from './utils.js'
$(() => {
    let cookie = getCookie('mm_session')
    if(cookie.lenght > 0) {
        
        $("#newMatchForm").on("submit", (event) =>{
            event.preventDefault()
            $.ajax("api.php/match", {
                method: "POST", data: {
                    new_match_sport: $("new_match_sport").val(),
                    min_players: $("#min_players").val(),
                    max_players: $("#max_players").val(),
                    price: $("#price").val(),
                    datetime: $("#datetime").val(),
                    duration: $("#duration").val(),
                    description: $("description").val(),
                    recommended_level: $("#recommended_level").val()
                    //add localization data
                }
            })
        })      
    }
})
