jQuery(document).ready(function($) {
    $('#loading').show();
    // Get date two weeks back (1000 miliseconds, 60 seconds, 60 minutes, 24 hours, 14 days)
    var date = new Date(new Date - (1000*60*60*24*14));

    // Get meetings
    client.meetings.calendar.read(date.getFullYear() +'-'+ (date.getMonth()+1) +'-'+ date.getDate(), { token: api_token }).done(function(data) {
        // Insert the events into the calendar
        $('#calendar').fullCalendar({
            defaultView: 'agendaWeek',
            height: 650,
            header: {
                left:   'title',
                center: 'agendaDay,agendaWeek,month',
                right:  'today prev,next'
            },
            events: data,
            eventClick: function(event) {
                // Create a pop over for this event
                $(this).popover({
                    placement: 'auto top',
                    title: event.title,
                    content: event.description
                });
                // Imediatly show it
                $(this).popover('show');
                return false;
            }
        });
        $('#loading').hide();
    }).fail(function() {
        addAlert('danger', '<strong>Error!</strong> Loading the calendar events failed.');
        $('#loading').hide();
    });
});