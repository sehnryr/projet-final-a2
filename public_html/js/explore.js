import { createCookie, getCookie } from './utils.js'

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
$("#date-7").on("click",(event)=>{
    event.preventDefault()
    $.ajax("api.php/match", {
        method: "GET", data: {
            
        }
    })
})
$("#date-15").on("click",(event)=>{
    event.preventDefault()
    $.ajax("api.php/match", {
        method: "GET", data: {
            
        }
    })
})
$("#date-30").on("click",(event)=>{
    event.preventDefault()
    $.ajax("api.php/match", {
        method: "GET", data: {
            
        }
    })
})