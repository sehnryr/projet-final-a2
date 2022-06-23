import { createCookie, getCookie } from './utils.js'

$("#match-add-info").on("submit",(event) =>{
    let current_match = $("#popup-match-id").val()
    $.ajax("api.php/sport", {
        
    })
});
/*
$.ajax("api.php/sport", {

})
*/
//Fill select with the list of each sport
/*
$.ajax("api.php/sport", {
    method: "GET"
}).done((data) => {
    data.forEach(item => {
        $('#select-sport').append('<option value="'+item['id']+'">'+item['name_id']+'</option>')
    });
});

$(() => {
    let cookie = getCookie('mm_session')
    if(cookie.lenght > 0) {
        $("#searchForm").on("submit", (event) =>{
            event.preventDefault()
            $.ajax("api.php/match", {
                method: "GET", data: {
                    name: $("#search_place").val(),
                    date: $("#match_date").val(),
                    sport: $("#select_sport").val(),
                    recommended_level: $("#recommended_level").val()
                }
            })
        })
        $("#date-7").on("click", (event) => {
            event.preventDefault()
            $.ajax("api.php/match", {
                method: "GET", data: {

                }
            })
        })
        $("#date-15").on("click", (event) => {
            event.preventDefault()
            $.ajax("api.php/match", {
                method: "GET", data: {

                }
            })
        })
        $("#date-30").on("click", (event) => {
            event.preventDefault()
            $.ajax("api.php/match", {
                method: "GET", data: {

                }
            })
        })
    }
})
*/
