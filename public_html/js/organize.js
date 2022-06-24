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

$('#newMatchForm').on('submit', (event) => {
    event.preventDefault()
    let cookie = getCookie('matchmaking_session')

    let sport_id = $("input[name='new_match_sport']:checked").val()
    let min_players = $('#min_players').val()
    let max_players = $('#max_players').val()
    let price = $('#price').val()
    let datetime = $('#datetime').val()
    let duration = $('#duration').val()
    let description = $('#description').val()
    let recommended_level = $('#recommended_level').val()

    console.log(duration)

    let address = $('#address').val()

    $.ajax('https://nominatim.openstreetmap.org/search.php', {
        method: "GET", data: {
            q: address,
            format: 'jsonv2'
        },
        error: (_) => {
            alert('Address could not be found.')
        }
    }).done((address_data) => {
        let latitude = address_data[0].lat
        let longitude = address_data[0].lon

        $.ajax('api.php/match', {
            method: "POST",
            headers: {
                Authorization: 'Bearer ' + cookie
            },
            data: {
                sport_id: sport_id,
                latitude: latitude,
                longitude: longitude,
                duration: duration,
                datetime: datetime,
                description: description,
                recommended_level: recommended_level,
                max_players: max_players,
                min_players: min_players,
                price: price
            }
        }).done((_) => {
            window.location.reload();
        })
    })

})