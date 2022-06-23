import { createCookie, getCookie } from './utils.js'

$(() => {
    let cookie = getCookie('matchmaking_session')
    // onload if session cookie exists, redirect to user.html
    if (cookie.length > 0) {
        $.ajax("api.php/user", {
            method: "GET", headers: {
                Authorization: 'Bearer ' + cookie
            },
            error: (_) => {
                let url = window.location.href.replace(/organize\.html.*/i, 'login.html')
                window.location.href = url
            }
        }).done((data) => {
            $.ajax("api.php/matches", {
                method: "GET", data: {
                    organizer_id: data['id']
                }
            }).done((data) => {

                data.forEach(element => {
                    $.ajax("api.php/sport", {
                        method: "GET", data: {
                            sport_id: element['sport_id']
                        }
                    }).done((sport_data) => {
                        $.ajax("https://nominatim.openstreetmap.org/reverse", {
                            method: "GET", data: {
                                lat: element['latitude'],
                                lon: element['longitude'],
                                format: 'json'
                            }
                        }).done((city_data) => {
                            $.ajax("api.php/match", { method: "GET", data: { match_id: element['id'] } }).done((match_data) => {
                                console.log(match_data);
                                let sport_name = sport_data['name_id']
                                    .replace('_', ' ')
                                    .replace(/\b\w/g, l => l.toUpperCase())
                                let address = city_data['address']['house_number'] + ' '
                                    + city_data['address']['road'] + ', '
                                    + city_data['address']['city']
                                let participation = match_data['participation'].length + '/' + match_data['max_players']
                                let field = $('<div></div>')
                                field.append('<p>' + sport_name + '</p>')
                                field.append('<p>' + address + '</p>')
                                field.append('<p>' + match_data['datetime'] + '</p>')
                                field.append('<p>Registered players: ' + participation + '</p>')
                                $('#match_list').append(field);
                            })
                        })
                    })
                })
            })
        })
    } else {
        let url = window.location.href.replace(/organize\.html.*/i, 'login.html')
        window.location.href = url
    }
})