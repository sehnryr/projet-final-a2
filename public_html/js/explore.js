// import { createCookie, getCookie } from './utils.js'

// $("#match-add-info").on("submit", (event) => {
//     let current_match = $("#popup-match-id").val()
//     $.ajax("api.php/sport", {

//     })
// });
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

var layerGroup
var sports = {
    1: 'Football',
    2: 'Basketball',
    3: 'Ping pong',
    4: 'Badminton',
    5: 'Volleyball',
    6: 'Rugby'
}

$(() => {
    var map = L.map("map", {
        center: [48.85341, 2.3488], //Centered on Paris by default
        zoom: 8,
    })
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        //Add the openstreetmap tile layer to the map
        maxZoom: 19,
    }).addTo(map)

    // L.marker([47.21725, -1.55336]).addTo(map).bindPopup("<b>Hello world!</b><br>I am a popup.")
    layerGroup = L.layerGroup().addTo(map);

    $.ajax('api.php/matches', {
        method: 'GET',
        data: {
            range: 30
        }
    }).done((data) => {
        layerGroup.clearLayers();
        data.forEach(element => {
            L.marker([element.latitude, element.longitude]).addTo(layerGroup)
                .bindPopup('<p>' + sports[element.sport_id] + '</p>')
        });
    })
})

$('#searchForm').on('submit', (event) => {
    event.preventDefault()

    let data = {}

    let range = event.originalEvent.submitter.attributes.name.value.split('-').at(-1)
    if (range == 'searchMatch') {
        range = 30
    }
    data['range'] = range

    let date = $('#match_date').val()
    if (date) {
        data['date'] = date
    }

    $.ajax('api.php/matches', {
        method: 'GET',
        data: data
    }).done((data) => {
        layerGroup.clearLayers();
        data.forEach(element => {
            L.marker([element.latitude, element.longitude]).addTo(layerGroup)
                .bindPopup('<p>' + sports[element.sport_id] + '</p>')
        });
    })
})