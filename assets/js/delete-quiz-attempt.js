jQuery(document).ready(function ($) {
    $('.delete-active-quiz-attempt').on('click', function (e) {
        e.preventDefault();

        const $link = $(this);
        const userId = $link.data('odd-user-id');
        const activityID = $link.data('odd-activity-id');
        const nonce = $link.data('odd-attempt-nonce');

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'delete_active_quiz_attempt',
                user_id: userId,
                activity_id: activityID,
                nonce: nonce,
            },
            success: function (response) {
                $link.closest('.active-quiz-attempt').remove();
                alert(response.data);
            },
            error: function (error) {
                console.error(error);
            },
        });
    });
});