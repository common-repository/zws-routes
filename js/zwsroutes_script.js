var map;
var directionsService;
var directionsDisplay;

function initMap() {
    // Create Map
    createMap();

    // Get objects
    directionsService = new google.maps.DirectionsService;
    directionsDisplay = new google.maps.DirectionsRenderer({
        draggable: false,
        map: map
    });

    directionsDisplay.setMap(map);

    // Set Start point
    setStartPoint();

    // Set Waypoints
    setWaypoints();

    // Set End Point
    setEndPoint();

    jQuery('#day-options').on('change', function (e) {
        var optionSelected = jQuery("option:selected", this);
        var day = this.value;
        if (day == '') {
            jQuery("#no-route-msg").removeClass('show');
        } else
        {
            jQuery.ajax({
                type: 'POST',
                cache: false,
                url: MyAjax.ajaxurl,
                data: {"action": "zws_getRoutes", "day": day},
                success: function (data) {
                    var json_result = JSON.parse(data);
                    if (json_result) {
                        jQuery("#no-route-msg").removeClass('show');
                        var getStart = json_result.start;
                        var getWaypoints = json_result.waypoints;
                        var getEnd = json_result.end;
                        zws_GetAndDisplayRoute(directionsService, directionsDisplay, getStart, getWaypoints, getEnd);
                        document.getElementById('start').value = getStart;
                        document.getElementById('waypoints').value = getWaypoints;
                        document.getElementById('end').value = getEnd;
                        document.getElementById('submit-routes').value = 'Update Routes';
                    } else {
                        jQuery("#no-route-msg").addClass('show');
                        //alert('There is no route on this day.');
                        document.getElementById('submit-routes').value = 'Add Routes';
                    }
                }
            });
        }
    });
}
function setStartPoint() {
    var autocomplete = new google.maps.places.Autocomplete(
            (document.getElementById('start')),
            {
                types: ['geocode']
            }
    );
}
function setWaypoints() {
    var autocomplete = new google.maps.places.Autocomplete(
            (document.getElementById('waypoints')),
            {
                types: ['geocode']
            }
    );
}
function setEndPoint() {
    var autocomplete = new google.maps.places.Autocomplete(
            (document.getElementById('end')),
            {
                types: ['geocode']
            }
    );
    // add change listener
    autocomplete.addListener('place_changed', function () {
        // if end point is available
        zws_AddAndDisplayRoute(directionsService, directionsDisplay);
    });
}
function createMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 10,
        center: {lat: 41.85, lng: -87.65}
    });
}
function zws_AddAndDisplayRoute(directionsService, directionsDisplay) {
    //console.log(document.getElementById('waypoints').value);
    directionsService.route({
        origin: document.getElementById('start').value,
        destination: document.getElementById('end').value,
        waypoints: [{location: document.getElementById('waypoints').value}],
        optimizeWaypoints: true,
        travelMode: google.maps.TravelMode.DRIVING
    }, function (response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
            directionsDisplay.setDirections(response);
        } else {
            window.alert('Directions request failed due to ' + status);
        }
    });
}
function zws_GetAndDisplayRoute(directionsService, directionsDisplay, getStart, getWaypoints, getEnd) {
    var start = getStart;
    var waypoints = getWaypoints;
    var end = getEnd;

    directionsService.route({
        origin: start,
        destination: end,
        waypoints: [{location: waypoints}],
        optimizeWaypoints: true,
        travelMode: google.maps.TravelMode.DRIVING
    }, function (response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
            directionsDisplay.setDirections(response);
        } else {
            window.alert('Directions request failed due to ' + status);
        }
    });
}
jQuery(document).ready(function () {
    jQuery("#submit-routes").click(function () {
        var day = jQuery("#day-options").val();
        var start = jQuery("#start").val();
        var waypoints = jQuery("#waypoints").val();
        var end = jQuery("#end").val();
        if (day == '') {
            alert("Please select a day");
        } else
        {
            jQuery.ajax({
                type: 'POST',
                cache: false,
                url: MyAjax.ajaxurl,
                data: {"action": "zws_addRoutes", "day": day, "start": start, "waypoints": waypoints, "end": end},
                success: function (data) {
                    var json_result = JSON.parse(data);
                    if (json_result == 'Successfully updated the route')
                    {
                        alert('Successfully updated the route');
                    } else {
                        alert('Successfully added the route');
                    }
                }
            });
        }
    });
});
jQuery(document).ready(function () {
    jQuery(function () {
        jQuery("select").val('');
    });
});