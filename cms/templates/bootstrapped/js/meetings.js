jQuery(document).ready(function($) {
    // Get date two weeks back (1000 miliseconds, 60 seconds, 60 minutes, 24 hours, 14 days)
    var date = new Date(new Date - (1000*60*60*24*14));


    $('#calendar').fullCalendar({
        defaultView: 'agendaWeek',
        events: {
            // Get all meetings from the past two weeks and the future. Month+1 because getMonth ranges from 0 to 11.
            url: base_url +'/api/meetings/'+ date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate() +'/calendar/?token='+ api_token
        },
        eventClick: function(event) {
            // Create a pop over for this event
            $(this).popover({
                placement: 'auto right',
                title: event.title,
                content: event.description
            });
            // Imediatly show it
            $(this).popover('show');
            return false;
        }
    });
});