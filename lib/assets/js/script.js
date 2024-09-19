jQuery(document).ready(function($) {

    // Function to load the waitlist
    function loadWaitlist() {
        $.ajax({
            url: pumpersWaitlist.ajax_url,
            method: 'POST',
            data: {
                action: 'pumpers_get_waitlist',
                nonce: pumpersWaitlist.nonce
            },
            success: function(response) {
                if (response.success) {
                    var tbody = $('#pumpers-waitlist-table tbody');
                    tbody.empty();
                    
                    // Check if there are guests in the waitlist
                    if(response.data.length === 0){
                        tbody.append('<tr><td colspan="7">No guests in the waitlist.</td></tr>');
                        return;
                    }

                    // Iterate through each guest and create table rows
                    $.each(response.data, function(index, guest) {
                        var row = '<tr>' +
                            '<td>' + guest.position + '</td>' +
                            '<td>' + escapeHtml(guest.name) + '</td>' +
                            '<td>' + escapeHtml(guest.phone) + '</td>' +
                            '<td>' + escapeHtml(guest.quoted_time) + '</td>' +
                            '<td>' + guest.time_added + '</td>' +
                            '<td>' + guest.guest_count + '</td>' +
                            '<td>' +
                                '<button class="pumpers-action-btn seat-guest" data-id="' + guest.id + '">Seat Guest</button>' +
                                '<button class="pumpers-action-btn cancel-guest" data-id="' + guest.id + '">Cancel</button>' +
                            '</td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                } else {
                    alert('Failed to load waitlist.');
                }
            },
            error: function() {
                alert('An error occurred while loading the waitlist.');
            }
        });
    }

    // Function to escape HTML to prevent XSS
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return text;
        }
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Load the waitlist on page load
    loadWaitlist();

    // Refresh the waitlist every 30 seconds
    setInterval(loadWaitlist, 30000);

    // Show the add guest form
    $('#pumpers-add-guest-btn').on('click', function() {
        $('#pumpers-add-guest-form').toggle();
    });

    // Handle form submission for adding a guest
    $('#pumpers-add-guest').on('submit', function(e) {
        e.preventDefault();

        // Gather form data
        var name = $('input[name="name"]').val().trim();
        var phone = $('input[name="phone"]').val().trim();
        var quoted_time = $('input[name="quoted_time"]').val().trim();
        var guest_count = $('input[name="guest_count"]').val().trim();

        // Simple front-end validation
        if(name === '' || phone === '' || quoted_time === '' || guest_count === ''){
            alert('Please fill in all required fields.');
            return;
        }

        $.ajax({
            url: pumpersWaitlist.ajax_url,
            method: 'POST',
            data: {
                action: 'pumpers_add_guest',
                nonce: pumpersWaitlist.nonce,
                name: name,
                phone: phone,
                quoted_time: quoted_time,
                guest_count: guest_count
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#pumpers-add-guest-form').hide();
                    $('#pumpers-add-guest')[0].reset();
                    loadWaitlist();
                } else {
                    console.error('Server response:', response);
                    alert(response.data || 'Failed to add guest. Check console for details.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while adding the guest. Check console for details.');
            }
        });
    });

    // Handle seating a guest
    $(document).on('click', '.seat-guest', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to seat this guest?')) {
            $.ajax({
                url: pumpersWaitlist.ajax_url,
                method: 'POST',
                data: {
                    action: 'pumpers_seat_guest',
                    nonce: pumpersWaitlist.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        loadWaitlist();
                    } else {
                        alert(response.data || 'Failed to seat guest.');
                    }
                },
                error: function() {
                    alert('An error occurred while seating the guest.');
                }
            });
        }
    });

    // Handle canceling a guest
    $(document).on('click', '.cancel-guest', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to cancel this guest?')) {
            $.ajax({
                url: pumpersWaitlist.ajax_url,
                method: 'POST',
                data: {
                    action: 'pumpers_cancel_guest',
                    nonce: pumpersWaitlist.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        loadWaitlist();
                    } else {
                        alert(response.data || 'Failed to cancel guest.');
                    }
                },
                error: function() {
                    alert('An error occurred while canceling the guest.');
                }
            });
        }
    });

});
